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

        $customers = $client->get_crm_customers(['per_page' => 1]);

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

        $endpoints = [
            ['label' => 'Products',            'path' => 'products',              'params' => ['limit' => 5, 'store_locale' => 'fr-FR']],
            ['label' => 'Partner Bookings',     'path' => 'partner/bookings',      'params' => ['limit' => 5, 'type' => 'offline_reservation,booking,voucher,redeem']],
            ['label' => 'Supplier Bookings',    'path' => 'supplier/bookings',     'params' => ['limit' => 5, 'type' => 'offline_reservation,booking,voucher,redeem']],
            ['label' => 'Partner Sold Items',   'path' => 'partner/solditems',     'params' => ['limit' => 5]],
            ['label' => 'Supplier Sold Items',  'path' => 'supplier/solditems',    'params' => ['limit' => 5]],
            ['label' => 'Partner CRM',          'path' => 'partner/crmcustomers',  'params' => ['limit' => 5]],
            ['label' => 'Reviews',              'path' => 'reviews',               'params' => ['limit' => 5]],
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
            'booking_hours'  => in_array('booking_hours', $includes) ? $db->query_booking_hours($from, $to) : null,
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

        $calendar = $db->query_calendar($from, $to);
        $total    = array_sum(array_column($calendar, 'count'));

        return rest_ensure_response([
            'calendar' => $calendar,
            'total'    => $total,
            'from'     => $from,
            'to'       => $to,
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
        $body = $req->get_json_params();
        $db   = new Db();

        // Résoudre la plage
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

        $sync   = new BookingSync();
        $result = $sync->sync_period($from, $to);

        // Mise à jour du statut global
        $status = $db->get_sync_status();
        $years  = $status['years_synced'] ?? [];
        if (!empty($body['year'])) {
            $years[] = (int) $body['year'];
            $years   = array_unique($years);
            sort($years);
            $db->update_sync_status(['years_synced' => $years]);
        }
        $db->update_sync_status(['last_full' => current_time('mysql', true)]);

        return rest_ensure_response(array_merge($result, ['db' => $db->get_sync_status()]));
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

            $sanitized[] = [
                'calendar_sold_id'   => sanitize_text_field($cal_id),
                'order_increment_id' => sanitize_text_field($item['order_increment_id'] ?? '') ?: null,
                'created_at'         => sanitize_text_field($item['created_at'] ?? ''),
                'offer_raw'          => wp_strip_all_tags($item['offer_raw'] ?? ''),
                'product_name'       => sanitize_text_field($item['product_name'] ?? ''),
                'quantity'           => max(1, (int) ($item['quantity'] ?? 1)),
                'price_total'        => ($item['price_total'] ?? null) !== null ? (float) $item['price_total'] : null,
                'buyer_name'         => sanitize_text_field($item['buyer_name'] ?? ''),
                'buyer_email'        => sanitize_email($item['buyer_email'] ?? ''),
                'appointment_date'   => sanitize_text_field($item['appointment_date'] ?? ''),
                'channel'            => sanitize_text_field($item['channel'] ?? ''),
                'booking_status'     => sanitize_text_field($item['booking_status'] ?? ''),
                'payment_method'     => sanitize_text_field($item['payment_method'] ?? ''),
                'payment_status'     => sanitize_text_field($item['payment_status'] ?? ''),
                'booking_key'        => sanitize_text_field($item['booking_key'] ?? ''),
            ];
        }

        if (empty($sanitized)) {
            return new \WP_REST_Response(['error' => 'Aucune ligne valide (calendar_sold_id manquant ?).'], 400);
        }

        return rest_ensure_response((new ReservationDb())->upsert($sanitized));
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

    public function sync_products(\WP_REST_Request $req): \WP_REST_Response {
        $sync   = new Sync();
        $result = $sync->run(
            $req->get_param('product_id') ? [(int) $req->get_param('product_id')] : null
        );
        return rest_ensure_response($result);
    }
}
