<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

class Backoffice {

    public function init(): void {
        add_action('admin_menu',            [$this, 'add_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function add_page(): void {
        add_menu_page(
            'BlackTenders',
            'BlackTenders',
            'manage_options',
            'blacktenderscore',
            [$this, 'render'],
            'dashicons-anchor',
            30
        );
    }

    public function render(): void {
        if (!current_user_can('manage_options')) return;
        ?>
        <div id="bt-backoffice-root"></div>
        <?php
    }

    public function enqueue(string $hook): void {
        if ($hook !== 'toplevel_page_blacktenderscore') return;

        $build_dir = BT_DIR . 'admin/backoffice/build/assets/';
        $build_url = BT_URL . 'admin/backoffice/build/assets/';

        if (!is_dir($build_dir)) return;

        // Cache-bust based on actual file modification time
        $css_ver = file_exists($build_dir . 'index.css') ? filemtime($build_dir . 'index.css') : BT_VERSION;
        $js_ver  = file_exists($build_dir . 'index.js')  ? filemtime($build_dir . 'index.js')  : BT_VERSION;

        wp_enqueue_style(
            'bt-backoffice',
            $build_url . 'index.css',
            [],
            $css_ver
        );

        wp_enqueue_script(
            'bt-backoffice',
            $build_url . 'index.js',
            [],
            $js_ver,
            true
        );

        wp_localize_script('bt-backoffice', 'btBackoffice', [
            'rest_url' => rest_url('bt-regiondo/v1'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'version'  => BT_VERSION,
        ]);

        // Page en plein écran, fond blanc uniforme
        add_action('admin_head', function () {
            echo '<style>
                body.toplevel_page_blacktenderscore #wpbody-content { padding-bottom: 0 !important; }
                body.toplevel_page_blacktenderscore #wpfooter { display: none !important; }
                body.toplevel_page_blacktenderscore .notice,
                body.toplevel_page_blacktenderscore .update-nag,
                body.toplevel_page_blacktenderscore #screen-meta { display: none !important; }
            </style>';
        });
    }
}
