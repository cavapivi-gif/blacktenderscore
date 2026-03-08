<?php
namespace BlackTenders\Admin\Backoffice;

use BlackTenders\Api\Regiondo\Client;

defined('ABSPATH') || exit;

/**
 * Synchronise les produits Regiondo vers des posts WordPress.
 *
 * Pour chaque produit :
 *   - Crée ou met à jour un post du CPT configuré (ex: excursion)
 *   - Sauve le product_id en meta (_bt_regiondo_product_id)
 *   - Sauve les tickets en meta (_bt_regiondo_tickets) → shortcode opérationnel
 *   - Remplit les champs ACF si ACF est actif
 */
class Sync {

    private Client $client;
    private array  $post_types;

    public function __construct() {
        $this->client     = new Client();
        $this->post_types = get_option('bt_post_types', ['excursion']);
    }

    /** Callback WP cron → appelle run() silencieusement */
    public static function cron_run(): void {
        (new self())->run();
    }

    /**
     * Lance la synchronisation.
     *
     * @param int[]|null $product_ids null = tous les produits
     * @return array { created, updated, errors, log[] }
     */
    public function run(?array $product_ids = null): array {
        $post_type = $this->post_types[0] ?? 'excursion';
        $result    = ['created' => 0, 'updated' => 0, 'errors' => 0, 'log' => []];

        $products = $this->client->get_products('fr-FR');
        if (empty($products)) {
            $result['log'][] = 'Aucun produit Regiondo trouvé.';
            return $result;
        }

        if ($product_ids) {
            $products = array_filter($products, fn($p) => in_array($p['product_id'], $product_ids));
        }

        $widget_map = get_option('bt_widget_map', []);

        foreach ($products as $product) {
            try {
                $detail = $this->client->get_product($product['product_id'], 'fr-FR');
                $this->sync_one($product, $detail, $post_type, $widget_map, $result);
            } catch (\Exception $e) {
                $result['errors']++;
                $result['log'][] = 'Erreur #' . $product['product_id'] . ' : ' . $e->getMessage();
            }
        }

        return $result;
    }

    private function sync_one(array $product, array $detail, string $post_type, array $widget_map, array &$result): void {
        $product_id = $product['product_id'];

        // Cherche un post existant
        $existing = get_posts([
            'post_type'   => $post_type,
            'meta_key'    => '_bt_regiondo_product_id',
            'meta_value'  => $product_id,
            'numberposts' => 1,
            'post_status' => 'any',
            'fields'      => 'ids',
        ]);

        $description = wp_kses_post($detail['description'] ?? '');
        $name        = sanitize_text_field($product['name']);

        $post_data = [
            'post_title'   => $name,
            'post_content' => $description,
            'post_status'  => 'publish',
            'post_type'    => $post_type,
        ];

        if ($existing) {
            $post_data['ID'] = $existing[0];
            wp_update_post($post_data);
            $post_id = $existing[0];
            $result['updated']++;
            $result['log'][] = "Mis à jour : {$name} (#{$product_id})";
        } else {
            $post_id = wp_insert_post($post_data);
            if (is_wp_error($post_id)) {
                $result['errors']++;
                $result['log'][] = "Erreur création : {$name}";
                return;
            }
            $result['created']++;
            $result['log'][] = "Créé : {$name} (#{$product_id})";
        }

        // Metas de base
        update_post_meta($post_id, '_bt_regiondo_product_id', $product_id);
        update_post_meta($post_id, '_bt_regiondo_base_price', $product['base_price'] ?? 0);
        update_post_meta($post_id, '_bt_regiondo_currency',   $product['currency'] ?? 'EUR');

        // Image à la une depuis la première image du produit
        if (!empty($detail['images'][0]['url'])) {
            $this->maybe_set_thumbnail($post_id, $detail['images'][0]['url'], $name);
        }

        // Tickets → active le shortcode automatiquement
        $widget_id = $widget_map[$product_id] ?? '';
        $tickets   = [[
            'product_id' => $product_id,
            'label'      => $name,
            'widget_id'  => $widget_id,
        ]];
        update_post_meta($post_id, '_bt_regiondo_tickets', $tickets);

        // Champs ACF (si ACF actif)
        if (function_exists('update_field')) {
            update_field('regiondo_product_id', $product_id, $post_id);
            update_field('regiondo_base_price', $product['base_price'] ?? 0, $post_id);
            update_field('regiondo_currency',   $product['currency'] ?? 'EUR', $post_id);
            if (!empty($detail['category_id'])) {
                update_field('regiondo_category_id', $detail['category_id'], $post_id);
            }
        }
    }

    /**
     * Télécharge et attache l'image à la une si elle n'existe pas déjà.
     */
    private function maybe_set_thumbnail(int $post_id, string $image_url, string $title): void {
        if (has_post_thumbnail($post_id)) return;

        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) return;

        $file_array = [
            'name'     => sanitize_file_name($title . '.jpg'),
            'tmp_name' => $tmp,
        ];

        $attach_id = media_handle_sideload($file_array, $post_id);
        if (!is_wp_error($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
        }
    }
}
