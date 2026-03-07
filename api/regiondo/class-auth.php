<?php
namespace BT_Regiondo\Api\Regiondo;

defined('ABSPATH') || exit;

class Auth {

    private string $public_key;
    private string $secret_key;

    public function __construct() {
        $this->public_key = get_option('bt_regiondo_public_key', '');
        $this->secret_key = get_option('bt_regiondo_secret_key', '');
    }

    /**
     * Retourne les headers HMAC pour une URL donnée
     */
    public function get_headers(string $url): array {
        $timestamp    = (int) round(microtime(true) * 1000);
        $query_string = parse_url($url, PHP_URL_QUERY) ?? '';
        $data         = $timestamp . $this->public_key . $query_string;
        $hmac         = hash_hmac('sha256', $data, $this->secret_key);

        return [
            'X-API-HASH: ' . $hmac,
            'X-API-TIME: ' . $timestamp,
            'X-API-ID: '   . $this->public_key,
        ];
    }

    public function is_configured(): bool {
        return !empty($this->public_key) && !empty($this->secret_key);
    }
}