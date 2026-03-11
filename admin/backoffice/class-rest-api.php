<?php
namespace BlackTenders\Admin\Backoffice;

use BlackTenders\Api\Regiondo\Client;
use BlackTenders\Api\Regiondo\Cache;

defined('ABSPATH') || exit;

class RestApi {

    private const NS = 'bt-regiondo/v1';

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        $auth = fn() => current_user_can('manage_options');

        // Produits
        register_rest_route(self::NS, '/products', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_products'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_product'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)/variations', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_variations'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)/crossselling', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_crossselling'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/products/navigationattributes', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_navigation_attributes'],
            'permission_callback' => $auth,
        ]);

        // Catégories
        register_rest_route(self::NS, '/categories', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_categories'],
            'permission_callback' => $auth,
        ]);

        // Réservations
        register_rest_route(self::NS, '/bookings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_bookings'],
            'permission_callback' => $auth,
        ]);

        // Clients CRM
        register_rest_route(self::NS, '/customers', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_customers'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/customers/newsletter', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'update_newsletter'],
            'permission_callback' => $auth,
        ]);

        // Dashboard (agrégation)
        register_rest_route(self::NS, '/dashboard', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_dashboard'],
            'permission_callback' => $auth,
        ]);

        // Test connexion
        register_rest_route(self::NS, '/test-connection', [
            'methods'             => 'GET',
            'callback'            => [$this, 'test_connection'],
            'permission_callback' => $auth,
        ]);

        // Réglages
        register_rest_route(self::NS, '/settings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_settings'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/settings', [
            'methods'             => 'POST',
            'callback'            => [$this, 'save_settings'],
            'permission_callback' => $auth,
        ]);

        // Cache
        register_rest_route(self::NS, '/cache/flush', [
            'methods'             => 'POST',
            'callback'            => [$this, 'flush_cache'],
            'permission_callback' => $auth,
        ]);

        // Sync
        register_rest_route(self::NS, '/sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'sync_products'],
            'permission_callback' => $auth,
        ]);
    }

    // ─── Handlers ─────────────────────────────────────────────────────────────

    public function get_products(\WP_REST_Request $req): \WP_REST_Response {
        $locale   = $req->get_param('locale') ?: 'fr-FR';
        $client   = new Client();
        $products = $client->get_products($locale);

        // Enrichit avec le post WP lié (si sync déjà fait)
        foreach ($products as &$p) {
            $posts = get_posts([
                'post_type'  => get_option('bt_post_types', ['excursion']),
                'meta_key'   => '_bt_regiondo_product_id',
                'meta_value' => $p['product_id'],
                'numberposts'=> 1,
                'fields'     => 'ids',
            ]);
            $p['wp_post_id']  = $posts[0] ?? null;
            $p['wp_post_url'] = $posts[0] ? get_edit_post_link($posts[0], 'raw') : null;
        }

        return rest_ensure_response(['data' => $products, 'total' => count($products)]);
    }

    public function get_product(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();
        $data   = $client->get_product((int) $req['id'], $req->get_param('locale') ?: 'fr-FR');
        return rest_ensure_response($data);
    }

    public function get_variations(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();
        $data   = $client->get_variations((int) $req['id'], $req->get_param('locale') ?: 'fr-FR');
        return rest_ensure_response(['data' => $data]);
    }

    public function get_crossselling(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();
        $data   = $client->get_crossselling((int) $req['id'], $req->get_param('locale') ?: 'fr-FR');
        return rest_ensure_response(['data' => $data]);
    }

    public function get_navigation_attributes(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();
        $data   = $client->get_navigation_attributes($req->get_param('locale') ?: 'fr-FR');
        return rest_ensure_response(['data' => $data]);
    }

    public function get_categories(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();
        $data   = $client->get_categories($req->get_param('locale') ?: 'fr-FR');
        return rest_ensure_response(['data' => $data]);
    }

    public function get_bookings(\WP_REST_Request $req): \WP_REST_Response {
        $params = array_filter([
            'page'         => (int) ($req->get_param('page') ?: 1),
            'per_page'     => (int) ($req->get_param('per_page') ?: 50),
            'from'         => $req->get_param('from'),
            'to'           => $req->get_param('to'),
            'product_id'   => $req->get_param('product_id'),
            'order_number' => $req->get_param('order_number'),
        ]);
        $client = new Client();
        return rest_ensure_response($client->get_bookings($params));
    }

    public function get_customers(\WP_REST_Request $req): \WP_REST_Response {
        $params = [
            'page'     => (int) ($req->get_param('page') ?: 1),
            'per_page' => (int) ($req->get_param('per_page') ?: 50),
        ];
        $client   = new Client();
        $result   = $client->get_crm_customers($params);

        // Enrich with avis count from sj_avis CPT if available
        if (!empty($result['data']) && post_type_exists('sj_avis')) {
            foreach ($result['data'] as &$customer) {
                $email = $customer['email'] ?? '';
                if ($email) {
                    $count = (new \WP_Query([
                        'post_type'      => 'sj_avis',
                        'posts_per_page' => -1,
                        'fields'         => 'ids',
                        'meta_query'     => [
                            [
                                'key'   => 'avis_customer_email',
                                'value' => $email,
                            ],
                        ],
                    ]))->found_posts;
                    $customer['avis_count'] = $count;
                } else {
                    $customer['avis_count'] = 0;
                }
            }
        }

        return rest_ensure_response($result);
    }

    public function update_newsletter(\WP_REST_Request $req): \WP_REST_Response {
        $email      = sanitize_email($req->get_param('email') ?? '');
        $subscribed = (bool) $req->get_param('subscribed');

        if (!is_email($email)) {
            return new \WP_REST_Response(['error' => 'Email invalide'], 400);
        }

        $client  = new Client();
        $success = $client->update_crm_customer($email, $subscribed);
        return rest_ensure_response(['success' => $success]);
    }

    public function get_dashboard(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();

        // Test API connection first
        $api_status = 'ok';
        $api_error  = null;

        try {
            $products = $client->get_products('fr-FR');
        } catch (\Throwable $e) {
            $products   = [];
            $api_status = 'error';
            $api_error  = $e->getMessage();
        }

        // Get bookings for current month — try multiple approaches
        $bookings = $client->get_bookings([
            'per_page' => 50,
            'from'     => date('Y-m-01'),
            'to'       => date('Y-m-t'),
        ]);

        // If no bookings from partner endpoint, try sold items as fallback
        if (empty($bookings['data']) && ($bookings['total'] ?? 0) === 0) {
            $sold = $client->get_sold_items([
                'per_page' => 50,
                'from'     => date('Y-m-01'),
                'to'       => date('Y-m-t'),
            ]);

            if (!empty($sold['data'])) {
                $bookings = [
                    'data'  => array_map(function($item) {
                        return [
                            'booking_ref'   => $item['order_number'] ?? $item['reference_id'] ?? '',
                            'product_name'  => $item['product_name'] ?? $item['name'] ?? '',
                            'booking_date'  => $item['date'] ?? $item['created_at'] ?? '',
                            'customer_name' => trim(($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')),
                            'total_price'   => $item['total'] ?? $item['price'] ?? null,
                            'currency_code' => $item['currency'] ?? $item['currency_code'] ?? 'EUR',
                            'status'        => $item['status'] ?? 'confirmed',
                        ];
                    }, $sold['data']),
                    'total' => $sold['total'] ?? count($sold['data']),
                ];
            }
        }

        // Calculate revenue from confirmed bookings
        $revenue_month = 0;
        foreach (($bookings['data'] ?? []) as $b) {
            $status = $b['status'] ?? '';
            if ($status === 'confirmed' || $status === '') {
                $revenue_month += floatval($b['total_price'] ?? 0);
            }
        }

        $customers = $client->get_crm_customers(['per_page' => 1]);

        return rest_ensure_response([
            'products_count'   => count($products),
            'bookings_month'   => $bookings['total'] ?? 0,
            'revenue_month'    => round($revenue_month, 2),
            'customers_total'  => $customers['total'] ?? 0,
            'recent_bookings'  => array_slice($bookings['data'] ?? [], 0, 8),
            'api_status'       => $api_status,
            'api_error'        => $api_error,
        ]);
    }

    public function test_connection(): \WP_REST_Response {
        $client = new Client();

        try {
            $products = $client->get_products('fr-FR');

            if (empty($products)) {
                // Could be empty account or wrong keys — try account endpoint
                $account = $client->get_account_locale();
                if (empty($account)) {
                    return rest_ensure_response([
                        'success' => false,
                        'message' => 'Clés API invalides ou compte non trouvé.',
                    ]);
                }
            }

            return rest_ensure_response([
                'success' => true,
                'message' => 'Connexion réussie — ' . count($products) . ' produit(s) trouvé(s).',
            ]);
        } catch (\Throwable $e) {
            return rest_ensure_response([
                'success' => false,
                'message' => 'Erreur : ' . $e->getMessage(),
            ]);
        }
    }

    public function get_settings(): \WP_REST_Response {
        try {
            $products = (new Client())->get_products('fr-FR');
        } catch (\Throwable $e) {
            $products = [];
        }

        $next = wp_next_scheduled('bt_auto_sync');

        return rest_ensure_response([
            'public_key'     => get_option('bt_public_key', ''),
            'secret_key'     => get_option('bt_secret_key', ''),
            'cache_ttl'      => (int) get_option('bt_cache_ttl', 3600),
            'post_types'     => get_option('bt_post_types', ['excursion']),
            'sync_interval'  => (int) get_option('bt_regiondo_sync_interval', 0),
            'sync_next_run'  => $next ?: null,
            'widget_map'     => get_option('bt_widget_map', []),
            'products'       => $products,
            'all_post_types' => array_values(array_map(fn($pt) => [
                'name'  => $pt->name,
                'label' => $pt->label,
            ], get_post_types(['public' => true], 'objects'))),
        ]);
    }

    public function save_settings(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params();

        if (isset($body['public_key'])) {
            update_option('bt_public_key', sanitize_text_field($body['public_key']));
        }
        if (isset($body['secret_key'])) {
            update_option('bt_secret_key', sanitize_text_field($body['secret_key']));
        }
        if (isset($body['cache_ttl'])) {
            update_option('bt_cache_ttl', absint($body['cache_ttl']));
        }
        if (isset($body['post_types']) && is_array($body['post_types'])) {
            update_option('bt_post_types', array_map('sanitize_text_field', $body['post_types']));
        }
        if (isset($body['widget_map']) && is_array($body['widget_map'])) {
            $clean = [];
            foreach ($body['widget_map'] as $pid => $value) {
                $pid_clean = absint($pid);
                if (is_array($value)) {
                    $clean[$pid_clean] = [
                        'widget_id'  => sanitize_text_field($value['widget_id'] ?? ''),
                        'custom_css' => wp_strip_all_tags($value['custom_css'] ?? ''),
                    ];
                } else {
                    // Backward compat: string value = widget_id only
                    $clean[$pid_clean] = [
                        'widget_id'  => sanitize_text_field($value),
                        'custom_css' => '',
                    ];
                }
            }
            update_option('bt_widget_map', $clean);
        }
        if (isset($body['sync_interval'])) {
            $interval = absint($body['sync_interval']);
            update_option('bt_regiondo_sync_interval', $interval);
            $this->reschedule_cron($interval);
        }

        return rest_ensure_response(['success' => true]);
    }

    private function reschedule_cron(int $interval_minutes): void {
        wp_clear_scheduled_hook('bt_auto_sync');
        if ($interval_minutes <= 0) return;

        $recurrence = match ($interval_minutes) {
            30   => 'bt_30min',
            60   => 'hourly',
            360  => 'bt_6hours',
            720  => 'twicedaily',
            1440 => 'daily',
            default => null,
        };

        if ($recurrence) {
            wp_schedule_event(time(), $recurrence, 'bt_auto_sync');
        }
    }

    public function flush_cache(): \WP_REST_Response {
        (new Cache())->flush();
        return rest_ensure_response(['success' => true]);
    }

    public function sync_products(\WP_REST_Request $req): \WP_REST_Response {
        $sync   = new Sync();
        $result = $sync->run(
            $req->get_param('product_id') ? [(int) $req->get_param('product_id')] : null
        );
        return rest_ensure_response($result);
    }
}
