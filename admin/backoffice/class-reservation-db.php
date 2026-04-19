<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Couche base de données pour les articles vendus Regiondo (solditems).
 *
 * Table : {prefix}bt_reservations
 * Clé unique : calendar_sold_id (upsert sur conflit)
 * price_total peut être négatif pour les remboursements.
 *
 * RGPD : buyer_name, buyer_email, booking_key sont chiffrés AES-256-CBC (class-encryption.php).
 * Les colonnes buyer_name_hash et buyer_email_hash stockent un HMAC-SHA256
 * pour permettre la recherche exacte sans déchiffrer toutes les lignes.
 *
 * Analytics & stats → voir ReservationStats (class-reservation-stats.php).
 */
class ReservationDb {

    protected string $table;
    private string $option_key = 'bt_reservation_sync_status';

    /**
     * SQL expression for the "effective date" used in revenue/KPI stats.
     * Uses DATE(created_at) — the booking date — which is when revenue is captured.
     * appointment_date (activity date) is intentionally excluded here: a booking made
     * today for a June excursion must appear in today's CA, not in June's.
     * The Planner view uses appointment_date directly via its own queries.
     */
    protected const EDATE = 'DATE(created_at)';

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'bt_reservations';
    }

    // ─── Schéma ───────────────────────────────────────────────────────────────

    public function ensure_table(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table   = $this->table;

        // buyer_name, buyer_email, booking_key : TEXT pour stocker le ciphertext base64.
        // buyer_name_hash, buyer_email_hash : HMAC-SHA256 pour la recherche exacte (blind index).
        $sql = "CREATE TABLE {$table} (
            id                 bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            calendar_sold_id   varchar(100)        NOT NULL,
            order_increment_id varchar(100)        DEFAULT NULL,
            created_at         datetime            DEFAULT NULL,
            offer_raw          text                DEFAULT NULL,
            product_name       varchar(255)        DEFAULT NULL,
            quantity           int                 DEFAULT 0,
            price_total        decimal(10,2)       DEFAULT NULL,
            buyer_name         text                DEFAULT NULL,
            buyer_name_hash    varchar(64)         DEFAULT NULL,
            buyer_email        text                DEFAULT NULL,
            buyer_email_hash   varchar(64)         DEFAULT NULL,
            appointment_date   date                DEFAULT NULL,
            channel            varchar(100)        DEFAULT NULL,
            booking_status     varchar(50)         DEFAULT NULL,
            payment_method     varchar(100)        DEFAULT NULL,
            payment_status     varchar(50)         DEFAULT NULL,
            booking_key        text                DEFAULT NULL,
            buyer_country      varchar(5)          DEFAULT NULL,
            imported_at        datetime            NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY calendar_sold_id (calendar_sold_id),
            KEY order_increment_id (order_increment_id),
            KEY appointment_date   (appointment_date),
            KEY booking_status     (booking_status),
            KEY buyer_email_hash   (buyer_email_hash),
            KEY buyer_name_hash    (buyer_name_hash),
            KEY idx_stats (appointment_date, booking_status, price_total),
            KEY idx_channel_date (channel, appointment_date),
            KEY idx_product_date (product_name(100), appointment_date),
            KEY idx_created_hour (created_at),
            KEY idx_payment (payment_method, payment_status)
        ) ENGINE=InnoDB {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    /**
     * Insère ou met à jour un lot d'articles vendus.
     * Les données personnelles (buyer_name, buyer_email, booking_key) sont chiffrées
     * avant stockage. Les blind index (hash) permettent la recherche exacte.
     *
     * @param array $items Tableau d'articles normalisés
     * @return array { inserted: int, updated: int, skipped: int, errors: string[] }
     */
    public function upsert(array $items): array {
        global $wpdb;

        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $now   = current_time('mysql', true);
        $enc   = new Encryption();

        // ── Crypto row-by-row, puis bulk INSERT via $wpdb->prepare() ─────────
        $placeholders = [];
        $flat_values  = [];

        foreach ($items as $item) {
            $ref = trim($item['calendar_sold_id'] ?? '');
            if (empty($ref)) { $stats['skipped']++; continue; }

            $buyer_name  = (string) ($item['buyer_name']  ?? '');
            $buyer_email = (string) ($item['buyer_email'] ?? '');
            $booking_key = (string) ($item['booking_key'] ?? '');

            $price_total = $item['price_total'] ?? null;

            // 19 colonnes → 19 placeholders par row
            $row_ph = [];
            $row_vals = [
                $ref,
                trim($item['order_increment_id'] ?? '') ?: null,
                trim($item['created_at']         ?? '') ?: null,
                (string) ($item['offer_raw']      ?? ''),
                (string) ($item['product_name']   ?? ''),
                (int) ($item['quantity'] ?? 1),
                $price_total !== null ? (float) $price_total : null,
                $buyer_name  !== '' ? $enc->encrypt($buyer_name)  : '',
                $buyer_name  !== '' ? $enc->blind_hash($buyer_name)  : null,
                $buyer_email !== '' ? $enc->encrypt($buyer_email) : '',
                $buyer_email !== '' ? $enc->blind_hash($buyer_email) : null,
                trim($item['appointment_date']   ?? '') ?: null,
                (string) ($item['channel']        ?? ''),
                (string) ($item['booking_status'] ?? ''),
                (string) ($item['payment_method'] ?? ''),
                (string) ($item['payment_status'] ?? ''),
                $booking_key !== '' ? $enc->encrypt($booking_key) : '',
                (string) ($item['buyer_country']  ?? ''),
                $now,
            ];

            // Build placeholder string with proper types
            $ph_parts = [];
            foreach ($row_vals as $v) {
                if ($v === null) {
                    $ph_parts[] = 'NULL';
                } elseif (is_int($v)) {
                    $ph_parts[] = '%d';
                    $flat_values[] = $v;
                } elseif (is_float($v)) {
                    $ph_parts[] = '%f';
                    $flat_values[] = $v;
                } else {
                    $ph_parts[] = '%s';
                    $flat_values[] = (string) $v;
                }
            }
            $placeholders[] = '(' . implode(',', $ph_parts) . ')';
        }

        if (empty($placeholders)) return $stats;

        $cols = "(calendar_sold_id, order_increment_id, created_at, offer_raw,
                  product_name, quantity, price_total,
                  buyer_name, buyer_name_hash, buyer_email, buyer_email_hash,
                  appointment_date, channel, booking_status, payment_method,
                  payment_status, booking_key, buyer_country, imported_at)";

        $sql = "INSERT INTO `{$this->table}` {$cols} VALUES "
             . implode(',', $placeholders)
             . " ON DUPLICATE KEY UPDATE
                  order_increment_id = VALUES(order_increment_id),
                  created_at         = VALUES(created_at),
                  offer_raw          = VALUES(offer_raw),
                  product_name       = VALUES(product_name),
                  quantity           = VALUES(quantity),
                  price_total        = VALUES(price_total),
                  buyer_name         = VALUES(buyer_name),
                  buyer_name_hash    = VALUES(buyer_name_hash),
                  buyer_email        = VALUES(buyer_email),
                  buyer_email_hash   = VALUES(buyer_email_hash),
                  appointment_date   = VALUES(appointment_date),
                  channel            = VALUES(channel),
                  booking_status     = VALUES(booking_status),
                  payment_method     = VALUES(payment_method),
                  payment_status     = VALUES(payment_status),
                  buyer_country      = VALUES(buyer_country),
                  booking_key        = VALUES(booking_key),
                  imported_at        = VALUES(imported_at)";

        // Use $wpdb->prepare() for all user-provided values (audit §C04)
        $prepared = empty($flat_values) ? $sql : $wpdb->prepare($sql, $flat_values);
        $affected = $wpdb->query($prepared);

        if ($affected === false) {
            $stats['errors'][] = $wpdb->last_error;
        } else {
            // MySQL: affected_rows = inserts*1 + updates*2 → inserts = 2n - affected
            $n = count($placeholders);
            $stats['updated']  = max(0, (int) $affected - $n);
            $stats['inserted'] = max(0, $n - $stats['updated']);
        }

        return $stats;
    }

    // ─── Lecture ──────────────────────────────────────────────────────────────

    /**
     * Retourne les articles vendus paginés, données personnelles déchiffrées.
     * La recherche sur buyer_name/buyer_email utilise le blind index (exact uniquement).
     *
     * @param array $params Keys: page, per_page, from, to, status, search
     * @return array { data: [], total: int }
     */
    public function query(array $params = []): array {
        global $wpdb;

        $enc      = new Encryption();
        $page     = max(1, (int) ($params['page']     ?? 1));
        $per_page = min(200, max(1, (int) ($params['per_page'] ?? 50)));
        $offset   = ($page - 1) * $per_page;

        $where  = ['1=1'];
        $values = [];

        if (!empty($params['from'])) {
            $where[]  = 'DATE(created_at) >= %s';
            $values[] = $params['from'];
        }
        if (!empty($params['to'])) {
            $where[]  = 'DATE(created_at) <= %s';
            $values[] = $params['to'];
        }
        if (!empty($params['status'])) {
            $where[]  = 'booking_status = %s';
            $values[] = $params['status'];
        }
        if (!empty($params['search'])) {
            $term = trim($params['search']);
            $hash = $enc->blind_hash($term);
            $like = '%' . $wpdb->esc_like($term) . '%';

            // Données personnelles : recherche exacte via blind index (HMAC)
            // order_increment_id et product_name : LIKE possible (non chiffrés)
            $where[]  = '(buyer_email_hash = %s OR buyer_name_hash = %s OR order_increment_id LIKE %s OR product_name LIKE %s)';
            $values[] = $hash;
            $values[] = $hash;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        $total = (int) $wpdb->get_var(
            $values
                ? $wpdb->prepare("SELECT COUNT(*) FROM `{$this->table}` WHERE {$where_sql}", ...$values)
                : "SELECT COUNT(*) FROM `{$this->table}` WHERE {$where_sql}"
        );

        $rows = $wpdb->get_results(
            $values
                ? $wpdb->prepare(
                    "SELECT * FROM `{$this->table}` WHERE {$where_sql}
                     ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                    ...[...$values, $per_page, $offset]
                )
                : $wpdb->prepare(
                    "SELECT * FROM `{$this->table}` WHERE {$where_sql}
                     ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset,
                ),
            ARRAY_A
        );

        if (!$rows) return ['data' => [], 'total' => $total];

        // Déchiffrement des données personnelles + suppression des colonnes internes
        foreach ($rows as &$row) {
            $row['buyer_name']  = $enc->decrypt($row['buyer_name']  ?? '');
            $row['buyer_email'] = $enc->decrypt($row['buyer_email'] ?? '');
            $row['booking_key'] = $enc->decrypt($row['booking_key'] ?? '');
            unset($row['buyer_name_hash'], $row['buyer_email_hash']); // ne pas exposer les hashs
        }
        unset($row);

        return ['data' => $rows, 'total' => $total];
    }

    // ─── Statut de synchronisation ─────────────────────────────────────────────

    public function get_sync_status(): array {
        global $wpdb;

        $meta  = get_option($this->option_key, []);
        $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$this->table}`");
        $range = $wpdb->get_row(
            "SELECT MIN(appointment_date) AS date_min, MAX(appointment_date) AS date_max FROM `{$this->table}`",
            ARRAY_A
        );

        return [
            'total_in_db'  => $count,
            'date_min'     => $range['date_min']   ?? null,
            'date_max'     => $range['date_max']   ?? null,
            'last_import'  => $meta['last_import'] ?? null,
            'years_synced' => $meta['years_synced'] ?? [],
            'in_progress'  => $meta['in_progress'] ?? false,
        ];
    }

    public function update_sync_status(array $data): void {
        $current = get_option($this->option_key, []);
        update_option($this->option_key, array_merge($current, $data), false);
    }

    public function truncate(): void {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE `{$this->table}`");
        delete_option($this->option_key);
    }

    // ─── Lecture — Dashboard summary ──────────────────────────────────────────

    /**
     * Résumé pour le dashboard : réservations du mois, CA, 8 dernières lignes.
     *
     * @return array { bookings_month: int, revenue_month: float, recent_bookings: array }
     */
    public function get_summary(): array {
        global $wpdb;

        $month_start = date('Y-m-01');
        $month_end   = date('Y-m-t');

        $total_in_db = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$this->table}`");

        $bookings_month = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s",
                $month_start,
                $month_end,
            )
        );

        $revenue_month = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(price_total), 0) FROM `{$this->table}`
                 WHERE " . self::EDATE . " BETWEEN %s AND %s
                   AND booking_status NOT IN ('canceled','cancelled','rejected')",
                $month_start,
                $month_end,
            )
        );

        $enc  = new Encryption();
        $rows = $wpdb->get_results(
            "SELECT order_increment_id, product_name, appointment_date,
                    buyer_name, buyer_email, price_total, booking_status
             FROM `{$this->table}`
             ORDER BY appointment_date DESC, id DESC
             LIMIT 8",
            ARRAY_A
        );

        $recent = [];
        foreach ($rows ?: [] as $r) {
            $recent[] = [
                'booking_ref'    => $r['order_increment_id'],
                'product_name'   => $r['product_name'],
                'booking_date'   => $r['appointment_date'],
                'customer_name'  => $enc->decrypt($r['buyer_name']  ?? ''),
                'customer_email' => $enc->decrypt($r['buyer_email'] ?? ''),
                'total_price'    => $r['price_total'],
                'currency_code'  => 'EUR',
                'status'         => $r['booking_status'],
            ];
        }

        return [
            'total_in_db'     => $total_in_db,
            'bookings_month'  => $bookings_month,
            'revenue_month'   => round($revenue_month, 2),
            'recent_bookings' => $recent,
        ];
    }
}
