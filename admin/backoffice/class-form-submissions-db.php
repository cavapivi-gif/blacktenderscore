<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * DB layer pour les soumissions de formulaires (devis, contact, etc.).
 * Table : {prefix}bt_form_submissions
 */
class FormSubmissionsDb {

    private string $table;
    private static bool $table_checked = false;

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'bt_form_submissions';

        // Lazy ensure — cree la table au premier usage si absente.
        if (!self::$table_checked) {
            self::$table_checked = true;
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $this->table)) !== $this->table) {
                $this->ensure_tables();
            }
        }
    }

    // -- Schema ---------------------------------------------------------------

    /** Cree la table (idempotent via dbDelta). */
    public function ensure_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$this->table} (
            id             bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            form_type      varchar(50)  NOT NULL DEFAULT 'quote',
            client_name    varchar(255) NOT NULL DEFAULT '',
            client_firstname varchar(255) NOT NULL DEFAULT '',
            client_email   varchar(255) NOT NULL DEFAULT '',
            client_phone   varchar(50)  NOT NULL DEFAULT '',
            excursion_id   bigint UNSIGNED DEFAULT NULL,
            excursion_name varchar(255) NOT NULL DEFAULT '',
            boat_id        bigint UNSIGNED DEFAULT NULL,
            boat_name      varchar(255) NOT NULL DEFAULT '',
            duration_type  varchar(50)  NOT NULL DEFAULT '',
            date_start     varchar(50)  NOT NULL DEFAULT '',
            date_end       varchar(50)  NOT NULL DEFAULT '',
            message        text         DEFAULT NULL,
            email_sent     tinyint(1)   NOT NULL DEFAULT 0,
            email_error    text         DEFAULT NULL,
            utm_source     varchar(255) NOT NULL DEFAULT '',
            utm_medium     varchar(255) NOT NULL DEFAULT '',
            utm_campaign   varchar(255) NOT NULL DEFAULT '',
            referrer       text         DEFAULT NULL,
            page_url       text         DEFAULT NULL,
            ip_address     varchar(45)  NOT NULL DEFAULT '',
            created_at     datetime     NOT NULL,
            PRIMARY KEY  (id),
            KEY client_email (client_email),
            KEY form_type (form_type),
            KEY created_at (created_at)
        ) ENGINE=InnoDB {$charset};");
    }

    // -- CRUD -----------------------------------------------------------------

    /**
     * Insere une soumission de formulaire.
     *
     * @param  array     $data Donnees du formulaire.
     * @return int|false ID de la row inseree ou false en cas d'erreur.
     */
    public function insert(array $data): int|false {
        global $wpdb;

        $row = [
            'form_type'        => sanitize_text_field($data['form_type'] ?? 'quote'),
            'client_name'      => sanitize_text_field($data['client_name'] ?? ''),
            'client_firstname' => sanitize_text_field($data['client_firstname'] ?? ''),
            'client_email'     => sanitize_email($data['client_email'] ?? ''),
            'client_phone'     => sanitize_text_field($data['client_phone'] ?? ''),
            'excursion_id'     => isset($data['excursion_id']) ? absint($data['excursion_id']) : null,
            'excursion_name'   => sanitize_text_field($data['excursion_name'] ?? ''),
            'boat_id'          => isset($data['boat_id']) ? absint($data['boat_id']) : null,
            'boat_name'        => sanitize_text_field($data['boat_name'] ?? ''),
            'duration_type'    => sanitize_text_field($data['duration_type'] ?? ''),
            'date_start'       => sanitize_text_field($data['date_start'] ?? ''),
            'date_end'         => sanitize_text_field($data['date_end'] ?? ''),
            'message'          => isset($data['message']) ? sanitize_textarea_field($data['message']) : null,
            'email_sent'       => !empty($data['email_sent']) ? 1 : 0,
            'email_error'      => isset($data['email_error']) ? sanitize_textarea_field($data['email_error']) : null,
            'utm_source'       => sanitize_text_field($data['utm_source'] ?? ''),
            'utm_medium'       => sanitize_text_field($data['utm_medium'] ?? ''),
            'utm_campaign'     => sanitize_text_field($data['utm_campaign'] ?? ''),
            'referrer'         => isset($data['referrer']) ? esc_url_raw($data['referrer']) : null,
            'page_url'         => isset($data['page_url']) ? esc_url_raw($data['page_url']) : null,
            'ip_address'       => sanitize_text_field($data['ip_address'] ?? ''),
            'created_at'       => $data['created_at'] ?? current_time('mysql'),
        ];

        $result = $wpdb->insert($this->table, $row);

        return $result !== false ? (int) $wpdb->insert_id : false;
    }

    /**
     * Met a jour le statut d'envoi d'email pour une soumission.
     *
     * @param int    $id    ID de la soumission.
     * @param bool   $sent  True si l'email a ete envoye avec succes.
     * @param string $error Message d'erreur eventuel.
     */
    public function update_email_status(int $id, bool $sent, string $error = ''): void {
        global $wpdb;

        $wpdb->update(
            $this->table,
            [
                'email_sent'  => $sent ? 1 : 0,
                'email_error' => $error !== '' ? sanitize_textarea_field($error) : null,
            ],
            ['id' => $id],
            ['%d', '%s'],
            ['%d']
        );
    }

    /**
     * Retourne les soumissions avec pagination, recherche et filtres.
     *
     * @param  array $args {
     *     @type int    $page       Page courante (defaut 1).
     *     @type int    $per_page   Resultats par page (defaut 50).
     *     @type string $search     Recherche sur nom, email, telephone.
     *     @type string $form_type  Filtre par type de formulaire.
     *     @type string $email_sent Filtre envoi email : 'all', 'yes', 'no'.
     *     @type string $sort_key   Colonne de tri.
     *     @type string $sort_dir   Direction : 'ASC' ou 'DESC'.
     * }
     * @return array{data: array, total: int}
     */
    public function get_all(array $args = []): array {
        global $wpdb;

        $page     = max(1, (int) ($args['page'] ?? 1));
        $per_page = max(1, min(200, (int) ($args['per_page'] ?? 50)));
        $offset   = ($page - 1) * $per_page;

        $where  = [];
        $values = [];

        // Recherche texte libre
        if (!empty($args['search'])) {
            $like     = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where[]  = '(client_name LIKE %s OR client_firstname LIKE %s OR client_email LIKE %s OR client_phone LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        // Filtre form_type
        if (!empty($args['form_type'])) {
            $where[]  = 'form_type = %s';
            $values[] = sanitize_text_field($args['form_type']);
        }

        // Filtre email_sent
        $email_filter = $args['email_sent'] ?? 'all';
        if ($email_filter === 'yes') {
            $where[] = 'email_sent = 1';
        } elseif ($email_filter === 'no') {
            $where[] = 'email_sent = 0';
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Tri securise
        $allowed_sort = ['id', 'form_type', 'client_name', 'client_email', 'excursion_name', 'boat_name', 'email_sent', 'created_at'];
        $sort_key     = in_array($args['sort_key'] ?? '', $allowed_sort, true) ? $args['sort_key'] : 'created_at';
        $sort_dir     = strtoupper($args['sort_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // Total
        $count_sql = "SELECT COUNT(*) FROM {$this->table} {$where_sql}";
        $total     = $values
            ? (int) $wpdb->get_var($wpdb->prepare($count_sql, ...$values))
            : (int) $wpdb->get_var($count_sql);

        // Resultats
        $query = "SELECT * FROM {$this->table} {$where_sql} ORDER BY {$sort_key} {$sort_dir} LIMIT %d OFFSET %d";
        $values[] = $per_page;
        $values[] = $offset;

        $data = $wpdb->get_results($wpdb->prepare($query, ...$values), ARRAY_A);

        return [
            'data'  => $data ?: [],
            'total' => $total,
        ];
    }

    /**
     * Retourne une soumission par ID.
     *
     * @param  int          $id ID de la soumission.
     * @return object|null  Row ou null si introuvable.
     */
    public function get_by_id(int $id): ?object {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id)
        );
    }

    /**
     * Supprime une soumission par ID.
     *
     * @param  int  $id ID de la soumission.
     * @return bool True si supprimee, false sinon.
     */
    public function delete(int $id): bool {
        global $wpdb;

        return $wpdb->delete($this->table, ['id' => $id], ['%d']) !== false;
    }

    // -- Stats ----------------------------------------------------------------

    /**
     * Retourne les statistiques globales des soumissions.
     *
     * @return array{total: int, sent: int, failed: int, today: int}
     */
    public function get_stats(): array {
        global $wpdb;

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        $sent = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE email_sent = 1"
        );

        $failed = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->table} WHERE email_sent = 0 AND email_error IS NOT NULL AND email_error != ''"
        );

        $today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE created_at >= %s",
            current_time('Y-m-d') . ' 00:00:00'
        ));

        return [
            'total'  => $total,
            'sent'   => $sent,
            'failed' => $failed,
            'today'  => $today,
        ];
    }
}
