<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiForms — CRUD et stats pour les soumissions de formulaires (bt_form_submissions).
 *
 * Endpoints :
 *   GET    /forms        — liste paginee avec recherche et filtres
 *   GET    /forms/stats  — statistiques agregees
 *   DELETE /forms/{id}   — suppression d'une soumission (admin only)
 */
trait RestApiForms {

    /**
     * Liste paginee des soumissions de formulaires.
     *
     * Params GET : page, per_page, search, form_type, email_sent, sort_key, sort_dir
     */
    public function get_form_submissions(\WP_REST_Request $req): \WP_REST_Response {
        $db = new FormSubmissionsDb();

        $result = $db->get_all([
            'page'       => (int)    ($req->get_param('page')     ?: 1),
            'per_page'   => (int)    ($req->get_param('per_page') ?: 50),
            'search'     => (string) ($req->get_param('search')   ?: ''),
            'form_type'  => (string) ($req->get_param('form_type') ?: ''),
            'email_sent' => $req->get_param('email_sent') !== null
                ? (int) $req->get_param('email_sent')
                : null,
            'sort_key'   => (string) ($req->get_param('sort_key') ?: 'created_at'),
            'sort_dir'   => (string) ($req->get_param('sort_dir') ?: 'DESC'),
        ]);

        return rest_ensure_response($result);
    }

    /**
     * Statistiques agregees des soumissions.
     */
    public function get_form_submissions_stats(\WP_REST_Request $req): \WP_REST_Response {
        $db     = new FormSubmissionsDb();
        $result = $db->get_stats();

        return rest_ensure_response($result);
    }

    /**
     * Supprime une soumission par ID (admin only).
     */
    public function delete_form_submission(\WP_REST_Request $req): \WP_REST_Response {
        $id = (int) $req['id'];
        if (!$id) {
            return new \WP_REST_Response(['error' => 'ID invalide'], 400);
        }

        $db      = new FormSubmissionsDb();
        $deleted = $db->delete($id);

        if (!$deleted) {
            return new \WP_REST_Response(['error' => 'Soumission introuvable'], 404);
        }

        return rest_ensure_response(['success' => true]);
    }
}
