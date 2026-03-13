<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiChat — endpoints pour la gestion des conversations IA partagées.
 * Chats, messages, partages (shares), recherche utilisateurs, permissions par rôle.
 */
trait RestApiChat {

    // ── /chats ────────────────────────────────────────────────────────────────

    /** GET /chats — liste les chats accessibles par l'utilisateur courant. */
    public function list_chats(): \WP_REST_Response {
        $db   = new ChatDb();
        $rows = $db->list_for_user(get_current_user_id());

        $uid   = get_current_user_id();
        $admin = current_user_can('manage_options');
        $chats = array_map(fn($r) => [
            'uuid'        => $r['uuid'],
            'title'       => $r['title'],
            'provider'    => $r['provider'],
            'owner_id'    => (int) $r['owner_id'],
            'owner_color' => $r['owner_color'],
            'permission'  => ($r['owner_id'] == $uid || $admin) ? 'owner' : ($r['permission'] ?? 'read'),
            'updated_at'  => $r['updated_at'],
        ], $rows);

        return rest_ensure_response(['chats' => $chats]);
    }

    /**
     * POST /chats — crée ou met à jour un chat (upsert par uuid).
     * Body: { uuid, title, provider, filter_params?, messages[] }
     */
    public function create_chat(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        $body  = $req->get_json_params();
        $uuid  = sanitize_key($body['uuid'] ?? '');
        if (!$uuid) return new \WP_Error('invalid', 'uuid requis.', ['status' => 400]);

        $db      = new ChatDb();
        $db->ensure_tables();
        $user_id = get_current_user_id();

        // Si le chat existe déjà, vérifie que l'utilisateur est owner ou a la permission write
        $existing = $db->get_by_uuid($uuid, $user_id);
        if ($existing) {
            $perm = $existing['permission'];
            if ($perm !== 'owner' && $perm !== 'write') {
                return new \WP_Error('forbidden', 'Permission insuffisante pour modifier ce chat.', ['status' => 403]);
            }
        }

        $chat_id = $db->upsert_chat(
            $uuid,
            sanitize_text_field($body['title'] ?? 'Nouvelle conversation'),
            sanitize_key($body['provider'] ?? 'anthropic'),
            $user_id,
            is_array($body['filter_params'] ?? null) ? $body['filter_params'] : null
        );

        if (!$chat_id) return new \WP_Error('db_error', 'Erreur lors de la création du chat.', ['status' => 500]);

        if (!empty($body['messages']) && is_array($body['messages'])) {
            $db->save_messages($chat_id, $body['messages'], $user_id);
        }

        return rest_ensure_response(['uuid' => $uuid, 'id' => $chat_id]);
    }

    /** GET /chats/{uuid} — retourne un chat avec ses messages et partages. */
    public function get_chat(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        $uuid = sanitize_key($req->get_param('uuid'));
        $db   = new ChatDb();
        $db->ensure_tables();

        $result = $db->get_by_uuid($uuid, get_current_user_id());
        if (!$result) return new \WP_Error('forbidden', 'Accès refusé ou chat introuvable.', ['status' => 403]);

        ['chat' => $chat, 'permission' => $perm] = $result;
        // Admin traité comme owner pour l'accès aux partages
        $is_owner = $perm === 'owner' || current_user_can('manage_options');
        $messages = $db->get_messages((int) $chat['id']);
        $shares   = $is_owner ? $db->get_shares((int) $chat['id']) : [];

        // Participants = owner + share users — used by frontend to display avatars/colors in messages
        $owner      = get_userdata((int) $chat['owner_id']);
        $participants = [];
        if ($owner) {
            $participants[] = [
                'user_id'      => (int) $chat['owner_id'],
                'display_name' => $owner->display_name,
                'avatar'       => get_avatar_url($chat['owner_id'], ['size' => 32]),
                'color'        => $chat['owner_color'] ?? '#f0f0ee',
                'permission'   => 'owner',
            ];
        }
        foreach ($db->get_shares((int) $chat['id']) as $s) {
            $participants[] = [
                'user_id'      => (int) $s['user_id'],
                'display_name' => $s['display_name'],
                'avatar'       => $s['avatar'],
                'color'        => $s['color'],
                'permission'   => $is_owner ? $s['permission'] : null,
            ];
        }

        return rest_ensure_response([
            'uuid'         => $chat['uuid'],
            'title'        => $chat['title'],
            'provider'     => $chat['provider'],
            'owner_id'     => (int) $chat['owner_id'],
            'owner_color'  => $chat['owner_color'],
            'permission'   => $perm,
            'messages'     => $messages,
            'shares'       => $shares,
            'participants' => $participants,
            'updated_at'   => $chat['updated_at'],
        ]);
    }

    /** PATCH /chats/{uuid} — met à jour titre, provider ou owner_color (owner only). */
    public function update_chat(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        $uuid = sanitize_key($req->get_param('uuid'));
        $body = $req->get_json_params();
        $db   = new ChatDb();

        $data = array_filter([
            'title'       => isset($body['title'])       ? sanitize_text_field($body['title'])  : null,
            'provider'    => isset($body['provider'])    ? sanitize_key($body['provider'])       : null,
            'owner_color' => isset($body['owner_color']) ? sanitize_hex_color($body['owner_color']) : null,
        ]);

        if (!$db->update_chat($uuid, get_current_user_id(), $data)) {
            return new \WP_Error('forbidden', 'Accès refusé ou chat introuvable.', ['status' => 403]);
        }
        return rest_ensure_response(['updated' => true]);
    }

    /** DELETE /chats/{uuid} — supprime le chat (owner only). */
    public function delete_chat(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        $uuid = sanitize_key($req->get_param('uuid'));
        $db   = new ChatDb();

        if (!$db->delete_chat($uuid, get_current_user_id())) {
            return new \WP_Error('forbidden', 'Accès refusé ou chat introuvable.', ['status' => 403]);
        }
        return rest_ensure_response(['deleted' => true]);
    }

    // ── /chats/{uuid}/shares ──────────────────────────────────────────────────

    /** GET /chats/{uuid}/shares — liste les partages (owner only). */
    public function list_shares(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        [$chat_id] = $this->require_owner($req->get_param('uuid'));
        if (!$chat_id) return new \WP_Error('forbidden', 'Réservé au propriétaire.', ['status' => 403]);

        return rest_ensure_response(['shares' => (new ChatDb())->get_shares($chat_id)]);
    }

    /** POST /chats/{uuid}/shares — invite un utilisateur. Body: { user_id, permission } */
    public function add_share(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        [$chat_id] = $this->require_owner($req->get_param('uuid'));
        if (!$chat_id) return new \WP_Error('forbidden', 'Réservé au propriétaire.', ['status' => 403]);

        $body    = $req->get_json_params();
        $user_id = (int) ($body['user_id'] ?? 0);
        $perm    = in_array($body['permission'] ?? 'read', ['read','write'], true) ? $body['permission'] : 'read';

        if (!$user_id || $user_id === get_current_user_id()) {
            return new \WP_Error('invalid', 'Utilisateur invalide.', ['status' => 400]);
        }

        $db = new ChatDb();
        if (!$db->role_has_chat_access($user_id)) {
            return new \WP_Error('no_access', "Cet utilisateur n'a pas accès au chat IA (permissions de rôle).", ['status' => 403]);
        }

        if (!$db->add_share($chat_id, $user_id, $perm, get_current_user_id())) {
            return new \WP_Error('already_shared', 'Utilisateur déjà invité.', ['status' => 409]);
        }

        return rest_ensure_response(['shares' => $db->get_shares($chat_id)]);
    }

    /** PATCH /chats/{uuid}/shares/{uid} — modifie permission ou couleur (owner only). */
    public function update_share(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        [$chat_id] = $this->require_owner($req->get_param('uuid'));
        if (!$chat_id) return new \WP_Error('forbidden', 'Réservé au propriétaire.', ['status' => 403]);

        $uid  = (int) $req->get_param('uid');
        $body = $req->get_json_params();
        $db   = new ChatDb();

        if (!$db->update_share($chat_id, $uid, $body)) {
            return new \WP_Error('not_found', 'Partage introuvable.', ['status' => 404]);
        }
        return rest_ensure_response(['shares' => $db->get_shares($chat_id)]);
    }

    /** DELETE /chats/{uuid}/shares/{uid} — révoque l'accès (owner only). */
    public function remove_share(\WP_REST_Request $req): \WP_REST_Response|\WP_Error {
        [$chat_id] = $this->require_owner($req->get_param('uuid'));
        if (!$chat_id) return new \WP_Error('forbidden', 'Réservé au propriétaire.', ['status' => 403]);

        $uid = (int) $req->get_param('uid');
        if ($uid === get_current_user_id()) {
            return new \WP_Error('invalid', 'Impossible de vous révoquer vous-même.', ['status' => 400]);
        }

        (new ChatDb())->remove_share($chat_id, $uid);
        return rest_ensure_response(['deleted' => true]);
    }

    // ── /users/search ─────────────────────────────────────────────────────────

    /** GET /users/search?q=xxx&exclude=1,2,3 — recherche d'utilisateurs WP pour l'invitation. */
    public function search_users(\WP_REST_Request $req): \WP_REST_Response {
        $q       = sanitize_text_field($req->get_param('q') ?? '');
        $exclude = array_map('intval', explode(',', $req->get_param('exclude') ?? ''));
        $exclude = array_filter($exclude);

        if (strlen($q) < 2) return rest_ensure_response(['users' => []]);

        $users = (new ChatDb())->search_users($q, $exclude);
        return rest_ensure_response(['users' => $users]);
    }

    // ── Permissions par rôle ──────────────────────────────────────────────────

    /** GET /settings/role-permissions — retourne les permissions par rôle. */
    public function get_role_permissions(): \WP_REST_Response {
        return rest_ensure_response((new ChatDb())->get_role_permissions());
    }

    /** POST /settings/role-permissions — sauvegarde les permissions par rôle. */
    public function save_role_permissions(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params();
        if (!is_array($body)) return rest_ensure_response(['saved' => false]);
        (new ChatDb())->save_role_permissions($body);
        return rest_ensure_response(['saved' => true]);
    }

    // ── Helper privé ──────────────────────────────────────────────────────────

    /**
     * Vérifie que l'utilisateur courant est l'owner du chat uuid.
     * Les administrateurs (manage_options) contournent la vérification d'ownership
     * et ont toujours les droits d'owner sur tous les chats.
     *
     * @return array [chat_id|null, 'owner'|null]
     */
    private function require_owner(string $uuid): array {
        $db     = new ChatDb();
        $uid    = get_current_user_id();
        $result = $db->get_by_uuid(sanitize_key($uuid), $uid);
        if (!$result) return [null, null];
        // Admin : accès owner sur tous les chats
        if (current_user_can('manage_options')) return [(int) $result['chat']['id'], 'owner'];
        if ($result['permission'] !== 'owner') return [null, null];
        return [(int) $result['chat']['id'], 'owner'];
    }
}
