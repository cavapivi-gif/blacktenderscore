<?php
namespace BlackTenders\Api\Regiondo;

defined('ABSPATH') || exit;

class Cache {

    private int $ttl;

    public function __construct() {
        // TTL configurable dans les settings (défaut : 1h)
        $this->ttl = (int) get_option('bt_cache_ttl', 3600);
    }

    public function get(string $key): mixed {
        return get_transient($key);
    }

    public function set(string $key, mixed $data): void {
        set_transient($key, $data, $this->ttl);
    }

    public function flush(): void {
        global $wpdb;
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_bt_regiondo_%'
             OR option_name LIKE '_transient_timeout_bt_regiondo_%'"
        );
    }
}