<?php
/**
 * Plugin Name: BlackTenders Core
 * Plugin URI:  https://studiojae.fr
 * Description: CPT Excursion & Bateau — widgets Elementor, API Regiondo, backoffice
 * Version:     1.0.1
 * Author:      StudioJae
 * Text Domain: blacktenderscore
 *
 * to be waited
 */

defined('ABSPATH') || exit;

define('BT_VERSION',  '1.0.1');
define('BT_DIR',      plugin_dir_path(__FILE__));
define('BT_URL',      plugin_dir_url(__FILE__));

require_once BT_DIR . 'core/class-loader.php';
require_once BT_DIR . 'core/class-plugin.php';

function bt_run(): void {
    $plugin = new BlackTenders\Core\Plugin();
    $plugin->init();
}
bt_run();