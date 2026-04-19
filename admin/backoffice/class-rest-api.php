<?php
namespace BlackTenders\Admin\Backoffice;

// Imports cross-namespace uniquement (les classes dans le même namespace n'ont pas besoin de `use`)
use BlackTenders\Api\Regiondo\Client;
use BlackTenders\Api\Regiondo\Cache;

defined('ABSPATH') || exit;

// ── Traits — découpage fonctionnel de RestApi ─────────────────────────────────
require_once __DIR__ . '/trait-rest-google.php';
require_once __DIR__ . '/trait-rest-stats.php';
require_once __DIR__ . '/trait-rest-sync.php';
require_once __DIR__ . '/trait-rest-settings.php';
require_once __DIR__ . '/trait-rest-ai.php';
require_once __DIR__ . '/trait-rest-chat.php';
require_once __DIR__ . '/trait-rest-translator.php';
require_once __DIR__ . '/trait-rest-forms.php';
require_once __DIR__ . '/trait-rest-import-profiles.php';

class RestApi {

    use RestApiGoogle, RestApiStats, RestApiSync, RestApiSettings, RestApiAi, RestApiChat, RestApiTranslator, RestApiForms, RestApiImportProfiles;

    private const NS = 'bt-regiondo/v1';

    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void {
        // ── Helpers permission ────────────────────────────────────────────────
        // Admin (manage_options) : accès total sans vérification des réglages.
        // Autres rôles : vérifié dans bt_role_permissions via ChatDb::role_has_permission().
        $db = new ChatDb();
        $uid = fn() => get_current_user_id();

        /** Accès admin uniquement (Réglages, Sync, Diagnostic, Onboarding). */
        $auth = fn() => current_user_can('manage_options');

        /** Fabrique un callback vérifiant une permission bt_role_permissions. */
        $perm = fn(string $cap) => fn() => $db->role_has_permission($uid(), $cap);

        // ── Produits ─────────────────────────────────────────────────────────
        register_rest_route(self::NS, '/products', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_products'],
            'permission_callback' => $perm('products'),
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_product'],
            'permission_callback' => $perm('products'),
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)/variations', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_variations'],
            'permission_callback' => $perm('products'),
        ]);

        register_rest_route(self::NS, '/products/(?P<id>\d+)/crossselling', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_crossselling'],
            'permission_callback' => $perm('products'),
        ]);

        register_rest_route(self::NS, '/products/navigationattributes', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_navigation_attributes'],
            'permission_callback' => $perm('products'),
        ]);

        // ── Catégories ───────────────────────────────────────────────────────
        register_rest_route(self::NS, '/categories', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_categories'],
            'permission_callback' => $perm('products'),
        ]);

        // ── Réservations ─────────────────────────────────────────────────────
        register_rest_route(self::NS, '/bookings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_bookings'],
            'permission_callback' => $perm('bookings'),
        ]);

        // ── Clients CRM ──────────────────────────────────────────────────────
        register_rest_route(self::NS, '/customers', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_customers'],
            'permission_callback' => $perm('customers'),
        ]);

        register_rest_route(self::NS, '/customers/newsletter', [
            'methods'             => 'PUT',
            'callback'            => [$this, 'update_newsletter'],
            'permission_callback' => $perm('customers'),
        ]);

        // ── Avis Regiondo ────────────────────────────────────────────────────
        register_rest_route(self::NS, '/reviews', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reviews'],
            'permission_callback' => $perm('avis'),
        ]);

        // ── Dashboard ────────────────────────────────────────────────────────
        register_rest_route(self::NS, '/dashboard', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_dashboard'],
            'permission_callback' => $perm('dashboard'),
        ]);

        // ── Test connexion (admin only) ───────────────────────────────────────
        register_rest_route(self::NS, '/test-connection', [
            'methods'             => 'GET',
            'callback'            => [$this, 'test_connection'],
            'permission_callback' => $auth,
        ]);

        // ── Diagnostic complet (admin only) ──────────────────────────────────
        register_rest_route(self::NS, '/diagnostic', [
            'methods'             => 'GET',
            'callback'            => [$this, 'run_diagnostic'],
            'permission_callback' => $auth,
        ]);

        // ── Réglages (admin only) ─────────────────────────────────────────────
        register_rest_route(self::NS, '/settings', [
            ['methods' => 'GET',  'callback' => [$this, 'get_settings'],  'permission_callback' => $perm('settings')],
            ['methods' => 'POST', 'callback' => [$this, 'save_settings'], 'permission_callback' => $auth],
        ]);

        // ── Google Analytics (GA4) ────────────────────────────────────────────
        register_rest_route(self::NS, '/ga4/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_ga4_stats'],
            'permission_callback' => $perm('analytics'),
        ]);

        register_rest_route(self::NS, '/search-console/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_search_console_stats'],
            'permission_callback' => $perm('analytics'),
        ]);

        register_rest_route(self::NS, '/google/test', [
            'methods'             => 'GET',
            'callback'            => [$this, 'test_google'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/ga4/cache/flush', [
            'methods'             => 'POST',
            'callback'            => [$this, 'flush_ga4_cache'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/gsc/cache/flush', [
            'methods'             => 'POST',
            'callback'            => [$this, 'flush_gsc_cache'],
            'permission_callback' => $auth,
        ]);

        // ── Snazzy Maps + Cache + Sync (admin only) ───────────────────────────
        register_rest_route(self::NS, '/snazzymaps-styles', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_snazzymaps_styles'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/cache/flush', [
            'methods'             => 'POST',
            'callback'            => [$this, 'flush_cache'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'sync_products'],
            'permission_callback' => $auth,
        ]);

        // ── Stats + Planificateur ─────────────────────────────────────────────
        register_rest_route(self::NS, '/bookings/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_bookings_stats'],
            'permission_callback' => $perm('bookings'),
        ]);

        register_rest_route(self::NS, '/planner', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_planner'],
            'permission_callback' => $perm('planner'),
        ]);

        // ── Sync réservations → DB (admin only) ───────────────────────────────
        register_rest_route(self::NS, '/bookings/sync', [
            'methods'             => 'POST',
            'callback'            => [$this, 'sync_bookings'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/bookings/sync/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_sync_status'],
            'permission_callback' => $perm('bookings'),
        ]);

        register_rest_route(self::NS, '/bookings/sync/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_bookings_db'],
            'permission_callback' => $auth,
        ]);

        // ── Solditems / Réservations enrichies ────────────────────────────────
        register_rest_route(self::NS, '/reservations', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reservations'],
            'permission_callback' => $perm('reservations'),
        ]);

        register_rest_route(self::NS, '/reservations/import', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_reservations'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/reservations/import/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_reservations_import_status'],
            'permission_callback' => $perm('reservations'),
        ]);

        register_rest_route(self::NS, '/reservations/import/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_reservations_db'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/reservations/import/csv', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_reservations_csv'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/reservations/reparse-prices', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reparse_prices'],
            'permission_callback' => $auth,
        ]);

        // ── Avis clients ──────────────────────────────────────────────────────
        register_rest_route(self::NS, '/avis', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_avis'],
            'permission_callback' => $perm('avis'),
        ]);

        register_rest_route(self::NS, '/avis/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_avis_stats'],
            'permission_callback' => $perm('avis'),
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
            'permission_callback' => $perm('avis'),
        ]);

        // ── Participations ────────────────────────────────────────────────────
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

        // ── Onboarding (admin only) ───────────────────────────────────────────
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

        // ── IA — contexte données + événements ────────────────────────────────
        register_rest_route(self::NS, '/ai/context', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_ai_context'],
            'permission_callback' => $perm('chat_access'),
        ]);

        register_rest_route(self::NS, '/ai/events', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_events'],
            'permission_callback' => $perm('chat_access'),
        ]);

        register_rest_route(self::NS, '/ai/events/generate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'generate_events'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/ai/events/import', [
            'methods'             => 'POST',
            'callback'            => [$this, 'import_events'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/ai/events/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'reset_events'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/ai/status', [
            'methods'             => 'GET',
            'callback'            => [$this, 'ai_status'],
            'permission_callback' => $perm('chat_access'),
        ]);

        // ── Traducteur + Correcteur IA ────────────────────────────────────────
        register_rest_route(self::NS, '/ai/translate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'translate_text'],
            'permission_callback' => $perm('translations'),
        ]);

        register_rest_route(self::NS, '/ai/correct', [
            'methods'             => 'POST',
            'callback'            => [$this, 'correct_text'],
            'permission_callback' => $perm('translations'),
        ]);

        // ── Conversations IA partagées (nouveau système granulaire) ──────────
        register_rest_route(self::NS, '/chats', [
            ['methods' => 'GET',  'callback' => [$this, 'list_chats'],   'permission_callback' => $perm('chat_access')],
            ['methods' => 'POST', 'callback' => [$this, 'create_chat'],  'permission_callback' => $perm('chat_access')],
        ]);

        register_rest_route(self::NS, '/chats/(?P<uuid>[a-z0-9_\-]+)', [
            ['methods' => 'GET',    'callback' => [$this, 'get_chat'],    'permission_callback' => $perm('chat_access')],
            ['methods' => 'PATCH',  'callback' => [$this, 'update_chat'], 'permission_callback' => $perm('chat_access')],
            ['methods' => 'DELETE', 'callback' => [$this, 'delete_chat'], 'permission_callback' => $perm('chat_access')],
        ]);

        register_rest_route(self::NS, '/chats/(?P<uuid>[a-z0-9_\-]+)/shares', [
            ['methods' => 'GET',  'callback' => [$this, 'list_shares'], 'permission_callback' => $perm('chat_share')],
            ['methods' => 'POST', 'callback' => [$this, 'add_share'],   'permission_callback' => $perm('chat_share')],
        ]);

        register_rest_route(self::NS, '/chats/(?P<uuid>[a-z0-9_\-]+)/shares/(?P<uid>\d+)', [
            ['methods' => 'PATCH',  'callback' => [$this, 'update_share'], 'permission_callback' => $perm('chat_share')],
            ['methods' => 'DELETE', 'callback' => [$this, 'remove_share'], 'permission_callback' => $perm('chat_share')],
        ]);

        register_rest_route(self::NS, '/users/search', [
            'methods'             => 'GET',
            'callback'            => [$this, 'search_users'],
            'permission_callback' => $perm('chat_share'),
        ]);

        register_rest_route(self::NS, '/settings/role-permissions', [
            ['methods' => 'GET',  'callback' => [$this, 'get_role_permissions'],  'permission_callback' => $auth],
            ['methods' => 'POST', 'callback' => [$this, 'save_role_permissions'], 'permission_callback' => $auth],
        ]);

        // ── Soumissions de formulaires ───────────────────────────────────────
        register_rest_route(self::NS, '/forms', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_form_submissions'],
            'permission_callback' => $perm('forms'),
        ]);

        register_rest_route(self::NS, '/forms/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_form_submissions_stats'],
            'permission_callback' => $perm('forms'),
        ]);

        register_rest_route(self::NS, '/forms/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'delete_form_submission'],
            'permission_callback' => $auth,
        ]);

        // ── Profils de mapping CSV ─────────────────────────────────────────
        register_rest_route(self::NS, '/import-profiles', [
            ['methods' => 'GET',  'callback' => [$this, 'list_import_profiles'],  'permission_callback' => $auth],
            ['methods' => 'POST', 'callback' => [$this, 'save_import_profile'],   'permission_callback' => $auth],
        ]);

        register_rest_route(self::NS, '/import-profiles/(?P<id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'delete_import_profile'],
            'permission_callback' => $auth,
        ]);

        // ── Schema.org SEO (admin only) ─────────────────────────────────────────
        register_rest_route(self::NS, '/schema/settings', [
            ['methods' => 'GET',  'callback' => [$this, 'get_schema_settings'],  'permission_callback' => $auth],
            ['methods' => 'POST', 'callback' => [$this, 'save_schema_settings'], 'permission_callback' => $auth],
        ]);

        register_rest_route(self::NS, '/schema/post-types', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_schema_post_types'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/schema/taxonomies', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_schema_taxonomies'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/schema/map-fields', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_schema_map_fields'],
            'permission_callback' => $auth,
        ]);

        register_rest_route(self::NS, '/schema/text-fields', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_schema_text_fields'],
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
        $db     = new ReservationDb();
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
        $db     = new ReservationStats();
        $result = $db->query_customers($page, $per_page, $search, $sort_key, $sort_dir);

        // Enrich with avis count from sj_avis CPT if available (single aggregated query)
        if (!empty($result['data']) && post_type_exists('sj_avis')) {
            global $wpdb;
            $emails = array_filter(array_column($result['data'], 'email'));
            $avis_counts = [];
            if (!empty($emails)) {
                $placeholders = implode(',', array_fill(0, count($emails), '%s'));
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT pm.meta_value AS email, COUNT(*) AS cnt
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type = 'sj_avis'
                     WHERE pm.meta_key = 'avis_customer_email' AND pm.meta_value IN ({$placeholders})
                     GROUP BY pm.meta_value",
                    ...$emails
                ), ARRAY_A);
                foreach ($rows as $row) {
                    $avis_counts[$row['email']] = (int) $row['cnt'];
                }
            }
            foreach ($result['data'] as &$customer) {
                $customer['avis_count'] = $avis_counts[$customer['email'] ?? ''] ?? 0;
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
        $local_db = new ReservationStats();
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

    // ── Helpers sécurité clés API ─────────────────────────────────────────────

    /**
     * Masque une clé API : garde les 4 derniers caractères lisibles.
     * Ex : "sk-ant-abc123def" → "••••••••••••def"
     * Retourne '' si la clé est vide.
     */
    private static function mask_key(string $key): string {
        if ($key === '') return '';
        $visible = min(4, strlen($key));
        return str_repeat('•', max(0, strlen($key) - $visible)) . substr($key, -$visible);
    }

    /**
     * Vérifie si une valeur est un masque (contient •) — pour éviter d'écraser par le masque.
     */
    private static function is_masked(string $val): bool {
        return str_contains($val, '•');
    }

    /**
     * Retourne uniquement l'email du service account (jamais la clé privée RSA).
     * Indique 'configured' si les credentials sont en DB.
     *
     * @return array{configured: bool, client_email?: string}
     */
    private static function redact_google_creds(string $json): array {
        if ($json === '') return ['configured' => false];
        $creds = json_decode($json, true);
        if (!$creds || ($creds['type'] ?? '') !== 'service_account') return ['configured' => false];
        return [
            'configured'   => true,
            'client_email' => $creds['client_email'] ?? '',
        ];
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
                'ok' => $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table,
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
        // Création idempotente de toutes les tables du plugin
        (new ReservationDb())->ensure_table();
        (new ReviewsDb())->ensure_table();
        (new ParticipationDb())->ensure_table();
        (new EventsDb())->ensure_table();

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
