<?php
namespace BlackTenders\Core;

use BlackTenders\Admin\MetaBox\MetaBox;
use BlackTenders\Admin\Backoffice\Backoffice;
use BlackTenders\Admin\Backoffice\RestApi;
use BlackTenders\Admin\Backoffice\Sync;
use BlackTenders\Elementor\ElementorManager;

defined('ABSPATH') || exit;

class Plugin {

    public function init(): void {
        // REST API disponible partout (front + admin)
        (new RestApi())->init();

        if (is_admin()) {
            (new Backoffice())->init();
            (new MetaBox())->init();
        }

        // Widgets Elementor (se branche sur elementor/loaded)
        (new ElementorManager())->init();

        // Injecte le style Google Maps global sur le front.
        // Tourne toujours pour gérer : style global, overrides per-widget, désactivation per-widget.
        //
        // data-bt-map-style sur le wrapper Elementor du widget :
        //   absent   → utiliser le style global
        //   'none'   → ne pas styler ce widget (désactivé dans l'onglet Advanced)
        //   '[...]'  → preset JSON spécifique pour ce widget uniquement
        add_action('wp_footer', static function(): void {
            $json  = get_option('bt_map_style_json', '');
            $style = json_decode($json, true);
            // Style global — tableau vide si non défini ou invalide
            $encoded = wp_json_encode(is_array($style) ? $style : []);
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo "<script id=\"bt-map-global-style\">(function(){"
               . "var g={$encoded};"
               . "function getStyle(el){"
               .   "var w=el&&el.closest('[data-bt-map-style]');"
               .   "if(w){"
               .     "var a=w.getAttribute('data-bt-map-style');"
               .     "if(a==='none')return null;"  // désactivé explicitement
               .     "try{return JSON.parse(a);}catch{}"
               .   "}"
               .   "return g;"
               . "}"
               . "function p(){"
               .   "if(!window.google||!google.maps||!google.maps.Map)return;"
               .   "var O=google.maps.Map;"
               .   "google.maps.Map=function(e,o){"
               .     "o=o||{};"
               .     "if(!o.styles||!o.styles.length){"
               .       "var s=getStyle(e);"
               .       "if(s&&s.length)o.styles=s;" // null → pas de style, [] → pas de style
               .     "}"
               .     "return new O(e,o);"
               .   "};"
               .   "google.maps.Map.prototype=O.prototype;"
               .   "Object.setPrototypeOf(google.maps.Map,O);"
               . "}"
               . "if(window.google&&google.maps&&google.maps.Map){p();}"
               . "else{var t=setInterval(function(){if(window.google&&google.maps&&google.maps.Map){clearInterval(t);p();}},50);}"
               . "})();</script>\n";
        }, 20);

        // Invalide le cache carte Static Maps quand un post est sauvegardé
        add_action('save_post', static function(int $post_id): void {
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bt_map_%' OR option_name LIKE '_transient_timeout_bt_map_%'"
            );
        });

        // Cron : intervalle personnalisé + hook de sync
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
        add_action('bt_auto_sync', [Sync::class, 'cron_run']);
    }

    public function add_cron_intervals(array $schedules): array {
        $schedules['bt_30min'] = [
            'interval' => 30 * MINUTE_IN_SECONDS,
            'display'  => 'Toutes les 30 minutes',
        ];
        $schedules['bt_6hours'] = [
            'interval' => 6 * HOUR_IN_SECONDS,
            'display'  => 'Toutes les 6 heures',
        ];
        return $schedules;
    }
}
