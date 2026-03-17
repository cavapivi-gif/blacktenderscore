<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Couche base de données pour les avis clients importés via CSV Regiondo.
 *
 * Table  : {prefix}bt_reviews
 * Upsert : order_number (N° de commande Regiondo)
 *
 * Pas de chiffrement — les avis sont des données semi-publiques.
 */
class ReviewsDb {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'bt_reviews';
    }

    // ─── Schéma ───────────────────────────────────────────────────────────────

    public function ensure_table(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table   = $this->table;

        $sql = "CREATE TABLE {$table} (
            id             bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number   varchar(100)        NOT NULL,
            product_name   varchar(255)        DEFAULT NULL,
            category       varchar(255)        DEFAULT NULL,
            guide          varchar(255)        DEFAULT NULL,
            booking_date   date                DEFAULT NULL,
            event_date     date                DEFAULT NULL,
            review_date    date                DEFAULT NULL,
            customer_name  varchar(255)        DEFAULT NULL,
            customer_email varchar(255)        DEFAULT NULL,
            customer_phone varchar(100)        DEFAULT NULL,
            rating         tinyint(1)          DEFAULT NULL,
            review_title   varchar(500)        DEFAULT NULL,
            review_body    text                DEFAULT NULL,
            review_status  varchar(100)        DEFAULT NULL,
            employee_name  varchar(255)        DEFAULT NULL,
            response       text                DEFAULT NULL,
            imported_at    datetime            NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY order_number (order_number),
            KEY rating         (rating),
            KEY review_date    (review_date),
            KEY customer_email (customer_email(100)),
            KEY idx_product_date (product_name(100), review_date)
        ) ENGINE=InnoDB {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    /**
     * Insère ou met à jour un lot d'avis (upsert sur order_number).
     *
     * @param array $items  Avis normalisés depuis le CSV côté JS
     * @return array{ inserted: int, updated: int, skipped: int, errors: string[] }
     */
    public function import_batch(array $items): array {
        global $wpdb;

        $stats = ['inserted' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        $now   = current_time('mysql', true);

        foreach ($items as $item) {
            $order_number = sanitize_text_field(trim((string) ($item['order_number'] ?? '')));
            if (!$order_number) {
                $stats['skipped']++;
                continue;
            }

            $rating = ($item['rating'] ?? '') !== '' ? (int) $item['rating'] : null;
            if ($rating !== null && ($rating < 1 || $rating > 5)) {
                $rating = null;
            }

            $row = [
                'order_number'   => $order_number,
                'product_name'   => sanitize_text_field($item['product_name']   ?? ''),
                'category'       => sanitize_text_field($item['category']       ?? ''),
                'guide'          => sanitize_text_field($item['guide']           ?? ''),
                'booking_date'   => $this->parse_date($item['booking_date']  ?? ''),
                'event_date'     => $this->parse_date($item['event_date']    ?? ''),
                'review_date'    => $this->parse_date($item['review_date']   ?? ''),
                'customer_name'  => sanitize_text_field($item['customer_name']  ?? ''),
                'customer_email' => sanitize_email($item['customer_email']      ?? ''),
                'customer_phone' => sanitize_text_field($item['customer_phone'] ?? ''),
                'rating'         => $rating,
                'review_title'   => sanitize_text_field($item['review_title']   ?? ''),
                'review_body'    => sanitize_textarea_field($item['review_body'] ?? ''),
                'review_status'  => sanitize_text_field($item['review_status']  ?? ''),
                'employee_name'  => sanitize_text_field($item['employee_name']  ?? ''),
                'response'       => sanitize_textarea_field($item['response']   ?? ''),
            ];

            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE order_number = %s LIMIT 1",
                $order_number
            ));

            if ($exists) {
                $result = $wpdb->update($this->table, $row, ['order_number' => $order_number]);
                if ($result === false) {
                    $stats['errors'][] = "Mise à jour #{$order_number} : " . $wpdb->last_error;
                } else {
                    $stats['updated']++;
                }
            } else {
                $row['imported_at'] = $now;
                $result = $wpdb->insert($this->table, $row);
                if ($result === false) {
                    $stats['errors'][] = "Insertion #{$order_number} : " . $wpdb->last_error;
                } else {
                    $stats['inserted']++;
                }
            }
        }

        return $stats;
    }

    // ─── Lecture ──────────────────────────────────────────────────────────────

    /**
     * Liste paginée des avis avec filtres.
     *
     * @param array $args { page, per_page, search, product, rating, from, to, sort, dir }
     * @return array{ data: array, total: int }
     */
    public function get_reviews(array $args = []): array {
        global $wpdb;

        $page     = max(1, (int) ($args['page']     ?? 1));
        $per_page = min(200, max(1, (int) ($args['per_page'] ?? 50)));
        $search   = sanitize_text_field($args['search']  ?? '');
        $product  = sanitize_text_field($args['product'] ?? '');
        $rating   = ($args['rating'] ?? '') !== '' ? (int) $args['rating'] : null;
        $from     = sanitize_text_field($args['from'] ?? '');
        $to       = sanitize_text_field($args['to']   ?? '');

        $allowed_sort = ['review_date', 'rating', 'customer_name', 'product_name', 'imported_at'];
        $sort = in_array($args['sort'] ?? '', $allowed_sort, true) ? $args['sort'] : 'review_date';
        $dir  = strtoupper($args['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        [$where, $params] = $this->build_filters($search, $product, $rating, $from, $to);
        $where_sql = implode(' AND ', $where);
        $offset    = ($page - 1) * $per_page;

        $count_q = "SELECT COUNT(*) FROM {$this->table} WHERE {$where_sql}";
        $data_q  = "SELECT * FROM {$this->table} WHERE {$where_sql} ORDER BY {$sort} {$dir} LIMIT %d OFFSET %d";

        if ($params) {
            $total           = (int) $wpdb->get_var($wpdb->prepare($count_q, ...$params));
            $params_paginated = array_merge($params, [$per_page, $offset]);
            $data            = $wpdb->get_results(
                $wpdb->prepare($data_q, ...$params_paginated),
                ARRAY_A
            );
        } else {
            $total = (int) $wpdb->get_var($count_q);
            $data  = $wpdb->get_results(
                $wpdb->prepare($data_q, $per_page, $offset),
                ARRAY_A
            );
        }

        return ['data' => $data ?: [], 'total' => $total];
    }

    /**
     * Statistiques agrégées : total, moyenne, distribution, tendance mensuelle, projection 4.8★.
     *
     * @param array $args { from, to, product }
     * @return array
     */
    public function get_stats(array $args = []): array {
        global $wpdb;

        // Filtres communs (sans rating pour total_all)
        [$where_all, $params_all] = $this->build_filters(
            '', $args['product'] ?? '', null, $args['from'] ?? '', $args['to'] ?? ''
        );
        $where_all_sql = implode(' AND ', $where_all);

        // Filtres avec rating IS NOT NULL pour les statistiques
        $where_rated     = array_merge($where_all, ['rating IS NOT NULL']);
        $where_rated_sql = implode(' AND ', $where_rated);

        $base_rated = $params_all
            ? $wpdb->prepare("FROM {$this->table} WHERE {$where_rated_sql}", ...$params_all)
            : "FROM {$this->table} WHERE {$where_rated_sql}";

        // Agrégats globaux
        $global = $wpdb->get_row(
            "SELECT COUNT(*) as total, AVG(rating) as avg_rating,
                    MIN(review_date) as min_date, MAX(review_date) as max_date
             {$base_rated}",
            ARRAY_A
        );

        // Distribution par étoile (1-5)
        $dist_rows = $wpdb->get_results(
            "SELECT rating, COUNT(*) as cnt {$base_rated} GROUP BY rating ORDER BY rating DESC",
            ARRAY_A
        );
        $distribution = array_fill(1, 5, 0);
        foreach ($dist_rows as $row) {
            if ($row['rating'] >= 1 && $row['rating'] <= 5) {
                $distribution[(int) $row['rating']] = (int) $row['cnt'];
            }
        }

        // Moyennes mensuelles (jusqu'à 36 mois)
        $monthly = $wpdb->get_results(
            "SELECT DATE_FORMAT(review_date, '%Y-%m') as month,
                    COUNT(*) as count,
                    ROUND(AVG(rating), 2) as avg_rating
             {$base_rated}
             AND review_date IS NOT NULL
             GROUP BY DATE_FORMAT(review_date, '%Y-%m') ORDER BY month ASC
             LIMIT 36",
            ARRAY_A
        );

        // Total sans filtre rating
        $base_all = $params_all
            ? $wpdb->prepare("FROM {$this->table} WHERE {$where_all_sql}", ...$params_all)
            : "FROM {$this->table} WHERE {$where_all_sql}";
        $total_all = (int) $wpdb->get_var("SELECT COUNT(*) {$base_all}");

        // Produits distincts (pour le filtre UI — non filtré par product)
        $products = $wpdb->get_col(
            "SELECT DISTINCT product_name FROM {$this->table}
             WHERE product_name != '' ORDER BY product_name"
        ) ?: [];

        $avg         = $global['avg_rating'] ? round((float) $global['avg_rating'], 2) : null;
        $total_rated = (int) ($global['total'] ?? 0);

        // Projection : nombre d'avis 5★ supplémentaires pour atteindre 4.8
        // Formule : chaque nouvel avis 5★ contribue (5 - avg_cible) au numérateur
        // n = (target * total - current_sum) / (5 - target)
        $reviews_needed_4_8 = null;
        if ($avg !== null && $avg < 4.8 && $total_rated > 0) {
            $current_sum        = $avg * $total_rated;
            $reviews_needed_4_8 = (int) ceil((4.8 * $total_rated - $current_sum) / (5 - 4.8));
        }

        // Par jour de la semaine (0=Dimanche … 6=Samedi)
        $by_weekday = $wpdb->get_results(
            "SELECT DAYOFWEEK(review_date) - 1 as weekday,
                    COUNT(*) as count,
                    ROUND(AVG(rating), 2) as avg_rating
             {$base_rated}
             AND review_date IS NOT NULL
             GROUP BY DAYOFWEEK(review_date) ORDER BY weekday ASC",
            ARRAY_A
        );

        // Par produit (top 15, triés par nombre d'avis)
        $by_product = $wpdb->get_results(
            "SELECT product_name,
                    COUNT(*) as count,
                    ROUND(AVG(rating), 2) as avg_rating
             {$base_rated}
             AND product_name != '' AND product_name IS NOT NULL
             GROUP BY product_name
             ORDER BY count DESC
             LIMIT 15",
            ARRAY_A
        );

        // Lead time : délai entre event_date et review_date (en jours)
        $lead_time_row = $wpdb->get_row(
            "SELECT ROUND(AVG(DATEDIFF(review_date, event_date)), 1) as avg_days
             {$base_rated}
             AND event_date IS NOT NULL
             AND review_date IS NOT NULL
             AND review_date >= event_date",
            ARRAY_A
        );

        // Buckets lead time
        $lead_time_buckets_raw = $wpdb->get_results(
            "SELECT
               CASE
                 WHEN DATEDIFF(review_date, event_date) < 7    THEN '<7j'
                 WHEN DATEDIFF(review_date, event_date) < 30   THEN '7-30j'
                 WHEN DATEDIFF(review_date, event_date) < 90   THEN '30-90j'
                 WHEN DATEDIFF(review_date, event_date) < 180  THEN '90-180j'
                 ELSE '>180j'
               END as bucket,
               COUNT(*) as count
             {$base_rated}
             AND event_date IS NOT NULL
             AND review_date IS NOT NULL
             AND review_date >= event_date
             GROUP BY 1",
            ARRAY_A
        );
        // Ordonner les buckets
        $bucket_order = ['<7j', '7-30j', '30-90j', '90-180j', '>180j'];
        $buckets_indexed = array_column($lead_time_buckets_raw ?? [], 'count', 'bucket');
        $lead_time_buckets = array_map(
            fn($b) => ['bucket' => $b, 'count' => (int) ($buckets_indexed[$b] ?? 0)],
            $bucket_order
        );

        return [
            'total'              => $total_all,
            'total_rated'        => $total_rated,
            'avg_rating'         => $avg,
            'min_date'           => $global['min_date'] ?? null,
            'max_date'           => $global['max_date'] ?? null,
            'distribution'       => $distribution,
            'monthly'            => $monthly,
            'products'           => $products,
            'reviews_needed_4_8' => $reviews_needed_4_8,
            'by_weekday'         => $by_weekday,
            'by_product'         => $by_product,
            'avg_lead_time_days' => $lead_time_row['avg_days'] ?? null,
            'lead_time_buckets'  => $lead_time_buckets,
        ];
    }

    /**
     * Retourne les avis d'un client donné par email (pour le drawer client).
     *
     * @return array  Tableau d'avis triés par review_date DESC
     */
    public function get_by_email(string $email, int $limit = 10): array {
        global $wpdb;
        $email = sanitize_email($email);
        if (!$email) return [];

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, order_number, product_name, rating, review_title, review_body, review_date, response
             FROM {$this->table}
             WHERE customer_email = %s
             ORDER BY review_date DESC
             LIMIT %d",
            $email,
            $limit
        ), ARRAY_A) ?: [];
    }

    /** Vide la table bt_reviews. */
    public function truncate(): void {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
        $wpdb->query("TRUNCATE TABLE {$this->table}");
    }

    /** Compte le nombre total d'avis en base. */
    public function count_all(): int {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Construit les clauses WHERE + paramètres pour les filtres communs.
     *
     * @return array{ list<string>, list<mixed> }  [$where, $params]
     */
    private function build_filters(
        string  $search,
        string  $product,
        ?int    $rating,
        string  $from,
        string  $to
    ): array {
        global $wpdb;

        $where  = ['1=1'];
        $params = [];

        if ($search) {
            $like     = '%' . $wpdb->esc_like($search) . '%';
            $where[]  = '(customer_name LIKE %s OR customer_email LIKE %s OR review_title LIKE %s OR order_number LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($product) {
            $where[]  = 'product_name = %s';
            $params[] = $product;
        }

        if ($rating !== null) {
            $where[]  = 'rating = %d';
            $params[] = $rating;
        }

        if ($from) {
            $where[]  = 'review_date >= %s';
            $params[] = $from;
        }

        if ($to) {
            $where[]  = 'review_date <= %s';
            $params[] = $to;
        }

        return [$where, $params];
    }

    /**
     * Normalise une chaîne date en YYYY-MM-DD (ou null si invalide).
     * Gère : YYYY-MM-DD, DD/MM/YYYY, "01 juin 2026".
     */
    private function parse_date(string $raw): ?string {
        $raw = trim($raw);
        if (!$raw) return null;

        // ISO déjà
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) return $raw;

        // DD/MM/YYYY ou D/M/YYYY
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }

        // "1 juin 2026" — mois français
        static $months = [
            'janvier' => '01', 'février' => '02', 'fevrier' => '02', 'mars' => '03',
            'avril'   => '04', 'mai'     => '05', 'juin'    => '06', 'juillet' => '07',
            'août'    => '08', 'aout'    => '08', 'septembre' => '09', 'octobre' => '10',
            'novembre'=> '11', 'décembre'=> '12', 'decembre'=> '12',
        ];
        if (preg_match('/(\d{1,2})\s+(\S+)\s+(\d{4})/i', $raw, $m)) {
            $month = $months[strtolower($m[2])] ?? null;
            if ($month) return sprintf('%04d-%s-%02d', (int) $m[3], $month, (int) $m[1]);
        }

        return null;
    }
}
