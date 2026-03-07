<?php
namespace BT_Regiondo\Admin\Backoffice;

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
            'bt-regiondo',
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
        if ($hook !== 'toplevel_page_bt-regiondo') return;

        $build_dir = BT_REGIONDO_DIR . 'admin/backoffice/build/assets/';
        $build_url = BT_REGIONDO_URL . 'admin/backoffice/build/assets/';

        if (!is_dir($build_dir)) return;

        // Désactive les styles WP admin qui pourraient interférer
        wp_enqueue_style(
            'bt-backoffice',
            $build_url . 'index.css',
            [],
            BT_REGIONDO_VERSION
        );

        wp_enqueue_script(
            'bt-backoffice',
            $build_url . 'index.js',
            [],
            BT_REGIONDO_VERSION,
            true
        );

        wp_localize_script('bt-backoffice', 'btBackoffice', [
            'rest_url' => rest_url('bt-regiondo/v1'),
            'nonce'    => wp_create_nonce('wp_rest'),
            'version'  => BT_REGIONDO_VERSION,
        ]);

        // Supprime la barre d'admin WP pour avoir la page en plein écran
        add_action('admin_head', function () {
            echo '<style>
                #wpcontent { padding-left: 0 !important; margin-left: 0 !important; }
                #wpfooter  { display: none !important; }
                .notice, .update-nag, #screen-meta { display: none !important; }
            </style>';
        });
    }
}
