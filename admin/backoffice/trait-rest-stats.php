<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiStats — stats réservations, charts, périodes, planner.
 * Lit depuis la DB locale (bt_reservations) sans appel API Regiondo.
 */
trait RestApiStats {

    /**
     * Retourne les stats de réservations pour les charts du dashboard.
     * Lit depuis la DB locale (wp_bt_bookings) — rapide, pas d'appel API.
     *
     * Paramètres GET supportés:
     *   from         YYYY-MM-DD (défaut: premier jour d'il y a 11 mois)
     *   to           YYYY-MM-DD (défaut: aujourd'hui)
     *   granularity  day|week|month (défaut: month)
     *   compare_from YYYY-MM-DD — début de la période de comparaison (optionnel)
     *   compare_to   YYYY-MM-DD — fin de la période de comparaison (optionnel)
     */
    public function get_bookings_stats(\WP_REST_Request $req): \WP_REST_Response {
        $db = new ReservationStats(); // lit bt_reservations (solditems importés)

        // ── Validation des paramètres ──────────────────────────────────────
        $granularity = in_array($req->get_param('granularity'), ['day', 'week', 'month'], true)
            ? $req->get_param('granularity')
            : 'month';

        $from = $req->get_param('from');
        $to   = $req->get_param('to');
        if (!$from || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-d', strtotime('first day of -11 months'));
        }
        if (!$to || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }

        $compare_from = $req->get_param('compare_from');
        $compare_to   = $req->get_param('compare_to');
        $has_compare  = $compare_from && $compare_to
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $compare_from)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $compare_to);

        // ── Lecture depuis la DB locale ────────────────────────────────────
        $raw_rows = $db->query_stats($from, $to, $granularity);
        $top_raw  = $db->query_top_products($from, $to, 8);
        // Use enhanced KPIs if available
        $include_raw = $req->get_param('include') ?? '';
        $includes = $include_raw ? array_map('trim', explode(',', $include_raw)) : [];
        // Always use enhanced KPIs (includes unique_customers, repeat_rate, etc.)
        $kpis = $db->query_enhanced_kpis($from, $to);
        $by_chan  = $db->query_by_channel($from, $to, 10);
        $by_wday  = $db->query_by_weekday($from, $to);

        // Indexer les résultats DB par période
        $db_by_key = [];
        $total_bookings = 0;
        foreach ($raw_rows as $row) {
            $db_by_key[$row['period_key']] = [
                'bookings'   => (int)   $row['bookings'],
                'revenue'    => (float) $row['revenue'],
                'cancelled'  => (int)   $row['cancelled'],
                'avg_basket' => $row['avg_basket'] !== null ? (float) $row['avg_basket'] : null,
            ];
            $total_bookings += (int) $row['bookings'];
        }

        // ── Construire les périodes (toutes, y compris celles sans données) ─
        $periods        = $this->build_periods($from, $to, $granularity);
        $result_periods = [];
        $peak_bookings  = 0;
        $peak_revenue   = 0.0;
        $peak_basket    = 0.0;

        foreach ($periods as $p) {
            $b  = $db_by_key[$p['key']]['bookings']   ?? 0;
            $r  = $db_by_key[$p['key']]['revenue']    ?? 0.0;
            $ab = $db_by_key[$p['key']]['avg_basket'] ?? null;
            $ca = $db_by_key[$p['key']]['cancelled']  ?? 0;
            $result_periods[] = [
                'key'        => $p['key'],
                'label'      => $p['label'],
                'bookings'   => $b,
                'revenue'    => round($r, 2),
                'cancelled'  => $ca,
                'avg_basket' => $ab !== null ? round($ab, 2) : null,
            ];
            $peak_bookings = max($peak_bookings, $b);
            $peak_revenue  = max($peak_revenue,  $r);
            if ($ab !== null) $peak_basket = max($peak_basket, $ab);
        }

        // ── Période de comparaison ─────────────────────────────────────────
        $compare_periods = [];
        $kpis_cmp = [];
        if ($has_compare) {
            $cmp_raw    = $db->query_stats($compare_from, $compare_to, $granularity);
            $kpis_cmp   = $db->query_period_kpis($compare_from, $compare_to);
            $cmp_by_key = [];
            foreach ($cmp_raw as $row) {
                $cmp_by_key[$row['period_key']] = [
                    'bookings'   => (int)   $row['bookings'],
                    'revenue'    => (float) $row['revenue'],
                    'cancelled'  => (int)   $row['cancelled'],
                    'avg_basket' => $row['avg_basket'] !== null ? (float) $row['avg_basket'] : null,
                ];
            }
            foreach ($this->build_periods($compare_from, $compare_to, $granularity) as $p) {
                $compare_periods[] = [
                    'key'        => $p['key'],
                    'label'      => $p['label'],
                    'bookings'   => $cmp_by_key[$p['key']]['bookings']   ?? 0,
                    'revenue'    => round($cmp_by_key[$p['key']]['revenue']    ?? 0.0, 2),
                    'cancelled'  => $cmp_by_key[$p['key']]['cancelled']  ?? 0,
                    'avg_basket' => isset($cmp_by_key[$p['key']]['avg_basket'])
                        ? round($cmp_by_key[$p['key']]['avg_basket'], 2)
                        : null,
                ];
            }
        }

        // ── Top produits avec revenue ──────────────────────────────────────
        $top_products = array_map(fn($r) => [
            'name'    => $r['name'],
            'count'   => (int) $r['count'],
            'revenue' => (float) $r['revenue'],
        ], $top_raw);

        // ── Jours de semaine : normalise DOW vers libellés FR ─────────────
        $dow_labels = [1 => 'Dim', 2 => 'Lun', 3 => 'Mar', 4 => 'Mer', 5 => 'Jeu', 6 => 'Ven', 7 => 'Sam'];
        $by_weekday = array_map(fn($r) => [
            'dow'      => (int) $r['dow'],
            'label'    => $dow_labels[(int) $r['dow']] ?? $r['dow'],
            'bookings' => (int) $r['bookings'],
            'revenue'  => (float) $r['revenue'],
        ], $by_wday);

        // ── Canaux de vente (normalize: strip trailing IDs/codes) ─────────
        // "Funbooker 35409", "Funbooker 35410" → "Funbooker"
        // "GetYourGuide Deutschland GmbH GYGZG2LHHQG" → "GetYourGuide Deutschland GmbH"
        $chan_grouped = [];
        foreach ($by_chan as $r) {
            $name = trim($r['channel']);
            // Strip trailing alphanumeric ID (e.g. "Funbooker 35409" or "GYG GYGZG2LHHQG")
            $normalized = preg_replace('/\s+[A-Z0-9]{5,}$/i', '', $name);
            // Also strip trailing pure numeric IDs (e.g. "Funbooker 35409")
            $normalized = preg_replace('/\s+\d+$/', '', $normalized);
            $normalized = trim($normalized) ?: $name;

            if (!isset($chan_grouped[$normalized])) {
                $chan_grouped[$normalized] = ['channel' => $normalized, 'bookings' => 0, 'revenue' => 0.0];
            }
            $chan_grouped[$normalized]['bookings'] += (int) $r['bookings'];
            $chan_grouped[$normalized]['revenue']  += (float) $r['revenue'];
        }
        // Re-sort by bookings desc
        usort($chan_grouped, fn($a, $b) => $b['bookings'] <=> $a['bookings']);
        $by_channel = array_values($chan_grouped);

        return rest_ensure_response([
            // Données de chart par période
            'periods'        => $result_periods,
            'compare'        => $compare_periods,
            // KPIs globaux période principale
            'kpis'           => $kpis,
            'kpis_compare'   => $kpis_cmp ?: null,
            // Distributions
            'by_product'     => $top_products,
            'by_channel'     => $by_channel,
            'by_weekday'          => $by_weekday,
            'by_weekday_compare'  => $has_compare ? $db->query_by_weekday($compare_from, $compare_to) : null,
            // Pics
            'total'          => $total_bookings,
            'peak_bookings'  => $peak_bookings,
            'peak_revenue'   => round($peak_revenue, 2),
            'peak_basket'    => round($peak_basket, 2),
            // Meta
            'granularity'    => $granularity,
            'period_start'   => $from,
            'period_end'     => $to,
            'monthly'        => $result_periods, // compat legacy
            // ── Optional data modules (loaded via ?include=...) ──────────────
            'heatmap'               => in_array('heatmap', $includes) ? $db->query_heatmap($from, $to) : null,
            'heatmap_compare'       => (in_array('heatmap', $includes) && $has_compare) ? $db->query_heatmap($compare_from, $compare_to) : null,
            'heatmap_cancellations' => in_array('heatmap', $includes) ? $db->query_heatmap_cancellations($from, $to) : null,
            'payments'       => in_array('payments', $includes) ? [
                'by_method' => $db->query_by_payment_method($from, $to),
                'by_status' => $db->query_by_payment_status($from, $to),
            ] : null,
            'lead_time_buckets'         => in_array('lead_time_buckets', $includes) ? $db->query_lead_time_buckets($from, $to) : null,
            'lead_time_buckets_compare' => (in_array('lead_time_buckets', $includes) && $has_compare) ? $db->query_lead_time_buckets($compare_from, $compare_to) : null,
            'lead_time'      => in_array('lead_time', $includes) ? $db->query_lead_time($from, $to) : null,
            'repeat_customers'         => in_array('repeat_customers', $includes) ? $db->query_repeat_customers($from, $to) : null,
            'repeat_customers_compare' => (in_array('repeat_customers', $includes) && $has_compare) ? $db->query_repeat_customers($compare_from, $compare_to) : null,
            'repeat_per_period'        => in_array('repeat_customers', $includes) ? $db->query_repeat_rate_by_period($from, $to) : null,
            'product_mix'    => in_array('product_mix', $includes) ? $db->query_product_mix($from, $to, $granularity) : null,
            'channel_status' => in_array('channel_status', $includes) ? $db->query_channel_status($from, $to) : null,
            'yoy'            => in_array('yoy', $includes) ? $db->query_yoy($from, $to) : null,
            'cumulative'     => in_array('cumulative', $includes) ? $db->query_cumulative($from, $to, $granularity) : null,
            'top_dates'              => in_array('top_dates', $includes) ? $db->query_top_dates($from, $to, 30) : null,
            'top_dates_compare'      => (in_array('top_dates', $includes) && $has_compare) ? $db->query_top_dates($compare_from, $compare_to, 30) : null,
            'top_cancellation_dates' => in_array('top_dates', $includes) ? $db->query_top_cancellation_dates($from, $to, 30) : null,
        ]);
    }

    /**
     * Construit la liste des périodes (clé + libellé) entre $from et $to.
     *
     * @param string $from        YYYY-MM-DD
     * @param string $to          YYYY-MM-DD
     * @param string $granularity day|week|month
     * @return array<array{key: string, label: string}>
     */
    private function build_periods(string $from, string $to, string $granularity): array {
        $periods = [];
        $cursor  = new \DateTime($from);
        $end     = new \DateTime($to);

        while ($cursor <= $end) {
            $key = $this->period_key($cursor->format('Y-m-d'), $granularity);
            if (empty($periods) || end($periods)['key'] !== $key) {
                $periods[] = [
                    'key'   => $key,
                    'label' => $this->format_period_label($key, $granularity),
                ];
            }
            $cursor->modify($granularity === 'day' ? '+1 day' : ($granularity === 'week' ? '+1 week' : '+1 month'));
        }

        return $periods;
    }

    /**
     * Retourne la clé de période pour une date donnée.
     *
     * @param string $date        YYYY-MM-DD (peut être tronqué)
     * @param string $granularity day|week|month
     * @return string Ex: "2026-03-12" | "2026-W10" | "2026-03"
     */
    private function period_key(string $date, string $granularity): string {
        if (strlen($date) < 10) return '';
        $ts = strtotime($date);
        if (!$ts) return '';
        return match ($granularity) {
            'day'   => date('Y-m-d', $ts),
            'week'  => date('Y', $ts) . '-W' . date('W', $ts),
            default => date('Y-m', $ts),
        };
    }

    /**
     * Retourne un libellé court français pour l'axe X des charts.
     *
     * @param string $key         Clé de période (YYYY-MM-DD | YYYY-Wnn | YYYY-MM)
     * @param string $granularity day|week|month
     */
    private function format_period_label(string $key, string $granularity): string {
        static $month_labels = [
            '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aoû',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc',
        ];

        if ($granularity === 'day') {
            // YYYY-MM-DD → "12 Mar"
            $ts = strtotime($key);
            return $ts ? date('j', $ts) . ' ' . ($month_labels[date('m', $ts)] ?? '') : $key;
        }

        if ($granularity === 'week') {
            // YYYY-Wnn → "S10 '26"
            [$year, $week] = explode('-W', $key . '-W');
            return 'S' . ltrim($week, '0') . " '" . substr($year, 2);
        }

        // month: YYYY-MM → "Mar 26"
        [$year, $month_num] = explode('-', $key . '-');
        return ($month_labels[$month_num] ?? $month_num) . ' ' . substr($year, 2);
    }

    /**
     * Planificateur — réservations groupées par date d'activité.
     * Lit depuis la DB locale (wp_bt_bookings) — pas d'appel API.
     *
     * Accepte soit ?from=YYYY-MM-DD&to=YYYY-MM-DD (navigation mensuelle)
     * soit ?days=N (horizon glissant, 7-90, défaut 30).
     */
    public function get_planner(\WP_REST_Request $req): \WP_REST_Response {
        $db   = new ReservationStats(); // query_calendar() et query_lead_time_buckets() sont dans ReservationStats
        $from = $req->get_param('from');
        $to   = $req->get_param('to');

        if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = null;
        if ($to   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = null;

        if (!$from || !$to) {
            $days = (int) ($req->get_param('days') ?: 30);
            $days = min(90, max(7, $days));
            $from = date('Y-m-01');
            $to   = date('Y-m-t', strtotime("+$days days"));
        }

        $calendar           = $db->query_calendar($from, $to);
        $total              = array_sum(array_column($calendar, 'count'));
        $lead_time_buckets  = $db->query_lead_time_buckets($from, $to);

        return rest_ensure_response([
            'calendar'          => $calendar,
            'total'             => $total,
            'from'              => $from,
            'to'                => $to,
            'lead_time_buckets' => $lead_time_buckets,
        ]);
    }
}
