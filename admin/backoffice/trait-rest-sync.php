<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiSync — synchronisation et import des réservations/solditems.
 * Gère bookings sync, reservations import, CSV import, reparse prices.
 */
trait RestApiSync {

    /**
     * Lance la synchronisation d'une période vers la DB.
     * Appelé par le frontend année par année pour la sync complète.
     *
     * Body JSON: { "from": "YYYY-MM-DD", "to": "YYYY-MM-DD" }
     * Ou :       { "year": 2023 }
     */
    public function sync_bookings(\WP_REST_Request $req): \WP_REST_Response {
        // Deprecated: /partner/bookings returns 401 for supplier accounts.
        // Use Import solditems (/reservations/import) instead.
        return new \WP_REST_Response([
            'error' => 'Endpoint désactivé. L\'API /partner/bookings retourne 401 pour les comptes supplier. Utilisez Import solditems à la place.',
        ], 410); // 410 Gone
    }

    /** Retourne les stats de la DB locale et le statut de la dernière sync. */
    public function get_sync_status(): \WP_REST_Response {
        return rest_ensure_response((new ReservationDb())->get_sync_status());
    }

    /** Vide complètement la table bt_reservations et remet le statut à zéro. */
    public function reset_bookings_db(): \WP_REST_Response {
        (new ReservationDb())->truncate();
        return rest_ensure_response(['success' => true]);
    }

    // ─── Handlers — Import réservations (solditems) ────────────────────────────

    /**
     * Liste paginée des articles vendus depuis la DB locale.
     * Paramètres : page, per_page, from, to, status, search.
     */
    public function get_reservations(\WP_REST_Request $req): \WP_REST_Response {
        $db     = new ReservationDb();
        $result = $db->query([
            'page'     => (int)    ($req->get_param('page')     ?: 1),
            'per_page' => (int)    ($req->get_param('per_page') ?: 50),
            'from'     => (string) ($req->get_param('from')     ?: ''),
            'to'       => (string) ($req->get_param('to')       ?: ''),
            'status'   => (string) ($req->get_param('status')   ?: ''),
            'search'   => (string) ($req->get_param('search')   ?: ''),
        ]);
        return rest_ensure_response($result);
    }

    /**
     * Lance l'import d'une période de solditems vers la DB.
     * Body JSON : { "year": 2023 } ou { "from": "YYYY-MM-DD", "to": "YYYY-MM-DD" }.
     */
    public function import_reservations(\WP_REST_Request $req): \WP_REST_Response {
        // Prevent concurrent imports
        if (get_transient('bt_import_lock')) {
            return new \WP_REST_Response(['error' => 'Import déjà en cours. Réessayez dans quelques minutes.'], 409);
        }
        set_transient('bt_import_lock', 1, 300);

        $body = $req->get_json_params();
        $db   = new ReservationDb();

        if (!empty($body['year'])) {
            $y    = (int) $body['year'];
            $from = "{$y}-01-01";
            $to   = "{$y}-12-31";
        } else {
            $from = sanitize_text_field($body['from'] ?? '');
            $to   = sanitize_text_field($body['to']   ?? '');
        }

        if (!$from || !$to
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)
        ) {
            return new \WP_REST_Response(['error' => 'Paramètres from/to ou year requis.'], 400);
        }

        $sync   = new ReservationSync();
        $result = $sync->import_period($from, $to);

        // Collecter les product_ids concernés depuis le résultat
        $synced_product_ids = [];
        if (!empty($result['product_ids']) && is_array($result['product_ids'])) {
            $synced_product_ids = $result['product_ids'];
        } elseif (!empty($result['imported']) || !empty($result['updated'])) {
            // Fallback : récupérer depuis le product_map GYG
            $product_map = json_decode(get_option('bt_gyg_product_map', '[]'), true);
            if (is_array($product_map)) {
                foreach ($product_map as $entry) {
                    if (!empty($entry['active']) && !empty($entry['notre_product_id'])) {
                        $synced_product_ids[] = $entry['notre_product_id'];
                    }
                }
            }
        }

        // Notifier les listeners qu'un sync Regiondo a eu lieu (ex: GYG notify-availability)
        if (!empty($synced_product_ids)) {
            do_action('bt_after_regiondo_sync', $synced_product_ids);
        }

        // Mémoriser l'année synchronisée
        $status = $db->get_sync_status();
        $years  = $status['years_synced'] ?? [];
        if (!empty($body['year'])) {
            $years[] = (int) $body['year'];
            $years   = array_unique($years);
            sort($years);
            $db->update_sync_status(['years_synced' => $years]);
        }
        $db->update_sync_status(['last_import' => current_time('mysql', true)]);
        delete_transient('bt_import_lock');

        return rest_ensure_response(array_merge($result, ['db' => $db->get_sync_status()]));
    }

    /** Retourne le statut de la dernière importation de réservations. */
    public function get_reservations_import_status(): \WP_REST_Response {
        return rest_ensure_response((new ReservationDb())->get_sync_status());
    }

    /** Vide complètement la table bt_reservations. */
    public function reset_reservations_db(): \WP_REST_Response {
        (new ReservationDb())->truncate();
        return rest_ensure_response(['success' => true]);
    }

    /**
     * Importe un batch de lignes parsées côté JS (export CSV Regiondo).
     * Body JSON attendu : { "items": [ { calendar_sold_id, … }, … ] }
     * Délégue l'upsert à ReservationDb::upsert().
     */
    public function import_reservations_csv(\WP_REST_Request $req): \WP_REST_Response {
        // Évite le timeout PHP (30s par défaut) sur les gros batches
        @set_time_limit(120);

        $body  = $req->get_json_params();
        $items = $body['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne reçue.'], 400);
        }
        if (count($items) > 1000) {
            return new \WP_REST_Response(['error' => 'Trop de lignes par batch (max 1000).'], 400);
        }

        $sanitized = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $cal_id = trim($item['calendar_sold_id'] ?? '');
            if (empty($cal_id)) continue;

            $offer_raw    = wp_strip_all_tags($item['offer_raw'] ?? '');
            $price_total  = ($item['price_total'] ?? null) !== null ? (float) $item['price_total'] : null;
            $product_name = sanitize_text_field($item['product_name'] ?? '');
            $quantity     = max(1, (int) ($item['quantity'] ?? 1));

            // PHP-side fallback: parse offer_raw if JS didn't extract fields
            if ($offer_raw !== '') {
                if ($price_total === null) {
                    $price_total = self::parse_price_from_offer_raw($offer_raw);
                }
                if ($product_name === '') {
                    $product_name = self::parse_product_from_offer_raw($offer_raw) ?? '';
                }
                $parsed_qty = self::parse_quantity_from_offer_raw($offer_raw);
                if ($parsed_qty !== null && ($item['quantity'] ?? null) === null) {
                    $quantity = $parsed_qty;
                }
            }

            $sanitized[] = [
                'calendar_sold_id'   => sanitize_text_field($cal_id),
                'order_increment_id' => sanitize_text_field($item['order_increment_id'] ?? '') ?: null,
                'created_at'         => sanitize_text_field($item['created_at'] ?? ''),
                'offer_raw'          => $offer_raw,
                'product_name'       => $product_name,
                'quantity'           => $quantity,
                'price_total'        => $price_total,
                'buyer_name'         => sanitize_text_field($item['buyer_name'] ?? ''),
                'buyer_email'        => sanitize_email($item['buyer_email'] ?? ''),
                'appointment_date'   => self::parse_appointment_date(sanitize_text_field($item['appointment_date'] ?? '')),
                'channel'            => self::sanitize_channel(sanitize_text_field($item['channel'] ?? '')),
                'booking_status'     => self::normalize_booking_status(sanitize_text_field($item['booking_status'] ?? '')),
                'payment_method'     => sanitize_text_field($item['payment_method'] ?? ''),
                'payment_status'     => self::normalize_payment_status(sanitize_text_field($item['payment_status'] ?? '')),
                'booking_key'        => sanitize_text_field($item['booking_key'] ?? ''),
                'buyer_country'      => strtoupper(substr(sanitize_text_field($item['buyer_country'] ?? ''), 0, 5)),
            ];
        }

        if (empty($sanitized)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne valide (calendar_sold_id manquant ?).'], 400);
        }

        return rest_ensure_response((new ReservationDb())->upsert($sanitized));
    }

    /**
     * Parse French appointment date to MySQL DATE.
     * Handles: "01 juin 2026 18:00", "1 juin 2026", "01/06/2026", ISO dates.
     * Returns empty string if unparseable (DB will store NULL).
     */
    private static function parse_appointment_date(string $raw): string {
        if (empty($raw)) return '';

        // Already ISO format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
            return substr($raw, 0, 10);
        }

        // French month names → number
        static $months = [
            'janvier'=>'01','février'=>'02','fevrier'=>'02','mars'=>'03',
            'avril'=>'04','mai'=>'05','juin'=>'06','juillet'=>'07',
            'août'=>'08','aout'=>'08','septembre'=>'09','octobre'=>'10',
            'novembre'=>'11','décembre'=>'12','decembre'=>'12',
        ];

        // "01 juin 2026 18:00" or "1 juin 2026"
        if (preg_match('/(\d{1,2})\s+(\S+)\s+(\d{4})/u', strtolower($raw), $m)) {
            $month = $months[$m[2]] ?? null;
            if ($month) {
                return $m[3] . '-' . $month . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
            }
        }

        // "01/06/2026" or "1/6/2026"
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $raw, $m)) {
            return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        return '';
    }

    /**
     * Normalize booking_status: handles French values from Regiondo CSV exports.
     */
    private static function normalize_booking_status(string $status): string {
        static $map = [
            'confirmé'                          => 'confirmed',
            'confirmé (bon enregistré)'         => 'confirmed',
            'confirmé (bon cadeau)'             => 'confirmed',
            'annulé'                            => 'cancelled',
            'annulé (commercial)'               => 'cancelled',
            'annulé (regiondo)'                 => 'cancelled',
            'annulé (paiement non effectué)'    => 'cancelled',
            'refusé'                            => 'rejected',
            'échu'                              => 'expired',
            'en attente'                        => 'pending',
            'remboursé'                         => 'refunded',
        ];

        $lower = mb_strtolower(trim($status), 'UTF-8');
        return $map[$lower] ?? $status;
    }

    /**
     * Sanitize channel: strip HTML </br> tags and booking reference codes appended by Regiondo.
     * "GetYourGuide Deutschland GmbH </br>GYGRFQWKLZWK" → "GetYourGuide Deutschland GmbH"
     */
    private static function sanitize_channel(string $channel): string {
        // Strip from </br> onwards (Regiondo appends booking ref this way)
        $channel = preg_replace('/<\/?\s*br\s*\/?>.*/si', '', $channel);
        // Strip any remaining HTML tags
        $channel = wp_strip_all_tags($channel);
        return trim($channel);
    }

    /**
     * Normalize payment_status: extract canonical state from descriptive strings.
     * "Payé (Carte de crédit) xxxx 2975" → "paid"
     */
    private static function normalize_payment_status(string $status): string {
        $lower = mb_strtolower(trim($status), 'UTF-8');
        if (str_starts_with($lower, 'payé') || str_starts_with($lower, 'paid') || str_starts_with($lower, 'completed') || str_starts_with($lower, 'succeeded')) {
            return 'paid';
        }
        if (str_contains($lower, 'non payé') || str_contains($lower, 'impayé') || $lower === 'unpaid') {
            return 'unpaid';
        }
        if (str_starts_with($lower, 'remboursé') || str_starts_with($lower, 'refunded')) {
            return 'refunded';
        }
        if (str_starts_with($lower, 'en attente') || str_starts_with($lower, 'pending') || str_starts_with($lower, 'processing')) {
            return 'pending';
        }
        return $status;
    }

    /**
     * Parse "Montant total: 55,00 €" from offer_raw string → float price.
     */
    private static function parse_price_from_offer_raw(string $raw): ?float {
        if (preg_match('/Montant\s+total\s*:\s*([\d\s.,]+)\s*€/i', $raw, $m)) {
            $price_str = str_replace([' ', ','], ['', '.'], $m[1]);
            $val = (float) $price_str;
            return $val > 0 ? $val : null;
        }
        return null;
    }

    /**
     * Parse quantity from offer_raw: "1 ×" or "2 x"
     */
    private static function parse_quantity_from_offer_raw(string $raw): ?int {
        if (preg_match('/(\d+)\s*[×x]/i', $raw, $m)) {
            return max(1, (int) $m[1]);
        }
        return null;
    }

    /**
     * Parse product name from offer_raw: text before "N ×"
     */
    private static function parse_product_from_offer_raw(string $raw): ?string {
        if (preg_match('/^(.+?)\s+\d+\s*[×x]\s/i', $raw, $m)) {
            return trim(preg_replace('/\s+/', ' ', $m[1]));
        }
        // Fallback: text before "Montant total"
        $parts = preg_split('/Montant\s+total/i', $raw);
        if (!empty($parts[0])) {
            return trim(preg_replace('/[\s,]+$/', '', preg_replace('/\s+/', ' ', $parts[0])));
        }
        return null;
    }

    /**
     * Re-parse offer_raw → price_total/product_name/quantity for rows where price_total IS NULL.
     * This fixes historical imports that didn't parse _produit_raw.
     */
    public function reparse_prices(): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'bt_reservations';

        // ── Phase 1: Fix appointment_date NULL / 0000-00-00 → use created_at ────
        $dates_fixed = (int) $wpdb->query(
            "UPDATE `{$table}`
             SET appointment_date = DATE(created_at)
             WHERE (appointment_date IS NULL OR appointment_date = '0000-00-00')
               AND created_at IS NOT NULL"
        );

        // ── Phase 2: Parse price_total from offer_raw ────────────────────
        // Small batch (200) to avoid Cloudflare 525 timeout
        $rows = $wpdb->get_results(
            "SELECT id, offer_raw FROM `{$table}`
             WHERE price_total IS NULL AND offer_raw IS NOT NULL AND offer_raw != ''
             LIMIT 200",
            ARRAY_A
        );

        $prices_fixed = 0;
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $raw   = $row['offer_raw'];
                $price = self::parse_price_from_offer_raw($raw);
                if ($price === null) continue;

                $sets   = ['price_total = %f'];
                $values = [$price];

                $qty = self::parse_quantity_from_offer_raw($raw);
                if ($qty !== null) {
                    $sets[]   = 'quantity = %d';
                    $values[] = $qty;
                }

                $name = self::parse_product_from_offer_raw($raw);
                if ($name !== null) {
                    $sets[]   = 'product_name = %s';
                    $values[] = $name;
                }

                $values[] = (int) $row['id'];
                $wpdb->query($wpdb->prepare(
                    "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = %d",
                    ...$values
                ));
                $prices_fixed++;
            }
        }

        // Check remaining
        $remaining_prices = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$table}` WHERE price_total IS NULL AND offer_raw IS NOT NULL AND offer_raw != ''"
        );
        $remaining_dates = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$table}` WHERE (appointment_date IS NULL OR appointment_date = '0000-00-00') AND created_at IS NOT NULL"
        );
        $remaining = $remaining_prices + $remaining_dates;

        return rest_ensure_response([
            'updated'      => $prices_fixed + $dates_fixed,
            'prices_fixed' => $prices_fixed,
            'dates_fixed'  => $dates_fixed,
            'remaining'    => $remaining,
            'message'      => $remaining > 0
                ? "Corrigé {$prices_fixed} prix + {$dates_fixed} dates, encore {$remaining} à traiter."
                : "Terminé — {$prices_fixed} prix + {$dates_fixed} dates corrigés.",
        ]);
    }
}
