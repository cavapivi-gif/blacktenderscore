<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Couche base de données pour les logs d'interactions GYG.
 *
 * Table : {prefix}bt_gyg_logs
 * Enregistre toutes les requêtes entrantes et sortantes liées à GYG
 * pour faciliter le debug et l'audit.
 */
class GygLogsDb {

    // ─── Schéma ───────────────────────────────────────────────────────────────

    /**
     * Crée (ou met à jour via dbDelta) la table bt_gyg_logs.
     * Idempotente — peut être appelée à chaque activation.
     */
    public static function create_table(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}bt_gyg_logs (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            direction   ENUM('inbound', 'outbound') NOT NULL,
            endpoint    VARCHAR(255) NOT NULL DEFAULT '',
            method      VARCHAR(10) NOT NULL DEFAULT 'GET',
            status_code INT DEFAULT NULL,
            payload     LONGTEXT DEFAULT NULL,
            response    LONGTEXT DEFAULT NULL,
            error       TEXT DEFAULT NULL,
            created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY direction (direction),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    /**
     * Insère une entrée de log GYG.
     *
     * @param string      $direction   'inbound' ou 'outbound'
     * @param string      $endpoint    URL ou chemin de l'endpoint
     * @param string      $method      Méthode HTTP
     * @param int         $status_code Code HTTP de la réponse
     * @param string|null $payload     Corps de la requête (JSON)
     * @param string|null $response    Corps de la réponse (JSON)
     * @param string|null $error       Message d'erreur si applicable
     */
    public static function log(
        string $direction,
        string $endpoint,
        string $method,
        int $status_code,
        ?string $payload,
        ?string $response,
        ?string $error
    ): void {
        global $wpdb;

        $allowed_directions = ['inbound', 'outbound'];
        if (!in_array($direction, $allowed_directions, true)) {
            $direction = 'inbound';
        }

        $wpdb->insert(
            $wpdb->prefix . 'bt_gyg_logs',
            [
                'direction'   => $direction,
                'endpoint'    => substr(sanitize_text_field($endpoint), 0, 255),
                'method'      => strtoupper(substr(sanitize_text_field($method), 0, 10)),
                'status_code' => $status_code,
                'payload'     => $payload,
                'response'    => $response,
                'error'       => $error ? substr($error, 0, 65535) : null,
                'created_at'  => current_time('mysql', true),
            ],
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }

    // ─── Lecture ──────────────────────────────────────────────────────────────

    /**
     * Retourne une liste paginée de logs GYG.
     *
     * @param array $args Clés supportées : page, per_page, direction, from, to
     * @return array { data: object[], total: int }
     */
    public static function query(array $args = []): array {
        global $wpdb;

        $table    = $wpdb->prefix . 'bt_gyg_logs';
        $page     = max(1, (int) ($args['page']     ?? 1));
        $per_page = min(200, max(1, (int) ($args['per_page'] ?? 50)));
        $offset   = ($page - 1) * $per_page;

        $where  = ['1=1'];
        $values = [];

        if (!empty($args['direction']) && in_array($args['direction'], ['inbound', 'outbound'], true)) {
            $where[]  = 'direction = %s';
            $values[] = $args['direction'];
        }

        if (!empty($args['from'])) {
            $where[]  = 'DATE(created_at) >= %s';
            $values[] = $args['from'];
        }

        if (!empty($args['to'])) {
            $where[]  = 'DATE(created_at) <= %s';
            $values[] = $args['to'];
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
                    "SELECT id, direction, endpoint, method, status_code, error, created_at
                     FROM `{$table}` WHERE {$where_sql} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                    ...[...$values, $per_page, $offset]
                )
                : $wpdb->prepare(
                    "SELECT id, direction, endpoint, method, status_code, error, created_at
                     FROM `{$table}` WHERE {$where_sql} ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d",
                    $per_page,
                    $offset
                )
        );

        return ['data' => $rows ?: [], 'total' => $total];
    }

    /**
     * Retourne des statistiques agrégées sur les logs.
     *
     * @return array { total: int, inbound: int, outbound: int, errors: int }
     */
    public static function get_stats(): array {
        global $wpdb;

        $table = $wpdb->prefix . 'bt_gyg_logs';

        $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}`");
        $inbound  = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE direction = 'inbound'");
        $outbound = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE direction = 'outbound'");
        $errors   = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$table}` WHERE error IS NOT NULL OR (status_code IS NOT NULL AND status_code >= 400)");

        return [
            'total'    => $total,
            'inbound'  => $inbound,
            'outbound' => $outbound,
            'errors'   => $errors,
        ];
    }
}
