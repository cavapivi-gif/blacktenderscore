<?php
namespace BlackTenders\Admin\Backoffice;

use BlackTenders\Api\Regiondo\Client;

defined('ABSPATH') || exit;

/**
 * Synchronise les produits Regiondo vers l'option bt_synced_products.
 *
 * IMPORTANT : ne crée AUCUN post WordPress.
 * Les données restent dans les options WP uniquement pour le backoffice React.
 */
class Sync {

    private Client $client;

    public function __construct() {
        $this->client = new Client();
    }

    /** Callback WP cron → appelle run() silencieusement */
    public static function cron_run(): void {
        (new self())->run();
    }

    /**
     * Lance la synchronisation.
     * Uses a transient lock to prevent concurrent sync runs.
     *
     * @param int[]|null $product_ids null = tous les produits
     * @return array { created, updated, errors, log[] }
     */
    public function run(?array $product_ids = null): array {
        $result = ['created' => 0, 'updated' => 0, 'errors' => 0, 'log' => []];

        // Prevent concurrent sync runs (lock for 5 minutes max)
        if (get_transient('bt_sync_lock')) {
            $result['log'][] = 'Sync déjà en cours, abandon.';
            return $result;
        }
        set_transient('bt_sync_lock', 1, 300);

        $products = $this->client->get_products('fr-FR');
        if (empty($products)) {
            $result['log'][] = 'Aucun produit Regiondo trouvé.';
            return $result;
        }

        if ($product_ids) {
            $products = array_filter($products, fn($p) => in_array($p['product_id'], $product_ids));
        }

        $existing = get_option('bt_synced_products', []);
        $widget_map = get_option('bt_widget_map', []);

        foreach ($products as $product) {
            try {
                $pid    = $product['product_id'];
                $detail = $this->client->get_product($pid, 'fr-FR');

                $entry = [
                    'product_id'  => $pid,
                    'name'        => sanitize_text_field($product['name']),
                    'description' => wp_kses_post($detail['description'] ?? ''),
                    'base_price'  => $product['base_price'] ?? 0,
                    'currency'    => $product['currency'] ?? 'EUR',
                    'category_id' => $detail['category_id'] ?? null,
                    'images'      => array_slice($detail['images'] ?? [], 0, 5),
                    'widget_id'   => $widget_map[$pid] ?? '',
                    'synced_at'   => gmdate('Y-m-d H:i:s'),
                ];

                $is_new = !isset($existing[$pid]);
                $existing[$pid] = $entry;

                if ($is_new) {
                    $result['created']++;
                    $result['log'][] = "Créé : {$entry['name']} (#{$pid})";
                } else {
                    $result['updated']++;
                    $result['log'][] = "Mis à jour : {$entry['name']} (#{$pid})";
                }
            } catch (\Exception $e) {
                $result['errors']++;
                $result['log'][] = 'Erreur #' . ($product['product_id'] ?? '?') . ' : ' . $e->getMessage();
            }
        }

        update_option('bt_synced_products', $existing, false);
        delete_transient('bt_sync_lock');

        return $result;
    }
}
