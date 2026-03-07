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

class ElementorManager {

    public function init(): void {
        add_action('elementor/loaded', [$this, 'setup']);
    }

    public function setup(): void {
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register',               [$this, 'register_widgets']);
        add_action('elementor/frontend/after_enqueue_styles',  [$this, 'enqueue_assets']);
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_assets']);
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_editor_extras']);
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
