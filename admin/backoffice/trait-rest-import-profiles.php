<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiImportProfiles — CRUD des profils de mapping CSV.
 */
trait RestApiImportProfiles {

    public function list_import_profiles(\WP_REST_Request $req): \WP_REST_Response {
        $type = sanitize_text_field($req->get_param('type') ?? '');
        if ($type === '') {
            return new \WP_REST_Response(['message' => 'Paramètre "type" requis.'], 400);
        }

        $db = new ImportProfilesDb();
        return rest_ensure_response($db->list($type));
    }

    public function save_import_profile(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params();
        $name        = $body['name']        ?? '';
        $import_type = $body['import_type'] ?? '';
        $mapping     = $body['mapping']     ?? [];

        if (empty($name) || empty($import_type) || !is_array($mapping)) {
            return new \WP_REST_Response(['message' => 'Champs name, import_type et mapping requis.'], 400);
        }

        $db     = new ImportProfilesDb();
        $result = $db->save($name, $import_type, $mapping);

        if (isset($result['error'])) {
            return new \WP_REST_Response(['message' => $result['error']], 422);
        }

        return rest_ensure_response($result);
    }

    public function delete_import_profile(\WP_REST_Request $req): \WP_REST_Response {
        $id = (int) $req['id'];
        $db = new ImportProfilesDb();

        if (!$db->delete($id)) {
            return new \WP_REST_Response(['message' => 'Profil introuvable.'], 404);
        }

        return rest_ensure_response(['deleted' => true]);
    }
}
