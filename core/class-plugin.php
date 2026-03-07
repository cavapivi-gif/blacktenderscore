<?php
namespace BT_Regiondo\Core;

use BT_Regiondo\Admin\MetaBox\MetaBox;
use BT_Regiondo\Admin\Backoffice\Backoffice;
use BT_Regiondo\Admin\Backoffice\RestApi;
use BT_Regiondo\Admin\Backoffice\Sync;
use BT_Regiondo\Frontend\Shortcode\Shortcode;
use BT_Regiondo\Elementor\ElementorManager;

defined('ABSPATH') || exit;

class Plugin {

    public function init(): void {
        // REST API disponible partout (front + admin)
        (new RestApi())->init();

        if (is_admin()) {
            (new Backoffice())->init();
            (new MetaBox())->init();
        }

        // Shortcode dispo partout
        (new Shortcode())->init();

        // Widgets Elementor (se branche sur elementor/loaded)
        (new ElementorManager())->init();

        // Cron : intervalle personnalisé + hook de sync
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
        add_action('bt_regiondo_auto_sync', [Sync::class, 'cron_run']);
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
