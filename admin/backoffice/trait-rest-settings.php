<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiSettings — gestion des réglages plugin, cache, Snazzy Maps, cron.
 */
trait RestApiSettings {

    public function get_settings(): \WP_REST_Response {
        try {
            $products = (new \BlackTenders\Api\Regiondo\Client())->get_products('fr-FR');
        } catch (\Throwable $e) {
            $products = [];
        }

        $next = wp_next_scheduled('bt_auto_sync');

        return rest_ensure_response([
            'public_key'     => get_option('bt_public_key', ''),
            'secret_key'     => self::mask_key(get_option('bt_secret_key', '')),
            'cache_ttl'      => (int) get_option('bt_cache_ttl', 3600),
            'post_types'     => get_option('bt_post_types', ['excursion']),
            'sync_interval'  => (int) get_option('bt_regiondo_sync_interval', 0),
            'sync_next_run'  => $next ?: null,
            'widget_map'          => get_option('bt_widget_map', []),
            'booking_custom_css'  => get_option('bt_booking_custom_css', ''),
            'booking_custom_js'   => get_option('bt_booking_custom_js', ''),
            'map_style_json'      => get_option('bt_map_style_json', ''),
            'map_presets'         => get_option('bt_map_presets', []),
            'maps_api_key'        => get_option('elementor_google_maps_api_key', ''),
            'snazzymaps_api_key'        => self::mask_key(get_option('bt_snazzymaps_api_key', '')),
            // Google (GA4 + Search Console) — jamais la clé privée, juste l'email du service account
            'google_credentials_json'   => self::redact_google_creds(get_option('bt_google_credentials_json', '')),
            'ga4_property_id'           => get_option('bt_ga4_property_id', ''),
            'ga4_measurement_id'        => get_option('bt_ga4_measurement_id', ''),
            'search_console_site_url'   => get_option('bt_search_console_site_url', ''),
            'ga4_cache_hours'           => (int) get_option('bt_ga4_cache_hours', 6),
            'gsc_cache_hours'           => (int) get_option('bt_gsc_cache_hours', 12),
            // IA — provider + clés masquées
            'ai_provider'               => get_option('bt_ai_provider', 'anthropic'),
            'anthropic_api_key'         => self::mask_key(get_option('bt_anthropic_api_key', '')),
            'openai_api_key'            => self::mask_key(get_option('bt_openai_api_key', '')),
            'gemini_api_key'            => self::mask_key(get_option('bt_gemini_api_key', '')),
            'mistral_api_key'           => self::mask_key(get_option('bt_mistral_api_key', '')),
            'grok_api_key'              => self::mask_key(get_option('bt_grok_api_key', '')),
            'meta_api_key'              => self::mask_key(get_option('bt_meta_api_key', '')),
            'products'                  => $products,
            'all_post_types' => array_values(array_map(fn($pt) => [
                'name'  => $pt->name,
                'label' => $pt->label,
            ], get_post_types(['public' => true], 'objects'))),
        ]);
    }

    public function save_settings(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params();

        if (isset($body['public_key'])) {
            update_option('bt_public_key', sanitize_text_field($body['public_key']));
        }
        // Ne jamais écraser avec la valeur masquée renvoyée par get_settings()
        if (isset($body['secret_key']) && !self::is_masked($body['secret_key'])) {
            if ($body['secret_key'] === '') {
                update_option('bt_secret_key', '');
            } else {
                update_option('bt_secret_key', sanitize_text_field($body['secret_key']));
            }
        }
        if (isset($body['snazzymaps_api_key']) && !self::is_masked($body['snazzymaps_api_key'])) {
            update_option('bt_snazzymaps_api_key', sanitize_text_field($body['snazzymaps_api_key']));
        }
        if (isset($body['cache_ttl'])) {
            update_option('bt_cache_ttl', absint($body['cache_ttl']));
        }
        if (isset($body['post_types']) && is_array($body['post_types'])) {
            update_option('bt_post_types', array_map('sanitize_text_field', $body['post_types']));
        }
        if (isset($body['widget_map']) && is_array($body['widget_map'])) {
            $clean = [];
            foreach ($body['widget_map'] as $pid => $value) {
                $pid_clean = absint($pid);
                if (is_array($value)) {
                    $clean[$pid_clean] = [
                        'widget_id'  => sanitize_text_field($value['widget_id'] ?? ''),
                        'custom_css' => wp_strip_all_tags($value['custom_css'] ?? ''),
                    ];
                } else {
                    // Backward compat: string value = widget_id only
                    $clean[$pid_clean] = [
                        'widget_id'  => sanitize_text_field($value),
                        'custom_css' => '',
                    ];
                }
            }
            update_option('bt_widget_map', $clean);
        }
        if (isset($body['booking_custom_css'])) {
            $css = wp_strip_all_tags($body['booking_custom_css']);
            // Strip CSS-based XSS vectors
            $css = preg_replace('/expression\s*\(/i', '/* blocked */(', $css);
            $css = preg_replace('/javascript\s*:/i', '/* blocked */', $css);
            $css = preg_replace('/-moz-binding\s*:/i', '/* blocked */', $css);
            $css = preg_replace('/behavior\s*:/i', '/* blocked */', $css);
            $css = preg_replace('/url\s*\(\s*["\']?\s*data\s*:\s*text\/html/i', 'url(/* blocked */', $css);
            $css = preg_replace('/@import\s+url/i', '/* blocked */', $css);
            update_option('bt_booking_custom_css', $css);
        }
        if (isset($body['booking_custom_js'])) {
            // Champ admin uniquement — on neutralise juste la balise fermante </script>
            // pour éviter une injection HTML dans le output. Pas de strip_tags : ça détruirait le JS.
            $js = str_replace('</script', '<\\/script', (string) $body['booking_custom_js']);
            update_option('bt_booking_custom_js', $js);
        }
        if (isset($body['sync_interval'])) {
            $interval = absint($body['sync_interval']);
            update_option('bt_regiondo_sync_interval', $interval);
            $this->reschedule_cron($interval);
        }
        if (isset($body['map_style_json'])) {
            $json = wp_strip_all_tags($body['map_style_json']);
            $decoded = json_decode($json, true);
            update_option('bt_map_style_json', is_array($decoded) ? $json : '');
        }
        if (isset($body['map_presets']) && is_array($body['map_presets'])) {
            $clean = [];
            foreach ($body['map_presets'] as $p) {
                if (empty($p['id']) || empty($p['name']) || empty($p['json'])) continue;
                $id      = sanitize_key($p['id']);
                $name    = sanitize_text_field($p['name']);
                $json    = wp_strip_all_tags($p['json']);
                $decoded = json_decode($json, true);
                if (!$id || !$name || !is_array($decoded)) continue;
                $clean[] = ['id' => $id, 'name' => $name, 'json' => $json];
            }
            update_option('bt_map_presets', $clean);
        }
        // Google (GA4 + Search Console) — service account JSON partagé
        // Ne jamais utiliser wp_slash() sur du JSON : corrompt les \n de la clé privée RSA
        if (isset($body['google_credentials_json'])) {
            $raw = $body['google_credentials_json'];
            // Si c'est un tableau/objet (ex: {configured:true, client_email:...} renvoyé par get_settings),
            // c'est la valeur masquée du frontend — ne pas écraser ce qui est déjà en DB.
            if (is_array($raw)) {
                // Rien à faire
            } elseif ($raw === '' || $raw === null) {
                // Effacement explicite
                update_option('bt_google_credentials_json', '');
            } elseif (is_string($raw) && !self::is_masked($raw)) {
                // Valide que c'est bien un JSON service_account avant de stocker
                $creds = json_decode($raw, true);
                if ($creds && ($creds['type'] ?? '') === 'service_account' && !empty($creds['private_key'])) {
                    update_option('bt_google_credentials_json', $raw);
                }
                // Sinon : JSON invalide — on ignore silencieusement pour ne pas écraser
            }
        }
        if (isset($body['ga4_property_id'])) {
            update_option('bt_ga4_property_id', sanitize_text_field($body['ga4_property_id']));
        }
        if (isset($body['ga4_measurement_id'])) {
            // Format attendu : G-XXXXXXXXXX (ou vide pour effacer)
            $mid = sanitize_text_field($body['ga4_measurement_id']);
            update_option('bt_ga4_measurement_id', $mid);
        }
        if (isset($body['search_console_site_url'])) {
            update_option('bt_search_console_site_url', esc_url_raw($body['search_console_site_url']));
        }
        if (isset($body['ga4_cache_hours'])) {
            update_option('bt_ga4_cache_hours', max(1, (int) $body['ga4_cache_hours']));
        }
        if (isset($body['gsc_cache_hours'])) {
            update_option('bt_gsc_cache_hours', max(1, (int) $body['gsc_cache_hours']));
        }
        // IA — provider
        if (isset($body['ai_provider'])) {
            $valid_providers = ['anthropic', 'openai', 'gemini', 'mistral', 'grok', 'meta'];
            if (in_array($body['ai_provider'], $valid_providers, true)) {
                update_option('bt_ai_provider', $body['ai_provider']);
            }
        }
        // Clés API IA — helper DRY
        $ai_keys = [
            'anthropic_api_key' => 'bt_anthropic_api_key',
            'openai_api_key'    => 'bt_openai_api_key',
            'gemini_api_key'    => 'bt_gemini_api_key',
            'mistral_api_key'   => 'bt_mistral_api_key',
            'grok_api_key'      => 'bt_grok_api_key',
            'meta_api_key'      => 'bt_meta_api_key',
        ];
        foreach ($ai_keys as $field => $option) {
            if (isset($body[$field]) && !self::is_masked($body[$field])) {
                update_option($option, $body[$field] === '' ? '' : sanitize_text_field($body[$field]));
            }
        }

        return rest_ensure_response(['success' => true]);
    }

    public function flush_cache(): \WP_REST_Response {
        (new \BlackTenders\Api\Regiondo\Cache())->flush();
        return rest_ensure_response(['success' => true]);
    }

    /**
     * Proxy GET /snazzymaps-styles → Snazzy Maps API.
     * Évite les problèmes CORS et garde la clé côté serveur.
     */
    public function get_snazzymaps_styles(\WP_REST_Request $req): \WP_REST_Response {
        $api_key = get_option('bt_snazzymaps_api_key', '');
        if (!$api_key) {
            return rest_ensure_response(['styles' => [], 'error' => 'Clé Snazzy Maps manquante.']);
        }

        $per_page = min(absint($req->get_param('per_page') ?: 30), 50);
        $tags     = sanitize_text_field($req->get_param('tags') ?: '');
        $search   = sanitize_text_field($req->get_param('search') ?: '');

        $args = ['apikey' => $api_key, 'per_page' => $per_page];
        if ($tags)   $args['tags']   = $tags;
        if ($search) $args['search'] = $search;

        $url      = add_query_arg($args, 'https://snazzymaps.com/api/styles');
        $response = wp_remote_get($url, ['timeout' => 10]);

        if (is_wp_error($response)) {
            return rest_ensure_response(['styles' => [], 'error' => $response->get_error_message()]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $data = json_decode(wp_remote_retrieve_body($response), true);

        // L'API Snazzy Maps retourne un tableau directement (pas {"styles": [...]})
        if ($code !== 200 || !is_array($data)) {
            return rest_ensure_response(['styles' => [], 'error' => "Erreur Snazzy Maps ({$code})"]);
        }

        $styles = array_map(static function (array $s): array {
            return [
                'id'          => (int) ($s['id'] ?? 0),
                'name'        => sanitize_text_field($s['name'] ?? ''),
                'description' => sanitize_text_field($s['description'] ?? ''),
                'tags'        => array_map('sanitize_text_field', (array) ($s['tags'] ?? [])),
                'views'       => (int) ($s['views'] ?? 0),
                'favorites'   => (int) ($s['favorites'] ?? 0),
                'imageUrl'    => esc_url_raw($s['imageUrl'] ?? ''),
                'url'         => esc_url_raw($s['url'] ?? ''),
                'json'        => $s['json'] ?? '[]',
            ];
        }, $data);

        return rest_ensure_response(['styles' => $styles]);
    }

    private function reschedule_cron(int $interval_minutes): void {
        wp_clear_scheduled_hook('bt_auto_sync');
        if ($interval_minutes <= 0) return;

        $recurrence = match ($interval_minutes) {
            30   => 'bt_30min',
            60   => 'hourly',
            360  => 'bt_6hours',
            720  => 'twicedaily',
            1440 => 'daily',
            default => null,
        };

        if ($recurrence) {
            wp_schedule_event(time(), $recurrence, 'bt_auto_sync');
        }
    }
}
