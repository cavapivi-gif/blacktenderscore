<?php
/**
 * BT Carousel — Swiper Carousel for Elementor Container
 *
 * @package BlackTenders
 */

namespace BlackTenders\Elementor;

use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

defined('ABSPATH') || exit;

class ContainerScroll {

    public function init(): void {
        // Content controls → Layout section
        add_action(
            'elementor/element/container/section_layout_additional_options/before_section_end',
            [$this, 'register_content_controls']
        );
        // Style controls → Style tab (new section)
        add_action(
            'elementor/element/container/section_effects/after_section_end',
            [$this, 'register_style_section']
        );
        add_action('elementor/frontend/container/before_render', [$this, 'before_render']);
        add_action('elementor/frontend/container/after_render', [$this, 'after_render']);
    }

    /**
     * Content controls — Carousel behavior
     */
    public function register_content_controls($element): void {

        // ═══════════════════════════════════════════════════════════════════
        // ENABLE
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_carousel_heading', [
            'label'     => '<span class="bt-elementorBadge">BT</span> ' . esc_html__('Carousel', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $element->add_control('bt_carousel_enable', [
            'label'        => esc_html__('Activer le Carousel', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // ACTIVER SUR — device selection
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_carousel_devices', [
            'label'              => esc_html__('Activer sur', 'blacktenderscore'),
            'type'               => Controls_Manager::SELECT2,
            'multiple'           => true,
            'options'            => [
                'desktop' => esc_html__('Desktop', 'blacktenderscore'),
                'tablet'  => esc_html__('Tablet', 'blacktenderscore'),
                'mobile'  => esc_html__('Mobile', 'blacktenderscore'),
            ],
            'default'            => ['desktop', 'tablet', 'mobile'],
            'frontend_available' => true,
            'render_type'        => 'template',
            'condition'          => ['bt_carousel_enable' => 'yes'],
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // OFFSET — like Loop Carousel
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_offset_sides', [
            'label'              => esc_html__('Offset côtés', 'blacktenderscore'),
            'type'               => Controls_Manager::SELECT,
            'default'            => 'none',
            'options'            => [
                'none'  => esc_html__('Aucun', 'blacktenderscore'),
                'left'  => esc_html__('Gauche', 'blacktenderscore'),
                'right' => esc_html__('Droite', 'blacktenderscore'),
                'both'  => esc_html__('Les deux', 'blacktenderscore'),
            ],
            'frontend_available' => true,
            'prefix_class'       => 'bt-offset--',
            'condition'          => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_responsive_control('bt_offset_value', [
            'label'              => esc_html__('Valeur offset', 'blacktenderscore'),
            'type'               => Controls_Manager::SLIDER,
            'size_units'         => ['px'],  // Only px supported by Swiper slidesOffset
            'range'              => [
                'px' => ['min' => 0, 'max' => 300],
            ],
            'default'            => ['size' => 50, 'unit' => 'px'],
            'frontend_available' => true,
            'condition'          => [
                'bt_carousel_enable' => 'yes',
                'bt_offset_sides!'   => 'none',
            ],
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // SLIDES
        // ═══════════════════════════════════════════════════════════════════
        $slides_options = ['' => 'Auto'] + array_combine(range(1, 10), range(1, 10));

        $element->add_responsive_control('bt_slides_to_show', [
            'label'          => esc_html__('Slides visibles', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => $slides_options,
            'default'        => '3',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'condition'      => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_responsive_control('bt_slides_to_scroll', [
            'label'     => esc_html__('Slides à scroller', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => $slides_options,
            'default'   => '1',
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_responsive_control('bt_space_between', [
            'label'          => esc_html__('Espacement', 'blacktenderscore'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', 'em', 'rem'],
            'range'          => [
                'px'  => ['min' => 0, 'max' => 100],
                'em'  => ['min' => 0, 'max' => 10],
                'rem' => ['min' => 0, 'max' => 10],
            ],
            'default'        => ['size' => 20, 'unit' => 'px'],
            'tablet_default' => ['size' => 15, 'unit' => 'px'],
            'mobile_default' => ['size' => 10, 'unit' => 'px'],
            'selectors'      => ['{{WRAPPER}}' => '--bt-gap: {{SIZE}}{{UNIT}};'],
            'condition'      => ['bt_carousel_enable' => 'yes'],
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // SETTINGS
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_settings_heading', [
            'label'     => esc_html__('Paramètres', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_control('bt_loop', [
            'label'     => esc_html__('Boucle infinie', 'blacktenderscore'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_control('bt_autoplay', [
            'label'     => esc_html__('Autoplay', 'blacktenderscore'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => '',
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_control('bt_autoplay_speed', [
            'label'     => esc_html__('Délai autoplay (ms)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 5000,
            'min'       => 500,
            'max'       => 10000,
            'condition' => ['bt_carousel_enable' => 'yes', 'bt_autoplay' => 'yes'],
        ]);

        $element->add_control('bt_pause_on_hover', [
            'label'     => esc_html__('Pause au survol', 'blacktenderscore'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['bt_carousel_enable' => 'yes', 'bt_autoplay' => 'yes'],
        ]);

        $element->add_control('bt_speed', [
            'label'     => esc_html__('Vitesse transition (ms)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 500,
            'min'       => 100,
            'max'       => 2000,
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // NAVIGATION
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_nav_heading', [
            'label'     => esc_html__('Navigation', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_control('bt_arrows', [
            'label'     => esc_html__('Flèches', 'blacktenderscore'),
            'type'      => Controls_Manager::SWITCHER,
            'default'   => 'yes',
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_control('bt_pagination', [
            'label'     => esc_html__('Pagination', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'default'   => 'bullets',
            'options'   => [
                ''            => esc_html__('Aucune', 'blacktenderscore'),
                'bullets'     => esc_html__('Points', 'blacktenderscore'),
                'fraction'    => esc_html__('Fraction', 'blacktenderscore'),
                'progressbar' => esc_html__('Barre', 'blacktenderscore'),
            ],
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        $element->add_control('bt_pagination_position', [
            'label'        => esc_html__('Position pagination', 'blacktenderscore'),
            'type'         => Controls_Manager::SELECT,
            'default'      => 'outside',
            'options'      => [
                'inside'  => esc_html__('Intérieur', 'blacktenderscore'),
                'outside' => esc_html__('Extérieur', 'blacktenderscore'),
            ],
            'prefix_class' => 'bt-pagination--',
            'condition'    => ['bt_carousel_enable' => 'yes', 'bt_pagination!' => ''],
        ]);
    }

    /**
     * Style section for carousel — Arrows & Pagination
     */
    public function register_style_section($element): void {

        $element->start_controls_section('bt_carousel_style', [
            'label'     => '<span class="bt-elementorBadge">BT</span> ' . esc_html__('Carousel', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['bt_carousel_enable' => 'yes'],
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // STYLE — Arrows
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_style_arrows', [
            'label'     => esc_html__('Flèches', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrows_size', [
            'label'      => esc_html__('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 10, 'max' => 60], 'em' => ['min' => 0.5, 'max' => 4]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}}' => '--bt-arrow-size: {{SIZE}}{{UNIT}};'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrows_box_size', [
            'label'      => esc_html__('Taille bouton', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 20, 'max' => 100], 'em' => ['min' => 1, 'max' => 8]],
            'default'    => ['size' => 44, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}}' => '--bt-arrow-box: {{SIZE}}{{UNIT}};'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrows_color', [
            'label'     => esc_html__('Couleur icône', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}}' => '--bt-arrow-color: {{VALUE}};'],
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrows_bg', [
            'label'     => esc_html__('Couleur fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}}' => '--bt-arrow-bg: {{VALUE}};'],
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrows_color_hover', [
            'label'     => esc_html__('Couleur icône (survol)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}}' => '--bt-arrow-color-hover: {{VALUE}};'],
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrows_bg_hover', [
            'label'     => esc_html__('Couleur fond (survol)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}}' => '--bt-arrow-bg-hover: {{VALUE}};'],
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrows_radius', [
            'label'      => esc_html__('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 50], '%' => ['min' => 0, 'max' => 50]],
            'default'    => ['size' => 50, 'unit' => '%'],
            'selectors'  => ['{{WRAPPER}}' => '--bt-arrow-radius: {{SIZE}}{{UNIT}};'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        // ── Flèche Précédent — Position ─────────────────────────────────────
        $element->add_control('bt_arrow_prev_heading', [
            'label'     => esc_html__('Position — Flèche Précédent', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrow_prev_h_orient', [
            'label'   => esc_html__('Ancrage horizontal', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => esc_html__('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'center' => ['title' => esc_html__('Centre', 'blacktenderscore'), 'icon' => 'eicon-h-align-center'],
                'end'    => ['title' => esc_html__('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default'              => 'start',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-prev-l: 0px; --bt-prev-r: auto; --bt-prev-trx: 0px',
                'center' => '--bt-prev-l: 50%; --bt-prev-r: auto; --bt-prev-trx: -50%',
                'end'    => '--bt-prev-l: auto; --bt-prev-r: 0px; --bt-prev-trx: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-arrow--prev' => '{{VALUE}}'],
            'condition'            => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrow_prev_h_pos', [
            'label'      => esc_html__('Décalage horizontal', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'em'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-arrow--prev' => '--bt-prev-h-off: {{SIZE}}{{UNIT}}'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrow_prev_v_orient', [
            'label'   => esc_html__('Ancrage vertical', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => esc_html__('Haut', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center' => ['title' => esc_html__('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'end'    => ['title' => esc_html__('Bas', 'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'              => 'center',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-prev-t: 0px; --bt-prev-b: auto; --bt-prev-try: 0px',
                'center' => '--bt-prev-t: 50%; --bt-prev-b: auto; --bt-prev-try: -50%',
                'end'    => '--bt-prev-t: auto; --bt-prev-b: 0px; --bt-prev-try: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-arrow--prev' => '{{VALUE}}'],
            'condition'            => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrow_prev_v_pos', [
            'label'      => esc_html__('Décalage vertical', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'em'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-arrow--prev' => '--bt-prev-v-off: {{SIZE}}{{UNIT}}'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        // ── Flèche Suivant — Position ───────────────────────────────────────
        $element->add_control('bt_arrow_next_heading', [
            'label'     => esc_html__('Position — Flèche Suivant', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrow_next_h_orient', [
            'label'   => esc_html__('Ancrage horizontal', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => esc_html__('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'center' => ['title' => esc_html__('Centre', 'blacktenderscore'), 'icon' => 'eicon-h-align-center'],
                'end'    => ['title' => esc_html__('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default'              => 'end',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-next-l: 0px; --bt-next-r: auto; --bt-next-trx: 0px',
                'center' => '--bt-next-l: 50%; --bt-next-r: auto; --bt-next-trx: -50%',
                'end'    => '--bt-next-l: auto; --bt-next-r: 0px; --bt-next-trx: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-arrow--next' => '{{VALUE}}'],
            'condition'            => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrow_next_h_pos', [
            'label'      => esc_html__('Décalage horizontal', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'em'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => -16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-arrow--next' => '--bt-next-h-off: {{SIZE}}{{UNIT}}'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        $element->add_control('bt_arrow_next_v_orient', [
            'label'   => esc_html__('Ancrage vertical', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => esc_html__('Haut', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center' => ['title' => esc_html__('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'end'    => ['title' => esc_html__('Bas', 'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'              => 'center',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-next-t: 0px; --bt-next-b: auto; --bt-next-try: 0px',
                'center' => '--bt-next-t: 50%; --bt-next-b: auto; --bt-next-try: -50%',
                'end'    => '--bt-next-t: auto; --bt-next-b: 0px; --bt-next-try: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-arrow--next' => '{{VALUE}}'],
            'condition'            => ['bt_arrows' => 'yes'],
        ]);

        $element->add_responsive_control('bt_arrow_next_v_pos', [
            'label'      => esc_html__('Décalage vertical', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'em'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-arrow--next' => '--bt-next-v-off: {{SIZE}}{{UNIT}}'],
            'condition'  => ['bt_arrows' => 'yes'],
        ]);

        // ═══════════════════════════════════════════════════════════════════
        // STYLE — Pagination
        // ═══════════════════════════════════════════════════════════════════
        $element->add_control('bt_style_pagination', [
            'label'     => esc_html__('Pagination', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['bt_pagination' => 'bullets'],
        ]);

        $element->add_responsive_control('bt_dots_size', [
            'label'      => esc_html__('Taille points', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 4, 'max' => 20], 'em' => ['min' => 0.25, 'max' => 1.5]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}}' => '--bt-dot-size: {{SIZE}}{{UNIT}};'],
            'condition'  => ['bt_pagination' => 'bullets'],
        ]);

        $element->add_responsive_control('bt_dots_gap', [
            'label'      => esc_html__('Espace entre points', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}}' => '--bt-dot-gap: {{SIZE}}{{UNIT}};'],
            'condition'  => ['bt_pagination' => 'bullets'],
        ]);

        $element->add_responsive_control('bt_dots_spacing', [
            'label'      => esc_html__('Espace au-dessus', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}}' => '--bt-dot-spacing: {{SIZE}}{{UNIT}};'],
            'condition'  => ['bt_pagination' => 'bullets'],
        ]);

        $element->add_control('bt_dots_color', [
            'label'     => esc_html__('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}}' => '--bt-dot-color: {{VALUE}};'],
            'condition' => ['bt_pagination' => 'bullets'],
        ]);

        $element->add_control('bt_dots_active_color', [
            'label'     => esc_html__('Couleur active', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}}' => '--bt-dot-active: {{VALUE}};'],
            'condition' => ['bt_pagination' => 'bullets'],
        ]);

        $element->end_controls_section();
    }

    /**
     * Build Swiper config array from settings
     */
    private function build_swiper_config(array $s): array {
        $breakpoints = \Elementor\Plugin::$instance->breakpoints->get_active_breakpoints();
        $mobile_bp = $breakpoints['mobile']->get_value() ?? 767;
        $tablet_bp = $breakpoints['tablet']->get_value() ?? 1024;

        // Get gap values (slider returns array with size/unit)
        $gap_desktop = isset($s['bt_space_between']['size']) ? (int) $s['bt_space_between']['size'] : 20;
        $gap_tablet  = isset($s['bt_space_between_tablet']['size']) ? (int) $s['bt_space_between_tablet']['size'] : $gap_desktop;
        $gap_mobile  = isset($s['bt_space_between_mobile']['size']) ? (int) $s['bt_space_between_mobile']['size'] : 10;

        // Get offset value (only px supported for Swiper slidesOffset)
        $offset_sides = $s['bt_offset_sides'] ?? 'none';
        $offset_value = 0;
        if ($offset_sides !== 'none' && isset($s['bt_offset_value']['size'])) {
            $offset_value = (int) $s['bt_offset_value']['size'];
        }

        // Swiper is mobile-first: base = mobile, breakpoints = overrides for larger screens
        $config = [
            'slidesPerView'  => (int) ($s['bt_slides_to_show_mobile'] ?? 1) ?: 1,
            'slidesPerGroup' => (int) ($s['bt_slides_to_scroll_mobile'] ?? 1) ?: 1,
            'spaceBetween'   => $gap_mobile,
            'speed'          => (int) ($s['bt_speed'] ?? 500),
            'loop'           => !empty($s['bt_loop']) && $s['bt_loop'] === 'yes',
            'grabCursor'     => true,
            'navigation'     => !empty($s['bt_arrows']) && $s['bt_arrows'] === 'yes',
            'pagination'     => !empty($s['bt_pagination']) ? $s['bt_pagination'] : false,
            'breakpoints'    => [
                $mobile_bp + 1 => [
                    'slidesPerView'  => (int) ($s['bt_slides_to_show_tablet'] ?? $s['bt_slides_to_show'] ?? 2) ?: 2,
                    'slidesPerGroup' => (int) ($s['bt_slides_to_scroll_tablet'] ?? 1) ?: 1,
                    'spaceBetween'   => $gap_tablet,
                ],
                $tablet_bp + 1 => [
                    'slidesPerView'  => (int) ($s['bt_slides_to_show'] ?? 3) ?: 3,
                    'slidesPerGroup' => (int) ($s['bt_slides_to_scroll'] ?? 1) ?: 1,
                    'spaceBetween'   => $gap_desktop,
                ],
            ],
            // Elementor breakpoint values for JS device detection
            '_breakpoints' => [
                'mobile' => $mobile_bp,
                'tablet' => $tablet_bp,
            ],
        ];

        // Devices on which carousel is active
        $devices = $s['bt_carousel_devices'] ?? ['desktop', 'tablet', 'mobile'];
        if (!is_array($devices)) {
            $devices = ['desktop', 'tablet', 'mobile'];
        }
        $config['_devices'] = $devices;

        // Offset — use Swiper's native slidesOffsetBefore/After (not CSS)
        // slidesOffsetBefore = space before first slide (slides peek from left)
        // slidesOffsetAfter = space after last slide (slides peek from right)
        if ($offset_sides !== 'none' && $offset_value > 0) {
            $config['_offsetSides'] = $offset_sides;
            if ($offset_sides === 'left' || $offset_sides === 'both') {
                $config['slidesOffsetBefore'] = $offset_value;
            }
            if ($offset_sides === 'right' || $offset_sides === 'both') {
                $config['slidesOffsetAfter'] = $offset_value;
            }
        }

        // Autoplay
        if (!empty($s['bt_autoplay']) && $s['bt_autoplay'] === 'yes') {
            $config['autoplay'] = [
                'delay'                => (int) ($s['bt_autoplay_speed'] ?? 5000),
                'disableOnInteraction' => false,
            ];
            if (!empty($s['bt_pause_on_hover']) && $s['bt_pause_on_hover'] === 'yes') {
                $config['autoplay']['pauseOnMouseEnter'] = true;
            }
        }

        return $config;
    }

    public function before_render($element): void {
        $settings = $element->get_settings();

        if (empty($settings['bt_carousel_enable']) || $settings['bt_carousel_enable'] !== 'yes') {
            return;
        }

        wp_enqueue_style('swiper');
        wp_enqueue_script('swiper');
        wp_enqueue_style('bt-carousel');
        wp_enqueue_script('bt-carousel');

        $config = $this->build_swiper_config($settings);

        $element->add_render_attribute('_wrapper', [
            'class'          => 'bt-carousel',
            'data-bt-swiper' => wp_json_encode($config),
        ]);
    }

    public function after_render($element): void {
        $settings = $element->get_settings();

        if (empty($settings['bt_carousel_enable']) || $settings['bt_carousel_enable'] !== 'yes') {
            return;
        }

        // Arrows
        if (!empty($settings['bt_arrows']) && $settings['bt_arrows'] === 'yes') {
            ?>
            <div class="bt-arrow bt-arrow--prev" role="button" tabindex="0" aria-label="Précédent">
                <i class="eicon-chevron-left"></i>
            </div>
            <div class="bt-arrow bt-arrow--next" role="button" tabindex="0" aria-label="Suivant">
                <i class="eicon-chevron-right"></i>
            </div>
            <?php
        }

        // Pagination
        if (!empty($settings['bt_pagination'])) {
            echo '<div class="bt-pagination swiper-pagination"></div>';
        }
    }
}
