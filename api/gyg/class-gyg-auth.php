<?php
namespace BlackTenders\Api\Gyg;

use BlackTenders\Admin\Backoffice\Encryption;

defined('ABSPATH') || exit;

/**
 * Authentification GYG Supplier API.
 *
 * Gère le Basic Auth entrant (vérification des appels GYG → nous)
 * et sortant (appels de notre plugin → GYG API).
 *
 * Les credentials sont chiffrés en DB via Encryption (defuse/php-encryption).
 */
class GygAuth {

    /**
     * Génère le header Authorization pour les appels SORTANTS vers GYG.
     *
     * Lit bt_gyg_username + bt_gyg_password depuis les options WP,
     * déchiffre via Encryption et retourne "Basic <base64>".
     *
     * @return string "Basic <base64>" ou chaîne vide si credentials manquants.
     */
    public static function get_outbound_header(): string {
        $username_enc = get_option('bt_gyg_username', '');
        $password_enc = get_option('bt_gyg_password', '');

        if (empty($username_enc) || empty($password_enc)) {
            return '';
        }

        try {
            $enc      = new Encryption();
            $username = $enc->decrypt($username_enc);
            $password = $enc->decrypt($password_enc);
        } catch (\Throwable $e) {
            error_log('[GYG_AUTH] Échec déchiffrement credentials sortants : ' . $e->getMessage());
            return '';
        }

        if (empty($username) || empty($password)) {
            return '';
        }

        return 'Basic ' . base64_encode($username . ':' . $password);
    }

    /**
     * Valide le Basic Auth ENTRANT envoyé par GYG sur nos endpoints.
     *
     * Compare les credentials fournis dans l'en-tête Authorization
     * avec bt_gyg_incoming_username / bt_gyg_incoming_password stockés chiffrés.
     * Utilise hash_equals() pour éviter les timing attacks.
     *
     * @param \WP_REST_Request $req La requête REST entrante.
     * @return bool true si les credentials sont valides.
     */
    public static function validate_inbound(\WP_REST_Request $req): bool {
        $auth_header = $req->get_header('Authorization');

        if (empty($auth_header) || stripos($auth_header, 'Basic ') !== 0) {
            return false;
        }

        $decoded = base64_decode(substr($auth_header, 6), true);
        if ($decoded === false) {
            return false;
        }

        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) {
            return false;
        }

        [$provided_user, $provided_pass] = $parts;

        $stored_user_enc = get_option('bt_gyg_incoming_username', '');
        $stored_pass_enc = get_option('bt_gyg_incoming_password', '');

        if (empty($stored_user_enc) || empty($stored_pass_enc)) {
            return false;
        }

        try {
            $enc         = new Encryption();
            $stored_user = $enc->decrypt($stored_user_enc);
            $stored_pass = $enc->decrypt($stored_pass_enc);
        } catch (\Throwable $e) {
            error_log('[GYG_AUTH] Échec déchiffrement credentials entrants : ' . $e->getMessage());
            return false;
        }

        if (empty($stored_user) || empty($stored_pass)) {
            return false;
        }

        // Comparaison résistante aux timing attacks
        return hash_equals($stored_user, $provided_user)
            && hash_equals($stored_pass, $provided_pass);
    }
}
