<?php
/**
 * Plugin Name: BlackTenders Regiondo
 * Plugin URI:  https://studiojae.fr
 * Description: Liaison Regiondo ↔ CPT Excursion — meta box backoffice + shortcode front
 * Version:     1.0.1
 * Author:      StudioJae
 * Text Domain: bt-regiondo
 */

defined('ABSPATH') || exit;

define('BT_REGIONDO_VERSION',  '1.0.0');
define('BT_REGIONDO_DIR',      plugin_dir_path(__FILE__));
define('BT_REGIONDO_URL',      plugin_dir_url(__FILE__));

require_once BT_REGIONDO_DIR . 'core/class-loader.php';
require_once BT_REGIONDO_DIR . 'core/class-plugin.php';

function bt_regiondo_run(): void {
    $plugin = new BT_Regiondo\Core\Plugin();
    $plugin->init();
}
bt_regiondo_run();