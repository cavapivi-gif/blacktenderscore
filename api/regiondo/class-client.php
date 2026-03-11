<?php
namespace BlackTenders\Api\Regiondo;

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
            'product_id'       => $p['product_id'],
            'name'             => $p['name'],
            'short_description'=> $p['short_description'] ?? $p['teaser'] ?? '',
            'base_price'       => $p['base_price'] ?? 0,
            'currency'         => $p['currency_code'] ?? 'EUR',
            'category_id'      => $p['category_id'] ?? null,
            'category_name'    => $p['category_name'] ?? null,
            'duration'         => $p['duration'] ?? null,
            'duration_unit'    => $p['duration_unit'] ?? null,
            'capacity'         => $p['capacity'] ?? null,
            'thumbnail_url'    => $p['thumbnail_url'] ?? $p['image_url'] ?? $p['images'][0]['url'] ?? null,
            'location'         => $p['location'] ?? $p['city'] ?? null,
            'status'           => $p['status'] ?? null,
            'rating'           => $p['rating'] ?? null,
            'reviews_count'    => $p['reviews_count'] ?? null,
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
        $cache_key = 'bt_regiondo_crossselling_' . $product_id . '_' . $locale;
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) return $cached;

        $url    = self::BASE_URL . 'products/crossselling/' . $product_id . '?store_locale=' . $locale;
        $data   = $this->request($url);
        $result = $data['data'] ?? [];

        $this->cache->set($cache_key, $result);
        return $result;
    }

    public function get_navigation_attributes(string $locale = 'fr-FR'): array {
        $cache_key = 'bt_regiondo_nav_attrs_' . $locale;
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) return $cached;

        $url    = self::BASE_URL . 'products/navigationattributes?store_locale=' . $locale;
        $data   = $this->request($url);
        $result = $data['data'] ?? (is_array($data) ? $data : []);

        $this->cache->set($cache_key, $result);
        return $result;
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

    // ─── RÉSERVATIONS / REPORTING ────────────────────────────────────────────

    /**
     * Fetch bookings via Regiondo API.
     * Tries /partner/bookings (Swagger spec) then /supplier/bookings (PDF doc).
     *
     * @param array $params Keys: from (YYYY-MM-DD), to, product_id, status, limit, offset
     */
    public function get_bookings(array $params = []): array {
        $query = $this->build_booking_query($params);

        // Try /partner/bookings first (confirmed in Swagger OpenAPI spec)
        $url  = self::BASE_URL . 'partner/bookings?' . $this->build_query_string($query);
        $data = $this->request($url);

        if ($this->is_valid_response($data)) {
            return $this->parse_booking_response($data, $params);
        }

        // Fallback: /supplier/bookings (referenced in PDF doc)
        $url  = self::BASE_URL . 'supplier/bookings?' . $this->build_query_string($query);
        $data = $this->request($url);

        if ($this->is_valid_response($data)) {
            return $this->parse_booking_response($data, $params);
        }

        return ['data' => [], 'total' => 0, 'page' => 1];
    }

    private function build_booking_query(array $params): array {
        $query = [
            'limit'  => $params['per_page'] ?? $params['limit'] ?? 250,
            'offset' => isset($params['page']) ? (($params['page'] - 1) * ($params['per_page'] ?? 250)) : ($params['offset'] ?? 0),
            // Include all booking types (default is "booking" only)
            'type'   => $params['type'] ?? 'offline_reservation,booking,voucher,redeem',
        ];

        if (!empty($params['from']) || !empty($params['to'])) {
            $from = $params['from'] ?? '2020-01-01';
            $to   = $params['to']   ?? date('Y-m-d');
            $query['date_range']    = $from . ',' . $to;
            $query['date_range_by'] = $params['date_range_by'] ?? 'date_bought';
        }

        if (!empty($params['product_id']))   $query['product_ids'] = $params['product_id'];
        if (!empty($params['order_number'])) $query['order_ids']   = $params['order_number'];
        if (!empty($params['status']))       $query['status']      = $params['status'];

        return $query;
    }

    private function parse_booking_response(array $data, array $params): array {
        $items = $data['data'] ?? [];
        return [
            'data'  => $this->normalize_bookings($items),
            'total' => $data['total'] ?? count($items),
            'page'  => ($params['page'] ?? 1),
        ];
    }

    /**
     * Check if API response is valid (not an error disguised as 200).
     * Regiondo can return 200 with error info in the body.
     */
    private function is_valid_response(array $data): bool {
        if (empty($data)) return false;
        // Regiondo silent errors: 200 status but error fields in body
        if (isset($data['error']) || isset($data['error_code'])) {
            error_log('[BlackTenders] Regiondo API error: ' . json_encode($data));
            return false;
        }
        return isset($data['data']) || isset($data['total']);
    }

    /**
     * Normalize various booking response formats to a consistent structure.
     */
    private function normalize_bookings(array $items): array {
        return array_map(function($b) {
            return [
                'booking_ref'   => $b['booking_ref'] ?? $b['order_number'] ?? $b['reference_id'] ?? $b['id'] ?? '',
                'product_name'  => $b['product_name'] ?? $b['name'] ?? $b['product'] ?? '',
                'booking_date'  => $b['booking_date'] ?? $b['date'] ?? $b['created_at'] ?? $b['order_date'] ?? '',
                'customer_name' => $b['customer_name'] ?? trim(($b['first_name'] ?? '') . ' ' . ($b['last_name'] ?? '')) ?: ($b['customer'] ?? ''),
                'total_price'   => $b['total_price'] ?? $b['total'] ?? $b['price'] ?? $b['amount'] ?? null,
                'currency_code' => $b['currency_code'] ?? $b['currency'] ?? 'EUR',
                'status'        => $b['status'] ?? 'confirmed',
                'customer_email'=> $b['customer_email'] ?? $b['email'] ?? '',
            ];
        }, $items);
    }

    public function get_sold_items(array $params = []): array {
        $query = [
            'limit'  => $params['per_page'] ?? $params['limit'] ?? 250,
            'offset' => isset($params['page']) ? (($params['page'] - 1) * ($params['per_page'] ?? 250)) : ($params['offset'] ?? 0),
        ];

        if (!empty($params['from']) || !empty($params['to'])) {
            $from = $params['from'] ?? '2020-01-01';
            $to   = $params['to']   ?? date('Y-m-d');
            $query['date_range']    = $from . ',' . $to;
            $query['date_range_by'] = $params['date_range_by'] ?? 'date_bought';
        }

        // Try /partner/solditems first, then /supplier/solditems
        foreach (['partner/solditems', 'supplier/solditems'] as $endpoint) {
            $url  = self::BASE_URL . $endpoint . '?' . $this->build_query_string($query);
            $data = $this->request($url);
            if ($this->is_valid_response($data)) {
                return [
                    'data'  => $data['data'] ?? [],
                    'total' => $data['total'] ?? 0,
                ];
            }
        }

        return ['data' => [], 'total' => 0];
    }

    /**
     * Fetch CRM customers.
     * Tries /partner/crmcustomers (confirmed in Swagger spec),
     * falls back to extracting from bookings data.
     */
    public function get_crm_customers(array $params = []): array {
        $query = [
            'limit'  => $params['per_page'] ?? $params['limit'] ?? 250,
            'offset' => isset($params['page']) ? (($params['page'] - 1) * ($params['per_page'] ?? 250)) : ($params['offset'] ?? 0),
        ];

        // Try /partner/crmcustomers (exists per Swagger spec)
        $url  = self::BASE_URL . 'partner/crmcustomers?' . $this->build_query_string($query);
        $data = $this->request($url);

        if ($this->is_valid_response($data)) {
            return [
                'data'  => $data['data'] ?? [],
                'total' => $data['total'] ?? count($data['data'] ?? []),
            ];
        }

        // Fallback: derive customers from bookings
        $cache_key = 'bt_regiondo_customers';
        $cached    = $this->cache->get($cache_key);
        if ($cached !== false) {
            $total = count($cached);
            $page  = (int) ($params['page'] ?? 1);
            $per   = (int) ($params['per_page'] ?? 50);
            return [
                'data'  => array_slice($cached, ($page - 1) * $per, $per),
                'total' => $total,
            ];
        }

        $bookings = $this->get_bookings(['limit' => 250]);
        $by_email = [];

        foreach ($bookings['data'] as $b) {
            $email = $b['customer_email'] ?? '';
            $name  = $b['customer_name'] ?? '';
            if (empty($email) && empty($name)) continue;

            $key = $email ?: sanitize_title($name);
            if (!isset($by_email[$key])) {
                $by_email[$key] = [
                    'email'          => $email,
                    'name'           => $name,
                    'bookings_count' => 0,
                    'total_spent'    => 0,
                    'currency'       => $b['currency_code'] ?? 'EUR',
                    'last_booking'   => $b['booking_date'] ?? '',
                ];
            }
            $by_email[$key]['bookings_count']++;
            $by_email[$key]['total_spent'] += floatval($b['total_price'] ?? 0);
            if (($b['booking_date'] ?? '') > $by_email[$key]['last_booking']) {
                $by_email[$key]['last_booking'] = $b['booking_date'];
            }
        }

        $customers = array_values($by_email);
        $this->cache->set($cache_key, $customers);

        $total = count($customers);
        $page  = (int) ($params['page'] ?? 1);
        $per   = (int) ($params['per_page'] ?? 50);

        return [
            'data'  => array_slice($customers, ($page - 1) * $per, $per),
            'total' => $total,
        ];
    }

    public function update_crm_customer(string $email, bool $subscribed): bool {
        $url  = self::BASE_URL . 'partner/crmcustomers';
        $body = json_encode(['email' => $email, 'newsletter' => $subscribed]);
        $data = $this->request($url, 'PUT', $body);
        return !empty($data);
    }

    // ─── AVIS ─────────────────────────────────────────────────────────────────

    public function get_reviews(array $params = []): array {
        $query = ['limit' => $params['limit'] ?? 250];
        if (!empty($params['product_id'])) {
            $query['product_id'] = $params['product_id'];
        }
        if (isset($params['offset'])) {
            $query['offset'] = $params['offset'];
        }

        $url  = self::BASE_URL . 'reviews?' . http_build_query($query);
        $data = $this->request($url);
        return [
            'data'  => $data['data'] ?? [],
            'total' => $data['total'] ?? 0,
        ];
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

    /**
     * Build query string without encoding commas.
     * Regiondo uses commas as separators (date_range, type) and
     * encoding them as %2C can break HMAC signature matching.
     */
    private function build_query_string(array $params): string {
        $qs = http_build_query($params);
        // Restore commas — Regiondo expects raw commas in date_range and type params
        return str_replace('%2C', ',', $qs);
    }

    /**
     * Raw request for diagnostics — returns full debug info.
     */
    public function raw_request(string $endpoint, array $params = []): array {
        if (!$this->auth->is_configured()) {
            return ['url' => $endpoint, 'error' => 'API keys not configured', 'status' => 0];
        }

        $url = self::BASE_URL . $endpoint;
        if ($params) {
            $url .= '?' . $this->build_query_string($params);
        }

        $ch      = curl_init($url);
        $headers = $this->auth->get_headers($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        $decoded = $response ? (json_decode($response, true) ?? null) : null;

        return [
            'url'      => $url,
            'status'   => $status,
            'error'    => $error ?: null,
            'response' => $decoded,
            'raw'      => !$decoded ? substr($response ?? '', 0, 1000) : null,
        ];
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
            CURLOPT_FOLLOWLOCATION => true,
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
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log('[BlackTenders] cURL error: ' . $error . ' URL: ' . $url);
            return [];
        }

        if ($status < 200 || $status >= 300 || empty($response)) {
            error_log('[BlackTenders] API ' . $status . ' for: ' . $url . ' — ' . substr($response ?? '', 0, 500));
            return [];
        }

        $decoded = json_decode($response, true) ?? [];

        // Detect silent errors (Regiondo can return 200 with error in body)
        if (isset($decoded['error']) || isset($decoded['error_code']) || isset($decoded['error_message'])) {
            error_log('[BlackTenders] API silent error for: ' . $url . ' — ' . substr($response, 0, 500));
        }

        return $decoded;
    }
}
