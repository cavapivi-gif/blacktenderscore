<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * DB layer pour les conversations IA partagées.
 * Tables : wp_bt_chats, wp_bt_chat_messages, wp_bt_chat_shares
 */
class ChatDb {

    /** Couleurs auto-assignées aux participants (index cyclique selon nb de partages) */
    private const PALETTE = ['#dbeafe','#dcfce7','#fef3c7','#fce7f3','#ede9fe','#ffedd5','#cffafe','#f1f5f9'];

    private string $chats;
    private string $messages;
    private string $shares;

    public function __construct() {
        global $wpdb;
        $this->chats    = $wpdb->prefix . 'bt_chats';
        $this->messages = $wpdb->prefix . 'bt_chat_messages';
        $this->shares   = $wpdb->prefix . 'bt_chat_shares';
    }

    // ── Schéma ────────────────────────────────────────────────────────────────

    /** Crée les 3 tables (idempotent via dbDelta). */
    public function ensure_tables(): void {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE {$this->chats} (
            id           bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid         varchar(40)  NOT NULL,
            title        varchar(255) NOT NULL DEFAULT 'Nouvelle conversation',
            provider     varchar(50)  NOT NULL DEFAULT 'anthropic',
            owner_id     bigint UNSIGNED NOT NULL,
            filter_params text         DEFAULT NULL,
            owner_color  varchar(7)   NOT NULL DEFAULT '#f0f0ee',
            created_at   datetime     NOT NULL,
            updated_at   datetime     NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY owner_id (owner_id)
        ) ENGINE=InnoDB {$charset};");

        dbDelta("CREATE TABLE {$this->messages} (
            id         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id    bigint UNSIGNED NOT NULL,
            user_id    bigint UNSIGNED DEFAULT NULL,
            role       varchar(20) NOT NULL DEFAULT 'user',
            content    longtext    NOT NULL DEFAULT '',
            provider   varchar(50) DEFAULT NULL,
            created_at datetime    NOT NULL,
            PRIMARY KEY (id),
            KEY chat_id (chat_id),
            KEY chat_created (chat_id, created_at)
        ) ENGINE=InnoDB {$charset};");

        dbDelta("CREATE TABLE {$this->shares} (
            id         bigint UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id    bigint UNSIGNED NOT NULL,
            user_id    bigint UNSIGNED NOT NULL,
            permission varchar(10) NOT NULL DEFAULT 'read',
            color      varchar(7)  NOT NULL DEFAULT '#dbeafe',
            invited_by bigint UNSIGNED NOT NULL,
            created_at datetime    NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY chat_user (chat_id, user_id),
            KEY user_id (user_id)
        ) ENGINE=InnoDB {$charset};");
    }

    // ── Chats CRUD ────────────────────────────────────────────────────────────

    /**
     * Crée ou met à jour un chat (upsert par uuid).
     * @return int|false ID de la row ou false
     */
    public function upsert_chat(string $uuid, string $title, string $provider, int $owner_id, ?array $filter_params = null) {
        global $wpdb;
        $now = current_time('mysql', true);

        $existing = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->chats} WHERE uuid=%s", $uuid));

        if ($existing) {
            $wpdb->update($this->chats,
                ['title' => $title, 'provider' => $provider, 'updated_at' => $now, 'filter_params' => $filter_params ? json_encode($filter_params) : null],
                ['id' => $existing]
            );
            return (int) $existing;
        }

        $wpdb->insert($this->chats, [
            'uuid'         => $uuid,
            'title'        => $title,
            'provider'     => $provider,
            'owner_id'     => $owner_id,
            'filter_params'=> $filter_params ? json_encode($filter_params) : null,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);
        return $wpdb->insert_id ?: false;
    }

    /**
     * Retourne les chats accessibles par l'utilisateur (propriétaire + partagés).
     * Les administrateurs (manage_options) voient tous les chats.
     * @return array[]
     */
    public function list_for_user(int $user_id): array {
        global $wpdb;

        // Admin : accès à tous les chats sans filtre ownership
        if (user_can($user_id, 'manage_options')) {
            return $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, s.permission, s.color as share_color
                 FROM {$this->chats} c
                 LEFT JOIN {$this->shares} s ON s.chat_id = c.id AND s.user_id = %d
                 ORDER BY c.updated_at DESC",
                $user_id
            ), ARRAY_A);
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, s.permission, s.color as share_color
             FROM {$this->chats} c
             LEFT JOIN {$this->shares} s ON s.chat_id = c.id AND s.user_id = %d
             WHERE c.owner_id = %d OR s.user_id = %d
             ORDER BY c.updated_at DESC",
            $user_id, $user_id, $user_id
        ), ARRAY_A);
    }

    /**
     * Retourne un chat par uuid + vérifie l'accès de l'utilisateur.
     * @return array|null  ['chat' => ..., 'permission' => 'owner'|'read'|'write'|null]
     */
    public function get_by_uuid(string $uuid, int $user_id): ?array {
        global $wpdb;
        $chat = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->chats} WHERE uuid=%s", $uuid), ARRAY_A);
        if (!$chat) return null;

        // Owner du chat
        if ((int) $chat['owner_id'] === $user_id) {
            return ['chat' => $chat, 'permission' => 'owner'];
        }

        // Admin WP : accès owner sur tous les chats, même ceux qu'il n'a pas créés
        if (user_can($user_id, 'manage_options')) {
            return ['chat' => $chat, 'permission' => 'owner'];
        }

        // Utilisateur invité via partage
        $share = $wpdb->get_row($wpdb->prepare(
            "SELECT permission FROM {$this->shares} WHERE chat_id=%d AND user_id=%d",
            $chat['id'], $user_id
        ), ARRAY_A);

        if (!$share) return null; // pas d'accès

        // Vérifier que le rôle WP de l'invité lui donne toujours accès au chat
        if (!$this->role_has_chat_access($user_id)) return null;

        return ['chat' => $chat, 'permission' => $share['permission']];
    }

    /** Supprime un chat et toutes ses données associées (owner ou admin). */
    public function delete_chat(string $uuid, int $owner_id): bool {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT id,owner_id FROM {$this->chats} WHERE uuid=%s", $uuid), ARRAY_A);
        // Admin peut supprimer n'importe quel chat
        if (!$row) return false;
        if ((int) $row['owner_id'] !== $owner_id && !user_can($owner_id, 'manage_options')) return false;

        $wpdb->delete($this->messages, ['chat_id' => $row['id']]);
        $wpdb->delete($this->shares,   ['chat_id' => $row['id']]);
        $wpdb->delete($this->chats,    ['id'      => $row['id']]);
        return true;
    }

    /** Met à jour titre et/ou provider (owner ou admin). */
    public function update_chat(string $uuid, int $owner_id, array $data): bool {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT id,owner_id FROM {$this->chats} WHERE uuid=%s", $uuid), ARRAY_A);
        if (!$row) return false;
        // Admin peut modifier n'importe quel chat
        if ((int) $row['owner_id'] !== $owner_id && !user_can($owner_id, 'manage_options')) return false;

        $allowed = array_intersect_key($data, array_flip(['title', 'provider', 'owner_color']));
        if (empty($allowed)) return false;

        $allowed['updated_at'] = current_time('mysql', true);
        $wpdb->update($this->chats, $allowed, ['id' => $row['id']]);
        return true;
    }

    // ── Messages ──────────────────────────────────────────────────────────────

    /**
     * Synchronise les messages d'un chat depuis localStorage.
     * Append-only : ne supprime plus tous les messages existants.
     * Compare le nombre de messages en DB et n'insère que les nouveaux.
     */
    public function save_messages(int $chat_id, array $messages, int $owner_id): void {
        global $wpdb;
        $now = current_time('mysql', true);

        $existing_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->messages} WHERE chat_id=%d", $chat_id
        ));

        // Seuls les messages au-delà de ceux déjà en DB sont insérés
        $new_messages = array_slice($messages, $existing_count);

        foreach ($new_messages as $msg) {
            $role = in_array($msg['role'] ?? 'user', ['user','assistant'], true) ? $msg['role'] : 'user';
            $wpdb->insert($this->messages, [
                'chat_id'    => $chat_id,
                'user_id'    => ($role === 'user') ? ($msg['user_id'] ?? $owner_id) : null,
                'role'       => $role,
                'content'    => sanitize_textarea_field($msg['content'] ?? ''),
                'provider'   => sanitize_key($msg['provider'] ?? ''),
                'created_at' => $msg['created_at'] ?? $now,
            ]);
        }
    }

    /** Retourne les messages d'un chat. */
    public function get_messages(int $chat_id): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT id,user_id,role,content,provider,created_at FROM {$this->messages} WHERE chat_id=%d ORDER BY id ASC",
            $chat_id
        ), ARRAY_A);
    }

    // ── Partages ──────────────────────────────────────────────────────────────

    /**
     * Retourne les partages d'un chat avec les infos utilisateur.
     * @return array[]
     */
    public function get_shares(int $chat_id): array {
        global $wpdb;
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT s.user_id, s.permission, s.color, s.created_at,
                    u.display_name, u.user_email
             FROM {$this->shares} s
             JOIN {$wpdb->users} u ON u.ID = s.user_id
             WHERE s.chat_id = %d ORDER BY s.created_at ASC",
            $chat_id
        ), ARRAY_A);

        return array_map(fn($r) => array_merge($r, [
            'avatar' => get_avatar_url($r['user_id'], ['size' => 32]),
        ]), $rows);
    }

    /** Ajoute un partage. Couleur auto depuis la palette. */
    public function add_share(int $chat_id, int $user_id, string $permission, int $invited_by): bool {
        global $wpdb;
        $count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->shares} WHERE chat_id=%d", $chat_id));
        $color = self::PALETTE[$count % count(self::PALETTE)];

        $result = $wpdb->insert($this->shares, [
            'chat_id'    => $chat_id,
            'user_id'    => $user_id,
            'permission' => in_array($permission, ['read','write'], true) ? $permission : 'read',
            'color'      => $color,
            'invited_by' => $invited_by,
            'created_at' => current_time('mysql', true),
        ]);
        return $result !== false;
    }

    /** Met à jour permission et/ou couleur d'un partage. */
    public function update_share(int $chat_id, int $user_id, array $data): bool {
        global $wpdb;
        $allowed = [];
        if (isset($data['permission']) && in_array($data['permission'], ['read','write'], true)) $allowed['permission'] = $data['permission'];
        if (isset($data['color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'])) $allowed['color'] = $data['color'];
        if (empty($allowed)) return false;

        return $wpdb->update($this->shares, $allowed, ['chat_id' => $chat_id, 'user_id' => $user_id]) !== false;
    }

    /** Révoque l'accès d'un utilisateur. */
    public function remove_share(int $chat_id, int $user_id): bool {
        global $wpdb;
        return $wpdb->delete($this->shares, ['chat_id' => $chat_id, 'user_id' => $user_id]) !== false;
    }

    // ── Utilisateurs ──────────────────────────────────────────────────────────

    /**
     * Recherche des utilisateurs WP par nom ou email (pour l'invitation).
     * @param int[] $exclude IDs à exclure (owner + déjà invités)
     */
    public function search_users(string $q, array $exclude = []): array {
        $args = [
            'search'         => '*' . $q . '*',
            'search_columns' => ['user_login', 'user_email', 'display_name'],
            'number'         => 10,
            'exclude'        => $exclude,
            'fields'         => ['ID', 'display_name', 'user_email'],
        ];
        $users = get_users($args);

        return array_map(fn($u) => [
            'id'     => (int) $u->ID,
            'name'   => $u->display_name,
            'email'  => $u->user_email,
            'avatar' => get_avatar_url($u->ID, ['size' => 32]),
        ], $users);
    }

    // ── Permissions par rôle ──────────────────────────────────────────────────

    /** Retourne les permissions par rôle WP depuis les options. */
    public function get_role_permissions(): array {
        $defaults = $this->default_permissions();
        $saved    = get_option('bt_role_permissions', []);
        return is_array($saved) ? array_replace_recursive($defaults, $saved) : $defaults;
    }

    /** Vérifie si l'utilisateur a accès au chat selon les permissions de son rôle. */
    public function role_has_chat_access(int $user_id): bool {
        return $this->role_has_permission($user_id, 'chat_access');
    }

    /**
     * Vérifie si un utilisateur possède une permission donnée dans bt_role_permissions.
     * Les administrateurs (manage_options) ont toujours accès, quel que soit le réglage.
     *
     * @param int    $user_id ID WP de l'utilisateur
     * @param string $cap     Clé de permission (ex: 'bookings', 'dashboard', 'translations'…)
     */
    public function role_has_permission(int $user_id, string $cap): bool {
        if (user_can($user_id, 'manage_options')) return true;
        $user  = get_userdata($user_id);
        if (!$user) return false;
        $perms = $this->get_role_permissions();
        foreach ($user->roles as $role) {
            if (!empty($perms[$role][$cap])) return true;
        }
        return false;
    }

    /** Sauvegarde les permissions par rôle (liste complète des caps gérées). */
    public function save_role_permissions(array $permissions): void {
        $clean = [];
        $keys  = self::ALL_CAPS;
        foreach ($permissions as $role => $caps) {
            if (!is_array($caps)) continue;
            foreach ($keys as $k) {
                $clean[$role][$k] = !empty($caps[$k]);
            }
        }
        update_option('bt_role_permissions', $clean, false);
    }

    /** Permissions par défaut : admin = tout, autres = rien. */
    private function default_permissions(): array {
        global $wp_roles;
        $defaults = [];
        foreach (array_keys($wp_roles->roles ?? []) as $role) {
            $defaults[$role] = array_fill_keys(self::ALL_CAPS, $role === 'administrator');
        }
        return $defaults;
    }

    /** Liste canonique de toutes les permissions gérées. */
    public const ALL_CAPS = [
        'plugin',
        'dashboard',
        'bookings',
        'products',
        'planner',
        'customers',
        'analytics',
        'avis',
        'translations',
        'reservations',
        'chat_access',
        'chat_create',
        'chat_share',
        'settings',
    ];
}
