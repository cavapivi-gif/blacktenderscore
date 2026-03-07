<?php
namespace BT_Regiondo\Frontend\Shortcode;

use BT_Regiondo\Frontend\Widget\Renderer;

defined('ABSPATH') || exit;

class Shortcode {

    public function init(): void {
        add_shortcode('regiondo_widget', [$this, 'render']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    /**
     * [regiondo_widget] ou [regiondo_widget post_id="42"]
     */
    public function render(array $atts): string {
        $atts = shortcode_atts([
            'post_id' => get_the_ID(),
        ], $atts);

        $post_id = (int) $atts['post_id'];
        $tickets = get_post_meta($post_id, '_bt_regiondo_tickets', true) ?: [];

        if (empty($tickets)) return '';

        $renderer = new Renderer();
        return $renderer->render($tickets);
    }

    public function enqueue(): void {
        wp_enqueue_style(
            'bt-regiondo-widget',
            BT_REGIONDO_URL . 'frontend/widget/widget.css',
            [],
            BT_REGIONDO_VERSION
        );
        wp_enqueue_script(
            'bt-regiondo-widget',
            BT_REGIONDO_URL . 'frontend/widget/widget.js',
            [],
            BT_REGIONDO_VERSION,
            true
        );
    }
}