<?php
namespace BT_Regiondo\Elementor;

use BT_Regiondo\Elementor\Widgets\TaxonomyList;
use BT_Regiondo\Elementor\Widgets\FaqAccordion;
use BT_Regiondo\Elementor\Widgets\PricingTabs;
use BT_Regiondo\Elementor\Widgets\BoatSpecs;
use BT_Regiondo\Elementor\Widgets\BoatPricing;
use BT_Regiondo\Elementor\Widgets\RelatedBoats;
use BT_Regiondo\Elementor\Widgets\RelatedExcursions;
use BT_Regiondo\Elementor\Widgets\Itinerary;
use BT_Regiondo\Elementor\Widgets\DepartureTimes;
use BT_Regiondo\Elementor\Widgets\Reviews;
use BT_Regiondo\Elementor\Widgets\Gallery;
use BT_Regiondo\Elementor\Widgets\ExcursionSchema;

defined('ABSPATH') || exit;

// ── Sous-systèmes ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/dynamic-tags/class-dynamic-tags-manager.php';
require_once __DIR__ . '/loop-queries/class-loop-queries.php';

class ElementorManager {

    public function init(): void {
        // Dynamic Tags (pas besoin d'attendre elementor/loaded)
        (new DynamicTags\Dynamic_Tags_Manager())->init();

        // Loop Builder Query Sources
        (new LoopQueries\Loop_Queries())->init();

        // Invalidation transients RelatedExcursions lors de la sauvegarde
        add_action('save_post_excursion', [$this, 'invalidate_relexp_transients'], 10, 2);
        add_action('save_post_boat',      [$this, 'invalidate_relboat_transients'], 10, 1);

        add_action('elementor/loaded', [$this, 'setup']);
    }

    public function setup(): void {
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
        $manager->add_category('bt-regiondo', [
            'title' => 'BlackTenders',
            'icon'  => 'eicon-anchor',
        ]);
    }

    public function register_widgets(\Elementor\Widgets_Manager $manager): void {
        // ── Widgets excursion ─────────────────────────────────────────────
        $manager->register(new TaxonomyList());
        $manager->register(new FaqAccordion());
        $manager->register(new PricingTabs());
        $manager->register(new Itinerary());
        $manager->register(new DepartureTimes());
        $manager->register(new Reviews());
        $manager->register(new Gallery());
        $manager->register(new ExcursionSchema());
        // ── Relations excursion <-> bateau ────────────────────────────────
        $manager->register(new RelatedBoats());
        $manager->register(new RelatedExcursions());
        // ── Widgets bateau ────────────────────────────────────────────────
        $manager->register(new BoatSpecs());
        $manager->register(new BoatPricing());
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
        wp_enqueue_style(
            'bt-elementor',
            BT_REGIONDO_URL . 'elementor/assets/bt-elementor.css',
            [],
            BT_REGIONDO_VERSION
        );
        wp_enqueue_script(
            'bt-elementor',
            BT_REGIONDO_URL . 'elementor/assets/bt-elementor.js',
            [],
            BT_REGIONDO_VERSION,
            true
        );
    }
}
