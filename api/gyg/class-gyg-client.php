<?php
namespace BlackTenders\Api\Gyg;

defined('ABSPATH') || exit;

/**
 * Client HTTP pour la GYG Supplier API.
 *
 * Toutes les requêtes sortantes utilisent wp_remote_request() + Basic Auth via GygAuth.
 * Les erreurs GYG ({ errorCode, errorMessage }) sont converties en WP_Error.
 *
 * @see https://api.getyourguide.com/docs/supplier
 */
class GygClient {

    const GYG_API_LIVE    = 'https://supplier-api.getyourguide.com/1';
    const GYG_API_SANDBOX = 'https://sandbox-supplier-api.getyourguide.com/1';

    /**
     * Retourne l'URL de base selon le mode configuré (sandbox|live).
     */
    private static function get_base_url(): string {
        $mode = get_option('bt_gyg_mode', 'sandbox');
        return $mode === 'live' ? self::GYG_API_LIVE : self::GYG_API_SANDBOX;
    }

    /**
     * Effectue une requête HTTP vers la GYG API.
     *
     * @param string $method Méthode HTTP (GET, POST, PATCH…)
     * @param string $path   Chemin relatif (ex: "/suppliers/123/products")
     * @param array  $body   Corps de la requête (encodé en JSON si non vide)
     * @return array|\WP_Error Tableau décodé ou WP_Error en cas d'erreur réseau/API
     */
    private static function request(string $method, string $path, array $body = []): array|\WP_Error {
        $url      = self::get_base_url() . $path;
        $auth     = GygAuth::get_outbound_header();

        $args = [
            'method'  => strtoupper($method),
            'timeout' => 30,
            'headers' => [
                'Authorization' => $auth,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ],
        ];

        if (!empty($body)) {
            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        // Erreur réseau / transport
        if (is_wp_error($response)) {
            return $response;
        }

        $code        = (int) wp_remote_retrieve_response_code($response);
        $raw_body    = wp_remote_retrieve_body($response);
        $decoded     = json_decode($raw_body, true);

        // Parser le format d'erreur GYG { errorCode, errorMessage }
        if (isset($decoded['errorCode'])) {
            return new \WP_Error(
                'gyg_api_error',
                $decoded['errorMessage'] ?? 'GYG API error',
                ['errorCode' => $decoded['errorCode'], 'http_code' => $code]
            );
        }

        // Réponse HTTP non-2xx sans errorCode structuré
        if ($code < 200 || $code >= 300) {
            return new \WP_Error(
                'gyg_http_error',
                "GYG API HTTP {$code}",
                ['body' => $raw_body, 'http_code' => $code]
            );
        }

        return is_array($decoded) ? $decoded : [];
    }

    // ─── Endpoints GYG sortants ──────────────────────────────────────────────

    /**
     * Notifie GYG d'une mise à jour de disponibilités pour un produit.
     * POST /1/notify-availability-update
     *
     * @param string $product_id    Identifiant produit GYG
     * @param array  $availabilities Tableau de créneaux mis à jour
     * @return array|\WP_Error
     */
    public static function notify_availability_update(string $product_id, array $availabilities): array|\WP_Error {
        return self::request('POST', '/notify-availability-update', [
            'data' => [
                'productId'      => $product_id,
                'availabilities' => $availabilities,
            ],
        ]);
    }

    /**
     * Active un produit GYG et le lie à notre identifiant produit interne.
     * PATCH /1/products/{gyg_option_id}/activate
     *
     * @param int    $gyg_option_id       ID option côté GYG
     * @param string $external_product_id Notre identifiant produit interne
     * @return array|\WP_Error
     */
    public static function activate_product(int $gyg_option_id, string $external_product_id): array|\WP_Error {
        return self::request('PATCH', "/products/{$gyg_option_id}/activate", [
            'data' => [
                'externalProductId' => $external_product_id,
            ],
        ]);
    }

    /**
     * Rembourse/valide une réservation GYG (redeem).
     * POST /1/redeem-booking
     *
     * @param string $gyg_booking_reference Référence booking GYG
     * @return array|\WP_Error
     */
    public static function redeem_booking(string $gyg_booking_reference): array|\WP_Error {
        return self::request('POST', '/redeem-booking', [
            'data' => [
                'gygBookingReference' => $gyg_booking_reference,
            ],
        ]);
    }

    /**
     * Valide un ticket individuel GYG.
     * POST /1/redeem-ticket
     *
     * @param string $ticket_code           Code billet à valider
     * @param string $gyg_booking_reference Référence booking associée
     * @return array|\WP_Error
     */
    public static function redeem_ticket(string $ticket_code, string $gyg_booking_reference): array|\WP_Error {
        return self::request('POST', '/redeem-ticket', [
            'data' => [
                'ticketCode'          => $ticket_code,
                'gygBookingReference' => $gyg_booking_reference,
            ],
        ]);
    }

    // ─── Deals (promotions) ──────────────────────────────────────────────────

    /**
     * Liste tous les deals (promotions) du supplier GYG.
     * GET /1/deals
     *
     * @return array|\WP_Error
     */
    public static function list_deals(): array|\WP_Error {
        return self::request('GET', '/deals');
    }

    /**
     * Crée un nouveau deal (promotion) GYG.
     * POST /1/deals
     *
     * @param array $deal_data Données du deal à créer
     * @return array|\WP_Error
     */
    public static function create_deal(array $deal_data): array|\WP_Error {
        return self::request('POST', '/deals', [
            'data' => $deal_data,
        ]);
    }

    /**
     * Supprime un deal GYG existant.
     * DELETE /1/deals/{dealId}
     *
     * @param string $deal_id Identifiant du deal à supprimer
     * @return array|\WP_Error
     */
    public static function delete_deal(string $deal_id): array|\WP_Error {
        return self::request('DELETE', '/deals/' . rawurlencode($deal_id));
    }

    // ─── Supplier registration ────────────────────────────────────────────────

    /**
     * Enregistre ce supplier sur la plateforme GYG.
     * POST /1/suppliers
     * Usage unique lors de l'onboarding initial.
     *
     * @param array $supplier_data Données du supplier (company_name, email, phone…)
     * @return array|\WP_Error
     */
    public static function register_supplier(array $supplier_data): array|\WP_Error {
        return self::request('POST', '/suppliers', [
            'data' => $supplier_data,
        ]);
    }

    /**
     * Test de connectivité : vérifie que les credentials sont valides.
     * GET /1/suppliers/{supplier_id}/products
     *
     * @return bool true si la réponse est 200, false sinon
     */
    public static function ping(): bool {
        $supplier_id = get_option('bt_gyg_supplier_id', '');

        if (empty($supplier_id)) {
            return false;
        }

        $result = self::request('GET', "/suppliers/{$supplier_id}/products");

        return !is_wp_error($result);
    }
}
