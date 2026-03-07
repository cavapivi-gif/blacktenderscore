<?php
namespace BT_Regiondo\Elementor;

use BT_Regiondo\Elementor\Widgets\TaxonomyList;
use BT_Regiondo\Elementor\Widgets\FaqAccordion;
use BT_Regiondo\Elementor\Widgets\PricingTabs;

defined('ABSPATH') || exit;

class ElementorManager {

    public function init(): void {
        // Guard: Elementor must be active
        add_action('elementor/loaded', [$this, 'setup']);
    }

    public function setup(): void {
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);
        add_action('elementor/widgets/register',               [$this, 'register_widgets']);
        add_action('elementor/frontend/after_enqueue_styles',  [$this, 'enqueue_assets']);
        // Editor preview
        add_action('elementor/editor/after_enqueue_styles',    [$this, 'enqueue_assets']);
    }

    public function register_category(\Elementor\Elements_Manager $manager): void {
        $manager->add_category('bt-regiondo', [
            'title' => 'BlackTenders',
            'icon'  => 'eicon-anchor',
        ]);
    }

    public function register_widgets(\Elementor\Widgets_Manager $manager): void {
        $manager->register(new TaxonomyList());
        $manager->register(new FaqAccordion());
        $manager->register(new PricingTabs());
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
