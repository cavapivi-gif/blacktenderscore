<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

class Backoffice {

    public function init(): void {
        add_action('admin_menu',            [$this, 'add_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);

        // Vite génère du code ESM qui utilise import.meta — nécessite type="module"
        add_filter('script_loader_tag', [$this, 'add_module_type'], 10, 2);
    }

    /**
     * Ajoute type="module" au script Vite pour que WordPress le charge en ESM.
     * Sans ça, import.meta lance une SyntaxError dans le contexte non-module.
     */
    public function add_module_type(string $tag, string $handle): string {
        if ($handle !== 'bt-backoffice') return $tag;
        return str_replace(' src=', ' type="module" src=', $tag);
    }

    public function add_page(): void {
        // 'read' = capability minimale (tous les users connectés).
        // Le vrai contrôle d'accès est géré par bt_role_permissions dans le plugin.
        add_menu_page(
            'BlackTenders',
            'BlackTenders',
            'read',
            'blacktenderscore',
            [$this, 'render'],
            'dashicons-anchor',
            30
        );
    }

    public function render(): void {
        if (!is_user_logged_in()) return;
        // Wrapper isolé : la div interne est le vrai React root.
        // Le wrapper absorbe les notices WP injectées par d'autres plugins
        // avant le mount React, évitant le bug removeChild.
        ?>
        <div id="bt-backoffice-wrap">
            <div id="bt-backoffice-root"></div>
        </div>
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

        $cur = wp_get_current_user();
        wp_localize_script('bt-backoffice', 'btBackoffice', [
            'rest_url'        => rest_url('bt-regiondo/v1'),
            'nonce'           => wp_create_nonce('wp_rest'),
            'version'         => BT_VERSION,
            'onboarding_done' => (bool) get_option('bt_onboarding_done', false),
            'ajax_url'        => admin_url('admin-ajax.php'),
            'current_user'    => [
                'id'     => $cur->ID,
                'name'   => $cur->display_name,
                'email'  => $cur->user_email,
                'roles'  => $cur->roles,
                'avatar' => get_avatar_url($cur->ID, ['size' => 32]),
                'color'  => '#f0f0ee',
            ],
        ]);

        // Supprime toutes les notices admin pour éviter la pollution du DOM React
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');

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
