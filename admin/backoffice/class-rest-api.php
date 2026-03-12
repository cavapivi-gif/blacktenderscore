<?php
namespace BlackTenders\Admin\Backoffice;

// Imports cross-namespace uniquement (les classes dans le même namespace n'ont pas besoin de `use`)
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

        // Avis Regiondo
        register_rest_route(self::NS, '/reviews', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reviews'],
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

        // Diagnostic complet (réponses brutes de chaque endpoint)
        register_rest_route(self::NS, '/diagnostic', [
            'methods'             => 'GET',
            'callback'            => [$this, 'run_diagnostic'],
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

        // Stats réservations (charts dashboard — lit depuis la DB locale)
        register_rest_route(self::NS, '/bookings/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_bookings_stats'],
            'permission_callback' => $auth,
        ]);

        // Planificateur (réservations groupées par date — lit depuis la DB locale)
        register_rest_route(self::NS, '/planner', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_planner'],
            'permission_callback' => $auth,
        ]);

        // Sync réservations → DB
        register_rest_route(self::NS, '/bookings/sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'sync_bookings'],
            'permission_callback' => $auth,
        ]);

        // Statut de sync + stats DB
        register_rest_route(self::NS, '/bookings/sync/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_sync_status'],
            'permission_callback' => $auth,
        ]);

        // Reset DB réservations (vide la table + repart de zéro)
        register_rest_route(self::NS, '/bookings/sync/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_bookings_db'],
            'permission_callback' => $auth,
        ]);

        // Import solditems (réservations enrichies) → DB locale
        register_rest_route(self::NS, '/reservations', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reservations'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/reservations/import', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_reservations'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/reservations/import/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reservations_import_status'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/reservations/import/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_reservations_db'],
            'permission_callback' => $auth,
        ]);

        // Import CSV — reçoit un batch de lignes déjà parsées côté JS
        register_rest_route(self::NS, '/reservations/import/csv', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_reservations_csv'],
            'permission_callback' => $auth,
        ]);

        // Re-parse offer_raw → price_total for existing records with NULL price
        register_rest_route(self::NS, '/reservations/reparse-prices', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reparse_prices'],
            'permission_callback' => $auth,
        ]);

        // ── Avis clients (import CSV Regiondo) ────────────────────────────────
        register_rest_route(self::NS, '/avis', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_avis'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/avis/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_avis_stats'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/avis/import/csv', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_avis_csv'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/avis/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_avis_db'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/avis/by-email', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_avis_by_email'],
            'permission_callback' => $auth,
        ]);

        // Import participations (stats externes) → DB locale
        register_rest_route(self::NS, '/participations/import/csv', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_participations_csv'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/participations/import/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_participations_import_status'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/participations/import/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_participations_db'],
            'permission_callback' => $auth,
        ]);

        // Onboarding wizard
        register_rest_route(self::NS, '/onboarding/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_onboarding_status'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/onboarding/setup', [
            'methods'             => 'POST',
            'callback'            => [$this, 'run_onboarding_setup'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/onboarding/complete', [
            'methods'             => 'POST',
            'callback'            => [$this, 'complete_onboarding'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/onboarding/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_onboarding'],
            'permission_callback' => $auth,
        ]);
    }

    // ─── Handlers ─────────────────────────────────────────────────────────────

    public function get_products(\WP_REST_Request $req): \WP_REST_Response {
        $locale   = $req->get_param('locale') ?: 'fr-FR';
        $client   = new Client();
        $products = $client->get_products($locale);

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

    /**
     * Liste paginée des réservations depuis la DB locale.
     * Paramètres : page, per_page, from, to, status, product_id, search (ou order_number legacy).
     */
    public function get_bookings(\WP_REST_Request $req): \WP_REST_Response {
        $db     = new Db();
        $result = $db->query_bookings([
            'page'       => (int)    ($req->get_param('page')     ?: 1),
            'per_page'   => (int)    ($req->get_param('per_page') ?: 50),
            'from'       => (string) ($req->get_param('from')       ?: ''),
            'to'         => (string) ($req->get_param('to')         ?: ''),
            'status'     => (string) ($req->get_param('status')     ?: ''),
            'product_id' => (string) ($req->get_param('product_id') ?: ''),
            // Support 'search' (nouveau) et 'order_number' (legacy)
            'search'     => (string) ($req->get_param('search') ?: $req->get_param('order_number') ?: ''),
        ]);

        // Ajouter booking_date comme alias de activity_date pour compat frontend
        $result['data'] = array_map(function (array $row): array {
            $row['booking_date'] = $row['activity_date'] ?? null;
            return $row;
        }, $result['data']);

        return rest_ensure_response($result);
    }

    public function get_customers(\WP_REST_Request $req): \WP_REST_Response {
        $page     = (int) ($req->get_param('page') ?: 1);
        $per_page = (int) ($req->get_param('per_page') ?: 50);
        $search   = $req->get_param('search') ?: null;
        $sort_key = $req->get_param('sort_key') ?: 'last_booking';
        $sort_dir = $req->get_param('sort_dir') ?: 'desc';

        // Use local DB (bt_reservations) — Regiondo CRM API returns 404 for supplier accounts
        $db     = new ReservationDb();
        $result = $db->query_customers($page, $per_page, $search, $sort_key, $sort_dir);

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

    public function get_reviews(\WP_REST_Request $req): \WP_REST_Response {
        $client = new Client();
        $data   = $client->get_reviews(array_filter([
            'product_id' => $req->get_param('product_id'),
            'limit'      => (int) ($req->get_param('limit') ?: 250),
        ]));
        return rest_ensure_response($data);
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

        // Stats depuis la DB locale (bt_reservations — solditems importés)
        // Plus fiable et instantané que l'API Regiondo.
        $local_db = new ReservationDb();
        $summary  = $local_db->get_summary();

        // Customer count from local DB (Regiondo CRM API returns 404 for supplier accounts)
        $customers = $local_db->query_customers(1, 1);

        return rest_ensure_response([
            'products_count'   => count($products),
            'bookings_month'   => $summary['bookings_month'],
            'total_in_db'      => $summary['total_in_db'],
            'revenue_month'    => $summary['revenue_month'],
            'customers_total'  => $customers['total'] ?? 0,
            'recent_bookings'  => $summary['recent_bookings'],
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

    public function run_diagnostic(): \WP_REST_Response {
        $client = new Client();

        // Get first product ID for reviews test (reviews API requires product_id)
        $first_product_id = null;
        try {
            $products = $client->get_products('fr-FR');
            if (!empty($products[0]['product_id'])) {
                $first_product_id = $products[0]['product_id'];
            }
        } catch (\Throwable $e) {}

        $endpoints = [
            ['label' => 'Products',            'path' => 'products',              'params' => ['limit' => 5, 'store_locale' => 'fr-FR']],
            ['label' => 'Supplier Sold Items',  'path' => 'supplier/solditems',    'params' => ['limit' => 5]],
            ['label' => 'Reviews',              'path' => 'reviews',               'params' => array_filter(['limit' => 5, 'product_id' => $first_product_id])],
            ['label' => 'Categories',           'path' => 'categories',            'params' => ['limit' => 5]],
            ['label' => 'Account Locale',       'path' => 'account/locale',        'params' => []],
        ];

        $results = [];
        foreach ($endpoints as $ep) {
            $results[] = array_merge(
                ['label' => $ep['label']],
                $client->raw_request($ep['path'], $ep['params'])
            );
        }

        return rest_ensure_response(['endpoints' => $results]);
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
            'widget_map'          => get_option('bt_widget_map', []),
            'booking_custom_css'  => get_option('bt_booking_custom_css', ''),
            'booking_custom_js'   => get_option('bt_booking_custom_js', ''),
            'map_style_json'      => get_option('bt_map_style_json', ''),
            'map_presets'         => get_option('bt_map_presets', []),
            'maps_api_key'        => get_option('elementor_google_maps_api_key', ''),
            'products'            => $products,
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
        if (isset($body['booking_custom_css'])) {
            $css = wp_strip_all_tags($body['booking_custom_css']);
            // Strip CSS-based XSS vectors
            $css = preg_replace('/expression\s*\(/i', '/* blocked */(', $css);
            $css = preg_replace('/javascript\s*:/i', '/* blocked */', $css);
            $css = preg_replace('/-moz-binding\s*:/i', '/* blocked */', $css);
            $css = preg_replace('/behavior\s*:/i', '/* blocked */', $css);
            $css = preg_replace('/url\s*\(\s*["\']?\s*data\s*:\s*text\/html/i', 'url(/* blocked */', $css);
            $css = preg_replace('/@import\s+url/i', '/* blocked */', $css);
            update_option('bt_booking_custom_css', $css);
        }
        if (isset($body['booking_custom_js'])) {
            // Champ admin uniquement — on neutralise juste la balise fermante </script>
            // pour éviter une injection HTML dans le output. Pas de strip_tags : ça détruirait le JS.
            $js = str_replace('</script', '<\\/script', (string) $body['booking_custom_js']);
            update_option('bt_booking_custom_js', $js);
        }
        if (isset($body['sync_interval'])) {
            $interval = absint($body['sync_interval']);
            update_option('bt_regiondo_sync_interval', $interval);
            $this->reschedule_cron($interval);
        }
        if (isset($body['map_style_json'])) {
            $json = wp_strip_all_tags($body['map_style_json']);
            $decoded = json_decode($json, true);
            update_option('bt_map_style_json', is_array($decoded) ? $json : '');
        }
        if (isset($body['map_presets']) && is_array($body['map_presets'])) {
            $clean = [];
            foreach ($body['map_presets'] as $p) {
                if (empty($p['id']) || empty($p['name']) || empty($p['json'])) continue;
                $id      = sanitize_key($p['id']);
                $name    = sanitize_text_field($p['name']);
                $json    = wp_strip_all_tags($p['json']);
                $decoded = json_decode($json, true);
                if (!$id || !$name || !is_array($decoded)) continue;
                $clean[] = ['id' => $id, 'name' => $name, 'json' => $json];
            }
            update_option('bt_map_presets', $clean);
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

    /**
     * Retourne les stats de réservations pour les charts du dashboard.
     * Lit depuis la DB locale (wp_bt_bookings) — rapide, pas d'appel API.
     *
     * Paramètres GET supportés:
     *   from         YYYY-MM-DD (défaut: premier jour d'il y a 11 mois)
     *   to           YYYY-MM-DD (défaut: aujourd'hui)
     *   granularity  day|week|month (défaut: month)
     *   compare_from YYYY-MM-DD — début de la période de comparaison (optionnel)
     *   compare_to   YYYY-MM-DD — fin de la période de comparaison (optionnel)
     */
    public function get_bookings_stats(\WP_REST_Request $req): \WP_REST_Response {
        $db = new ReservationDb(); // lit bt_reservations (solditems importés)

        // ── Validation des paramètres ──────────────────────────────────────
        $granularity = in_array($req->get_param('granularity'), ['day', 'week', 'month'], true)
            ? $req->get_param('granularity')
            : 'month';

        $from = $req->get_param('from');
        $to   = $req->get_param('to');
        if (!$from || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            $from = date('Y-m-d', strtotime('first day of -11 months'));
        }
        if (!$to || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $to = date('Y-m-d');
        }

        $compare_from = $req->get_param('compare_from');
        $compare_to   = $req->get_param('compare_to');
        $has_compare  = $compare_from && $compare_to
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $compare_from)
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $compare_to);

        // ── Lecture depuis la DB locale ────────────────────────────────────
        $raw_rows = $db->query_stats($from, $to, $granularity);
        $top_raw  = $db->query_top_products($from, $to, 8);
        // Use enhanced KPIs if available
        $include_raw = $req->get_param('include') ?? '';
        $includes = $include_raw ? array_map('trim', explode(',', $include_raw)) : [];
        // Always use enhanced KPIs (includes unique_customers, repeat_rate, etc.)
        $kpis = $db->query_enhanced_kpis($from, $to);
        $by_chan  = $db->query_by_channel($from, $to, 10);
        $by_wday  = $db->query_by_weekday($from, $to);

        // Indexer les résultats DB par période
        $db_by_key = [];
        $total_bookings = 0;
        foreach ($raw_rows as $row) {
            $db_by_key[$row['period_key']] = [
                'bookings'   => (int)   $row['bookings'],
                'revenue'    => (float) $row['revenue'],
                'cancelled'  => (int)   $row['cancelled'],
                'avg_basket' => $row['avg_basket'] !== null ? (float) $row['avg_basket'] : null,
            ];
            $total_bookings += (int) $row['bookings'];
        }

        // ── Construire les périodes (toutes, y compris celles sans données) ─
        $periods        = $this->build_periods($from, $to, $granularity);
        $result_periods = [];
        $peak_bookings  = 0;
        $peak_revenue   = 0.0;
        $peak_basket    = 0.0;

        foreach ($periods as $p) {
            $b  = $db_by_key[$p['key']]['bookings']   ?? 0;
            $r  = $db_by_key[$p['key']]['revenue']    ?? 0.0;
            $ab = $db_by_key[$p['key']]['avg_basket'] ?? null;
            $ca = $db_by_key[$p['key']]['cancelled']  ?? 0;
            $result_periods[] = [
                'key'        => $p['key'],
                'label'      => $p['label'],
                'bookings'   => $b,
                'revenue'    => round($r, 2),
                'cancelled'  => $ca,
                'avg_basket' => $ab !== null ? round($ab, 2) : null,
            ];
            $peak_bookings = max($peak_bookings, $b);
            $peak_revenue  = max($peak_revenue,  $r);
            if ($ab !== null) $peak_basket = max($peak_basket, $ab);
        }

        // ── Période de comparaison ─────────────────────────────────────────
        $compare_periods = [];
        $kpis_cmp = [];
        if ($has_compare) {
            $cmp_raw    = $db->query_stats($compare_from, $compare_to, $granularity);
            $kpis_cmp   = $db->query_period_kpis($compare_from, $compare_to);
            $cmp_by_key = [];
            foreach ($cmp_raw as $row) {
                $cmp_by_key[$row['period_key']] = [
                    'bookings'   => (int)   $row['bookings'],
                    'revenue'    => (float) $row['revenue'],
                    'cancelled'  => (int)   $row['cancelled'],
                    'avg_basket' => $row['avg_basket'] !== null ? (float) $row['avg_basket'] : null,
                ];
            }
            foreach ($this->build_periods($compare_from, $compare_to, $granularity) as $p) {
                $compare_periods[] = [
                    'key'        => $p['key'],
                    'label'      => $p['label'],
                    'bookings'   => $cmp_by_key[$p['key']]['bookings']   ?? 0,
                    'revenue'    => round($cmp_by_key[$p['key']]['revenue']    ?? 0.0, 2),
                    'cancelled'  => $cmp_by_key[$p['key']]['cancelled']  ?? 0,
                    'avg_basket' => isset($cmp_by_key[$p['key']]['avg_basket'])
                        ? round($cmp_by_key[$p['key']]['avg_basket'], 2)
                        : null,
                ];
            }
        }

        // ── Top produits avec revenue ──────────────────────────────────────
        $top_products = array_map(fn($r) => [
            'name'    => $r['name'],
            'count'   => (int) $r['count'],
            'revenue' => (float) $r['revenue'],
        ], $top_raw);

        // ── Jours de semaine : normalise DOW vers libellés FR ─────────────
        $dow_labels = [1 => 'Dim', 2 => 'Lun', 3 => 'Mar', 4 => 'Mer', 5 => 'Jeu', 6 => 'Ven', 7 => 'Sam'];
        $by_weekday = array_map(fn($r) => [
            'dow'      => (int) $r['dow'],
            'label'    => $dow_labels[(int) $r['dow']] ?? $r['dow'],
            'bookings' => (int) $r['bookings'],
            'revenue'  => (float) $r['revenue'],
        ], $by_wday);

        // ── Canaux de vente (normalize: strip trailing IDs/codes) ─────────
        // "Funbooker 35409", "Funbooker 35410" → "Funbooker"
        // "GetYourGuide Deutschland GmbH GYGZG2LHHQG" → "GetYourGuide Deutschland GmbH"
        $chan_grouped = [];
        foreach ($by_chan as $r) {
            $name = trim($r['channel']);
            // Strip trailing alphanumeric ID (e.g. "Funbooker 35409" or "GYG GYGZG2LHHQG")
            $normalized = preg_replace('/\s+[A-Z0-9]{5,}$/i', '', $name);
            // Also strip trailing pure numeric IDs (e.g. "Funbooker 35409")
            $normalized = preg_replace('/\s+\d+$/', '', $normalized);
            $normalized = trim($normalized) ?: $name;

            if (!isset($chan_grouped[$normalized])) {
                $chan_grouped[$normalized] = ['channel' => $normalized, 'bookings' => 0, 'revenue' => 0.0];
            }
            $chan_grouped[$normalized]['bookings'] += (int) $r['bookings'];
            $chan_grouped[$normalized]['revenue']  += (float) $r['revenue'];
        }
        // Re-sort by bookings desc
        usort($chan_grouped, fn($a, $b) => $b['bookings'] <=> $a['bookings']);
        $by_channel = array_values($chan_grouped);

        return rest_ensure_response([
            // Données de chart par période
            'periods'        => $result_periods,
            'compare'        => $compare_periods,
            // KPIs globaux période principale
            'kpis'           => $kpis,
            'kpis_compare'   => $kpis_cmp ?: null,
            // Distributions
            'by_product'     => $top_products,
            'by_channel'     => $by_channel,
            'by_weekday'     => $by_weekday,
            // Pics
            'total'          => $total_bookings,
            'peak_bookings'  => $peak_bookings,
            'peak_revenue'   => round($peak_revenue, 2),
            'peak_basket'    => round($peak_basket, 2),
            // Meta
            'granularity'    => $granularity,
            'period_start'   => $from,
            'period_end'     => $to,
            'monthly'        => $result_periods, // compat legacy
            // ── Optional data modules (loaded via ?include=...) ──────────────
            'heatmap'        => in_array('heatmap', $includes) ? $db->query_heatmap($from, $to) : null,
            'payments'       => in_array('payments', $includes) ? [
                'by_method' => $db->query_by_payment_method($from, $to),
                'by_status' => $db->query_by_payment_status($from, $to),
            ] : null,
            'lead_time_buckets'         => in_array('lead_time_buckets', $includes) ? $db->query_lead_time_buckets($from, $to) : null,
            'lead_time_buckets_compare' => (in_array('lead_time_buckets', $includes) && $has_compare) ? $db->query_lead_time_buckets($compare_from, $compare_to) : null,
            'lead_time'      => in_array('lead_time', $includes) ? $db->query_lead_time($from, $to) : null,
            'repeat_customers' => in_array('repeat_customers', $includes) ? $db->query_repeat_customers($from, $to) : null,
            'product_mix'    => in_array('product_mix', $includes) ? $db->query_product_mix($from, $to, $granularity) : null,
            'channel_status' => in_array('channel_status', $includes) ? $db->query_channel_status($from, $to) : null,
            'yoy'            => in_array('yoy', $includes) ? $db->query_yoy($from, $to) : null,
            'cumulative'     => in_array('cumulative', $includes) ? $db->query_cumulative($from, $to, $granularity) : null,
        ]);
    }

    /**
     * Construit la liste des périodes (clé + libellé) entre $from et $to.
     *
     * @param string $from        YYYY-MM-DD
     * @param string $to          YYYY-MM-DD
     * @param string $granularity day|week|month
     * @return array<array{key: string, label: string}>
     */
    private function build_periods(string $from, string $to, string $granularity): array {
        $periods = [];
        $cursor  = new \DateTime($from);
        $end     = new \DateTime($to);

        while ($cursor <= $end) {
            $key = $this->period_key($cursor->format('Y-m-d'), $granularity);
            if (empty($periods) || end($periods)['key'] !== $key) {
                $periods[] = [
                    'key'   => $key,
                    'label' => $this->format_period_label($key, $granularity),
                ];
            }
            $cursor->modify($granularity === 'day' ? '+1 day' : ($granularity === 'week' ? '+1 week' : '+1 month'));
        }

        return $periods;
    }

    /**
     * Retourne la clé de période pour une date donnée.
     *
     * @param string $date        YYYY-MM-DD (peut être tronqué)
     * @param string $granularity day|week|month
     * @return string Ex: "2026-03-12" | "2026-W10" | "2026-03"
     */
    private function period_key(string $date, string $granularity): string {
        if (strlen($date) < 10) return '';
        $ts = strtotime($date);
        if (!$ts) return '';
        return match ($granularity) {
            'day'   => date('Y-m-d', $ts),
            'week'  => date('Y', $ts) . '-W' . date('W', $ts),
            default => date('Y-m', $ts),
        };
    }

    /**
     * Retourne un libellé court français pour l'axe X des charts.
     *
     * @param string $key         Clé de période (YYYY-MM-DD | YYYY-Wnn | YYYY-MM)
     * @param string $granularity day|week|month
     */
    private function format_period_label(string $key, string $granularity): string {
        static $month_labels = [
            '01' => 'Jan', '02' => 'Fév', '03' => 'Mar', '04' => 'Avr',
            '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aoû',
            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Déc',
        ];

        if ($granularity === 'day') {
            // YYYY-MM-DD → "12 Mar"
            $ts = strtotime($key);
            return $ts ? date('j', $ts) . ' ' . ($month_labels[date('m', $ts)] ?? '') : $key;
        }

        if ($granularity === 'week') {
            // YYYY-Wnn → "S10 '26"
            [$year, $week] = explode('-W', $key . '-W');
            return 'S' . ltrim($week, '0') . " '" . substr($year, 2);
        }

        // month: YYYY-MM → "Mar 26"
        [$year, $month_num] = explode('-', $key . '-');
        return ($month_labels[$month_num] ?? $month_num) . ' ' . substr($year, 2);
    }

    /**
     * Planificateur — réservations groupées par date d'activité.
     * Lit depuis la DB locale (wp_bt_bookings) — pas d'appel API.
     *
     * Accepte soit ?from=YYYY-MM-DD&to=YYYY-MM-DD (navigation mensuelle)
     * soit ?days=N (horizon glissant, 7-90, défaut 30).
     */
    public function get_planner(\WP_REST_Request $req): \WP_REST_Response {
        $db   = new ReservationDb(); // lit bt_reservations (solditems importés)
        $from = $req->get_param('from');
        $to   = $req->get_param('to');

        if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = null;
        if ($to   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = null;

        if (!$from || !$to) {
            $days = (int) ($req->get_param('days') ?: 30);
            $days = min(90, max(7, $days));
            $from = date('Y-m-01');
            $to   = date('Y-m-t', strtotime("+$days days"));
        }

        $calendar           = $db->query_calendar($from, $to);
        $total              = array_sum(array_column($calendar, 'count'));
        $lead_time_buckets  = $db->query_lead_time_buckets($from, $to);

        return rest_ensure_response([
            'calendar'          => $calendar,
            'total'             => $total,
            'from'              => $from,
            'to'                => $to,
            'lead_time_buckets' => $lead_time_buckets,
        ]);
    }

    /**
     * Lance la synchronisation d'une période vers la DB.
     * Appelé par le frontend année par année pour la sync complète.
     *
     * Body JSON: { "from": "YYYY-MM-DD", "to": "YYYY-MM-DD" }
     * Ou :       { "year": 2023 }
     */
    public function sync_bookings(\WP_REST_Request $req): \WP_REST_Response {
        // Deprecated: /partner/bookings returns 401 for supplier accounts.
        // Use Import solditems (/reservations/import) instead.
        return new \WP_REST_Response([
            'error' => 'Endpoint désactivé. L\'API /partner/bookings retourne 401 pour les comptes supplier. Utilisez Import solditems à la place.',
        ], 410); // 410 Gone
    }

    /** Retourne les stats de la DB locale et le statut de la dernière sync. */
    public function get_sync_status(): \WP_REST_Response {
        return rest_ensure_response((new Db())->get_sync_status());
    }

    /** Vide complètement la table bt_bookings et remet le statut à zéro. */
    public function reset_bookings_db(): \WP_REST_Response {
        (new Db())->truncate();
        return rest_ensure_response(['success' => true]);
    }

    // ─── Handlers — Import réservations (solditems) ────────────────────────────

    /**
     * Liste paginée des articles vendus depuis la DB locale.
     * Paramètres : page, per_page, from, to, status, search.
     */
    public function get_reservations(\WP_REST_Request $req): \WP_REST_Response {
        $db     = new ReservationDb();
        $result = $db->query([
            'page'     => (int)    ($req->get_param('page')     ?: 1),
            'per_page' => (int)    ($req->get_param('per_page') ?: 50),
            'from'     => (string) ($req->get_param('from')     ?: ''),
            'to'       => (string) ($req->get_param('to')       ?: ''),
            'status'   => (string) ($req->get_param('status')   ?: ''),
            'search'   => (string) ($req->get_param('search')   ?: ''),
        ]);
        return rest_ensure_response($result);
    }

    /**
     * Lance l'import d'une période de solditems vers la DB.
     * Body JSON : { "year": 2023 } ou { "from": "YYYY-MM-DD", "to": "YYYY-MM-DD" }.
     */
    public function import_reservations(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params();
        $db   = new ReservationDb();

        if (!empty($body['year'])) {
            $y    = (int) $body['year'];
            $from = "{$y}-01-01";
            $to   = "{$y}-12-31";
        } else {
            $from = sanitize_text_field($body['from'] ?? '');
            $to   = sanitize_text_field($body['to']   ?? '');
        }

        if (!$from || !$to
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
            || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)
        ) {
            return new \WP_REST_Response(['error' => 'Paramètres from/to ou year requis.'], 400);
        }

        $sync   = new ReservationSync();
        $result = $sync->import_period($from, $to);

        // Mémoriser l'année synchronisée
        $status = $db->get_sync_status();
        $years  = $status['years_synced'] ?? [];
        if (!empty($body['year'])) {
            $years[] = (int) $body['year'];
            $years   = array_unique($years);
            sort($years);
            $db->update_sync_status(['years_synced' => $years]);
        }
        $db->update_sync_status(['last_import' => current_time('mysql', true)]);

        return rest_ensure_response(array_merge($result, ['db' => $db->get_sync_status()]));
    }

    /** Retourne le statut de la dernière importation de réservations. */
    public function get_reservations_import_status(): \WP_REST_Response {
        return rest_ensure_response((new ReservationDb())->get_sync_status());
    }

    /** Vide complètement la table bt_reservations. */
    public function reset_reservations_db(): \WP_REST_Response {
        (new ReservationDb())->truncate();
        return rest_ensure_response(['success' => true]);
    }

    /**
     * Importe un batch de lignes parsées côté JS (export CSV Regiondo).
     * Body JSON attendu : { "items": [ { calendar_sold_id, … }, … ] }
     * Délégue l'upsert à ReservationDb::upsert().
     */
    public function import_reservations_csv(\WP_REST_Request $req): \WP_REST_Response {
        $body  = $req->get_json_params();
        $items = $body['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne reçue.'], 400);
        }
        if (count($items) > 1000) {
            return new \WP_REST_Response(['error' => 'Trop de lignes par batch (max 1000).'], 400);
        }

        $sanitized = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $cal_id = trim($item['calendar_sold_id'] ?? '');
            if (empty($cal_id)) continue;

            $offer_raw    = wp_strip_all_tags($item['offer_raw'] ?? '');
            $price_total  = ($item['price_total'] ?? null) !== null ? (float) $item['price_total'] : null;
            $product_name = sanitize_text_field($item['product_name'] ?? '');
            $quantity     = max(1, (int) ($item['quantity'] ?? 1));

            // PHP-side fallback: parse offer_raw if JS didn't extract fields
            if ($offer_raw !== '') {
                if ($price_total === null) {
                    $price_total = self::parse_price_from_offer_raw($offer_raw);
                }
                if ($product_name === '') {
                    $product_name = self::parse_product_from_offer_raw($offer_raw) ?? '';
                }
                $parsed_qty = self::parse_quantity_from_offer_raw($offer_raw);
                if ($parsed_qty !== null && ($item['quantity'] ?? null) === null) {
                    $quantity = $parsed_qty;
                }
            }

            $sanitized[] = [
                'calendar_sold_id'   => sanitize_text_field($cal_id),
                'order_increment_id' => sanitize_text_field($item['order_increment_id'] ?? '') ?: null,
                'created_at'         => sanitize_text_field($item['created_at'] ?? ''),
                'offer_raw'          => $offer_raw,
                'product_name'       => $product_name,
                'quantity'           => $quantity,
                'price_total'        => $price_total,
                'buyer_name'         => sanitize_text_field($item['buyer_name'] ?? ''),
                'buyer_email'        => sanitize_email($item['buyer_email'] ?? ''),
                'appointment_date'   => self::parse_appointment_date(sanitize_text_field($item['appointment_date'] ?? '')),
                'channel'            => self::sanitize_channel(sanitize_text_field($item['channel'] ?? '')),
                'booking_status'     => self::normalize_booking_status(sanitize_text_field($item['booking_status'] ?? '')),
                'payment_method'     => sanitize_text_field($item['payment_method'] ?? ''),
                'payment_status'     => self::normalize_payment_status(sanitize_text_field($item['payment_status'] ?? '')),
                'booking_key'        => sanitize_text_field($item['booking_key'] ?? ''),
                'buyer_country'      => strtoupper(substr(sanitize_text_field($item['buyer_country'] ?? ''), 0, 5)),
            ];
        }

        if (empty($sanitized)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne valide (calendar_sold_id manquant ?).'], 400);
        }

        return rest_ensure_response((new ReservationDb())->upsert($sanitized));
    }

    /**
     * Parse French appointment date to MySQL DATE.
     * Handles: "01 juin 2026 18:00", "1 juin 2026", "01/06/2026", ISO dates.
     * Returns empty string if unparseable (DB will store NULL).
     */
    private static function parse_appointment_date(string $raw): string {
        if (empty($raw)) return '';

        // Already ISO format YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $raw)) {
            return substr($raw, 0, 10);
        }

        // French month names → number
        static $months = [
            'janvier'=>'01','février'=>'02','fevrier'=>'02','mars'=>'03',
            'avril'=>'04','mai'=>'05','juin'=>'06','juillet'=>'07',
            'août'=>'08','aout'=>'08','septembre'=>'09','octobre'=>'10',
            'novembre'=>'11','décembre'=>'12','decembre'=>'12',
        ];

        // "01 juin 2026 18:00" or "1 juin 2026"
        if (preg_match('/(\d{1,2})\s+(\S+)\s+(\d{4})/u', strtolower($raw), $m)) {
            $month = $months[$m[2]] ?? null;
            if ($month) {
                return $m[3] . '-' . $month . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
            }
        }

        // "01/06/2026" or "1/6/2026"
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})#', $raw, $m)) {
            return $m[3] . '-' . str_pad($m[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
        }

        return '';
    }

    /**
     * Normalize booking_status: handles French values from Regiondo CSV exports.
     */
    private static function normalize_booking_status(string $status): string {
        static $map = [
            'confirmé'                          => 'confirmed',
            'confirmé (bon enregistré)'         => 'confirmed',
            'confirmé (bon cadeau)'             => 'confirmed',
            'annulé'                            => 'cancelled',
            'annulé (commercial)'               => 'cancelled',
            'annulé (regiondo)'                 => 'cancelled',
            'annulé (paiement non effectué)'    => 'cancelled',
            'refusé'                            => 'rejected',
            'échu'                              => 'expired',
            'en attente'                        => 'pending',
            'remboursé'                         => 'refunded',
        ];

        $lower = mb_strtolower(trim($status), 'UTF-8');
        return $map[$lower] ?? $status;
    }

    /**
     * Sanitize channel: strip HTML </br> tags and booking reference codes appended by Regiondo.
     * "GetYourGuide Deutschland GmbH </br>GYGRFQWKLZWK" → "GetYourGuide Deutschland GmbH"
     */
    private static function sanitize_channel(string $channel): string {
        // Strip from </br> onwards (Regiondo appends booking ref this way)
        $channel = preg_replace('/<\/?\s*br\s*\/?>.*/si', '', $channel);
        // Strip any remaining HTML tags
        $channel = wp_strip_all_tags($channel);
        return trim($channel);
    }

    /**
     * Normalize payment_status: extract canonical state from descriptive strings.
     * "Payé (Carte de crédit) xxxx 2975" → "paid"
     */
    private static function normalize_payment_status(string $status): string {
        $lower = mb_strtolower(trim($status), 'UTF-8');
        if (str_starts_with($lower, 'payé') || str_starts_with($lower, 'paid') || str_starts_with($lower, 'completed') || str_starts_with($lower, 'succeeded')) {
            return 'paid';
        }
        if (str_contains($lower, 'non payé') || str_contains($lower, 'impayé') || $lower === 'unpaid') {
            return 'unpaid';
        }
        if (str_starts_with($lower, 'remboursé') || str_starts_with($lower, 'refunded')) {
            return 'refunded';
        }
        if (str_starts_with($lower, 'en attente') || str_starts_with($lower, 'pending') || str_starts_with($lower, 'processing')) {
            return 'pending';
        }
        return $status;
    }

    /**
     * Parse "Montant total: 55,00 €" from offer_raw string → float price.
     */
    private static function parse_price_from_offer_raw(string $raw): ?float {
        if (preg_match('/Montant\s+total\s*:\s*([\d\s.,]+)\s*€/i', $raw, $m)) {
            $price_str = str_replace([' ', ','], ['', '.'], $m[1]);
            $val = (float) $price_str;
            return $val > 0 ? $val : null;
        }
        return null;
    }

    /**
     * Parse quantity from offer_raw: "1 ×" or "2 x"
     */
    private static function parse_quantity_from_offer_raw(string $raw): ?int {
        if (preg_match('/(\d+)\s*[×x]/i', $raw, $m)) {
            return max(1, (int) $m[1]);
        }
        return null;
    }

    /**
     * Parse product name from offer_raw: text before "N ×"
     */
    private static function parse_product_from_offer_raw(string $raw): ?string {
        if (preg_match('/^(.+?)\s+\d+\s*[×x]\s/i', $raw, $m)) {
            return trim(preg_replace('/\s+/', ' ', $m[1]));
        }
        // Fallback: text before "Montant total"
        $parts = preg_split('/Montant\s+total/i', $raw);
        if (!empty($parts[0])) {
            return trim(preg_replace('/[\s,]+$/', '', preg_replace('/\s+/', ' ', $parts[0])));
        }
        return null;
    }

    /**
     * Re-parse offer_raw → price_total/product_name/quantity for rows where price_total IS NULL.
     * This fixes historical imports that didn't parse _produit_raw.
     */
    public function reparse_prices(): \WP_REST_Response {
        global $wpdb;
        $table = $wpdb->prefix . 'bt_reservations';

        // ── Phase 1: Fix appointment_date NULL / 0000-00-00 → use created_at ────
        $dates_fixed = (int) $wpdb->query(
            "UPDATE `{$table}`
             SET appointment_date = DATE(created_at)
             WHERE (appointment_date IS NULL OR appointment_date = '0000-00-00')
               AND created_at IS NOT NULL"
        );

        // ── Phase 2: Parse price_total from offer_raw ────────────────────
        // Small batch (200) to avoid Cloudflare 525 timeout
        $rows = $wpdb->get_results(
            "SELECT id, offer_raw FROM `{$table}`
             WHERE price_total IS NULL AND offer_raw IS NOT NULL AND offer_raw != ''
             LIMIT 200",
            ARRAY_A
        );

        $prices_fixed = 0;
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $raw   = $row['offer_raw'];
                $price = self::parse_price_from_offer_raw($raw);
                if ($price === null) continue;

                $sets   = ['price_total = %f'];
                $values = [$price];

                $qty = self::parse_quantity_from_offer_raw($raw);
                if ($qty !== null) {
                    $sets[]   = 'quantity = %d';
                    $values[] = $qty;
                }

                $name = self::parse_product_from_offer_raw($raw);
                if ($name !== null) {
                    $sets[]   = 'product_name = %s';
                    $values[] = $name;
                }

                $values[] = (int) $row['id'];
                $wpdb->query($wpdb->prepare(
                    "UPDATE `{$table}` SET " . implode(', ', $sets) . " WHERE id = %d",
                    ...$values
                ));
                $prices_fixed++;
            }
        }

        // Check remaining
        $remaining_prices = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$table}` WHERE price_total IS NULL AND offer_raw IS NOT NULL AND offer_raw != ''"
        );
        $remaining_dates = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `{$table}` WHERE (appointment_date IS NULL OR appointment_date = '0000-00-00') AND created_at IS NOT NULL"
        );
        $remaining = $remaining_prices + $remaining_dates;

        return rest_ensure_response([
            'updated'      => $prices_fixed + $dates_fixed,
            'prices_fixed' => $prices_fixed,
            'dates_fixed'  => $dates_fixed,
            'remaining'    => $remaining,
            'message'      => $remaining > 0
                ? "Corrigé {$prices_fixed} prix + {$dates_fixed} dates, encore {$remaining} à traiter."
                : "Terminé — {$prices_fixed} prix + {$dates_fixed} dates corrigés.",
        ]);
    }

    public function flush_cache(): \WP_REST_Response {
        (new Cache())->flush();
        return rest_ensure_response(['success' => true]);
    }

    // ─── Onboarding ───────────────────────────────────────────────────────────

    /**
     * Retourne le statut de chaque prérequis.
     * Utilisé par le wizard pour afficher les checks visuels.
     */
    public function get_onboarding_status(): \WP_REST_Response {
        global $wpdb;

        $table = $wpdb->prefix . 'bt_reservations';

        return rest_ensure_response([
            'php_version'    => [
                'ok'    => version_compare(PHP_VERSION, '8.0', '>='),
                'value' => PHP_VERSION,
            ],
            'openssl'        => ['ok' => function_exists('openssl_encrypt')],
            'db_table'       => [
                'ok' => $wpdb->get_var("SHOW TABLES LIKE '{$table}'") === $table,
            ],
            'encryption_key' => [
                'ok' => defined('BT_ENCRYPTION_KEY') && strlen(BT_ENCRYPTION_KEY) >= 32,
            ],
            'api_key'        => ['ok' => !empty(get_option('bt_public_key', ''))],
            'done'           => (bool) get_option('bt_onboarding_done', false),
        ]);
    }

    /**
     * Crée la table bt_reservations + sauvegarde les clés API si fournies.
     * Body JSON optionnel : { "public_key": "...", "secret_key": "..." }
     */
    public function run_onboarding_setup(\WP_REST_Request $req): \WP_REST_Response {
        (new ReservationDb())->ensure_table();

        $body = $req->get_json_params();

        if (!empty($body['public_key'])) {
            update_option('bt_public_key', sanitize_text_field($body['public_key']));
        }
        if (!empty($body['secret_key'])) {
            update_option('bt_secret_key', sanitize_text_field($body['secret_key']));
        }

        return rest_ensure_response(['success' => true]);
    }

    /** Marque l'onboarding comme terminé (sauvegarde l'option bt_onboarding_done). */
    public function complete_onboarding(): \WP_REST_Response {
        update_option('bt_onboarding_done', true, false);
        return rest_ensure_response(['success' => true]);
    }

    /** Réinitialise l'onboarding — le wizard réapparaît au prochain chargement de la page. */
    public function reset_onboarding(): \WP_REST_Response {
        update_option('bt_onboarding_done', false, false);
        return rest_ensure_response(['success' => true]);
    }

    // ── Avis ──────────────────────────────────────────────────────────────────

    /**
     * Liste paginée des avis importés depuis bt_reviews.
     * Params : page, per_page, search, product, rating, from, to, sort, dir
     */
    public function get_avis(\WP_REST_Request $req): \WP_REST_Response {
        $db     = new ReviewsDb();
        $result = $db->get_reviews([
            'page'     => (int)    ($req->get_param('page')     ?: 1),
            'per_page' => (int)    ($req->get_param('per_page') ?: 50),
            'search'   => (string) ($req->get_param('search')   ?: ''),
            'product'  => (string) ($req->get_param('product')  ?: ''),
            'rating'   => $req->get_param('rating') !== null ? (int) $req->get_param('rating') : null,
            'from'     => (string) ($req->get_param('from') ?: ''),
            'to'       => (string) ($req->get_param('to')   ?: ''),
            'sort'     => (string) ($req->get_param('sort') ?: 'review_date'),
            'dir'      => (string) ($req->get_param('dir')  ?: 'DESC'),
        ]);
        return rest_ensure_response($result);
    }

    /** Statistiques agrégées : total, avg, distribution, tendance mensuelle, projection 4.8★. */
    public function get_avis_stats(\WP_REST_Request $req): \WP_REST_Response {
        $db     = new ReviewsDb();
        $result = $db->get_stats([
            'from'    => (string) ($req->get_param('from')    ?: ''),
            'to'      => (string) ($req->get_param('to')      ?: ''),
            'product' => (string) ($req->get_param('product') ?: ''),
        ]);
        return rest_ensure_response($result);
    }

    /**
     * Import CSV avis — reçoit un batch de lignes déjà parsées côté JS.
     * Crée la table si elle n'existe pas encore.
     */
    public function import_avis_csv(\WP_REST_Request $req): \WP_REST_Response {
        $db    = new ReviewsDb();
        $db->ensure_table();

        $items = $req->get_json_params()['items'] ?? [];
        if (!is_array($items)) {
            return new \WP_REST_Response(['error' => 'Payload invalide'], 400);
        }

        return rest_ensure_response($db->import_batch($items));
    }

    /** Vide la table bt_reviews. */
    public function reset_avis_db(): \WP_REST_Response {
        (new ReviewsDb())->truncate();
        return rest_ensure_response(['success' => true]);
    }

    /** Retourne les avis d'un client par email (pour le drawer client). */
    public function get_avis_by_email(\WP_REST_Request $req): \WP_REST_Response {
        $email = sanitize_email($req->get_param('email') ?? '');
        if (!$email) return new \WP_REST_Response(['data' => []], 200);
        $db   = new ReviewsDb();
        $data = $db->get_by_email($email);
        return rest_ensure_response(['data' => $data]);
    }

    // ── Participations ────────────────────────────────────────────────────────

    /**
     * Import CSV participations — reçoit un batch de lignes parsées côté JS.
     * Colonnes attendues : participation_date, product_name, buyer_firstname,
     *   buyer_lastname, buyer_email, price_net, price_gross, phone.
     * Crée la table si elle n'existe pas encore.
     */
    public function import_participations_csv(\WP_REST_Request $req): \WP_REST_Response {
        $body  = $req->get_json_params();
        $items = $body['items'] ?? [];

        if (!is_array($items) || empty($items)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne reçue.'], 400);
        }
        if (count($items) > 1000) {
            return new \WP_REST_Response(['error' => 'Trop de lignes par batch (max 1000).'], 400);
        }

        $db = new ParticipationDb();
        $db->ensure_table();

        $sanitized = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;
            $product = sanitize_text_field($item['product_name'] ?? '');
            if ($product === '') continue;

            $price_net_raw   = $item['price_net']   ?? null;
            $price_gross_raw = $item['price_gross']  ?? null;

            $sanitized[] = [
                'participation_date' => sanitize_text_field($item['participation_date'] ?? ''),
                'product_name'       => $product,
                'buyer_firstname'    => sanitize_text_field($item['buyer_firstname'] ?? ''),
                'buyer_lastname'     => sanitize_text_field($item['buyer_lastname']  ?? ''),
                'buyer_email'        => sanitize_email($item['buyer_email'] ?? ''),
                'price_net'          => $price_net_raw   !== null ? (float) $price_net_raw   : null,
                'price_gross'        => $price_gross_raw !== null ? (float) $price_gross_raw : null,
                'phone'              => sanitize_text_field($item['phone'] ?? ''),
            ];
        }

        if (empty($sanitized)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne valide (product_name manquant ?).'], 400);
        }

        return rest_ensure_response($db->upsert($sanitized));
    }

    /** Retourne le statut de la table bt_participations (crée si inexistante). */
    public function get_participations_import_status(): \WP_REST_Response {
        $db = new ParticipationDb();
        $db->ensure_table();
        return rest_ensure_response($db->get_status());
    }

    /** Vide la table bt_participations. */
    public function reset_participations_db(): \WP_REST_Response {
        (new ParticipationDb())->truncate();
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
