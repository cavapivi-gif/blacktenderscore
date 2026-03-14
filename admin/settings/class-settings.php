<?php
namespace BlackTenders\Admin\Settings;

defined('ABSPATH') || exit;

class Settings {

    public function init(): void {
        add_action('admin_menu',    [$this, 'add_page']);
        add_action('admin_init',    [$this, 'register_fields']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function add_page(): void {
        add_options_page(
            'Regiondo — BlackTenders',
            '🎫 Regiondo',
            'manage_options',
            'bt-settings',
            [$this, 'render']
        );
    }

    public function register_fields(): void {
        register_setting('bt_settings', 'bt_public_key',  ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('bt_settings', 'bt_secret_key',  ['sanitize_callback' => 'sanitize_text_field']);
        register_setting('bt_settings', 'bt_cache_ttl',   ['sanitize_callback' => 'absint']);
        register_setting('bt_settings', 'bt_post_types',  ['sanitize_callback' => [$this, 'sanitize_post_types']]);
        register_setting('bt_settings', 'bt_widget_map', ['sanitize_callback' => [$this, 'sanitize_widget_map']]);
    }

    public function sanitize_widget_map(mixed $value): array {
        if (!is_array($value)) return [];
        $clean = [];
        foreach ($value as $product_id => $widget_id) {
            $pid = absint($product_id);
            if ($pid > 0) {
                $clean[$pid] = sanitize_text_field($widget_id);
            }
        }
        return $clean;
    }

    public function sanitize_post_types(mixed $value): array {
        if (!is_array($value)) return ['excursion'];
        return array_map('sanitize_text_field', $value);
    }

    public function enqueue(string $hook): void {
        if ($hook !== 'settings_page_bt-settings') return;
    }

    public function render(): void {
        if (!current_user_can('manage_options')) return;

        // Flush cache si demandé (avec vérification nonce CSRF)
        if (isset($_POST['bt_flush_cache'])) {
            check_admin_referer('bt_flush_cache_action', 'bt_flush_cache_nonce');
            (new \BlackTenders\Api\Regiondo\Cache())->flush();
            echo '<div class="notice notice-success"><p>Cache Regiondo vidé.</p></div>';
        }
        ?>
        <div class="wrap bt-regiondo-settings">
            <h1>🎫 Regiondo — Configuration</h1>

            <form method="post" action="options.php">
                <?php settings_fields('bt_settings'); ?>

                <table class="form-table">
                    <tr>
                        <th>Clé publique (Public Key)</th>
                        <td>
                            <input type="text" name="bt_public_key"
                                   value="<?= esc_attr(get_option('bt_public_key')) ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Clé secrète (Secret Key)</th>
                        <td>
                            <input type="password" name="bt_secret_key"
                                   value="<?= esc_attr(get_option('bt_secret_key')) ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th>Durée du cache (secondes)</th>
                        <td>
                            <input type="number" name="bt_cache_ttl"
                                   value="<?= esc_attr(get_option('bt_cache_ttl', 3600)) ?>"
                                   class="small-text" min="60" />
                            <p class="description">3600 = 1h recommandé</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Post types actifs</th>
                        <td>
                            <?php
                            $saved = get_option('bt_post_types', ['excursion']);
                            $all   = get_post_types(['public' => true], 'objects');
                            foreach ($all as $pt): ?>
                                <label style="display:block; margin-bottom:4px">
                                    <input type="checkbox" name="bt_post_types[]"
                                           value="<?= esc_attr($pt->name) ?>"
                                           <?= in_array($pt->name, $saved) ? 'checked' : '' ?>>
                                    <?= esc_html($pt->label) ?> <code><?= esc_html($pt->name) ?></code>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>

                <hr>
                <h2>🔗 Mapping Produit → Widget ID</h2>
                <p class="description">Saisir une seule fois le Widget ID Regiondo par produit. Trouvable dans <strong>Shop Config → Website Integration → Booking Widgets</strong>.</p>

                <button type="button" id="bt-load-mapping" class="button button-secondary">
                    🔄 Charger les produits
                </button>

                <div id="bt-widget-map-table" style="margin-top:16px">
                    <?php
                    $map      = get_option('bt_widget_map', []);
                    $products = (new \BlackTenders\Api\Regiondo\Client())->get_products('fr-FR');
                    foreach ($products as $p):
                        $wid = $map[$p['product_id']] ?? '';
                    ?>
                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px; padding:8px; background:#f9f9f9; border-radius:4px">
                        <span style="flex:1; font-weight:500"><?= esc_html($p['name']) ?></span>
                        <code style="color:#999">#<?= esc_html($p['product_id']) ?></code>
                        <input type="text"
                               name="bt_widget_map[<?= esc_attr($p['product_id']) ?>]"
                               value="<?= esc_attr($wid) ?>"
                               placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                               style="font-family:monospace; font-size:11px; width:320px"
                               class="<?= !empty($wid) ? 'bt-wid-filled' : '' ?>" />
                        <span><?= !empty($wid) ? '✅' : '⚠️' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php submit_button('Enregistrer'); ?>
            </form>

            <form method="post">
                <?php wp_nonce_field('bt_flush_cache_action', 'bt_flush_cache_nonce'); ?>
                <hr>
                <h2>Maintenance</h2>
                <p>
                    <button type="submit" name="bt_flush_cache" class="button button-secondary">
                        🗑 Vider le cache Regiondo
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}