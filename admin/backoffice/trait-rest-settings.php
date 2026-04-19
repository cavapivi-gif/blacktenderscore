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
            // GetYourGuide — credentials masqués
            'gyg_username'              => get_option('bt_gyg_username') ? '••••••' : '',
            'gyg_supplier_id'           => get_option('bt_gyg_supplier_id', ''),
            'gyg_mode'                  => get_option('bt_gyg_mode', 'sandbox'),
            'gyg_incoming_username'     => get_option('bt_gyg_incoming_username') ? '••••••' : '',
            'gyg_has_password'          => !empty(get_option('bt_gyg_password')),
            'gyg_has_incoming_password' => !empty(get_option('bt_gyg_incoming_password')),
            'gyg_product_map'           => json_decode(get_option('bt_gyg_product_map', '[]'), true),
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

        // ── GetYourGuide credentials ──────────────────────────────────────────
        // Les valeurs masquées ('••••••') ne sont jamais sauvegardées.
        if (isset($body['gyg_username']) && !self::is_masked($body['gyg_username'])) {
            $gyg_enc = new Encryption();
            update_option('bt_gyg_username', $gyg_enc->encrypt(sanitize_text_field($body['gyg_username'])));
        }
        if (isset($body['gyg_password']) && !self::is_masked($body['gyg_password'])) {
            $gyg_enc = $gyg_enc ?? new Encryption();
            update_option('bt_gyg_password', $gyg_enc->encrypt(sanitize_text_field($body['gyg_password'])));
        }
        if (isset($body['gyg_supplier_id'])) {
            update_option('bt_gyg_supplier_id', sanitize_text_field($body['gyg_supplier_id']));
        }
        if (isset($body['gyg_mode'])) {
            $mode = in_array($body['gyg_mode'], ['sandbox', 'live'], true) ? $body['gyg_mode'] : 'sandbox';
            update_option('bt_gyg_mode', $mode);
        }
        if (isset($body['gyg_incoming_username']) && !self::is_masked($body['gyg_incoming_username'])) {
            $gyg_enc = $gyg_enc ?? new Encryption();
            update_option('bt_gyg_incoming_username', $gyg_enc->encrypt(sanitize_text_field($body['gyg_incoming_username'])));
        }
        if (isset($body['gyg_incoming_password']) && !self::is_masked($body['gyg_incoming_password'])) {
            $gyg_enc = $gyg_enc ?? new Encryption();
            update_option('bt_gyg_incoming_password', $gyg_enc->encrypt(sanitize_text_field($body['gyg_incoming_password'])));
        }
        if (isset($body['gyg_product_map']) && is_array($body['gyg_product_map'])) {
            // Sanitiser chaque entrée du mapping produit
            $clean_map = [];
            foreach ($body['gyg_product_map'] as $entry) {
                if (!is_array($entry)) continue;
                $clean_map[] = [
                    'notre_product_id' => sanitize_text_field($entry['notre_product_id'] ?? ''),
                    'gyg_option_id'    => absint($entry['gyg_option_id']    ?? 0),
                    'active'           => !empty($entry['active']),
                ];
            }
            update_option('bt_gyg_product_map', wp_json_encode($clean_map));
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

    // ── Schema.org SEO ────────────────────────────────────────────────────────

    /**
     * Retourne la configuration Schema.org sauvegardée.
     */
    public function get_schema_settings(): \WP_REST_Response {
        $settings = get_option('bt_schema_settings', [
            'post_types'    => [],
            'taxonomies'    => [],
            'provider_name' => '',
            'provider_url'  => '',
        ]);
        return rest_ensure_response($settings);
    }

    /**
     * Sauvegarde la configuration Schema.org.
     */
    public function save_schema_settings(\WP_REST_Request $req): \WP_REST_Response {
        $body = $req->get_json_params();

        $clean_pt = [];
        if (!empty($body['post_types']) && is_array($body['post_types'])) {
            foreach ($body['post_types'] as $cfg) {
                if (empty($cfg['post_type'])) continue;
                $clean_pt[] = [
                    'post_type'         => sanitize_key($cfg['post_type']),
                    'enabled'           => !empty($cfg['enabled']),
                    'schema_type'       => sanitize_text_field($cfg['schema_type'] ?? 'TouristTrip'),
                    'field_description' => sanitize_key($cfg['field_description'] ?? ''),
                    'field_depart'      => sanitize_key($cfg['field_depart'] ?? ''),
                    'field_arrivee'     => sanitize_key($cfg['field_arrivee'] ?? ''),
                ];
            }
        }

        $clean_tax = [];
        if (!empty($body['taxonomies']) && is_array($body['taxonomies'])) {
            foreach ($body['taxonomies'] as $cfg) {
                if (empty($cfg['taxonomy'])) continue;
                $clean_tax[] = [
                    'taxonomy'    => sanitize_key($cfg['taxonomy']),
                    'enabled'     => !empty($cfg['enabled']),
                    'schema_type' => sanitize_text_field($cfg['schema_type'] ?? 'TouristDestination'),
                    'field_gps'   => sanitize_key($cfg['field_gps'] ?? ''),
                ];
            }
        }

        $settings = [
            'post_types'    => $clean_pt,
            'taxonomies'    => $clean_tax,
            'provider_name' => sanitize_text_field($body['provider_name'] ?? ''),
            'provider_url'  => esc_url_raw($body['provider_url'] ?? ''),
        ];

        update_option('bt_schema_settings', $settings);

        return rest_ensure_response(['success' => true]);
    }

    /**
     * Retourne la liste des custom post types publics (hors natifs WP).
     */
    public function get_schema_post_types(): \WP_REST_Response {
        $excluded = [
            'post', 'page', 'attachment', 'revision', 'nav_menu_item',
            'custom_css', 'customize_changeset', 'oembed_cache', 'user_request',
            'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles',
            'wp_navigation', 'acf-field-group', 'acf-field', 'acf-post-type',
            'acf-taxonomy', 'acf-ui-options-page', 'elementor_library',
            'e-landing-page', 'e-floating-buttons',
        ];

        $pts = get_post_types(['public' => true], 'objects');
        $result = [];

        foreach ($pts as $pt) {
            if (in_array($pt->name, $excluded, true)) continue;
            $result[] = [
                'name'  => $pt->name,
                'label' => $pt->label,
            ];
        }

        return rest_ensure_response($result);
    }

    /**
     * Retourne la liste des taxonomies publiques (hors natives WP).
     */
    public function get_schema_taxonomies(): \WP_REST_Response {
        $excluded = [
            'category', 'post_tag', 'nav_menu', 'link_category', 'post_format',
            'wp_theme', 'wp_template_part_area', 'elementor_library_type',
        ];

        $taxs = get_taxonomies(['public' => true], 'objects');
        $result = [];

        foreach ($taxs as $tax) {
            if (in_array($tax->name, $excluded, true)) continue;
            $result[] = [
                'name'  => $tax->name,
                'label' => $tax->label,
            ];
        }

        return rest_ensure_response($result);
    }

    /**
     * Retourne les champs ACF de type google_map pour un post type ou une taxonomie.
     *
     * @param \WP_REST_Request $req Params: name (post_type ou taxonomy), type ('post_type' ou 'taxonomy')
     */
    public function get_schema_map_fields(\WP_REST_Request $req): \WP_REST_Response {
        $name = sanitize_key($req->get_param('name') ?? '');
        $type = sanitize_key($req->get_param('type') ?? 'post_type');

        if (!$name || !function_exists('acf_get_field_groups')) {
            return rest_ensure_response([]);
        }

        // Trouver les field groups assignés à ce post type ou taxonomie
        $location_rule = $type === 'taxonomy'
            ? [['param' => 'taxonomy', 'operator' => '==', 'value' => $name]]
            : [['param' => 'post_type', 'operator' => '==', 'value' => $name]];

        $groups = acf_get_field_groups();
        $matching_groups = [];

        foreach ($groups as $group) {
            foreach ($group['location'] ?? [] as $loc_group) {
                foreach ($loc_group as $rule) {
                    if (
                        $rule['param'] === $location_rule[0]['param'] &&
                        $rule['operator'] === '==' &&
                        $rule['value'] === $name
                    ) {
                        $matching_groups[] = $group['key'];
                        break 2;
                    }
                }
            }
        }

        // Extraire les champs google_map de ces groupes
        $map_fields = [];
        foreach ($matching_groups as $group_key) {
            $fields = acf_get_fields($group_key);
            if (!$fields) continue;

            foreach ($fields as $field) {
                if (($field['type'] ?? '') === 'google_map') {
                    $map_fields[] = [
                        'name'  => $field['name'],
                        'label' => $field['label'],
                    ];
                }
            }
        }

        return rest_ensure_response($map_fields);
    }

    /**
     * Retourne les champs ACF de type texte pour la description (text, textarea, wysiwyg).
     *
     * @param \WP_REST_Request $req Params: name (post_type ou taxonomy), type ('post_type' ou 'taxonomy')
     */
    public function get_schema_text_fields(\WP_REST_Request $req): \WP_REST_Response {
        $name = sanitize_key($req->get_param('name') ?? '');
        $type = sanitize_key($req->get_param('type') ?? 'post_type');

        if (!$name || !function_exists('acf_get_field_groups')) {
            return rest_ensure_response([]);
        }

        // Trouver les field groups assignés à ce post type ou taxonomie
        $location_rule = $type === 'taxonomy'
            ? [['param' => 'taxonomy', 'operator' => '==', 'value' => $name]]
            : [['param' => 'post_type', 'operator' => '==', 'value' => $name]];

        $groups = acf_get_field_groups();
        $matching_groups = [];

        foreach ($groups as $group) {
            foreach ($group['location'] ?? [] as $loc_group) {
                foreach ($loc_group as $rule) {
                    if (
                        $rule['param'] === $location_rule[0]['param'] &&
                        $rule['operator'] === '==' &&
                        $rule['value'] === $name
                    ) {
                        $matching_groups[] = $group['key'];
                        break 2;
                    }
                }
            }
        }

        // Types de champs texte valides pour la description
        $text_types = ['text', 'textarea', 'wysiwyg'];
        $text_fields = [];

        foreach ($matching_groups as $group_key) {
            $fields = acf_get_fields($group_key);
            if (!$fields) continue;

            foreach ($fields as $field) {
                if (in_array($field['type'] ?? '', $text_types, true)) {
                    $text_fields[] = [
                        'name'  => $field['name'],
                        'label' => $field['label'],
                        'type'  => $field['type'],
                    ];
                }
            }
        }

        return rest_ensure_response($text_fields);
    }
}
