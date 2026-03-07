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

    // ─── PRODUITS ────────────────────────────────────────────────────────────

    public function get_products(string $locale = 'fr-FR'): array {
        $cache_key = 'bt_regiondo_products_' . $locale;
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) return $cached;

        $url  = self::BASE_URL . 'products?limit=250&store_locale=' . $locale;
        $data = $this->request($url);
        if (empty($data['data'])) return [];

        $products = array_map(fn($p) => [
            'product_id'  => $p['product_id'],
            'name'        => $p['name'],
            'base_price'  => $p['base_price'] ?? 0,
            'currency'    => $p['currency_code'] ?? 'EUR',
            'category_id' => $p['category_id'] ?? null,
        ], $data['data']);

        $this->cache->set($cache_key, $products);
        return $products;
    }

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

    public function get_variations(int $product_id, string $locale = 'fr-FR'): array {
        $cache_key = 'bt_regiondo_variations_' . $product_id . '_' . $locale;
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) return $cached;

        $url  = self::BASE_URL . 'products/variations/' . $product_id . '?store_locale=' . $locale;
        $data = $this->request($url);
        $result = $data['data'] ?? $data;

        $this->cache->set($cache_key, $result);
        return is_array($result) ? $result : [];
    }

    public function get_crossselling(int $product_id, string $locale = 'fr-FR'): array {
        $url  = self::BASE_URL . 'products/crossselling/' . $product_id . '?store_locale=' . $locale;
        $data = $this->request($url);
        return $data['data'] ?? [];
    }

    // ─── CATALOGUE ───────────────────────────────────────────────────────────

    public function get_categories(string $locale = 'fr-FR'): array {
        $cache_key = 'bt_regiondo_categories_' . $locale;
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) return $cached;

        $url  = self::BASE_URL . 'categories?limit=100&store_locale=' . $locale;
        $data = $this->request($url);
        $result = $data['data'] ?? [];

        $this->cache->set($cache_key, $result);
        return $result;
    }

    public function get_locations(string $query = ''): array {
        $url = self::BASE_URL . 'locations?limit=100';
        if ($query) $url .= '&query=' . urlencode($query);
        $data = $this->request($url);
        return $data['data'] ?? [];
    }

    public function get_languages(): array {
        $cache_key = 'bt_regiondo_languages';
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) return $cached;

        $url    = self::BASE_URL . 'languages?limit=50';
        $data   = $this->request($url);
        $result = $data['data'] ?? [];

        $this->cache->set($cache_key, $result);
        return $result;
    }

    // ─── PARTENAIRE / REPORTING ───────────────────────────────────────────────

    /**
     * @param array $params Clés : page, per_page, from (YYYY-MM-DD), to, product_id, order_number
     */
    public function get_bookings(array $params = []): array {
        $defaults = ['page' => 1, 'per_page' => 50];
        $params   = array_merge($defaults, $params);
        $url      = self::BASE_URL . 'partner/bookings?' . http_build_query($params);
        $data     = $this->request($url);
        return [
            'data'  => $data['data']  ?? [],
            'total' => $data['total'] ?? 0,
            'page'  => $data['page']  ?? 1,
        ];
    }

    public function get_sold_items(array $params = []): array {
        $defaults = ['page' => 1, 'per_page' => 50];
        $params   = array_merge($defaults, $params);
        $url      = self::BASE_URL . 'partner/solditems?' . http_build_query($params);
        $data     = $this->request($url);
        return [
            'data'  => $data['data']  ?? [],
            'total' => $data['total'] ?? 0,
        ];
    }

    public function get_crm_customers(array $params = []): array {
        $defaults = ['page' => 1, 'per_page' => 50];
        $params   = array_merge($defaults, $params);
        $url      = self::BASE_URL . 'partner/crmcustomers?' . http_build_query($params);
        $data     = $this->request($url);
        return [
            'data'  => $data['data']  ?? [],
            'total' => $data['total'] ?? 0,
        ];
    }

    public function update_crm_customer(string $email, bool $subscribed): bool {
        $url  = self::BASE_URL . 'partner/crmcustomers';
        $body = json_encode(['email' => $email, 'newsletter' => $subscribed]);
        $data = $this->request($url, 'PUT', $body);
        return !empty($data);
    }

    // ─── COMPTE ───────────────────────────────────────────────────────────────

    public function get_account_locale(): array {
        $data = $this->request(self::BASE_URL . 'account/locale');
        return $data['data'] ?? $data;
    }

    public function get_account_currency(): array {
        $data = $this->request(self::BASE_URL . 'account/currency');
        return $data['data'] ?? $data;
    }

    // ─── CHECKOUT / UTILITAIRES ───────────────────────────────────────────────

    public function checkout_totals(array $items): array {
        $body = json_encode(['items' => $items]);
        return $this->request(self::BASE_URL . 'checkout/totals', 'POST', $body);
    }

    public function cancel_tickets(array $ref_ids): array {
        $body = json_encode(['reference_ids' => $ref_ids]);
        return $this->request(self::BASE_URL . 'checkout/cancel', 'POST', $body);
    }

    public function get_booking_info(string $ref): array {
        $url  = self::BASE_URL . 'checkout/booking?booking_ref=' . urlencode($ref);
        return $this->request($url);
    }

    // ─── HTTP ─────────────────────────────────────────────────────────────────

    private function request(string $url, string $method = 'GET', ?string $body = null): array {
        if (!$this->auth->is_configured()) return [];

        $ch = curl_init($url);

        $headers = $this->auth->get_headers($url);
        if ($body) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: ' . strlen($body);
        }

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST]       = true;
            $opts[CURLOPT_POSTFIELDS] = $body;
        } elseif ($method === 'PUT') {
            $opts[CURLOPT_CUSTOMREQUEST] = 'PUT';
            $opts[CURLOPT_POSTFIELDS]    = $body;
        }

        curl_setopt_array($ch, $opts);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300 || empty($response)) return [];

        return json_decode($response, true) ?? [];
    }
}
