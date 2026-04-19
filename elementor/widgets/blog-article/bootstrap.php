<?php
/**
 * Blog Article Widget — Bootstrap
 *
 * Drop this snippet into the plugin's widget registration flow (same pattern
 * as ContainerScroll, BoatPricing, Fixed_CTA).
 *
 * Usage:
 *   1. Place the widget folder at: plugins/blacktenderscore/widgets/blog-article/
 *   2. Require this file (or merge into your existing widget loader).
 *
 * @package BlackTendersCore
 */

defined('ABSPATH') || exit;

// Register style — enqueued on-demand by Elementor via get_style_depends().
add_action( 'wp_enqueue_scripts', static function () {
    wp_register_style(
        'bt-blog-article',
        BT_URL . 'elementor/widgets/blog-article/blog-article.css',
        [],
        BT_VERSION
    );
}, 5 );

// Register widget class with Elementor.
add_action( 'elementor/widgets/register', static function ( $widgets_manager ) {
    require_once __DIR__ . '/class-blog-article.php';
    $widgets_manager->register( new \BlackTenders\Elementor\Widgets\BlogArticle\Blog_Article() );
} );
