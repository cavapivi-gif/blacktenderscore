<?php
namespace BlackTenders\Elementor;

use BlackTenders\Elementor\Widgets\TaxonomyList;
use BlackTenders\Elementor\Widgets\FaqAccordion;
use BlackTenders\Elementor\Widgets\BoatSpecs;
use BlackTenders\Elementor\Widgets\BoatPricing;
use BlackTenders\Elementor\Widgets\BoatTarifs;
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
use BlackTenders\Elementor\Widgets\VideoPlayer;
use BlackTenders\Elementor\Widgets\FixedCta;
use BlackTenders\Elementor\Widgets\FancyMenu;
use BlackTenders\Elementor\Widgets\HighDemandBadge;
use BlackTenders\Elementor\Widgets\AcfBadge;
use BlackTenders\Elementor\ContainerScroll;

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
        require_once __DIR__ . '/class-loop-mobile-template.php';
        require_once __DIR__ . '/class-loop-query-controls.php';
        require_once __DIR__ . '/class-container-scroll.php';

        (new DynamicTags\Dynamic_Tags_Manager())->init();
        (new LoopQueries\Loop_Queries())->init();
        (new LoopMobileTemplate())->init();
        (new LoopQueryControls())->init();
        (new ContainerScroll())->init();

        require_once plugin_dir_path( __FILE__ ) . 'widgets/blog-article/bootstrap.php';

        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register',               [$this, 'register_widgets']);
        add_action('elementor/frontend/after_enqueue_styles',  [$this, 'enqueue_assets']);
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_assets']);
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_editor_extras']);

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
        $manager->register(new BoatTarifs());
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
        // ── Vidéo ─────────────────────────────────────────────────────────
        $manager->register(new VideoPlayer());
        // ── CTA fixe ────────────────────────────────────────────────────
        $manager->register(new FixedCta());
        // ── Navigation ─────────────────────────────────────────────────
        $manager->register(new FancyMenu());
        // ── Badges ──────────────────────────────────────────────────────────
        $manager->register(new HighDemandBadge());
        $manager->register(new AcfBadge());
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

        // Filter Everything — toggle switch styles pour filtres true/false
        wp_enqueue_style(
            'bt-filter-toggle',
            BT_URL . 'elementor/assets/bt-filter-toggle.css',
            [],
            BT_VERSION
        );

        // CSS bundles — 3 fichiers au lieu de 20+ requêtes individuelles
        // bt-bundle        : tous les widgets excursion/bateau/contenu
        // bt-bundle-gallery: gallery + lightbox + gallery-preview
        // bt-bundle-extra  : related-boats, related-excursions, google-map
        wp_register_style('bt-bundle',         BT_URL . 'elementor/assets/bt-bundle.css',         ['bt-elementor'], BT_VERSION);
        wp_register_style('bt-bundle-gallery', BT_URL . 'elementor/assets/bt-bundle-gallery.css', ['bt-elementor'], BT_VERSION);
        wp_register_style('bt-bundle-extra',   BT_URL . 'elementor/assets/bt-bundle-extra.css',   ['bt-elementor'], BT_VERSION);

        // Aliases per-widget → bundle (get_style_depends() des widgets continue à fonctionner)
        // Les handles individuels sont des no-op qui chargent le bundle en dépendance.
        $bundle_aliases = [
            'bt-taxonomy-list', 'bt-faq-accordion', 'bt-pricing-tabs', 'bt-boat-pricing',
            'bt-boat-specs', 'bt-itinerary', 'bt-departure-times', 'bt-reviews',
            'bt-repeater-section', 'bt-highlights', 'bt-captain', 'bt-included-excluded',
            'bt-share', 'bt-title-icon-desc', 'bt-taxonomy-display',
            'bt-segmented-control', 'bt-quote-substep', 'bt-fixed-cta', 'bt-high-demand-badge', 'bt-acf-badge',
        ];
        foreach ($bundle_aliases as $handle) {
            wp_register_style($handle, false, ['bt-bundle'], BT_VERSION);
        }

        // Quote form CSS — fichier séparé (boat cards, steps, etc.)
        wp_register_style('bt-quote-form', BT_URL . 'elementor/assets/bt-quote-form.css', ['bt-bundle'], BT_VERSION);

        // Gallery + extra — handles aliasés vers leurs bundles respectifs
        foreach (['bt-gallery', 'bt-lightbox', 'bt-gallery-preview'] as $handle) {
            wp_register_style($handle, false, ['bt-bundle-gallery'], BT_VERSION);
        }
        foreach (['bt-related-boats', 'bt-related-excursions', 'bt-google-map'] as $handle) {
            wp_register_style($handle, false, ['bt-bundle-extra'], BT_VERSION);
        }

        // Sub-steps composant (doit charger avant bt-boat-pricing-quote)
        wp_register_script(
            'bt-quote-substep',
            BT_URL . 'elementor/assets/bt-quote-substep.js',
            ['bt-elementor'],
            BT_VERSION,
            true
        );

        // Quote form JS (devis multi-étapes — shared by bt-quote-form & bt-boat-pricing)
        wp_register_script(
            'bt-boat-pricing-quote',
            BT_URL . 'elementor/assets/bt-boat-pricing-quote.js',
            ['bt-elementor', 'bt-quote-substep'],
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

        // Plyr video player — CDN + widget CSS/JS (enqueued on-demand via get_style/script_depends)
        VideoPlayer::register_plyr_assets();
        wp_register_style(
            'bt-video-player',
            BT_URL . 'elementor/assets/bt-video-player.css',
            ['plyr-css'],
            BT_VERSION
        );
        wp_register_script(
            'bt-video-player',
            BT_URL . 'elementor/assets/bt-video-player.js',
            ['plyr-js'],
            BT_VERSION,
            true
        );

        // Fixed CTA — script + localize pour l'AJAX nonce
        wp_register_script(
            'bt-fixed-cta',
            BT_URL . 'elementor/assets/bt-fixed-cta.js',
            [],
            BT_VERSION,
            true
        );
        wp_localize_script('bt-fixed-cta', 'btcFixedCta', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('btc_store_nonce'),
        ]);

        // Fancy Menu — menu multi-niveaux animé
        wp_register_style(
            'bt-fancy-menu',
            BT_URL . 'elementor/assets/bt-fancy-menu.css',
            [],
            BT_VERSION
        );
        wp_register_script(
            'bt-fancy-menu',
            BT_URL . 'elementor/assets/bt-fancy-menu.js',
            [],
            BT_VERSION,
            true
        );

        // Leaflet — enregistré seulement (enqueued à la demande dans render_map_leaflet)
        wp_register_style('bt-leaflet-css', BT_URL . 'elementor/assets/leaflet.min.css', [], '1.9.4');
        wp_register_script('bt-leaflet',     BT_URL . 'elementor/assets/leaflet.min.js',  [], '1.9.4', true);
        wp_register_script('bt-leaflet-init', BT_URL . 'elementor/assets/bt-leaflet-init.js', ['bt-leaflet'], BT_VERSION, true);

        // Itinerary v4 — accordéon + map↔timeline sync (dépend de bt-leaflet pour flyTo)
        wp_register_script('bt-itinerary', BT_URL . 'elementor/assets/bt-itinerary.js', [], BT_VERSION, true);

        // Google Maps JS API init — registered only, enqueued on-demand via GoogleMap widget (get_bt_config['js'])
        wp_register_script('bt-gmaps-init', BT_URL . 'elementor/assets/bt-gmaps-init.js', [], BT_VERSION, true);

        // BT Carousel — Swiper carousel for containers (like Loop Carousel)
        wp_register_style('bt-carousel', BT_URL . 'elementor/assets/bt-carousel.css', ['swiper'], BT_VERSION);
        wp_register_script('bt-carousel', BT_URL . 'elementor/assets/bt-carousel.js', ['swiper', 'elementor-frontend'], BT_VERSION, true);

        // Legacy: Scroll Container (deprecated, use bt-carousel)
        wp_register_style('bt-scroll-container', BT_URL . 'elementor/assets/bt-scroll-container.css', ['swiper'], BT_VERSION);
        wp_register_script('bt-scroll-container', BT_URL . 'elementor/assets/bt-scroll-container.js', ['swiper'], BT_VERSION, true);
    }
}
