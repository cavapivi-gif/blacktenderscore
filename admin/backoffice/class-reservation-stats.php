<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Analytics & statistiques pour les réservations Regiondo (solditems).
 *
 * Étend ReservationDb pour hériter de l'accès à $this->table et EDATE.
 * Toutes les méthodes query_* sont extraites de l'ancien monolithe
 * ReservationDb — aucun changement de signature ou de logique.
 */
class ReservationStats extends ReservationDb {

    // ─── Charts (bookings stats) ────────────────────────────────────────────

    /**
     * Agrège les réservations par période pour les charts.
     * Inclut avg_basket et cancelled en plus de bookings/revenue.
     *
     * @param string $from        YYYY-MM-DD
     * @param string $to          YYYY-MM-DD
     * @param string $granularity day|week|month
     * @return array [ ['period_key'=>string, 'bookings'=>int, 'revenue'=>float, 'avg_basket'=>float|null, 'cancelled'=>int], … ]
     */
    public function query_stats(string $from, string $to, string $granularity = 'month'): array {
        global $wpdb;

        $format = match ($granularity) {
            'day'   => '%Y-%m-%d',
            'week'  => '%x-W%v',
            default => '%Y-%m',
        };

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared — format est une constante interne
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DATE_FORMAT(" . self::EDATE . ", %s) AS period_key,
                    COUNT(*)                           AS bookings,
                    SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                             THEN COALESCE(price_total, 0) ELSE 0 END) AS revenue,
                    SUM(CASE WHEN booking_status IN ('canceled','cancelled','rejected')
                             THEN 1 ELSE 0 END) AS cancelled,
                    ROUND(AVG(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              AND price_total IS NOT NULL AND price_total > 0
                              THEN price_total ELSE NULL END), 2) AS avg_basket
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY period_key
                 ORDER BY period_key ASC",
                $format,
                $from,
                $to,
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Top N produits par nombre de réservations + revenue sur une période.
     *
     * @param string $from  YYYY-MM-DD
     * @param string $to    YYYY-MM-DD
     * @param int    $limit Nombre de produits à retourner
     * @return array [ ['name'=>string, 'count'=>int, 'revenue'=>float], … ]
     */
    public function query_top_products(string $from, string $to, int $limit = 5): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    product_name AS name,
                    COUNT(*) AS `count`,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY product_name
                 ORDER BY `count` DESC
                 LIMIT %d",
                $from,
                $to,
                $limit,
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Répartition par canal de vente.
     *
     * @param string $from  YYYY-MM-DD
     * @param string $to    YYYY-MM-DD
     * @param int    $limit Nombre de canaux à retourner
     * @return array [ ['channel'=>string, 'bookings'=>int, 'revenue'=>float], … ]
     */
    public function query_by_channel(string $from, string $to, int $limit = 10): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    COALESCE(NULLIF(TRIM(channel),''), 'Non renseigné') AS channel,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY channel
                 ORDER BY bookings DESC
                 LIMIT %d",
                $from,
                $to,
                $limit,
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Répartition par jour de semaine (1=Dimanche … 7=Samedi).
     *
     * @param string $from YYYY-MM-DD
     * @param string $to   YYYY-MM-DD
     * @return array [ ['dow'=>int, 'bookings'=>int, 'revenue'=>float], … ]
     */
    public function query_by_weekday(string $from, string $to): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DAYOFWEEK(" . self::EDATE . ") AS dow,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY dow
                 ORDER BY dow",
                $from,
                $to,
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * KPIs globaux pour une période : total, CA, panier moyen, taux annulation, remboursements.
     *
     * @param string $from YYYY-MM-DD
     * @param string $to   YYYY-MM-DD
     * @return array
     */
    public function query_period_kpis(string $from, string $to): array {
        global $wpdb;

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(*) AS total_bookings,
                    SUM(CASE WHEN booking_status IN ('canceled','cancelled','rejected') THEN 1 ELSE 0 END) AS total_cancelled,
                    SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                             THEN COALESCE(price_total, 0) ELSE 0 END) AS total_revenue,
                    ROUND(AVG(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              AND price_total IS NOT NULL AND price_total > 0
                              THEN price_total ELSE NULL END), 2) AS avg_basket,
                    ROUND(SUM(CASE WHEN price_total < 0 THEN price_total ELSE 0 END), 2) AS refunds_total,
                    SUM(CASE WHEN price_total IS NOT NULL AND price_total > 0 THEN 1 ELSE 0 END) AS paid_bookings,
                    COUNT(DISTINCT product_name) AS unique_products,
                    COUNT(DISTINCT DATE_FORMAT(" . self::EDATE . ",'%%Y-%%m')) AS active_months
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL",
                $from,
                $to,
            ),
            ARRAY_A
        );

        if (!$row) return [];

        $total     = (int) $row['total_bookings'];
        $cancelled = (int) $row['total_cancelled'];

        return [
            'total_bookings'    => $total,
            'total_confirmed'   => $total - $cancelled,
            'total_cancelled'   => $cancelled,
            'cancellation_rate' => $total > 0 ? round($cancelled / $total * 100, 1) : 0.0,
            'total_revenue'     => (float) ($row['total_revenue'] ?? 0),
            'avg_basket'        => $row['avg_basket'] !== null ? (float) $row['avg_basket'] : null,
            'refunds_total'     => (float) ($row['refunds_total'] ?? 0),
            'paid_bookings'     => (int) ($row['paid_bookings'] ?? 0),
            'unique_products'   => (int) ($row['unique_products'] ?? 0),
            'active_months'     => (int) ($row['active_months'] ?? 0),
        ];
    }

    // ─── Planificateur ──────────────────────────────────────────────────────

    /**
     * Réservations groupées par date pour le planificateur.
     * Données personnelles déchiffrées, champs normalisés pour Planner.jsx.
     *
     * @param string $from YYYY-MM-DD
     * @param string $to   YYYY-MM-DD
     * @return array [ ['date' => YYYY-MM-DD, 'count' => int, 'bookings' => [...]], … ]
     */
    public function query_calendar(string $from, string $to): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DATE_FORMAT(" . self::EDATE . ", '%%Y-%%m-%%d') AS booking_date,
                    order_increment_id AS booking_ref,
                    product_name,
                    buyer_name,
                    buyer_email,
                    price_total        AS total_price,
                    booking_status     AS status
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 ORDER BY appointment_date ASC, id ASC",
                $from,
                $to,
            ),
            ARRAY_A
        );

        if (!$rows) return [];

        $enc     = new Encryption();
        $by_date = [];
        foreach ($rows as $r) {
            $date = $r['booking_date'];
            $by_date[$date][] = [
                'booking_ref'    => $r['booking_ref'],
                'product_name'   => $r['product_name'],
                'customer_name'  => $enc->decrypt($r['buyer_name']  ?? ''),
                'customer_email' => $enc->decrypt($r['buyer_email'] ?? ''),
                'total_price'    => $r['total_price'],
                'currency_code'  => 'EUR',
                'status'         => $r['status'],
            ];
        }

        $calendar = [];
        foreach ($by_date as $date => $bookings) {
            $calendar[] = [
                'date'     => $date,
                'count'    => count($bookings),
                'bookings' => $bookings,
            ];
        }

        return $calendar;
    }

    // ─── Advanced analytics ─────────────────────────────────────────────────

    /**
     * Heatmap: mois × jour de semaine.
     */
    public function query_heatmap(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DATE_FORMAT(" . self::EDATE . ", '%%Y-%%m') AS month,
                    DAYOFWEEK(" . self::EDATE . ") AS dow,
                    COUNT(*) AS total,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY month, dow
                 ORDER BY month, dow",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Heatmap des annulations — même format que query_heatmap mais filtre sur les statuts annulés.
     * Permet d'identifier les mois × jours où les annulations se concentrent.
     *
     * @param string $from YYYY-MM-DD
     * @param string $to   YYYY-MM-DD
     * @return array [{month: 'YYYY-MM', dow: 1-7, total: N}]
     */
    public function query_heatmap_cancellations(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DATE_FORMAT(" . self::EDATE . ", '%%Y-%%m') AS month,
                    DAYOFWEEK(" . self::EDATE . ") AS dow,
                    COUNT(*) AS total
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                   AND booking_status IN ('canceled','cancelled','rejected')
                 GROUP BY month, dow
                 ORDER BY month, dow",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Répartition par méthode de paiement.
     */
    public function query_by_payment_method(string $from, string $to, int $limit = 10): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    COALESCE(NULLIF(TRIM(payment_method),''), 'Non renseigné') AS method,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY method
                 ORDER BY bookings DESC
                 LIMIT %d",
                $from, $to, $limit
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Répartition par statut de paiement.
     */
    public function query_by_payment_status(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    COALESCE(NULLIF(TRIM(payment_status),''), 'Non renseigné') AS status,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY status
                 ORDER BY bookings DESC",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Distribution par heure de réservation (created_at).
     */
    public function query_booking_hours(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    HOUR(created_at) AS hour,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND created_at IS NOT NULL
                 GROUP BY hour
                 ORDER BY hour",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Distribution par avance de réservation (buckets : Jour J / 1-7j / 8-30j / 31-90j / +90j).
     * Répond à : "est-ce que nos clients réservent à la dernière minute ou à l'avance ?"
     */
    public function query_lead_time_buckets(string $from, string $to): array {
        global $wpdb;
        // DATEDIFF(appointment_date, created_at) = nb jours entre la commande et la date d'activité
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    CASE
                        WHEN DATEDIFF(appointment_date, DATE(created_at)) = 0 THEN 'Jour J'
                        WHEN DATEDIFF(appointment_date, DATE(created_at)) BETWEEN 1 AND 7 THEN '1-7j'
                        WHEN DATEDIFF(appointment_date, DATE(created_at)) BETWEEN 8 AND 30 THEN '8-30j'
                        WHEN DATEDIFF(appointment_date, DATE(created_at)) BETWEEN 31 AND 90 THEN '31-90j'
                        ELSE '+90j'
                    END AS bucket,
                    COUNT(*) AS bookings,
                    ROUND(AVG(DATEDIFF(appointment_date, DATE(created_at))), 1) AS avg_days
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND created_at IS NOT NULL
                   AND appointment_date IS NOT NULL
                   AND DATEDIFF(appointment_date, DATE(created_at)) >= 0
                 GROUP BY bucket
                 ORDER BY MIN(DATEDIFF(appointment_date, DATE(created_at)))",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Lead time moyen par produit (jours entre created_at et appointment_date).
     */
    public function query_lead_time(string $from, string $to, int $limit = 10): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    product_name AS name,
                    COUNT(*) AS bookings,
                    ROUND(AVG(DATEDIFF(appointment_date, DATE(created_at))), 1) AS avg_lead_days,
                    MIN(DATEDIFF(appointment_date, DATE(created_at))) AS min_lead_days,
                    MAX(DATEDIFF(appointment_date, DATE(created_at))) AS max_lead_days
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND created_at IS NOT NULL
                   AND " . self::EDATE . " IS NOT NULL
                   AND DATEDIFF(appointment_date, DATE(created_at)) >= 0
                 GROUP BY product_name
                 ORDER BY bookings DESC
                 LIMIT %d",
                $from, $to, $limit
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Fréquence client (repeat customers via buyer_email_hash).
     */
    public function query_repeat_customers(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    freq_bucket,
                    COUNT(*) AS customers,
                    SUM(total_bookings) AS bookings,
                    ROUND(SUM(total_revenue), 2) AS revenue
                 FROM (
                    SELECT
                        buyer_email_hash,
                        COUNT(*) AS total_bookings,
                        SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                                 THEN COALESCE(price_total, 0) ELSE 0 END) AS total_revenue,
                        CASE
                            WHEN COUNT(*) = 1 THEN '1 visite'
                            WHEN COUNT(*) = 2 THEN '2 visites'
                            WHEN COUNT(*) BETWEEN 3 AND 4 THEN '3-4 visites'
                            ELSE '5+ visites (VIP)'
                        END AS freq_bucket
                    FROM `{$this->table}`
                    WHERE " . self::EDATE . " BETWEEN %s AND %s
                      AND buyer_email_hash IS NOT NULL
                      AND buyer_email_hash != ''
                    GROUP BY buyer_email_hash
                 ) AS sub
                 GROUP BY freq_bucket
                 ORDER BY FIELD(freq_bucket, '1 visite', '2 visites', '3-4 visites', '5+ visites (VIP)')",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Taux de repeat par période (mois).
     * Pour chaque mois, retourne le nombre de clients uniques et ceux
     * qui ont aussi réservé dans un autre mois de la même période (= clients fidèles).
     *
     * Approche PHP-side pour éviter les sous-requêtes corrélées coûteuses :
     * 1. On récupère (period, email_hash) pour tous les clients actifs
     * 2. On détermine en PHP lesquels apparaissent dans >1 période (repeat)
     * 3. On calcule le taux par période
     */
    public function query_repeat_rate_by_period(string $from, string $to): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DATE_FORMAT(" . self::EDATE . ", '%%Y-%%m') AS period,
                        buyer_email_hash
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND booking_status NOT IN ('canceled','cancelled','rejected')
                   AND buyer_email_hash IS NOT NULL
                   AND buyer_email_hash != ''
                 GROUP BY period, buyer_email_hash
                 ORDER BY period",
                $from, $to
            ), ARRAY_A
        );

        if ( ! $rows ) return [];

        // Index : email_hash → liste des périodes où il apparaît
        $customer_periods = [];
        foreach ( $rows as $row ) {
            $customer_periods[ $row['buyer_email_hash'] ][] = $row['period'];
        }

        // Clients qui apparaissent dans ≥2 périodes = repeat
        $repeat_set = [];
        foreach ( $customer_periods as $hash => $periods ) {
            if ( count( array_unique( $periods ) ) >= 2 ) {
                $repeat_set[ $hash ] = true;
            }
        }

        // Agrégation par période
        $by_period = [];
        foreach ( $rows as $row ) {
            $p = $row['period'];
            if ( ! isset( $by_period[ $p ] ) ) {
                $by_period[ $p ] = [ 'period' => $p, 'unique_customers' => 0, 'repeat_customers' => 0 ];
            }
            $by_period[ $p ]['unique_customers']++;
            if ( isset( $repeat_set[ $row['buyer_email_hash'] ] ) ) {
                $by_period[ $p ]['repeat_customers']++;
            }
        }

        return array_map( function ( $p ) {
            $p['repeat_rate'] = $p['unique_customers'] > 0
                ? round( 100 * $p['repeat_customers'] / $p['unique_customers'], 1 )
                : 0;
            return $p;
        }, array_values( $by_period ) );
    }

    /**
     * Product mix over time (top N products × period).
     */
    public function query_product_mix(string $from, string $to, string $granularity = 'month', int $top_n = 5): array {
        global $wpdb;

        $format = match ($granularity) {
            'day'   => '%Y-%m-%d',
            'week'  => '%x-W%v',
            default => '%Y-%m',
        };

        // Get top N products first
        $top = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT product_name
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY product_name
                 ORDER BY COUNT(*) DESC
                 LIMIT %d",
                $from, $to, $top_n
            )
        );

        if (empty($top)) return [];

        // Build IN clause safely
        $placeholders = implode(',', array_fill(0, count($top), '%s'));
        $params = array_merge([$format, $from, $to], $top);

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    DATE_FORMAT(" . self::EDATE . ", %s) AS period_key,
                    product_name AS name,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                   AND product_name IN ({$placeholders})
                 GROUP BY period_key, product_name
                 ORDER BY period_key, revenue DESC",
                ...$params
            ), ARRAY_A
        );

        return ['products' => $top, 'data' => $rows ?: []];
    }

    /**
     * Matrice canal × statut de réservation.
     */
    public function query_channel_status(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    COALESCE(NULLIF(TRIM(channel),''), 'Non renseigné') AS channel,
                    booking_status AS status,
                    COUNT(*) AS bookings,
                    ROUND(SUM(COALESCE(price_total, 0)), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY channel, booking_status
                 ORDER BY channel, bookings DESC",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Saisonnalité Year-over-Year (même mois, années différentes).
     */
    public function query_yoy(string $from, string $to): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT
                    YEAR(appointment_date) AS year_num,
                    MONTH(appointment_date) AS month_num,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue,
                    ROUND(AVG(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              AND price_total IS NOT NULL AND price_total > 0
                              THEN price_total ELSE NULL END), 2) AS avg_basket
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY year_num, month_num
                 ORDER BY year_num, month_num",
                $from, $to
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Revenue cumulé par période (running sum calculé en PHP pour compat MySQL 5.7).
     */
    public function query_cumulative(string $from, string $to, string $granularity = 'month'): array {
        $stats = $this->query_stats($from, $to, $granularity);
        $cumulative_revenue  = 0;
        $cumulative_bookings = 0;
        $result = [];

        foreach ($stats as $row) {
            $revenue = (float) $row['revenue'];
            $bookings = (int) $row['bookings'];
            $cumulative_revenue  += $revenue;
            $cumulative_bookings += $bookings;
            $result[] = [
                'period_key'          => $row['period_key'],
                'revenue'             => $revenue,
                'bookings'            => $bookings,
                'cumulative_revenue'  => round($cumulative_revenue, 2),
                'cumulative_bookings' => $cumulative_bookings,
            ];
        }

        return $result;
    }

    /**
     * Top N dates par volume de réservation (pour le widget "Top jours").
     *
     * @param string $from  Date de début YYYY-MM-DD
     * @param string $to    Date de fin YYYY-MM-DD
     * @param int    $limit Nombre de dates à retourner (défaut 7)
     * @return array [{date, count}]
     */
    public function query_top_dates(string $from, string $to, int $limit = 7): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT " . self::EDATE . " AS date,
                        COUNT(*) AS count
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY date
                 ORDER BY count DESC
                 LIMIT %d",
                $from, $to, $limit
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * Top dates par volume d'annulations — même format que query_top_dates
     * mais filtré sur booking_status IN ('canceled','cancelled','rejected').
     *
     * @param string $from  YYYY-MM-DD
     * @param string $to    YYYY-MM-DD
     * @param int    $limit Nombre de dates à retourner (défaut 30 pour les sélecteurs 7/20/30)
     * @return array [{date: 'YYYY-MM-DD', count: N}] trié DESC
     */
    public function query_top_cancellation_dates(string $from, string $to, int $limit = 30): array {
        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT " . self::EDATE . " AS date,
                        COUNT(*) AS count
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                   AND booking_status IN ('canceled','cancelled','rejected')
                 GROUP BY date
                 ORDER BY count DESC
                 LIMIT %d",
                $from, $to, $limit
            ), ARRAY_A
        );
        return $rows ?: [];
    }

    /**
     * KPIs enrichis : ajoute lead time, repeat rate, revenue/jour, etc.
     */
    public function query_enhanced_kpis(string $from, string $to): array {
        global $wpdb;

        $base = $this->query_period_kpis($from, $to);
        if (empty($base)) return $base;

        $days = max(1, (int) round((strtotime($to) - strtotime($from)) / 86400) + 1);

        // Lead time moyen global
        $avg_lead = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ROUND(AVG(DATEDIFF(appointment_date, DATE(created_at))), 1)
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND created_at IS NOT NULL
                   AND DATEDIFF(appointment_date, DATE(created_at)) >= 0",
                $from, $to
            )
        );

        // Repeat rate
        $customer_stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT
                    COUNT(DISTINCT buyer_email_hash) AS unique_customers,
                    SUM(CASE WHEN cnt > 1 THEN 1 ELSE 0 END) AS repeat_customers
                 FROM (
                    SELECT buyer_email_hash, COUNT(*) AS cnt
                    FROM `{$this->table}`
                    WHERE " . self::EDATE . " BETWEEN %s AND %s
                      AND buyer_email_hash IS NOT NULL
                      AND buyer_email_hash != ''
                    GROUP BY buyer_email_hash
                 ) sub",
                $from, $to
            ), ARRAY_A
        );

        // Avg quantity
        $avg_qty = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ROUND(AVG(quantity), 1)
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND quantity > 0",
                $from, $to
            )
        );

        // Unpaid rate
        $unpaid = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND (payment_status IS NULL OR TRIM(payment_status) = '' OR payment_status NOT IN ('paid','completed','succeeded'))",
                $from, $to
            )
        );

        // Peak weekday
        $peak_dow = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT DAYOFWEEK(" . self::EDATE . ")
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY DAYOFWEEK(" . self::EDATE . ")
                 ORDER BY COUNT(*) DESC
                 LIMIT 1",
                $from, $to
            )
        );
        $dow_labels = [1 => 'Dim', 2 => 'Lun', 3 => 'Mar', 4 => 'Mer', 5 => 'Jeu', 6 => 'Ven', 7 => 'Sam'];

        // Top product name
        $top_prod = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT product_name
                 FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND " . self::EDATE . " IS NOT NULL
                 GROUP BY product_name
                 ORDER BY COUNT(*) DESC
                 LIMIT 1",
                $from, $to
            )
        );

        // Top 3 concentration
        $top3_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(cnt) FROM (
                    SELECT COUNT(*) AS cnt
                    FROM `{$this->table}`
                    WHERE " . self::EDATE . " BETWEEN %s AND %s
                    GROUP BY product_name
                    ORDER BY cnt DESC
                    LIMIT 3
                 ) sub",
                $from, $to
            )
        );

        $unique_cust = (int) ($customer_stats['unique_customers'] ?? 0);
        $repeat_cust = (int) ($customer_stats['repeat_customers'] ?? 0);
        $total = $base['total_bookings'] ?? 0;

        return array_merge($base, [
            'revenue_per_day'     => $days > 0 ? round(($base['total_revenue'] ?? 0) / $days, 2) : 0,
            'bookings_per_day'    => $days > 0 ? round($total / $days, 1) : 0,
            'avg_lead_time_days'  => $avg_lead !== null ? (float) $avg_lead : null,
            'unique_customers'    => $unique_cust,
            'repeat_customers'    => $repeat_cust,
            'repeat_rate'         => $unique_cust > 0 ? round($repeat_cust / $unique_cust * 100, 1) : 0,
            'avg_quantity'        => $avg_qty !== null ? (float) $avg_qty : null,
            'unpaid_rate'         => $total > 0 ? round((int) $unpaid / $total * 100, 1) : 0,
            'peak_weekday'        => $dow_labels[(int) $peak_dow] ?? null,
            'top_product_name'    => $top_prod,
            'top3_concentration'  => $total > 0 ? round((int) $top3_count / $total * 100, 1) : 0,
        ]);
    }

    // ─── Customers from local DB ────────────────────────────────────────────

    /**
     * Derive customer list from bt_reservations (buyer_email_hash based grouping).
     * Replaces the failing Regiondo CRM API.
     */
    public function query_customers(int $page = 1, int $per_page = 50, ?string $search = null, string $sort_key = 'last_booking', string $sort_dir = 'desc'): array {
        global $wpdb;

        $enc = new Encryption();

        // Whitelist sort columns to prevent SQL injection
        // Note: buyer_name is encrypted, sorting by it is meaningless — excluded (audit §C04)
        $allowed_sort = ['last_booking' => 'last_booking', 'bookings_count' => 'bookings_count', 'total_spent' => 'total_spent'];
        $order_col = $allowed_sort[$sort_key] ?? 'last_booking';
        $order_dir = strtoupper($sort_dir) === 'ASC' ? 'ASC' : 'DESC';

        // Count unique customers by email hash
        $where = "buyer_email_hash IS NOT NULL AND buyer_email_hash != ''";
        $params = [];

        if ($search) {
            $hash = $enc->blind_hash($search);
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= ' AND (buyer_email_hash = %s OR buyer_name_hash = %s OR product_name LIKE %s)';
            $params[] = $hash;
            $params[] = $hash;
            $params[] = $like;
        }

        $offset = ($page - 1) * $per_page;

        // Build count query with prepare() wrapping the full SQL
        $count_sql = "SELECT COUNT(DISTINCT buyer_email_hash) FROM `{$this->table}` WHERE {$where}";
        $total = (int) $wpdb->get_var(
            $params ? $wpdb->prepare($count_sql, $params) : $count_sql
        );

        // Build main query with prepare() wrapping the full SQL
        $main_sql = "SELECT
                buyer_email_hash,
                buyer_name,
                buyer_email,
                COUNT(*) AS bookings_count,
                COALESCE(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                             THEN price_total ELSE 0 END), 0) AS total_spent,
                MAX(appointment_date) AS last_booking
             FROM `{$this->table}`
             WHERE {$where}
             GROUP BY buyer_email_hash
             ORDER BY `{$order_col}` {$order_dir}
             LIMIT %d OFFSET %d";
        $main_params = array_merge($params, [$per_page, $offset]);

        $rows = $wpdb->get_results(
            $wpdb->prepare($main_sql, $main_params),
            ARRAY_A
        );

        $data = [];
        foreach ($rows ?: [] as $r) {
            $data[] = [
                'email'          => $enc->decrypt($r['buyer_email'] ?? ''),
                'name'           => $enc->decrypt($r['buyer_name']  ?? ''),
                'bookings_count' => (int) $r['bookings_count'],
                'total_spent'    => round((float) $r['total_spent'], 2),
                'currency'       => 'EUR',
                'last_booking'   => $r['last_booking'],
            ];
        }

        return ['data' => $data, 'total' => $total];
    }
}
