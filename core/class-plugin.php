<?php
namespace BT_Regiondo\Core;

use BT_Regiondo\Admin\Settings\Settings;
use BT_Regiondo\Admin\MetaBox\MetaBox;
use BT_Regiondo\Frontend\Shortcode\Shortcode;

defined('ABSPATH') || exit;

class Plugin {

    public function init(): void {
        // Settings (clés API) — admin only
        if (is_admin()) {
            (new Settings())->init();
            (new MetaBox())->init();
        }

        // Shortcode dispo partout
        (new Shortcode())->init();
    }
}