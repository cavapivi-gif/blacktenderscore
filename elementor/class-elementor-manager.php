<?php
namespace BlackTenders\Elementor;

use BlackTenders\Elementor\Widgets\TaxonomyList;
use BlackTenders\Elementor\Widgets\FaqAccordion;
use BlackTenders\Elementor\Widgets\BoatSpecs;
use BlackTenders\Elementor\Widgets\BoatPricing;
use BlackTenders\Elementor\Widgets\ExcursionPricing;
use BlackTenders\Elementor\Widgets\RelatedBoats;
use BlackTenders\Elementor\Widgets\RelatedExcursions;
use BlackTenders\Elementor\Widgets\Itinerary;
use BlackTenders\Elementor\Widgets\DepartureTimes;
use BlackTenders\Elementor\Widgets\Reviews;
use BlackTenders\Elementor\Widgets\Gallery;
use BlackTenders\Elementor\Widgets\GalleryPreview;
use BlackTenders\Elementor\Widgets\ExcursionSchema;
use BlackTenders\Elementor\Widgets\RepeaterSection;
use BlackTenders\Elementor\Widgets\Highlights;
use BlackTenders\Elementor\Widgets\Captain;
use BlackTenders\Elementor\Widgets\IncludedExcluded;
use BlackTenders\Elementor\Widgets\TaxonomyIncludes;
use BlackTenders\Elementor\Widgets\Share;
use BlackTenders\Elementor\Widgets\GoogleMap;
use BlackTenders\Elementor\Widgets\TitleIconDesc;
use BlackTenders\Elementor\Widgets\TaxonomyDisplay;
use BlackTenders\Elementor\Widgets\QuoteForm;
use BlackTenders\Elementor\Widgets\PricingBody;

defined('ABSPATH') || exit;

class ElementorManager {

    public function init(): void {
        // Invalidation transients (pas besoin d'Elementor)
        add_action('save_post_excursion', [$this, 'invalidate_relexp_transients'], 10, 2);
        add_action('save_post_boat',      [$this, 'invalidate_relboat_transients'], 10, 1);

        // Tout le reste attend qu'Elementor soit chargé
        add_action('elementor/loaded', [$this, 'setup']);
    }

    public function setup(): void {
        // Chargé ici pour que \Elementor\Core\DynamicTags\Tag existe déjà
        require_once __DIR__ . '/dynamic-tags/class-dynamic-tags-manager.php';
        require_once __DIR__ . '/loop-queries/class-loop-queries.php';

        (new DynamicTags\Dynamic_Tags_Manager())->init();
        (new LoopQueries\Loop_Queries())->init();

        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register',               [$this, 'register_widgets']);
        add_action('elementor/frontend/after_enqueue_styles',  [$this, 'enqueue_assets']);
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_assets']);
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_editor_extras']);

        // ── Map Style : injecte la section "Style de carte" dans TOUS les widgets ──
        // On s'accroche sur _section_responsive, toujours la DERNIÈRE section native
        // du tab Advanced d'Elementor → notre section s'insère après, tout à la fin.
        add_action('elementor/element/after_section_end', static function(
            $element, string $section_id, array $args
        ): void {
            // Le hook peut recevoir des Documents Elementor Pro (ex: Single_Page)
            // qui n'héritent pas de Element_Base → on filtre proprement.
            if (!($element instanceof \Elementor\Element_Base)) return;

            // _section_responsive est la dernière section du tab Advanced dans tout widget Elementor.
            // En s'y accrochant, on garantit que notre section arrive en toute fin du panneau Advanced.
            if ($section_id !== '_section_responsive') return;

            $element->start_controls_section('section_bt_map_style', [
                'label' => __('Style de carte (BT)', 'blacktenderscore'),
                'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
            ]);

            $element->add_control('bt_map_style_enabled', [
                'label'        => __('Appliquer un style', 'blacktenderscore'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Oui', 'blacktenderscore'),
                'label_off'    => __('Non', 'blacktenderscore'),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __('Active le style global Google Maps sur ce widget. Désactivez pour garder le style par défaut Google.', 'blacktenderscore'),
            ]);

            $element->add_control('bt_map_style_preset', [
                'label'     => __('Preset', 'blacktenderscore'),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'options'   => AbstractBtWidget::get_bt_map_style_options(),
                'default'   => '',
                'condition' => ['bt_map_style_enabled' => 'yes'],
            ]);

            $element->end_controls_section();
        }, 99, 3);

        // ── Google Maps iframe → JS API ────────────────────────────────────────────────────
        // Remplace l'iframe Embed (cross-origin, non stylable) par un <div> initialisé via
        // la Maps JS API, ce qui permet au monkey-patch de class-plugin.php d'appliquer le style.
        add_filter('elementor/widget/render_content', static function(string $content, \Elementor\Widget_Base $widget): string {
            if ($widget->get_name() !== 'google_maps') return $content;

            // Extrait le src de l'iframe (les deux formats : embed/v1/place et maps?q=)
            if (!preg_match('/src=["\']([^"\']*(?:maps\.google|google\.com\/maps)[^"\']*)["\']/', $content, $m)) {
                return $content;
            }

            $src   = html_entity_decode($m[1]);
            $query = wp_parse_url($src, PHP_URL_QUERY) ?? '';
            wp_parse_str($query, $params);

            $q    = rawurldecode($params['q'] ?? '');
            $zoom = (int) ($params['zoom'] ?? $params['z'] ?? 10);

            if ($q === '') return $content;  // impossible de centrer la carte sans adresse

            // Charge la Maps JS API si elle n'est pas encore enqueued
            // (l'iframe Embed ne la requiert pas, notre remplacement JS si)
            if (!wp_script_is('google-maps-api', 'enqueued')) {
                $api_key = get_option('elementor_google_maps_api_key', '');
                $maps_url = $api_key
                    ? 'https://maps.googleapis.com/maps/api/js?key=' . rawurlencode($api_key)
                    : 'https://maps.googleapis.com/maps/api/js';
                wp_enqueue_script('google-maps-api', $maps_url, [], null, true);
            }

            // Détermine si q est une paire lat,lng ou une adresse textuelle
            $is_latlng = (bool) preg_match('/^-?\d+\.?\d*\s*,\s*-?\d+\.?\d*$/', $q);
            $data_attr = $is_latlng
                ? 'data-bt-latlng="' . esc_attr($q) . '"'
                : 'data-bt-address="' . esc_attr($q) . '"';

            // Récupère la hauteur définie dans les settings du widget
            $settings    = $widget->get_settings_for_display();
            $height_val  = $settings['height']['size'] ?? 300;
            $height_unit = $settings['height']['unit'] ?? 'px';
            $height_css  = esc_attr($height_val . $height_unit);

            $replacement = '<div class="elementor-custom-embed">'
                         . '<div class="bt-gmaps-js" ' . $data_attr
                         .  ' data-zoom="' . $zoom . '"'
                         .  ' style="width:100%;height:' . $height_css . '">'
                         . '</div></div>';

            // Remplace la première occurrence de l'embed (ungreedy, stop au 1er </div>)
            return preg_replace(
                '/<div class="elementor-custom-embed">[\s\S]*?<\/div>/U',
                $replacement,
                $content,
                1
            );
        }, 10, 2);

        // ── Map Style : applique data-bt-map-style sur le wrapper du widget avant le rendu ──
        // Lit les settings BT map style et écrit l'attribut sur le wrapper.
        // Compatible avec tous les widgets (BT, Elementor natif, PowerPack, etc.)
        // Le JS global (wp_footer) lit cet attribut via el.closest('[data-bt-map-style]').
        add_action('elementor/frontend/element/before_render', static function(
            \Elementor\Element_Base $element
        ): void {
            if (!($element instanceof \Elementor\Widget_Base)) return;

            $settings = $element->get_settings();
            $enabled  = $settings['bt_map_style_enabled'] ?? null;

            // Le contrôle n'existe pas encore sur ce widget (cache vide → sera injecté au prochain rendu)
            if ($enabled === null) return;

            // Désactivé explicitement → sentinelle 'none' pour bloquer le style global JS
            if ($enabled !== 'yes') {
                $element->add_render_attribute('_wrapper', 'data-bt-map-style', 'none');
                return;
            }

            // Preset spécifique choisi → mettre le JSON dans l'attribut
            $preset = $settings['bt_map_style_preset'] ?? '';
            if ($preset === '') return;  // '' = utiliser le style global (aucun attribut nécessaire)

            $json = AbstractBtWidget::resolve_bt_map_style($preset);
            if ($json !== null) {
                $element->add_render_attribute('_wrapper', 'data-bt-map-style', $json);
            }
        }, 10);
    }

    // ── Invalidation transients ───────────────────────────────────────────────

    public function invalidate_relexp_transients(int $post_id, \WP_Post $post): void {
        // Quand on sauve une excursion, invalider le cache des bateaux liés
        if (!function_exists('get_field')) return;
        $boats = get_field('exp_boats', $post_id);
        if (!is_array($boats)) return;
        foreach ($boats as $boat) {
            $bid = $boat instanceof \WP_Post ? $boat->ID : (int) $boat;
            if ($bid) delete_transient('bt_relexp_' . $bid . '_exp_boats');
        }
    }

    public function invalidate_relboat_transients(int $post_id): void {
        // Quand on sauve un bateau, ses excursions liées peuvent changer
        delete_transient('bt_exc_by_boat_' . $post_id);
        delete_transient('bt_rel_count_'   . $post_id);
    }

    public function register_category(\Elementor\Elements_Manager $manager): void {
        $manager->add_category('blacktenderscore', [
            'title' => 'BlackTenders',
            'icon'  => 'eicon-anchor',
        ]);
    }

    public function register_widgets(\Elementor\Widgets_Manager $manager): void {
        // ── Widgets excursion ─────────────────────────────────────────────
        $manager->register(new TaxonomyList());
        $manager->register(new FaqAccordion());
        $manager->register(new Itinerary());
        $manager->register(new DepartureTimes());
        $manager->register(new Reviews());
        $manager->register(new Gallery());
        $manager->register(new GalleryPreview());
        $manager->register(new ExcursionSchema());
        $manager->register(new RepeaterSection());
        // ── Relations excursion <-> bateau ────────────────────────────────
        $manager->register(new RelatedBoats());
        $manager->register(new RelatedExcursions());
        // ── Widgets bateau ────────────────────────────────────────────────
        $manager->register(new BoatSpecs());
        $manager->register(new BoatPricing());
        $manager->register(new ExcursionPricing());
        // ── Widgets contenu générique ─────────────────────────────────────
        $manager->register(new Highlights());
        $manager->register(new Captain());
        $manager->register(new IncludedExcluded());
        $manager->register(new TaxonomyIncludes());
        $manager->register(new Share());
        $manager->register(new TitleIconDesc());
        $manager->register(new TaxonomyDisplay());
        // ── Formulaire devis ─────────────────────────────────────────────────
        $manager->register(new QuoteForm());
        // ── Utilitaires ───────────────────────────────────────────────────────
        $manager->register(new PricingBody());
        $manager->register(new GoogleMap());
    }

    public function enqueue_editor_extras(): void {
        wp_add_inline_style('elementor-editor', '
            .bt-elementorBadge {
                display: inline-block;
                font-size: 9px;
                font-weight: 700;
                letter-spacing: .5px;
                text-transform: uppercase;
                background: #0073aa;
                color: #fff;
                padding: 1px 5px;
                border-radius: 3px;
                margin-left: 6px;
                vertical-align: middle;
                line-height: 1.6;
            }
        ');

        wp_add_inline_script('elementor-editor', '
            (function () {
                var TITLE = "BlackTenders";
                function applyBadgeAndOrder() {
                    var items = document.querySelectorAll(
                        ".elementor-panel-category, .elementor-panel__editor .elementor-elements-category"
                    );
                    items.forEach(function (cat) {
                        var heading = cat.querySelector(
                            ".elementor-panel-heading-title, .elementor-elements-category__title"
                        );
                        if (!heading) return;
                        if (heading.textContent.trim() !== TITLE) return;
                        if (!heading.querySelector(".bt-elementorBadge")) {
                            heading.innerHTML = TITLE + \'<span class="bt-elementorBadge">CORE</span>\';
                        }
                        var parent = cat.parentNode;
                        if (parent && parent.firstElementChild !== cat) {
                            parent.insertBefore(cat, parent.firstElementChild);
                        }
                    });
                }
                var observer = new MutationObserver(applyBadgeAndOrder);
                observer.observe(document.body, { childList: true, subtree: true });
                applyBadgeAndOrder();
            })();
        ');
    }

    public function enqueue_assets(): void {
        // Global styles (variables, shared tab component, collapsible block, placeholder)
        wp_enqueue_style(
            'bt-elementor',
            BT_URL . 'elementor/assets/bt-elementor.css',
            [],
            BT_VERSION
        );
        wp_enqueue_script(
            'bt-elementor',
            BT_URL . 'elementor/assets/bt-elementor.js',
            [],
            BT_VERSION,
            true
        );

        // Per-widget styles — registered here, enqueued on-demand via get_style_depends()
        $widget_styles = [
            'bt-taxonomy-list',
            'bt-faq-accordion',
            'bt-pricing-tabs',
            'bt-boat-pricing',
            'bt-boat-specs',
            'bt-related-boats',
            'bt-related-excursions',
            'bt-itinerary',
            'bt-departure-times',
            'bt-reviews',
            'bt-gallery',
            'bt-lightbox',
            'bt-gallery-preview',
            'bt-repeater-section',
            'bt-highlights',
            'bt-captain',
            'bt-included-excluded',
            'bt-share',
            'bt-google-map',
            'bt-title-icon-desc',
            'bt-taxonomy-display',
            'bt-quote-form',
            'bt-segmented-control',
        ];
        foreach ($widget_styles as $handle) {
            wp_register_style(
                $handle,
                BT_URL . 'elementor/assets/' . $handle . '.css',
                ['bt-elementor'],
                BT_VERSION
            );
        }

        // Quote form JS (devis multi-étapes — shared by bt-quote-form & bt-boat-pricing)
        wp_register_script(
            'bt-boat-pricing-quote',
            BT_URL . 'elementor/assets/bt-boat-pricing-quote.js',
            ['bt-elementor'],
            BT_VERSION,
            true
        );
        // Alias for the new bt-quote-form widget
        wp_register_script(
            'bt-quote-form',
            BT_URL . 'elementor/assets/bt-boat-pricing-quote.js',
            ['bt-elementor'],
            BT_VERSION,
            true
        );

        // Segmented control JS (forfait cards — bt-pricing-body)
        wp_register_script(
            'bt-segmented-control',
            BT_URL . 'elementor/assets/bt-segmented-control.js',
            ['bt-elementor'],
            BT_VERSION,
            true
        );

        // Leaflet — enregistré seulement (enqueued à la demande dans render_map_leaflet)
        wp_register_style('bt-leaflet-css', BT_URL . 'elementor/assets/leaflet.min.css', [], '1.9.4');
        wp_register_script('bt-leaflet',     BT_URL . 'elementor/assets/leaflet.min.js',  [], '1.9.4', true);
        wp_register_script('bt-leaflet-init', BT_URL . 'elementor/assets/bt-leaflet-init.js', ['bt-leaflet'], BT_VERSION, true);

        // Google Maps JS API init — remplace l'iframe Embed par un div stylable
        wp_enqueue_script('bt-gmaps-init', BT_URL . 'elementor/assets/bt-gmaps-init.js', [], BT_VERSION, true);
    }
}
