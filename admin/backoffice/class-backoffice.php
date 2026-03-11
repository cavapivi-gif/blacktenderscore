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

        wp_enqueue_style(
            'bt-backoffice',
            $build_url . 'index.css',
            [],
            BT_VERSION
        );

        wp_enqueue_script(
            'bt-backoffice',
            $build_url . 'index.js',
            [],
            BT_VERSION,
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
                body.toplevel_page_blacktenderscore,
                body.toplevel_page_blacktenderscore #wpwrap,
                body.toplevel_page_blacktenderscore #wpcontent,
                body.toplevel_page_blacktenderscore #wpbody,
                body.toplevel_page_blacktenderscore #wpbody-content { background: #fff !important; }
                #wpcontent { padding-left: 0 !important; margin-left: 0 !important; }
                #wpfooter  { display: none !important; }
                .notice, .update-nag, #screen-meta { display: none !important; }
            </style>';
        });
    }
}
