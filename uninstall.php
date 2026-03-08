<?php
defined('WP_UNINSTALL_PLUGIN') || exit;

// Nettoie les options
delete_option('bt_public_key');
delete_option('bt_secret_key');
delete_option('bt_cache_ttl');
delete_option('bt_post_types');

// Nettoie les transients
global $wpdb;
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_bt_%'
     OR option_name LIKE '_transient_timeout_bt_%'"
);