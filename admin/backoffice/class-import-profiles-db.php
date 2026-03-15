<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Couche base de données pour les profils de mapping d'import CSV.
 *
 * Table : {prefix}bt_import_profiles
 * Stocke les correspondances colonnes CSV → champs BDD réutilisables.
 */
class ImportProfilesDb {

    private string $table;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'bt_import_profiles';
    }

    /**
     * Crée ou met à jour la table via dbDelta (idempotent).
     */
    public function ensure_table(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table   = $this->table;

        $sql = "CREATE TABLE {$table} (
            id          bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name        varchar(255)        NOT NULL,
            import_type varchar(50)         NOT NULL,
            mapping     longtext            NOT NULL,
            created_at  datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at  datetime            NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY name_type (name, import_type)
        ) ENGINE=InnoDB {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Liste les profils pour un type d'import donné.
     *
     * @param string $type  'reservations' | 'participations' | 'reviews'
     * @return array
     */
    public function list(string $type): array {
        global $wpdb;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, name, import_type, mapping, created_at, updated_at
                 FROM {$this->table}
                 WHERE import_type = %s
                 ORDER BY name ASC",
                $type
            ),
            ARRAY_A
        );

        return array_map(function ($row) {
            $row['id']      = (int) $row['id'];
            $row['mapping'] = json_decode($row['mapping'], true) ?: [];
            return $row;
        }, $rows ?: []);
    }

    /**
     * Crée ou met à jour un profil de mapping.
     *
     * @param string $name        Nom du profil
     * @param string $import_type Type d'import
     * @param array  $mapping     Mapping { csvHeader: fieldKey }
     * @return array  Le profil créé/mis à jour
     */
    public function save(string $name, string $import_type, array $mapping): array {
        global $wpdb;

        $name        = sanitize_text_field(trim($name));
        $import_type = sanitize_text_field(trim($import_type));
        $mapping_json = wp_json_encode($mapping);

        if ($name === '' || $import_type === '') {
            return ['error' => 'Nom et type requis.'];
        }

        // Upsert : INSERT ... ON DUPLICATE KEY UPDATE
        $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$this->table} (name, import_type, mapping, created_at, updated_at)
                 VALUES (%s, %s, %s, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE mapping = VALUES(mapping), updated_at = NOW()",
                $name,
                $import_type,
                $mapping_json
            )
        );

        if ($wpdb->last_error) {
            return ['error' => $wpdb->last_error];
        }

        $id = $wpdb->insert_id ?: $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->table} WHERE name = %s AND import_type = %s",
                $name,
                $import_type
            )
        );

        return [
            'id'          => (int) $id,
            'name'        => $name,
            'import_type' => $import_type,
            'mapping'     => $mapping,
        ];
    }

    /**
     * Supprime un profil par son ID.
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool {
        global $wpdb;

        $affected = $wpdb->delete($this->table, ['id' => $id], ['%d']);
        return $affected > 0;
    }
}
