<?php
namespace BlackTenders\Admin\Backoffice;

use BlackTenders\Api\Gyg\GygAuth;
use BlackTenders\Api\Gyg\GygClient;
use BlackTenders\Api\Regiondo\Client as RegionodoClient;

defined('ABSPATH') || exit;

/**
 * Contrôleur REST GYG — deux périmètres :
 *
 * 1) Endpoints exposés à GYG (namespace gyg/v1) :
 *    - POST get-availabilities, reserve, cancel-reservation, book, cancel-booking, notify
 *    - Authentification via GygAuth::validate_inbound() (Basic Auth entrant)
 *
 * 2) Endpoints internes (namespace bt-regiondo/v1) :
 *    - GET  gyg/test                        → ping GYG API
 *    - GET  gyg/bookings                    → liste paginée depuis GygDb
 *    - POST gyg/bookings/reset              → vide la table
 *    - GET  gyg/stats                       → stats agrégées
 *    - POST gyg/bookings/{id}/redeem        → valider un booking (P2.5)
 *    - POST gyg/flush-availability-cache    → vider les transients (P2.3)
 *    - POST gyg/notify-availability         → notifier GYG manuellement (P2.4)
 *    - GET  gyg/deals                       → liste deals (P3.1)
 *    - POST gyg/deals                       → créer un deal (P3.1)
 *    - DELETE gyg/deals/{id}               → supprimer un deal (P3.1)
 *    - POST gyg/products/{id}/activate      → réactiver un produit (P3.2)
 *    - POST gyg/register-supplier          → enregistrer le supplier (P3.3)
 *    - GET  gyg/logs                        → logs paginés (P3.5)
 */
class GygRestApi {

    private const GYG_NS = 'gyg/v1';
    private const BT_NS  = 'bt-regiondo/v1';

    /** TTL du cache disponibilités : 15 minutes */
    private const AVAIL_CACHE_TTL = 900;

    /**
     * Initialise les hooks REST WordPress.
     */
    public function init(): void {
        add_action('rest_api_init', [$this, 'register_routes']);
        // Transformer les erreurs des routes gyg/v1 au format GYG { errorCode, errorMessage }
        add_filter('rest_post_dispatch', [$this, 'filter_gyg_error_format'], 10, 3);
        // Écouter le hook post-sync Regiondo pour notifier GYG (P2.4)
        add_action('bt_after_regiondo_sync', [$this, 'on_regiondo_sync'], 10, 1);
    }

    /**
     * Reformate les erreurs des routes gyg/v1 au format GYG Supplier API.
     * Transforme { code, message, data } → { errorCode, errorMessage }.
     *
     * @param \WP_REST_Response $response Réponse finale
     * @param \WP_REST_Server   $server   Serveur REST
     * @param \WP_REST_Request  $request  Requête entrante
     * @return \WP_REST_Response
     */
    public function filter_gyg_error_format(\WP_REST_Response $response, $server, \WP_REST_Request $request): \WP_REST_Response {
        $route = $request->get_route();
        if (strpos($route, '/gyg/v1/') === false) {
            return $response;
        }

        $status = $response->get_status();
        $data   = $response->get_data();

        if (isset($data['errorCode'])) {
            return $response;
        }

        if ($status >= 400 && isset($data['code'])) {
            return new \WP_REST_Response(
                [
                    'errorCode'    => $data['code'],
                    'errorMessage' => $data['message'] ?? '',
                ],
                $status
            );
        }

        return $response;
    }

    /**
     * Enregistre toutes les routes REST GYG (entrantes + internes).
     */
    public function register_routes(): void {
        // ── Routes exposées à GYG ─────────────────────────────────────────────

        register_rest_route(self::GYG_NS, '/get-availabilities/', [
            'methods'             => 'GET',
            'callback'            => [$this, 'get_availabilities'],
            'permission_callback' => [$this, 'gyg_permission'],
        ]);

        register_rest_route(self::GYG_NS, '/reserve/', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_reserve'],
            'permission_callback' => [$this, 'gyg_permission'],
        ]);

        register_rest_route(self::GYG_NS, '/cancel-reservation/', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_cancel_reservation'],
            'permission_callback' => [$this, 'gyg_permission'],
        ]);

        register_rest_route(self::GYG_NS, '/book/', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_book'],
            'permission_callback' => [$this, 'gyg_permission'],
        ]);

        register_rest_route(self::GYG_NS, '/cancel-booking/', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_cancel_booking'],
            'permission_callback' => [$this, 'gyg_permission'],
        ]);

        register_rest_route(self::GYG_NS, '/notify/', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_notify'],
            'permission_callback' => [$this, 'gyg_permission'],
        ]);

        // ── Routes internes bt-regiondo/v1 ────────────────────────────────────

        register_rest_route(self::BT_NS, '/gyg/test', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_test_connection'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        register_rest_route(self::BT_NS, '/gyg/bookings', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_get_bookings'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        register_rest_route(self::BT_NS, '/gyg/bookings/reset', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_reset_bookings'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        register_rest_route(self::BT_NS, '/gyg/bookings/(?P<id>[a-zA-Z0-9\-]+)/redeem', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_redeem_booking'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        register_rest_route(self::BT_NS, '/gyg/stats', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_get_stats'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        // P2.3 — flush cache disponibilités
        register_rest_route(self::BT_NS, '/gyg/flush-availability-cache', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_flush_availability_cache'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        // P2.4 — notifier GYG disponibilités manuellement
        register_rest_route(self::BT_NS, '/gyg/notify-availability', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_notify_availability'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        // P3.1 — Deals
        register_rest_route(self::BT_NS, '/gyg/deals', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'handle_list_deals'],
                'permission_callback' => fn() => current_user_can('manage_options'),
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'handle_create_deal'],
                'permission_callback' => fn() => current_user_can('manage_options'),
            ],
        ]);

        register_rest_route(self::BT_NS, '/gyg/deals/(?P<id>[^/]+)', [
            'methods'             => 'DELETE',
            'callback'            => [$this, 'handle_delete_deal'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        // P3.2 — Réactiver un produit
        register_rest_route(self::BT_NS, '/gyg/products/(?P<gyg_option_id>\d+)/activate', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_activate_product'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        // P3.3 — Enregistrer le supplier
        register_rest_route(self::BT_NS, '/gyg/register-supplier', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handle_register_supplier'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);

        // P3.5 — Logs
        register_rest_route(self::BT_NS, '/gyg/logs', [
            'methods'             => 'GET',
            'callback'            => [$this, 'handle_get_logs'],
            'permission_callback' => fn() => current_user_can('manage_options'),
        ]);
    }

    // ─── Permission callback — routes GYG entrantes ───────────────────────────

    /**
     * Valide le Basic Auth entrant pour toutes les routes GYG.
     *
     * @param \WP_REST_Request $req
     * @return true|\WP_Error
     */
    public function gyg_permission(\WP_REST_Request $req): bool|\WP_Error {
        if (!GygAuth::validate_inbound($req)) {
            return new \WP_Error(
                'AUTHORIZATION_FAILURE',
                'Incorrect credentials provided',
                ['status' => 401]
            );
        }
        return true;
    }

    // ─── Logging interne ──────────────────────────────────────────────────────

    /**
     * Enregistre un log GYG en DB.
     * Ne lève jamais d'exception — les logs ne doivent pas bloquer le flux principal.
     *
     * @param string      $direction   'inbound' ou 'outbound'
     * @param string      $endpoint    Endpoint appelé
     * @param string      $method      Méthode HTTP
     * @param int         $status_code Code HTTP
     * @param mixed       $payload     Corps de requête (sera JSON-encodé)
     * @param mixed       $response    Corps de réponse (sera JSON-encodé)
     * @param string|null $error       Message d'erreur
     */
    private static function gyg_log(
        string $direction,
        string $endpoint,
        string $method,
        int $status_code,
        mixed $payload = null,
        mixed $response = null,
        ?string $error = null
    ): void {
        try {
            GygLogsDb::log(
                $direction,
                $endpoint,
                $method,
                $status_code,
                $payload !== null ? wp_json_encode($payload) : null,
                $response !== null ? wp_json_encode($response) : null,
                $error
            );
        } catch (\Throwable $e) {
            // Silencieux — les logs ne bloquent pas le flux
            error_log('[GYG_LOG_ERROR] ' . $e->getMessage());
        }
    }

    // ─── Handlers GYG entrants ────────────────────────────────────────────────

    /**
     * GET /gyg/v1/get-availabilities/?productId=...&fromDateTime=...&toDateTime=...
     *
     * Retourne les créneaux disponibles pour un produit GYG mappé.
     * P2.3 : branché sur les disponibilités Regiondo réelles avec cache 15 min.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function get_availabilities(\WP_REST_Request $req): \WP_REST_Response {
        $product_id    = $req->get_param('productId');
        $from_datetime = $req->get_param('fromDateTime');
        $to_datetime   = $req->get_param('toDateTime');

        $endpoint = '/gyg/v1/get-availabilities';

        // Validation des paramètres requis
        if (empty($product_id)) {
            self::gyg_log('inbound', $endpoint, 'GET', 400, $req->get_params(), null, 'Missing productId');
            return gyg_error_response('MISSING_PARAMETER', 'productId is required', 400);
        }
        if (empty($from_datetime)) {
            self::gyg_log('inbound', $endpoint, 'GET', 400, $req->get_params(), null, 'Missing fromDateTime');
            return gyg_error_response('MISSING_PARAMETER', 'fromDateTime is required', 400);
        }
        if (empty($to_datetime)) {
            self::gyg_log('inbound', $endpoint, 'GET', 400, $req->get_params(), null, 'Missing toDateTime');
            return gyg_error_response('MISSING_PARAMETER', 'toDateTime is required', 400);
        }

        // Vérifier que ce productId est dans le mapping et trouver le notre_product_id Regiondo
        $product_map  = json_decode(get_option('bt_gyg_product_map', '[]'), true);
        $mapped       = false;
        $regiondo_pid = null;

        if (is_array($product_map)) {
            foreach ($product_map as $entry) {
                if (
                    isset($entry['gyg_option_id']) &&
                    (string) $entry['gyg_option_id'] === (string) $product_id &&
                    !empty($entry['active'])
                ) {
                    $mapped       = true;
                    $regiondo_pid = $entry['notre_product_id'] ?? null;
                    break;
                }
                if (
                    isset($entry['notre_product_id']) &&
                    (string) $entry['notre_product_id'] === (string) $product_id &&
                    !empty($entry['active'])
                ) {
                    $mapped       = true;
                    $regiondo_pid = $entry['notre_product_id'];
                    break;
                }
            }
        }

        if (!$mapped) {
            self::gyg_log('inbound', $endpoint, 'GET', 404, $req->get_params(), null, 'Product not in mapping');
            return gyg_error_response('INVALID_PRODUCT', 'Product not found or not active in mapping', 404);
        }

        // Extraire la date depuis fromDateTime
        try {
            $date = (new \DateTime($from_datetime))->format('Y-m-d');
        } catch (\Throwable $e) {
            self::gyg_log('inbound', $endpoint, 'GET', 400, $req->get_params(), null, 'Invalid fromDateTime format');
            return gyg_error_response('INVALID_PARAMETER', 'fromDateTime format invalide', 400);
        }

        // Cache transient 15 min par produit+date
        $cache_key      = 'bt_gyg_avail_' . sanitize_key($product_id) . '_' . $date;
        $cached_avails  = get_transient($cache_key);

        if ($cached_avails !== false && is_array($cached_avails)) {
            $response_data = ['data' => ['availabilities' => $cached_avails]];
            self::gyg_log('inbound', $endpoint, 'GET', 200, $req->get_params(), $response_data);
            return new \WP_REST_Response($response_data, 200);
        }

        // Appel Regiondo pour les variations (créneaux + prix)
        $availabilities = [];

        if (!empty($regiondo_pid)) {
            try {
                $client     = new RegionodoClient();
                $variations = $client->get_variations((int) $regiondo_pid);

                if (!empty($variations) && is_array($variations)) {
                    foreach ($variations as $variation) {
                        // Construire les créneaux depuis les variations Regiondo
                        $start_time = $variation['start_time'] ?? $variation['time'] ?? null;
                        $capacity   = (int) ($variation['capacity'] ?? $variation['available'] ?? 10);
                        $price_data = $variation['prices'] ?? $variation['price'] ?? [];

                        // Construire le créneau au format GYG
                        if ($start_time) {
                            $start_dt = $date . 'T' . $start_time . '+00:00';
                        } else {
                            // Fallback : 09:00 et 14:00
                            $start_dt = $date . 'T09:00:00+00:00';
                        }

                        // Construire retailPrices depuis les données Regiondo
                        $retail_prices = [];
                        if (is_array($price_data)) {
                            foreach ($price_data as $price_entry) {
                                $category = strtoupper($price_entry['category'] ?? 'ADULT');
                                // Prix en centimes (Regiondo peut retourner en EUR)
                                $amount   = isset($price_entry['price'])
                                    ? (int) round((float) $price_entry['price'] * 100)
                                    : 5500;
                                $retail_prices[] = [
                                    'category' => $category,
                                    'price'    => $amount,
                                ];
                            }
                        }

                        if (empty($retail_prices)) {
                            // Tarifs par défaut si Regiondo n'en fournit pas
                            $base_price = isset($variation['base_price'])
                                ? (int) round((float) $variation['base_price'] * 100)
                                : 5500;
                            $retail_prices = [
                                ['category' => 'ADULT', 'price' => $base_price],
                                ['category' => 'CHILD', 'price' => (int) round($base_price * 0.6)],
                            ];
                        }

                        $availabilities[] = [
                            'dateTime'          => $start_dt,
                            'productId'         => $product_id,
                            'cutoffSeconds'     => 3600,
                            'vacancies'         => $capacity,
                            'currency'          => 'EUR',
                            'pricesByCategory'  => [
                                'retailPrices' => $retail_prices,
                            ],
                        ];
                    }
                }
            } catch (\Throwable $e) {
                error_log('[GYG_AVAIL] Erreur Regiondo pour produit ' . $regiondo_pid . ': ' . $e->getMessage());
                self::gyg_log('outbound', '/regiondo/variations/' . $regiondo_pid, 'GET', 500, null, null, $e->getMessage());
                // Retourner liste vide sans exposer l'erreur interne
                $empty_response = ['data' => ['availabilities' => []]];
                self::gyg_log('inbound', $endpoint, 'GET', 200, $req->get_params(), $empty_response);
                return new \WP_REST_Response($empty_response, 200);
            }
        }

        // Fallback stub si pas de données Regiondo (produit sans notre_product_id)
        if (empty($availabilities)) {
            $availabilities = [
                [
                    'dateTime'         => $date . 'T09:00:00+00:00',
                    'productId'        => $product_id,
                    'cutoffSeconds'    => 3600,
                    'vacancies'        => 10,
                    'currency'         => 'EUR',
                    'pricesByCategory' => [
                        'retailPrices' => [
                            ['category' => 'ADULT', 'price' => 5500],
                            ['category' => 'CHILD', 'price' => 3000],
                        ],
                    ],
                ],
                [
                    'dateTime'         => $date . 'T14:00:00+00:00',
                    'productId'        => $product_id,
                    'cutoffSeconds'    => 3600,
                    'vacancies'        => 10,
                    'currency'         => 'EUR',
                    'pricesByCategory' => [
                        'retailPrices' => [
                            ['category' => 'ADULT', 'price' => 5500],
                            ['category' => 'CHILD', 'price' => 3000],
                        ],
                    ],
                ],
            ];
        }

        // Mettre en cache 15 min
        set_transient($cache_key, $availabilities, self::AVAIL_CACHE_TTL);

        $response_data = ['data' => ['availabilities' => $availabilities]];
        self::gyg_log('inbound', $endpoint, 'GET', 200, $req->get_params(), $response_data);

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * POST /gyg/v1/reserve/
     *
     * Crée une réservation temporaire (reserve) :
     * - Génère une reservationReference UUID
     * - Expire dans 30 minutes
     * - Stocke en DB avec statut 'reserved'
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_reserve(\WP_REST_Request $req): \WP_REST_Response {
        $json = $req->get_json_params();
        $data = $json['data'] ?? [];

        $product_id          = $data['productId']          ?? '';
        $date_time           = $data['dateTime']           ?? '';
        $booking_items       = $data['bookingItems']       ?? [];
        $gyg_booking_ref     = $data['gygBookingReference'] ?? '';

        if (empty($product_id)) {
            self::gyg_log('inbound', '/gyg/v1/reserve', 'POST', 400, $data, null, 'Missing productId');
            return gyg_error_response('MISSING_PARAMETER', 'productId is required', 400);
        }
        if (empty($date_time)) {
            self::gyg_log('inbound', '/gyg/v1/reserve', 'POST', 400, $data, null, 'Missing dateTime');
            return gyg_error_response('MISSING_PARAMETER', 'dateTime is required', 400);
        }
        if (empty($booking_items) || !is_array($booking_items)) {
            self::gyg_log('inbound', '/gyg/v1/reserve', 'POST', 400, $data, null, 'Missing bookingItems');
            return gyg_error_response('MISSING_PARAMETER', 'bookingItems must be a non-empty array', 400);
        }
        if (empty($gyg_booking_ref)) {
            self::gyg_log('inbound', '/gyg/v1/reserve', 'POST', 400, $data, null, 'Missing gygBookingReference');
            return gyg_error_response('MISSING_PARAMETER', 'gygBookingReference is required', 400);
        }

        try {
            $start_dt = (new \DateTime($date_time))->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return gyg_error_response('INVALID_PARAMETER', 'dateTime format invalide', 400);
        }

        $option_id       = $booking_items[0]['optionId'] ?? '';
        $reservation_ref = wp_generate_uuid4();

        $result = GygDb::upsert([
            'gyg_reservation_id' => $reservation_ref,
            'product_id'         => sanitize_text_field($product_id),
            'option_id'          => sanitize_text_field($option_id),
            'start_datetime'     => $start_dt,
            'status'             => 'reserved',
            'pricing_categories' => $booking_items,
            'raw_payload'        => $data,
        ]);

        if (is_wp_error($result)) {
            error_log('[GYG_RESERVE] DB error : ' . $result->get_error_message());
            self::gyg_log('inbound', '/gyg/v1/reserve', 'POST', 500, $data, null, $result->get_error_message());
            return gyg_error_response('INTERNAL_ERROR', 'Could not create reservation', 500);
        }

        $expiration = (new \DateTime('now', new \DateTimeZone('UTC')))
            ->modify('+30 minutes')
            ->format(\DateTime::ATOM);

        $response_data = [
            'data' => [
                'reservationReference'  => $reservation_ref,
                'reservationExpiration' => $expiration,
            ],
        ];

        self::gyg_log('inbound', '/gyg/v1/reserve', 'POST', 200, $data, $response_data);

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * POST /gyg/v1/cancel-reservation/
     *
     * Annule une réservation temporaire avant confirmation.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_cancel_reservation(\WP_REST_Request $req): \WP_REST_Response {
        $json = $req->get_json_params();
        $data = $json['data'] ?? [];

        $reservation_ref = $data['reservationReference'] ?? '';

        if (empty($reservation_ref)) {
            self::gyg_log('inbound', '/gyg/v1/cancel-reservation', 'POST', 400, $data, null, 'Missing reservationReference');
            return gyg_error_response('MISSING_PARAMETER', 'reservationReference is required', 400);
        }

        $booking = GygDb::get_by_reservation_id(sanitize_text_field($reservation_ref));

        if (!$booking) {
            // GYG accepte une annulation idempotente
            self::gyg_log('inbound', '/gyg/v1/cancel-reservation', 'POST', 200, $data, []);
            return new \WP_REST_Response(['data' => []], 200);
        }

        GygDb::update_status('gyg_reservation_id', $reservation_ref, 'cancelled');
        self::gyg_log('inbound', '/gyg/v1/cancel-reservation', 'POST', 200, $data, []);

        return new \WP_REST_Response(['data' => []], 200);
    }

    /**
     * POST /gyg/v1/book/
     *
     * Confirme une réservation temporaire en booking définitif.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_book(\WP_REST_Request $req): \WP_REST_Response {
        $json = $req->get_json_params();
        $data = $json['data'] ?? [];

        $reservation_ref = $data['reservationReference'] ?? '';
        $gyg_booking_ref = $data['gygBookingReference']  ?? '';
        $booking_items   = $data['bookingItems']          ?? [];
        $travelers       = $data['travelers']             ?? [];

        if (empty($reservation_ref)) {
            self::gyg_log('inbound', '/gyg/v1/book', 'POST', 400, $data, null, 'Missing reservationReference');
            return gyg_error_response('MISSING_PARAMETER', 'reservationReference is required', 400);
        }

        $booking = GygDb::get_by_reservation_id(sanitize_text_field($reservation_ref));

        if (!$booking) {
            self::gyg_log('inbound', '/gyg/v1/book', 'POST', 404, $data, null, 'Reservation not found');
            return gyg_error_response('INVALID_RESERVATION', 'Reservation not found or expired', 404);
        }

        if ($booking->status === 'cancelled') {
            self::gyg_log('inbound', '/gyg/v1/book', 'POST', 409, $data, null, 'Reservation cancelled');
            return gyg_error_response('INVALID_RESERVATION', 'Reservation has been cancelled', 409);
        }

        $booking_reference = wp_generate_uuid4();

        // Générer un ticket par unité dans chaque bookingItem
        $tickets = [];
        foreach ($booking_items as $item) {
            $category = $item['category']       ?? 'ADULT';
            $count    = (int) ($item['count'] ?? $item['quantity'] ?? 1);

            for ($i = 0; $i < $count; $i++) {
                $ticket_code = strtoupper(wp_generate_uuid4());
                $tickets[]   = [
                    'category'       => $category,
                    'ticketCode'     => $ticket_code,
                    'ticketCodeType' => 'QR_CODE',
                ];
            }
        }

        $first_traveler = $travelers[0] ?? [];
        $customer_name  = trim(($first_traveler['firstName'] ?? '') . ' ' . ($first_traveler['lastName'] ?? ''));
        $customer_email = $first_traveler['email'] ?? '';

        $updated_payload = array_merge(
            is_string($booking->raw_payload) ? (json_decode($booking->raw_payload, true) ?? []) : [],
            [
                'travelers'        => $travelers,
                'tickets'          => $tickets,
                'bookingReference' => $booking_reference,
            ]
        );

        GygDb::upsert([
            'gyg_reservation_id' => $reservation_ref,
            'gyg_booking_id'     => $booking_reference,
            'status'             => 'confirmed',
            'customer_name'      => $customer_name   ?: null,
            'customer_email'     => $customer_email  ?: null,
            'raw_payload'        => $updated_payload,
            'product_id'         => $booking->product_id,
            'option_id'          => $booking->option_id,
            'start_datetime'     => $booking->start_datetime,
        ]);

        $response_data = [
            'data' => [
                'bookingReference' => $booking_reference,
                'tickets'          => $tickets,
            ],
        ];

        self::gyg_log('inbound', '/gyg/v1/book', 'POST', 200, $data, $response_data);

        return new \WP_REST_Response($response_data, 200);
    }

    /**
     * POST /gyg/v1/cancel-booking/
     *
     * Annule un booking confirmé.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_cancel_booking(\WP_REST_Request $req): \WP_REST_Response {
        $json = $req->get_json_params();
        $data = $json['data'] ?? [];

        $booking_ref     = $data['bookingReference']    ?? '';
        $gyg_booking_ref = $data['gygBookingReference'] ?? '';

        if (empty($booking_ref)) {
            self::gyg_log('inbound', '/gyg/v1/cancel-booking', 'POST', 400, $data, null, 'Missing bookingReference');
            return gyg_error_response('MISSING_PARAMETER', 'bookingReference is required', 400);
        }

        $booking = GygDb::get_by_gyg_id(sanitize_text_field($booking_ref));

        if (!$booking) {
            self::gyg_log('inbound', '/gyg/v1/cancel-booking', 'POST', 200, $data, []);
            return new \WP_REST_Response(['data' => []], 200);
        }

        GygDb::update_status('gyg_booking_id', $booking_ref, 'cancelled');
        self::gyg_log('inbound', '/gyg/v1/cancel-booking', 'POST', 200, $data, []);

        return new \WP_REST_Response(['data' => []], 200);
    }

    /**
     * POST /gyg/v1/notify/
     *
     * Reçoit les notifications GYG (désactivation produit, etc.).
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_notify(\WP_REST_Request $req): \WP_REST_Response {
        $payload = $req->get_json_params();

        error_log('[GYG_NOTIFY] ' . wp_json_encode($payload));

        $notification_type = $payload['data']['notificationType'] ?? $payload['notificationType'] ?? '';

        if ($notification_type === 'PRODUCT_DEACTIVATION') {
            $product_id  = $payload['data']['productId'] ?? '';
            $product_map = json_decode(get_option('bt_gyg_product_map', '[]'), true);

            if (is_array($product_map) && !empty($product_id)) {
                $updated = false;
                foreach ($product_map as &$entry) {
                    if (
                        isset($entry['gyg_option_id']) &&
                        (string) $entry['gyg_option_id'] === (string) $product_id
                    ) {
                        $entry['active'] = false;
                        $updated = true;
                    }
                }
                unset($entry);

                if ($updated) {
                    update_option('bt_gyg_product_map', wp_json_encode($product_map));
                    error_log('[GYG_NOTIFY] Produit désactivé dans le mapping : ' . $product_id);
                }
            }
        }

        self::gyg_log('inbound', '/gyg/v1/notify', 'POST', 200, $payload, []);

        return new \WP_REST_Response(['data' => []], 200);
    }

    // ─── Handlers internes bt-regiondo/v1 ────────────────────────────────────

    /**
     * GET /bt-regiondo/v1/gyg/test
     * Test de connectivité vers la GYG API.
     *
     * @return \WP_REST_Response { ok: bool, mode: string }
     */
    public function handle_test_connection(): \WP_REST_Response {
        $ok   = GygClient::ping();
        $mode = get_option('bt_gyg_mode', 'sandbox');

        return rest_ensure_response(['ok' => $ok, 'mode' => $mode]);
    }

    /**
     * GET /bt-regiondo/v1/gyg/bookings
     * Liste paginée des réservations GYG depuis la DB locale.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_get_bookings(\WP_REST_Request $req): \WP_REST_Response {
        $result = GygDb::query([
            'page'     => (int)    ($req->get_param('page')     ?: 1),
            'per_page' => (int)    ($req->get_param('per_page') ?: 50),
            'status'   => (string) ($req->get_param('status')   ?: ''),
            'search'   => (string) ($req->get_param('search')   ?: ''),
            'from'     => (string) ($req->get_param('from')     ?: ''),
            'to'       => (string) ($req->get_param('to')       ?: ''),
        ]);

        return rest_ensure_response($result);
    }

    /**
     * GET /bt-regiondo/v1/gyg/stats
     * Statistiques agrégées de la table GYG.
     *
     * @return \WP_REST_Response
     */
    public function handle_get_stats(): \WP_REST_Response {
        return rest_ensure_response(GygDb::get_stats());
    }

    /**
     * POST /bt-regiondo/v1/gyg/bookings/reset
     * Vide la table bt_gyg_bookings.
     *
     * @return \WP_REST_Response
     */
    public function handle_reset_bookings(): \WP_REST_Response {
        GygDb::truncate();
        return rest_ensure_response(['success' => true]);
    }

    /**
     * POST /bt-regiondo/v1/gyg/bookings/{id}/redeem (P2.5)
     *
     * Valide (redeem) un booking confirmé :
     * 1. Récupère le booking par ID local
     * 2. Appelle GygClient::redeem_booking
     * 3. Met à jour le statut en DB
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_redeem_booking(\WP_REST_Request $req): \WP_REST_Response {
        $id = sanitize_text_field($req->get_param('id'));

        // Chercher d'abord par gyg_booking_id, sinon par id numérique
        $booking = GygDb::get_by_gyg_id($id);

        if (!$booking && is_numeric($id)) {
            global $wpdb;
            $booking = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `{$wpdb->prefix}bt_gyg_bookings` WHERE id = %d LIMIT 1",
                    (int) $id
                )
            );
        }

        if (!$booking) {
            return rest_ensure_response(new \WP_Error('not_found', 'Booking introuvable', ['status' => 404]));
        }

        if ($booking->status !== 'confirmed') {
            return rest_ensure_response(new \WP_Error(
                'invalid_status',
                'Seuls les bookings confirmés peuvent être validés (statut actuel : ' . $booking->status . ')',
                ['status' => 422]
            ));
        }

        $gyg_booking_id = $booking->gyg_booking_id;

        if (!empty($gyg_booking_id)) {
            $result = GygClient::redeem_booking($gyg_booking_id);

            if (is_wp_error($result)) {
                error_log('[GYG_REDEEM] Erreur API GYG : ' . $result->get_error_message());
                self::gyg_log('outbound', '/1/redeem-booking', 'POST', 500, ['gygBookingId' => $gyg_booking_id], null, $result->get_error_message());
                return rest_ensure_response(new \WP_Error(
                    'gyg_error',
                    'Erreur GYG : ' . $result->get_error_message(),
                    ['status' => 502]
                ));
            }

            self::gyg_log('outbound', '/1/redeem-booking', 'POST', 200, ['gygBookingId' => $gyg_booking_id], $result);
        }

        // Mettre à jour le statut en DB
        $id_type = is_numeric($id) ? 'id' : 'gyg_booking_id';
        GygDb::update_status($id_type, $id, 'redeemed');

        return rest_ensure_response(['success' => true]);
    }

    /**
     * POST /bt-regiondo/v1/gyg/flush-availability-cache (P2.3)
     *
     * Vide tous les transients bt_gyg_avail_* pour forcer un refresh.
     *
     * @return \WP_REST_Response { success: true, cleared: N }
     */
    public function handle_flush_availability_cache(): \WP_REST_Response {
        global $wpdb;

        $cleared = (int) $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$wpdb->options}` WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_bt_gyg_avail_') . '%'
            )
        );

        // Supprimer aussi les timeout-transients correspondants
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `{$wpdb->options}` WHERE option_name LIKE %s",
                $wpdb->esc_like('_transient_timeout_bt_gyg_avail_') . '%'
            )
        );

        return rest_ensure_response(['success' => true, 'cleared' => $cleared]);
    }

    /**
     * POST /bt-regiondo/v1/gyg/notify-availability (P2.4)
     *
     * Déclenche GygClient::notify_availability_update manuellement.
     * Body : { "product_id": "..." } — si absent, notifie tous les produits actifs.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response { success: true, notified: string[] }
     */
    public function handle_notify_availability(\WP_REST_Request $req): \WP_REST_Response {
        $body       = $req->get_json_params();
        $product_id = $body['product_id'] ?? null;

        $product_map = json_decode(get_option('bt_gyg_product_map', '[]'), true);
        $notified    = [];

        if (!is_array($product_map)) {
            return rest_ensure_response(['success' => true, 'notified' => []]);
        }

        foreach ($product_map as $entry) {
            if (empty($entry['active'])) continue;

            $gyg_id = (string) ($entry['gyg_option_id'] ?? '');
            if (empty($gyg_id)) continue;

            // Filtrer sur un produit spécifique si fourni
            if ($product_id !== null && $gyg_id !== (string) $product_id) continue;

            $result = GygClient::notify_availability_update($gyg_id, []);

            if (is_wp_error($result)) {
                error_log('[GYG_NOTIFY_AVAIL] Erreur pour ' . $gyg_id . ': ' . $result->get_error_message());
                self::gyg_log('outbound', '/1/notify-availability-update', 'POST', 500, ['productId' => $gyg_id], null, $result->get_error_message());
            } else {
                self::gyg_log('outbound', '/1/notify-availability-update', 'POST', 200, ['productId' => $gyg_id], $result);
                $notified[] = $gyg_id;
            }
        }

        return rest_ensure_response(['success' => true, 'notified' => $notified]);
    }

    /**
     * Hook exécuté après un sync Regiondo pour notifier GYG automatiquement (P2.4).
     * Vide le cache de disponibilités et notifie GYG pour chaque produit mappé.
     *
     * @param array $product_ids IDs produits Regiondo synchronisés
     */
    public function on_regiondo_sync(array $product_ids): void {
        $product_map = json_decode(get_option('bt_gyg_product_map', '[]'), true);
        if (!is_array($product_map)) return;

        foreach ($product_map as $entry) {
            if (empty($entry['active'])) continue;

            $notre_pid = (string) ($entry['notre_product_id'] ?? '');
            $gyg_id    = (string) ($entry['gyg_option_id']    ?? '');

            if (empty($gyg_id)) continue;

            // Vérifier si ce produit Regiondo est dans la liste synchronisée
            $is_synced = empty($product_ids) || in_array($notre_pid, $product_ids, true);
            if (!$is_synced) continue;

            // Vider le cache de disponibilités pour ce produit
            $this->flush_product_availability_cache($gyg_id);

            // Notifier GYG
            $result = GygClient::notify_availability_update($gyg_id, []);

            if (is_wp_error($result)) {
                error_log('[GYG_SYNC] notify_availability_update FAILED productId=' . $gyg_id . ' : ' . $result->get_error_message());
            } else {
                error_log('[GYG_SYNC] notify_availability_update productId=' . $gyg_id);
            }
        }
    }

    /**
     * Vide les transients de cache de disponibilités pour un produit GYG spécifique.
     * Itère sur les 90 prochains jours de cache potentiel.
     *
     * @param string $gyg_product_id Identifiant produit GYG
     */
    private function flush_product_availability_cache(string $gyg_product_id): void {
        $safe_id = sanitize_key($gyg_product_id);

        // Itérer sur les 90 prochains jours et les 30 jours passés
        for ($i = -30; $i <= 90; $i++) {
            $date      = gmdate('Y-m-d', strtotime("+{$i} days"));
            $cache_key = 'bt_gyg_avail_' . $safe_id . '_' . $date;
            delete_transient($cache_key);
        }
    }

    // ─── Deals (P3.1) ────────────────────────────────────────────────────────

    /**
     * GET /bt-regiondo/v1/gyg/deals
     * Liste les deals GYG actifs.
     *
     * @return \WP_REST_Response
     */
    public function handle_list_deals(): \WP_REST_Response {
        $result = GygClient::list_deals();

        if (is_wp_error($result)) {
            self::gyg_log('outbound', '/1/deals', 'GET', 500, null, null, $result->get_error_message());
            return rest_ensure_response(['error' => $result->get_error_message()]);
        }

        self::gyg_log('outbound', '/1/deals', 'GET', 200, null, $result);
        return rest_ensure_response($result);
    }

    /**
     * POST /bt-regiondo/v1/gyg/deals
     * Crée un nouveau deal GYG.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_create_deal(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params() ?: [];
        $result = GygClient::create_deal($body);

        if (is_wp_error($result)) {
            self::gyg_log('outbound', '/1/deals', 'POST', 500, $body, null, $result->get_error_message());
            return rest_ensure_response(new \WP_Error('gyg_error', $result->get_error_message(), ['status' => 502]));
        }

        self::gyg_log('outbound', '/1/deals', 'POST', 200, $body, $result);
        return rest_ensure_response($result);
    }

    /**
     * DELETE /bt-regiondo/v1/gyg/deals/{id}
     * Supprime un deal GYG.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_delete_deal(\WP_REST_Request $req): \WP_REST_Response {
        $deal_id = sanitize_text_field($req->get_param('id'));

        if (empty($deal_id)) {
            return rest_ensure_response(new \WP_Error('missing_id', 'deal id requis', ['status' => 400]));
        }

        $result = GygClient::delete_deal($deal_id);

        if (is_wp_error($result)) {
            self::gyg_log('outbound', '/1/deals/' . $deal_id, 'DELETE', 500, null, null, $result->get_error_message());
            return rest_ensure_response(new \WP_Error('gyg_error', $result->get_error_message(), ['status' => 502]));
        }

        self::gyg_log('outbound', '/1/deals/' . $deal_id, 'DELETE', 200, null, $result);
        return rest_ensure_response(['success' => true]);
    }

    // ─── Activation produit (P3.2) ────────────────────────────────────────────

    /**
     * POST /bt-regiondo/v1/gyg/products/{gyg_option_id}/activate
     *
     * Réactive un produit désactivé par GYG et met à jour le mapping.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_activate_product(\WP_REST_Request $req): \WP_REST_Response {
        $gyg_option_id = (int) $req->get_param('gyg_option_id');

        if ($gyg_option_id <= 0) {
            return rest_ensure_response(new \WP_Error('invalid_id', 'gyg_option_id invalide', ['status' => 400]));
        }

        // Trouver le notre_product_id dans le mapping
        $product_map        = json_decode(get_option('bt_gyg_product_map', '[]'), true);
        $external_product_id = '';
        $found              = false;

        if (is_array($product_map)) {
            foreach ($product_map as $entry) {
                if ((int) ($entry['gyg_option_id'] ?? 0) === $gyg_option_id) {
                    $external_product_id = sanitize_text_field($entry['notre_product_id'] ?? '');
                    $found = true;
                    break;
                }
            }
        }

        if (!$found) {
            return rest_ensure_response(new \WP_Error('not_found', 'Produit non trouvé dans le mapping', ['status' => 404]));
        }

        // Appeler l'API GYG pour activer le produit
        $result = GygClient::activate_product($gyg_option_id, $external_product_id);

        if (is_wp_error($result)) {
            self::gyg_log('outbound', '/1/products/' . $gyg_option_id . '/activate', 'PATCH', 500, null, null, $result->get_error_message());
            return rest_ensure_response(new \WP_Error('gyg_error', $result->get_error_message(), ['status' => 502]));
        }

        // Mettre à jour active=true dans le mapping
        $updated_map = [];
        foreach ($product_map as $entry) {
            if ((int) ($entry['gyg_option_id'] ?? 0) === $gyg_option_id) {
                $entry['active'] = true;
            }
            $updated_map[] = $entry;
        }
        update_option('bt_gyg_product_map', wp_json_encode($updated_map));

        self::gyg_log('outbound', '/1/products/' . $gyg_option_id . '/activate', 'PATCH', 200, null, $result);

        return rest_ensure_response(['success' => true]);
    }

    // ─── Register supplier (P3.3) ─────────────────────────────────────────────

    /**
     * POST /bt-regiondo/v1/gyg/register-supplier
     *
     * Enregistre ce supplier sur la plateforme GYG.
     * Usage unique lors de l'onboarding initial.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_register_supplier(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params() ?: [];

        $result = GygClient::register_supplier($body);

        if (is_wp_error($result)) {
            self::gyg_log('outbound', '/1/suppliers', 'POST', 500, $body, null, $result->get_error_message());
            return rest_ensure_response(new \WP_Error('gyg_error', $result->get_error_message(), ['status' => 502]));
        }

        self::gyg_log('outbound', '/1/suppliers', 'POST', 200, $body, $result);
        return rest_ensure_response($result);
    }

    // ─── Logs (P3.5) ──────────────────────────────────────────────────────────

    /**
     * GET /bt-regiondo/v1/gyg/logs
     * Retourne les logs GYG paginés.
     *
     * @param \WP_REST_Request $req
     * @return \WP_REST_Response
     */
    public function handle_get_logs(\WP_REST_Request $req): \WP_REST_Response {
        $result = GygLogsDb::query([
            'page'      => (int)    ($req->get_param('page')      ?: 1),
            'per_page'  => (int)    ($req->get_param('per_page')  ?: 50),
            'direction' => (string) ($req->get_param('direction') ?: ''),
            'from'      => (string) ($req->get_param('from')      ?: ''),
            'to'        => (string) ($req->get_param('to')        ?: ''),
        ]);

        // Ajouter les stats au retour
        $result['stats'] = GygLogsDb::get_stats();

        return rest_ensure_response($result);
    }
}

// ─── Helper global ────────────────────────────────────────────────────────────

/**
 * Construit une réponse d'erreur au format GYG Supplier API.
 *
 * @param string $code    Code d'erreur GYG (ex: AUTHORIZATION_FAILURE)
 * @param string $message Message lisible
 * @param int    $status  Code HTTP de la réponse
 * @return \WP_REST_Response
 */
function gyg_error_response(string $code, string $message, int $status): \WP_REST_Response {
    return new \WP_REST_Response(
        [
            'errorCode'    => $code,
            'errorMessage' => $message,
        ],
        $status
    );
}
