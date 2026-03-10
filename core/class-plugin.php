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
