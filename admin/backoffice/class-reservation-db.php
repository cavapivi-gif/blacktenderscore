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

    /**
     * SQL expression for the "effective date" used in revenue/KPI stats.
     * Uses DATE(created_at) — the booking date — which is when revenue is captured.
     * appointment_date (activity date) is intentionally excluded here: a booking made
     * today for a June excursion must appear in today's CA, not in June's.
     * The Planner view uses appointment_date directly via its own queries.
     */
    private const EDATE = 'DATE(created_at)';

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
                         payment_status, booking_key, buyer_country, imported_at)
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
                        {$q((string) ($item['buyer_country']  ?? ''))},
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
                        buyer_country      = VALUES(buyer_country),
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

    // ─── Lecture — Advanced analytics ────────────────────────────────────────────

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
    public function query_customers(int $page = 1, int $per_page = 50, ?string $search = null): array {
        global $wpdb;

        $enc = new Encryption();

        // Count unique customers by email hash
        $where = "buyer_email_hash IS NOT NULL AND buyer_email_hash != ''";
        $values = [];

        if ($search) {
            $hash = $enc->blind_hash($search);
            $like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                ' AND (buyer_email_hash = %s OR buyer_name_hash = %s OR product_name LIKE %s)',
                $hash, $hash, $like
            );
        }

        $total = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT buyer_email_hash) FROM `{$this->table}` WHERE {$where}"
        );

        $offset = ($page - 1) * $per_page;

        $rows = $wpdb->get_results(
            "SELECT
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
             ORDER BY last_booking DESC
             LIMIT {$per_page} OFFSET {$offset}",
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
