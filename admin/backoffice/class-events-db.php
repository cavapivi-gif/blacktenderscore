<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Gestionnaire de la table wp_bt_events.
 * Stocke les événements touristiques générés par IA ou saisis manuellement.
 */
class EventsDb {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'bt_events';
    }

    /**
     * Crée la table si elle n'existe pas encore (idempotente).
     * Appelée lors du setup onboarding.
     */
    public function ensure_table(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        varchar(255) NOT NULL,
            date_start  date NOT NULL,
            date_end    date NOT NULL,
            location    varchar(255) DEFAULT NULL,
            source      varchar(20) DEFAULT 'ai',
            imported_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY idx_dates (date_start, date_end)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Insère ou ignore les événements (pas d'écrasement sur doublon).
     * Dédoublonnage sur (name, date_start).
     * @param array $items Liste d'événements [{name, date_start, date_end, location, source}]
     * @return int Nombre de lignes insérées
     */
    public function upsert(array $items): int {
        global $wpdb;

        if (empty($items)) return 0;

        $inserted = 0;
        $now      = current_time('mysql');

        foreach ($items as $item) {
            if (empty($item['name']) || empty($item['date_start'])) continue;

            // @phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $result = $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO {$this->table}
                     (name, date_start, date_end, location, source, imported_at)
                     VALUES (%s, %s, %s, %s, %s, %s)",
                    $item['name'],
                    $item['date_start'],
                    $item['date_end'] ?? $item['date_start'],
                    $item['location'] ?? '',
                    $item['source'] ?? 'ai',
                    $now
                )
            );

            if ($result) $inserted++;
        }

        return $inserted;
    }

    /**
     * Retourne les événements dont la plage de dates chevauche [$from, $to].
     * @param string $from Date de début (Y-m-d)
     * @param string $to   Date de fin (Y-m-d)
     * @return array Liste d'événements
     */
    public function query(string $from, string $to): array {
        global $wpdb;

        // @phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, date_start, date_end, location, source, imported_at
                 FROM {$this->table}
                 WHERE date_start <= %s AND date_end >= %s
                 ORDER BY date_start ASC",
                $to,
                $from
            ),
            ARRAY_A
        );

        return $rows ?: [];
    }

    /**
     * Vide entièrement la table (pour reset).
     */
    public function truncate(): void {
        global $wpdb;
        // @phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query("TRUNCATE TABLE {$this->table}");
    }
}
