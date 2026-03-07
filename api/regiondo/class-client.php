<?php
namespace BT_Regiondo\Api\Regiondo;

defined('ABSPATH') || exit;

class Client {

    private const BASE_URL = 'https://api.regiondo.com/v1/';

    private Auth  $auth;
    private Cache $cache;

    public function __construct() {
        $this->auth  = new Auth();
        $this->cache = new Cache();
    }

    /**
     * GET /products — liste tous les produits du supplier
     */
    public function get_products(string $locale = 'fr-FR'): array {
        $cache_key = 'bt_regiondo_products_' . $locale;
        $cached    = $this->cache->get($cache_key);

        if ($cached !== false) return $cached;

        $url  = self::BASE_URL . 'products?limit=250&store_locale=' . $locale;
        $data = $this->request($url);

        if (empty($data['data'])) return [];

        $products = array_map(fn($p) => [
            'product_id' => $p['product_id'],
            'name'       => $p['name'],
            'base_price' => $p['base_price'] ?? 0,
            'currency'   => $p['currency_code'] ?? 'EUR',
        ], $data['data']);

        $this->cache->set($cache_key, $products);

        return $products;
    }

    /**
     * GET /products/{id} — détail d'un produit
     */
    public function get_product(int $product_id, string $locale = 'fr-FR'): array {
        $cache_key = 'bt_regiondo_product_' . $product_id . '_' . $locale;
        $cached    = $this->cache->get($cache_key);

        if ($cached !== false) return $cached;

        $url  = self::BASE_URL . 'products/' . $product_id . '?store_locale=' . $locale;
        $data = $this->request($url);

        if (empty($data)) return [];

        $this->cache->set($cache_key, $data);

        return $data;
    }

    /**
     * Effectue un appel GET signé HMAC
     */
    private function request(string $url): array {
        if (!$this->auth->is_configured()) return [];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $this->auth->get_headers($url),
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200 || empty($response)) return [];

        return json_decode($response, true) ?? [];
    }
}