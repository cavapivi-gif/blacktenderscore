<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Couche base de données pour les réservations GYG (GetYourGuide).
 *
 * Table : {prefix}bt_gyg_bookings
 * Clé unique principale : gyg_booking_id (après confirmation)
 * Clé unique secondaire : gyg_reservation_id (dès la réservation temporaire)
 *
 * Le cycle de vie d'une réservation GYG suit :
 *   reserve (gyg_reservation_id) → book (gyg_booking_id) → redeem|cancel
 */
class GygDb {

    // ─── Schéma ───────────────────────────────────────────────────────────────

    /**
     * Crée (ou met à jour via dbDelta) la table bt_gyg_bookings.
     * Idempotente — peut être appelée à chaque activation.
     */
    public static function create_table(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bt_gyg_bookings (
            id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            gyg_booking_id     VARCHAR(100) DEFAULT NULL,
            gyg_reservation_id VARCHAR(100) DEFAULT NULL,
            option_id          VARCHAR(100) NOT NULL DEFAULT '',
            product_id         VARCHAR(100) NOT NULL DEFAULT '',
            start_datetime     DATETIME DEFAULT NULL,
            status             ENUM('reserved','confirmed','cancelled','redeemed') NOT NULL DEFAULT 'reserved',
            pricing_categories LONGTEXT DEFAULT NULL,
            vacancies          INT DEFAULT NULL,
            customer_name      VARCHAR(255) DEFAULT NULL,
            customer_email     VARCHAR(255) DEFAULT NULL,
            raw_payload        LONGTEXT DEFAULT NULL,
            created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY gyg_booking_id (gyg_booking_id),
            UNIQUE KEY gyg_reservation_id (gyg_reservation_id),
            KEY idx_product_id (product_id),
            KEY idx_status (status),
            KEY idx_start_datetime (start_datetime)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    /**
     * Insère ou met à jour une réservation GYG.
     *
     * L'upsert se base sur gyg_reservation_id si présent,
     * sinon sur gyg_booking_id. Les deux peuvent être NULL initialement
     * (cas rare) — l'auto-increment id est alors la clé.
     *
     * @param array $data Données normalisées de la réservation
     * @return int|\WP_Error ID de la ligne insérée/mise à jour, ou WP_Error
     */
    public static function upsert(array $data): int|\WP_Error {
        global $wpdb;

        $table = $wpdb->prefix . 'bt_gyg_bookings';
        $now   = current_time('mysql', true);

        // Normalisation des champs
        $row = [
            'gyg_booking_id'     => isset($data['gyg_booking_id'])     ? sanitize_text_field($data['gyg_booking_id'])     : null,
            'gyg_reservation_id' => isset($data['gyg_reservation_id']) ? sanitize_text_field($data['gyg_reservation_id']) : null,
            'option_id'          => sanitize_text_field($data['option_id']  ?? ''),
            'product_id'         => sanitize_text_field($data['product_id'] ?? ''),
            'start_datetime'     => isset($data['start_datetime'])      ? sanitize_text_field($data['start_datetime'])     : null,
            'status'             => in_array($data['status'] ?? '', ['reserved', 'confirmed', 'cancelled', 'redeemed'], true)
                                        ? $data['status'] : 'reserved',
            'pricing_categories' => isset($data['pricing_categories']) ? wp_json_encode($data['pricing_categories']) : null,
            'vacancies'          => isset($data['vacancies']) ? (int) $data['vacancies'] : null,
            'customer_name'      => isset($data['customer_name'])  ? sanitize_text_field($data['customer_name'])  : null,
            'customer_email'     => isset($data['customer_email']) ? sanitize_email($data['customer_email'])       : null,
            'raw_payload'        => isset($data['raw_payload'])    ? wp_json_encode($data['raw_payload'])          : null,
        ];

        // Formats pour $wpdb->prepare
        $formats = ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s'];

        // Vérifier si une ligne avec gyg_reservation_id existe déjà (pour UPDATE)
        $existing_id = null;
        if (!empty($row['gyg_reservation_id'])) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE gyg_reservation_id = %s LIMIT 1",
                    $row['gyg_reservation_id']
                )
            );
        }

        if (!$existing_id && !empty($row['gyg_booking_id'])) {
            $existing_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM `{$table}` WHERE gyg_booking_id = %s LIMIT 1",
                    $row['gyg_booking_id']
                )
            );
        }

        if ($existing_id) {
            // UPDATE
            $row['updated_at'] = $now;
            $result = $wpdb->update($table, $row, ['id' => (int) $existing_id]);
            if ($result === false) {
                return new \WP_Error('gyg_db_error', $wpdb->last_error);
            }
            return (int) $existing_id;
        }

        // INSERT
        $row['created_at'] = $now;
        $row['updated_at'] = $now;
        $result = $wpdb->insert($table, $row);

        if ($result === false) {
            return new \WP_Error('gyg_db_error', $wpdb->last_error);
        }

        return (int) $wpdb->insert_id;
    }

    // ─── Lecture ──────────────────────────────────────────────────────────────

    /**
     * Cherche une réservation par gyg_booking_id.
     *
     * @param string $gyg_booking_id
     * @return object|null Ligne DB ou null si introuvable
     */
    public static function get_by_gyg_id(string $gyg_booking_id): ?object {
        global $wpdb;
        $table = $wpdb->prefix . 'bt_gyg_bookings';
        $row   = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE gyg_booking_id = %s LIMIT 1", $gyg_booking_id)
        );
        return $row ?: null;
    }

    /**
     * Cherche une réservation par gyg_reservation_id.
     *
     * @param string $gyg_reservation_id
     * @return object|null Ligne DB ou null si introuvable
     */
    public static function get_by_reservation_id(string $gyg_reservation_id): ?object {
        global $wpdb;
        $table = $wpdb->prefix . 'bt_gyg_bookings';
        $row   = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM `{$table}` WHERE gyg_reservation_id = %s LIMIT 1", $gyg_reservation_id)
        );
        return $row ?: null;
    }

    /**
     * Retourne une liste paginée des réservations GYG.
     *
     * @param array $args Clés supportées : page, per_page, status, search, from, to
     * @return array { data: object[], total: int }
     */
    public static function query(array $args = []): array {
        global $wpdb;

        $table    = $wpdb->prefix . 'bt_gyg_bookings';
        $page     = max(1, (int) ($args['page']     ?? 1));
        $per_page = min(200, max(1, (int) ($args['per_page'] ?? 50)));
        $offset   = ($page - 1) * $per_page;

        $where  = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['from'])) {
            $where[]  = 'DATE(start_datetime) >= %s';
            $values[] = $args['from'];
        }

        if (!empty($args['to'])) {
            $where[]  = 'DATE(start_datetime) <= %s';
            $values[] = $args['to'];
        }

        if (!empty($args['search'])) {
            $like     = '%' . $wpdb->esc_like(trim($args['search'])) . '%';
            $where[]  = '(gyg_booking_id LIKE %s OR gyg_reservation_id LIKE %s OR customer_name LIKE %s OR customer_email LIKE %s OR product_id LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $where_sql = implode(' AND ', $where);

        $total = (int) $wpdb->get_var(
            $values
                ? $wpdb->prepare("SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}", ...$values)
                : "SELECT COUNT(*) FROM `{$table}` WHERE {$where_sql}"
        );

        $rows = $wpdb->get_results(
            $values
                ? $wpdb->prepare(
                    "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                    ...[...$values, $per_page, $offset]
                )
                : $wpdb->prepare(
                    "SELECT * FROM `{$table}` WHERE {$where_sql} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                )
        );

        return ['data' => $rows ?: [], 'total' => $total];
    }

    /**
     * Met à jour le statut d'une réservation GYG.
     *
     * @param string $id_type   Type d'identifiant : 'gyg_booking_id' | 'gyg_reservation_id' | 'id'
     * @param string $id_value  Valeur de l'identifiant
     * @param string $status    Nouveau statut ('reserved'|'confirmed'|'cancelled'|'redeemed')
     * @return bool true si au moins une ligne a été mise à jour
     */
    public static function update_status(string $id_type, string $id_value, string $status): bool {
        global $wpdb;

        $allowed_id_types = ['gyg_booking_id', 'gyg_reservation_id', 'id'];
        if (!in_array($id_type, $allowed_id_types, true)) {
            return false;
        }

        $allowed_statuses = ['reserved', 'confirmed', 'cancelled', 'redeemed'];
        if (!in_array($status, $allowed_statuses, true)) {
            return false;
        }

        $table = $wpdb->prefix . 'bt_gyg_bookings';

        // Le nom de colonne est contrôlé par liste blanche — safe pour interpolation
        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE `{$table}` SET status = %s, updated_at = %s WHERE `{$id_type}` = %s",
                $status,
                current_time('mysql', true),
                $id_value
            )
        );

        return $result !== false && $result > 0;
    }

    /**
     * Retourne des statistiques agrégées : total, aujourd'hui, par statut.
     *
     * @return array { total: int, today: int, by_status: array }
     */
    public static function get_stats(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'bt_gyg_bookings';
        $today = current_time('Y-m-d');

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");

        $today_count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM `{$table}` WHERE DATE(created_at) = %s",
                $today
            )
        );

        $by_status_rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS cnt FROM `{$table}` GROUP BY status",
            ARRAY_A
        );

        $by_status = [];
        foreach ($by_status_rows ?: [] as $row) {
            $by_status[$row['status']] = (int) $row['cnt'];
        }

        return [
            'total'     => $total,
            'today'     => $today_count,
            'by_status' => $by_status,
        ];
    }

    /**
     * Vide la table bt_gyg_bookings.
     */
    public static function truncate(): void {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE `{$wpdb->prefix}bt_gyg_bookings`");
    }
}
