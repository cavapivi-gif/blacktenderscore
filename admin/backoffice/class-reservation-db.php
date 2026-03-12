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
 */
class ReservationDb {

    private string $table;
    private string $option_key = 'bt_reservation_sync_status';

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
            imported_at        datetime            NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY calendar_sold_id (calendar_sold_id),
            KEY order_increment_id (order_increment_id),
            KEY appointment_date   (appointment_date),
            KEY booking_status     (booking_status),
            KEY buyer_email_hash   (buyer_email_hash),
            KEY buyer_name_hash    (buyer_name_hash)
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

        // On bypasse $wpdb->query() : son check post-vsprintf rejette les valeurs
        // contenant %s/%d/%f dans le texte (ex: libellé produit). On utilise
        // mysqli_real_escape_string() directement sur la connexion.
        $conn = $wpdb->dbh;
        $q    = function(?string $v) use ($conn): string {
            if ($v === null || $v === '') return 'NULL';
            $escaped = ($conn instanceof \mysqli)
                ? mysqli_real_escape_string($conn, $v)
                : addslashes($v);
            return "'" . $escaped . "'";
        };

        foreach ($items as $item) {
            $ref = trim($item['calendar_sold_id'] ?? '');
            if (empty($ref)) {
                $stats['skipped']++;
                continue;
            }

            // ── Chiffrement des données personnelles (RGPD) ──────────────────
            $buyer_name  = (string) ($item['buyer_name']  ?? '');
            $buyer_email = (string) ($item['buyer_email'] ?? '');
            $booking_key = (string) ($item['booking_key'] ?? '');

            $buyer_name_enc  = $buyer_name  !== '' ? $enc->encrypt($buyer_name)  : '';
            $buyer_email_enc = $buyer_email !== '' ? $enc->encrypt($buyer_email) : '';
            $booking_key_enc = $booking_key !== '' ? $enc->encrypt($booking_key) : '';

            // Blind index HMAC pour recherche exacte sans déchiffrement SQL
            $buyer_name_hash  = $buyer_name  !== '' ? $enc->blind_hash($buyer_name)  : null;
            $buyer_email_hash = $buyer_email !== '' ? $enc->blind_hash($buyer_email) : null;

            $price_total = $item['price_total'] ?? null;
            $price_sql   = ($price_total !== null) ? number_format((float) $price_total, 2, '.', '') : 'NULL';

            // ON DUPLICATE KEY UPDATE : 1 = insert, 2 = update, 0 = no-op
            $sql = "INSERT INTO `{$this->table}`
                        (calendar_sold_id, order_increment_id, created_at, offer_raw,
                         product_name, quantity, price_total,
                         buyer_name, buyer_name_hash, buyer_email, buyer_email_hash,
                         appointment_date, channel, booking_status, payment_method,
                         payment_status, booking_key, imported_at)
                     VALUES (
                        {$q($ref)},
                        {$q(trim($item['order_increment_id'] ?? ''))},
                        {$q(trim($item['created_at']         ?? ''))},
                        {$q((string) ($item['offer_raw']      ?? ''))},
                        {$q((string) ($item['product_name']   ?? ''))},
                        " . (int) ($item['quantity'] ?? 1) . ",
                        {$price_sql},
                        {$q($buyer_name_enc)},
                        {$q($buyer_name_hash)},
                        {$q($buyer_email_enc)},
                        {$q($buyer_email_hash)},
                        {$q(trim($item['appointment_date']   ?? ''))},
                        {$q((string) ($item['channel']        ?? ''))},
                        {$q((string) ($item['booking_status'] ?? ''))},
                        {$q((string) ($item['payment_method'] ?? ''))},
                        {$q((string) ($item['payment_status'] ?? ''))},
                        {$q($booking_key_enc)},
                        {$q($now)}
                     )
                     ON DUPLICATE KEY UPDATE
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
                        booking_key        = VALUES(booking_key),
                        imported_at        = VALUES(imported_at)";

            if ($conn instanceof \mysqli) {
                $conn->query($sql);
                $affected = $conn->errno ? false : $conn->affected_rows;
                $db_error = $conn->error;
            } else {
                $affected = $wpdb->query($sql);
                $db_error = $wpdb->last_error;
            }

            if ($affected === false) {
                $stats['errors'][] = "calendar_sold_id={$ref}: " . ($db_error ?: 'query failed');
            } elseif ($affected === 2) {
                $stats['updated']++;
            } elseif ($affected === 1) {
                $stats['inserted']++;
            } else {
                $stats['skipped']++; // no-op : ligne identique
            }
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
            $where[]  = 'appointment_date >= %s';
            $values[] = $params['from'];
        }
        if (!empty($params['to'])) {
            $where[]  = 'appointment_date <= %s';
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
                     ORDER BY appointment_date DESC, id DESC LIMIT %d OFFSET %d",
                    ...[...$values, $per_page, $offset]
                )
                : $wpdb->prepare(
                    "SELECT * FROM `{$this->table}` WHERE {$where_sql}
                     ORDER BY appointment_date DESC, id DESC LIMIT %d OFFSET %d",
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

        $bookings_month = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$this->table}`
                 WHERE appointment_date BETWEEN %s AND %s",
                $month_start,
                $month_end,
            )
        );

        $revenue_month = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(price_total), 0) FROM `{$this->table}`
                 WHERE appointment_date BETWEEN %s AND %s
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
            'bookings_month'  => $bookings_month,
            'revenue_month'   => round($revenue_month, 2),
            'recent_bookings' => $recent,
        ];
    }

    // ─── Lecture — Charts (bookings stats) ────────────────────────────────────

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
                    DATE_FORMAT(appointment_date, %s) AS period_key,
                    COUNT(*)                           AS bookings,
                    SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                             THEN COALESCE(price_total, 0) ELSE 0 END) AS revenue,
                    SUM(CASE WHEN booking_status IN ('canceled','cancelled','rejected')
                             THEN 1 ELSE 0 END) AS cancelled,
                    ROUND(AVG(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              AND price_total IS NOT NULL AND price_total > 0
                              THEN price_total ELSE NULL END), 2) AS avg_basket
                 FROM `{$this->table}`
                 WHERE appointment_date BETWEEN %s AND %s
                   AND appointment_date IS NOT NULL
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
                 WHERE appointment_date BETWEEN %s AND %s
                   AND appointment_date IS NOT NULL
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
                 WHERE appointment_date BETWEEN %s AND %s
                   AND appointment_date IS NOT NULL
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
                    DAYOFWEEK(appointment_date) AS dow,
                    COUNT(*) AS bookings,
                    ROUND(SUM(CASE WHEN booking_status NOT IN ('canceled','cancelled','rejected')
                              THEN COALESCE(price_total, 0) ELSE 0 END), 2) AS revenue
                 FROM `{$this->table}`
                 WHERE appointment_date BETWEEN %s AND %s
                   AND appointment_date IS NOT NULL
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
                    COUNT(DISTINCT DATE_FORMAT(appointment_date,'%%Y-%%m')) AS active_months
                 FROM `{$this->table}`
                 WHERE appointment_date BETWEEN %s AND %s
                   AND appointment_date IS NOT NULL",
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

    // ─── Lecture — Planificateur ───────────────────────────────────────────────

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
                    DATE_FORMAT(appointment_date, '%%Y-%%m-%%d') AS booking_date,
                    order_increment_id AS booking_ref,
                    product_name,
                    buyer_name,
                    buyer_email,
                    price_total        AS total_price,
                    booking_status     AS status
                 FROM `{$this->table}`
                 WHERE appointment_date BETWEEN %s AND %s
                   AND appointment_date IS NOT NULL
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
}
