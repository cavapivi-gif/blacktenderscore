<?php
namespace BlackTenders\Core;

use BlackTenders\Admin\MetaBox\MetaBox;
use BlackTenders\Admin\Backoffice\Backoffice;
use BlackTenders\Admin\Backoffice\RestApi;
use BlackTenders\Admin\Backoffice\GygRestApi;
use BlackTenders\Admin\Backoffice\Sync;
use BlackTenders\Admin\Backoffice\Ai;
use BlackTenders\Admin\Backoffice\SchemaInjector;
use BlackTenders\Elementor\ElementorManager;
use BlackTenders\Elementor\VideoThumbnailCache;
use BlackTenders\Core\QuoteHandler;
use BlackTenders\Core\StoreStatusAjax;
use BlackTenders\Acf\MenuFields;
defined('ABSPATH') || exit;

class Plugin {

    public function init(): void {
        // REST API disponible partout (front + admin)
        (new RestApi())->init();
        // GYG Supplier API — endpoints entrants (gyg/v1) + internes (bt-regiondo/v1/gyg/*)
        (new GygRestApi())->init();
        // AJAX SSE pour le chat IA (doit s'enregistrer côté admin-ajax)
        (new Ai())->init();
        // AJAX devis (formulaire multi-étapes bt-boat-pricing)
        (new QuoteHandler())->init();
        // AJAX statut store (Fixed CTA widget)
        (new StoreStatusAjax())->init();

        // Schema.org JSON-LD injection (front-end uniquement)
        if (!is_admin()) {
            (new SchemaInjector())->init();
        }

        if (is_admin()) {
            (new Backoffice())->init();
            (new MetaBox())->init();
        }

        // Widgets Elementor (se branche sur elementor/loaded)
        (new ElementorManager())->init();

        // Champs ACF pour les items de menu (description)
        (new MenuFields())->init();

        // Filter Everything — labels lisibles pour les champs ACF true/false
        add_filter('wpc_filter_post_meta_term_name', [$this, 'filter_everything_acf_labels'], 10, 2);

        // Filter Everything — injecter les icônes ACF des taxonomies
        add_filter('wpc_filters_checkbox_term_html', [$this, 'filter_everything_taxonomy_icons'], 10, 4);
        add_filter('wpc_filters_radio_term_html', [$this, 'filter_everything_taxonomy_icons'], 10, 4);
        add_filter('wpc_filters_label_term_html', [$this, 'filter_everything_taxonomy_icons'], 10, 4);

        // Preload LCP image pour les pages avec galerie BT
        add_action('template_redirect', [$this, 'maybe_preload_gallery_lcp']);

        // Preload LCP background-image Elementor (hero sections)
        add_action('template_redirect', [$this, 'maybe_preload_elementor_bg_lcp']);

        // Preload les CSS critiques pour les découvrir avant le parsing du <head>
        // Cloudflare Early Hints (103) les enverra aussi en avance.
        add_action('wp_head', static function(): void {
            $critical = [
                content_url('/plugins/elementor/assets/css/frontend.min.css'),
                BT_URL . 'elementor/assets/bt-elementor.css',
            ];
            foreach ($critical as $url) {
                echo '<link rel="preload" as="style" href="' . esc_url($url) . '" />' . "\n";
            }
        }, 0);

        // WP Rocket RUCSS — alléger la page quand le crawler SaaS visite
        // Le crawler envoie ?nowprocket=1 et n'a besoin que du DOM + CSS,
        // pas des scripts tiers lourds qui causent des timeouts 45s.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!empty($_GET['nowprocket'])) {
            add_action('wp_enqueue_scripts', [$this, 'lighten_page_for_rucss_crawler'], 9999);
            add_action('template_redirect', [$this, 'start_rucss_ob'], 0);
        }

        // WP Rocket — exclure les scripts BT, Elementor, Plyr et Regiondo du delay JS
        add_filter('rocket_delay_js_exclusions', static function(array $excluded): array {
            return array_merge($excluded, [
                '/bt-elementor\.js',
                '/bt-boat-pricing-quote\.js',
                '/bt-segmented-control\.js',
                '/bt-video-player\.js',
                'elementor/assets/js/frontend',
                'cdn.plyr.io',               // Plyr CDN JS
                'plyr.polyfilled.js',
                'widgets.regiondo.net',      // Regiondo booking widget
                'booking-widget',            // Custom element <booking-widget>
            ]);
        });

        // WP Rocket — exclure Plyr et Regiondo du minify/combine JS
        add_filter('rocket_exclude_js', static function(array $excluded): array {
            return array_merge($excluded, [
                'cdn.plyr.io/(.*).js',
                '/bt-video-player\.js',
                'widgets.regiondo.net/(.*).js',
            ]);
        });

        // WP Rocket — exclure Plyr du minify/combine CSS
        add_filter('rocket_exclude_css', static function(array $excluded): array {
            return array_merge($excluded, [
                'cdn.plyr.io/(.*).css',
                '/bt-video-player\.css',
            ]);
        });

        // WP Rocket — exclure Plyr du RUCSS (Remove Unused CSS)
        // RUCSS supprime les styles "non utilisés" au chargement initial,
        // mais Plyr est chargé en lazy donc ses styles semblent "inutilisés".
        add_filter('rocket_rucss_external_exclusions', static function(array $excluded): array {
            return array_merge($excluded, [
                'cdn.plyr.io',
                'plyr.css',
                'bt-video-player',
            ]);
        });

        // WP Rocket — safelist pour inline styles Plyr
        add_filter('rocket_rucss_safelist', static function(array $safelist): array {
            return array_merge($safelist, [
                '.plyr',
                '.plyr__control',
                '.plyr__video-wrapper',
                '.plyr--video',
                '.plyr--youtube',
                '.plyr--vimeo',
            ]);
        });

        // WP Rocket RUCSS — préserver le CSS inline Regiondo dans <booking-widget>
        // Le contenu (.regiondo-*) est injecté dynamiquement par le script Regiondo
        // donc RUCSS le considère "unused" et vide le <style>.
        add_filter('rocket_rucss_inline_content_exclusions', static function(array $excluded): array {
            $excluded[] = 'regiondo';
            return $excluded;
        });

        // PerfMatters — exclure Plyr, BT Video et Regiondo du delay JS
        add_filter('perfmatters_delay_js_exclusions', static function(array $excluded): array {
            return array_merge($excluded, [
                'cdn.plyr.io',
                'plyr.polyfilled.js',
                'bt-video-player',
                'widgets.regiondo.net',
                'booking-widget',
            ]);
        });

        // PerfMatters — exclure Plyr et Regiondo du defer JS (pour éviter les problèmes d'ordre)
        add_filter('perfmatters_defer_js_exclusions', static function(array $excluded): array {
            return array_merge($excluded, [
                'cdn.plyr.io',
                'plyr',
                'bt-video-player',
                'widgets.regiondo.net',
            ]);
        });

        // Defer CSS non-critiques — media="print" onload pour libérer le rendu
        // Les CSS critiques (layout + typo above-the-fold) restent blocking.
        add_filter('style_loader_tag', static function(string $tag, string $handle): string {
            $critical = [
                'elementor-frontend',
                'rey-hs',
                'bt-elementor',
                'wp-block-library',
            ];
            if (is_admin() || in_array($handle, $critical, true)) {
                return $tag;
            }
            if (strpos($tag, "media='all'") === false) {
                return $tag;
            }
            return str_replace("media='all'", "media='print' onload=\"this.media='all'\"", $tag);
        }, 99, 2);

        // Invalide le cache carte Static Maps quand un post est sauvegardé
        add_action('save_post', static function(int $post_id): void {
            global $wpdb;
            $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bt_map_%' OR option_name LIKE '_transient_timeout_bt_map_%'"
            );
        });

        // Invalide la liste excursions mise en cache quand une excursion est modifiée
        add_action('save_post_excursion', static function(): void {
            delete_transient('bt_exc_list_50');
        });

        // Cron : intervalle personnalisé + hooks
        add_filter('cron_schedules', [$this, 'add_cron_intervals']);
        add_action('bt_auto_sync', [Sync::class, 'cron_run']);
        add_action('bt_video_cache_cleanup', [VideoThumbnailCache::class, 'cleanup_expired']);

        // Schedule le cron si pas encore fait
        if (!wp_next_scheduled('bt_video_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'bt_video_cache_cleanup');
        }
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

    /**
     * Déqueue les scripts tiers lourds quand le crawler RUCSS de WP Rocket visite.
     *
     * Le SaaS envoie ?nowprocket=1 pour obtenir la page brute. Il n'a besoin
     * que du DOM + CSS pour analyser les règles utilisées, pas des scripts
     * tiers (Regiondo, Plyr, Lenis…) qui font timeout à 45s.
     */
    public function lighten_page_for_rucss_crawler(): void {
        // Scripts JS lourds — le crawler n'en a pas besoin pour l'analyse CSS
        $scripts_to_remove = [
            'lenis',
            'lenis-init',
            'plyr-js',
            'bt-video-player',
            'bt-gmaps-init',
        ];
        foreach ($scripts_to_remove as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }
    }

    /**
     * OB sur la page entière pour retirer les scripts tiers inline (Regiondo)
     * quand le crawler RUCSS visite.
     */
    public function start_rucss_ob(): void {
        ob_start(static function(string $html): string {
            return preg_replace(
                '#<script[^>]*widgets\.regiondo\.net[^>]*></script>#i',
                '',
                $html
            );
        });
    }

    /**
     * Injecte un <link rel="preload"> pour l'image LCP des galeries BT.
     *
     * Exécuté sur template_redirect (avant wp_head) pour les single boats/excursions
     * qui ont un champ ACF galerie. Améliore le LCP score Lighthouse.
     */
    public function maybe_preload_gallery_lcp(): void {
        // Seulement sur les singles boat/excursion
        if (!is_singular(['boat', 'excursion'])) {
            return;
        }

        $post_id    = get_the_ID();
        $post_type  = get_post_type($post_id);
        $field_name = $post_type === 'boat' ? 'boat_gallery' : 'exp_gallery';

        $gallery = function_exists('get_field') ? get_field($field_name, $post_id) : null;
        if (!$gallery || !is_array($gallery) || empty($gallery[0])) {
            return;
        }

        $img       = $gallery[0];
        $thumb_url = $img['sizes']['large'] ?? ($img['url'] ?? '');
        if (!$thumb_url) {
            return;
        }

        $img_id = (int) ($img['ID'] ?? 0);
        $srcset = $img_id ? wp_get_attachment_image_srcset($img_id, 'large') : '';
        // Sizes approximatifs pour layout airbnb par défaut (50vw desktop, 100vw mobile)
        $sizes  = '(max-width: 767px) 100vw, 50vw';

        add_action('wp_head', static function () use ($thumb_url, $srcset, $sizes): void {
            echo '<link rel="preload" as="image" fetchpriority="high"'
                . ' href="' . esc_url($thumb_url) . '"'
                . ($srcset ? ' imagesrcset="' . esc_attr($srcset) . '"' : '')
                . ' imagesizes="' . esc_attr($sizes) . '"'
                . ' />' . "\n";
        }, 1);
    }

    /**
     * Preload la première background-image Elementor (hero LCP).
     *
     * Parse _elementor_data du post courant, trouve la première background_image,
     * et injecte <link rel="preload" as="image" fetchpriority="high"> dans <head>.
     * Ne s'active que si maybe_preload_gallery_lcp() n'a pas déjà injecté un preload.
     */
    public function maybe_preload_elementor_bg_lcp(): void {
        // Skip si galerie ACF déjà gérée (boats/excursions)
        if (is_singular(['boat', 'excursion'])) {
            return;
        }

        $post_id = get_queried_object_id();
        if (!$post_id) {
            return;
        }

        $data = get_post_meta($post_id, '_elementor_data', true);
        if (!$data || !is_string($data)) {
            return;
        }

        $elements = json_decode($data, true);
        if (!is_array($elements)) {
            return;
        }

        $bg_url = $this->find_first_bg_image($elements);
        if (!$bg_url) {
            return;
        }

        add_action('wp_head', static function () use ($bg_url): void {
            echo '<link rel="preload" as="image" fetchpriority="high"'
                . ' href="' . esc_url($bg_url) . '" />' . "\n";
        }, 1);
    }

    private function find_first_bg_image(array $elements): string {
        foreach ($elements as $el) {
            $url = $el['settings']['background_image']['url'] ?? '';
            if ($url) {
                return $url;
            }
            if (!empty($el['elements'])) {
                $found = $this->find_first_bg_image($el['elements']);
                if ($found) {
                    return $found;
                }
            }
        }
        return '';
    }

    /**
     * Filter Everything — remplace les valeurs brutes des champs ACF true/false
     * par des labels lisibles.
     *
     * @param string $name  Valeur brute (ex: "1")
     * @param string $meta_key Nom du champ ACF
     * @return string Label lisible
     */
    public function filter_everything_acf_labels(string $name, string $meta_key): string {
        // Map des champs ACF true/false → labels
        $labels = [
            'high_demand' => [
                '1' => __('Forte Demande', 'blacktenderscore'),
            ],
            'best_seller' => [
                '1' => __('Best Seller', 'blacktenderscore'),
            ],
            'is_featured' => [
                '1' => __('En Vedette', 'blacktenderscore'),
            ],
            'is_new' => [
                '1' => __('Nouveaute', 'blacktenderscore'),
            ],
            // Ajouter d'autres champs ACF true/false ici si besoin
        ];

        if (isset($labels[$meta_key][$name])) {
            return $labels[$meta_key][$name];
        }

        return $name;
    }

    /**
     * Filter Everything — injecte les icônes dans les filtres.
     *
     * Pour les taxonomies: lit les champs ACF taxomonies_icons / term_icon_class
     * Pour les post meta true/false: ajoute une icône configurable
     *
     * @param string $term_html HTML du terme
     * @param string $link_attributes Attributs du lien
     * @param mixed  $term_object Objet terme
     * @param array  $filter Configuration du filtre
     * @return string HTML modifie avec icône
     */
    public function filter_everything_taxonomy_icons($term_html, $link_attributes, $term_object, $filter): string {
        if (!is_object($term_object)) {
            return (string) $term_html;
        }

        $icon_html = '';
        $filter_entity = $filter['e_name'] ?? '';

        // ══════════════════════════════════════════════════════════════════
        // 1. Post Meta true/false — icônes depuis options ACF
        // Champs ACF: filter_icon_{meta_key} (ex: filter_icon_high_demand)
        // ══════════════════════════════════════════════════════════════════
        $term_slug = $term_object->slug ?? '';

        // Seulement pour la valeur "1" (true)
        if ($term_slug === '1' && function_exists('get_field')) {
            $option_field = 'filter_icon_' . $filter_entity;
            $icon_option = get_field($option_field, 'option');

            if ($icon_option) {
                if (is_array($icon_option) && !empty($icon_option['url'])) {
                    // Image ACF (array)
                    $url = esc_url($icon_option['url']);
                    $icon_html = '<img src="' . $url . '" alt="" class="bt-filter-icon" style="width:20px;height:20px;object-fit:contain;margin-right:6px;vertical-align:middle;" />';
                } elseif (is_string($icon_option) && !empty($icon_option)) {
                    // URL directe
                    $icon_html = '<img src="' . esc_url($icon_option) . '" alt="" class="bt-filter-icon" style="width:20px;height:20px;object-fit:contain;margin-right:6px;vertical-align:middle;" />';
                }
            }

            // Fallback icônes par défaut si pas configuré dans ACF
            if (empty($icon_html)) {
                $default_icons = [
                    'high_demand' => '<i class="fas fa-fire" style="color:#ff6b35;margin-right:6px;"></i>',
                    'best_seller' => '<i class="fas fa-crown" style="color:#ffb743;margin-right:6px;"></i>',
                    'is_featured' => '<i class="fas fa-star" style="color:#ffd700;margin-right:6px;"></i>',
                    'is_new'      => '<i class="fas fa-bolt" style="color:#10b981;margin-right:6px;"></i>',
                ];
                $icon_html = $default_icons[$filter_entity] ?? '';
            }
        }

        // ══════════════════════════════════════════════════════════════════
        // 2. Taxonomies — icônes ACF
        // ══════════════════════════════════════════════════════════════════
        if (empty($icon_html) && isset($term_object->term_id) && function_exists('get_field')) {
            // Essayer taxomonies_icons (image/SVG)
            $icon_field = get_field('taxomonies_icons', $term_object);
            if ($icon_field) {
                if (is_array($icon_field) && !empty($icon_field['url'])) {
                    $url = esc_url($icon_field['url']);
                    $alt = esc_attr($icon_field['alt'] ?? $term_object->name);
                    $icon_html = '<img src="' . $url . '" alt="' . $alt . '" class="bt-filter-icon" style="width:20px;height:20px;object-fit:contain;margin-right:6px;vertical-align:middle;" />';
                } elseif (is_string($icon_field)) {
                    if (strpos($icon_field, '<svg') !== false) {
                        $icon_html = '<span class="bt-filter-icon" style="display:inline-flex;width:20px;height:20px;margin-right:6px;vertical-align:middle;">' . $icon_field . '</span>';
                    } else {
                        $icon_html = '<img src="' . esc_url($icon_field) . '" alt="" class="bt-filter-icon" style="width:20px;height:20px;object-fit:contain;margin-right:6px;vertical-align:middle;" />';
                    }
                }
            }

            // Fallback: term_icon_class (Font Awesome)
            if (empty($icon_html)) {
                $fa_class = get_field('term_icon_class', $term_object);
                if ($fa_class && is_string($fa_class)) {
                    $icon_html = '<i class="' . esc_attr(trim($fa_class)) . ' bt-filter-icon" style="margin-right:6px;"></i>';
                }
            }
        }

        // Si pas d'icône, retourner le HTML original
        if (empty($icon_html)) {
            return (string) $term_html;
        }

        // Injecter l'icône dans le HTML du terme
        if (preg_match('/<a\s*([^>]*)>(.*?)<\/a>/is', $term_html, $matches)) {
            $attrs = $matches[1];
            $content = $matches[2];
            $term_html = '<a ' . $attrs . '>' . $icon_html . $content . '</a>';
        }

        return $term_html;
    }
}
