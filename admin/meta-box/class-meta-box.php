<?php
namespace BT_Regiondo\Admin\MetaBox;

use BT_Regiondo\Api\Regiondo\Client;

defined('ABSPATH') || exit;

class MetaBox {

    public function init(): void {
        add_action('add_meta_boxes',        [$this, 'register']);
        add_action('save_post',             [$this, 'save']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        add_action('wp_ajax_bt_regiondo_fetch_products', [$this, 'ajax_fetch_products']);
    }

    public function register(): void {
        $post_types = get_option('bt_regiondo_post_types', ['excursion']);

        foreach ($post_types as $pt) {
            add_meta_box(
                'bt-regiondo-tickets',
                '🎫 Tickets Regiondo',
                [$this, 'render'],
                $pt,
                'side',
                'high'
            );
        }
    }

    public function render(\WP_Post $post): void {
        wp_nonce_field('bt_regiondo_save', 'bt_regiondo_nonce');
        $saved = get_post_meta($post->ID, '_bt_regiondo_tickets', true) ?: [];
        require BT_REGIONDO_DIR . 'admin/meta-box/template.php';
    }

    public function save(int $post_id): void {
        if (!isset($_POST['bt_regiondo_nonce'])) return;
        if (!wp_verify_nonce($_POST['bt_regiondo_nonce'], 'bt_regiondo_save')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $tickets = [];
        $map     = get_option('bt_regiondo_widget_map', []);

        if (!empty($_POST['bt_regiondo_tickets']) && is_array($_POST['bt_regiondo_tickets'])) {
            foreach ($_POST['bt_regiondo_tickets'] as $ticket) {
                $product_id = absint($ticket['product_id'] ?? 0);
                $label      = sanitize_text_field($ticket['label'] ?? '');

                if ($product_id > 0) {
                    $widget_id = $map[$product_id] ?? sanitize_text_field($ticket['widget_id'] ?? '');
                    $tickets[] = compact('product_id', 'widget_id', 'label');
                }
            }
        }

        update_post_meta($post_id, '_bt_regiondo_tickets', $tickets);
    }

    public function enqueue(string $hook): void {
        if (!in_array($hook, ['post.php', 'post-new.php'])) return;

        $screen = get_current_screen();
        $types  = get_option('bt_regiondo_post_types', ['excursion']);
        if (!in_array($screen->post_type, $types)) return;

        wp_enqueue_style(
            'bt-regiondo-meta-box',
            BT_REGIONDO_URL . 'admin/meta-box/meta-box.css',
            [],
            BT_REGIONDO_VERSION
        );

        wp_enqueue_script(
            'bt-regiondo-meta-box',
            BT_REGIONDO_URL . 'admin/meta-box/meta-box.js',
            ['jquery'],
            BT_REGIONDO_VERSION,
            true
        );

        wp_localize_script('bt-regiondo-meta-box', 'btRegionado', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('bt_regiondo_fetch'),
        ]);
    }

    public function ajax_fetch_products(): void {
        check_ajax_referer('bt_regiondo_fetch', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission refusée.');
        }

        $client   = new Client();
        $products = $client->get_products('fr-FR');

        wp_send_json_success($products);
    }
}