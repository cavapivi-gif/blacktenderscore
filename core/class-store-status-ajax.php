<?php
namespace BlackTenders\Core;

defined('ABSPATH') || exit;

/**
 * Handler AJAX pour le statut d'ouverture d'un store (CPT studiojaecore).
 *
 * Utilise lsm_get_store_opening_status() de studiojaecore pour déterminer
 * si le store est actuellement ouvert. Retourne { online: bool, next_open: string }.
 *
 * Accessible en no-priv (visiteurs non connectés) pour le widget Fixed CTA.
 */
class StoreStatusAjax {

    public function init(): void {
        add_action('wp_ajax_btc_get_store_status',        [$this, 'handle']);
        add_action('wp_ajax_nopriv_btc_get_store_status', [$this, 'handle']);
    }

    /**
     * Traite la requête AJAX.
     * Input : store_id, nonce
     * Output : { online: bool, next_open: string }
     */
    public function handle(): void {
        check_ajax_referer('btc_store_nonce', 'nonce');

        $store_id = absint($_POST['store_id'] ?? 0);

        if (!$store_id || get_post_type($store_id) !== 'store') {
            wp_send_json_error(['message' => 'Invalid store ID']);
            exit;
        }

        // Vérifie que studiojaecore est actif et la fonction disponible
        if (!function_exists('lsm_get_store_opening_status')) {
            wp_send_json_error(['message' => 'Store manager not available']);
            exit;
        }

        $status = lsm_get_store_opening_status($store_id);

        $online_statuses = ['open', 'closing-soon', 'opening-soon'];
        $is_online       = in_array($status['status'] ?? '', $online_statuses, true);
        $next_open       = $status['message'] ?? '';

        wp_send_json_success([
            'online'    => $is_online,
            'status'    => $status['status'] ?? 'unknown',
            'next_open' => $next_open,
        ]);
        exit;
    }
}
