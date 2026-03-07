<?php
namespace BT_Regiondo\Core;

use BT_Regiondo\Admin\Settings\Settings;
use BT_Regiondo\Admin\MetaBox\MetaBox;
use BT_Regiondo\Admin\Backoffice\Backoffice;
use BT_Regiondo\Admin\Backoffice\RestApi;
use BT_Regiondo\Frontend\Shortcode\Shortcode;

defined('ABSPATH') || exit;

class Plugin {

    public function init(): void {
        // REST API disponible partout (front + admin)
        (new RestApi())->init();

        if (is_admin()) {
            (new Backoffice())->init();
            (new Settings())->init();
            (new MetaBox())->init();
        }

        // Shortcode dispo partout
        (new Shortcode())->init();
    }
}