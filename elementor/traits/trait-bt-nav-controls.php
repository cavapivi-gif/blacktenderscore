<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

/**
 * BtNavControls — Onglets, panneaux, items multi-états.
 *
 * Options avancées (comme le widget Tabs natif Elementor) :
 *   'with_direction'  — Direction responsive (horizontal/vertical)
 *   'with_justify'    — Alignement onglets (start/center/end/stretch)
 *   'with_scroll'     — Scroll horizontal responsive
 *   'with_breakpoint' — Breakpoint pour passer en accordéon
 *   'with_hover'      — État survol
 *   'with_radius'     — Border radius
 *   'with_indicator'  — Épaisseur indicateur actif
 *   'with_panel'      — Padding panneau (+ 'panel_sel')
 */
trait BtNavControls {

    protected function register_tabs_nav_style(
        string $prefix,
        string $label,
        string $tab_sel,
        string $active_sel,
        string $tablist_sel = '',
        array  $condition   = [],
        array  $options     = []
    ): void {
        $section_args = [
            'label' => $label,
            'tab'   => Controls_Manager::TAB_STYLE,
        ];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        // ── Direction (responsive) ────────────────────────────────────────
        if (!empty($options['with_direction']) && $tablist_sel) {
            $this->add_responsive_control("{$prefix}_direction", [
                'label'   => __('Direction', 'blacktenderscore'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'row'    => ['title' => __('Horizontal', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                    'column' => ['title' => __('Vertical', 'blacktenderscore'),   'icon' => 'eicon-v-align-top'],
                ],
                'default'   => 'row',
                'selectors' => [$tablist_sel => 'flex-direction: {{VALUE}}'],
            ]);
        }

        // ── Justify (responsive) ──────────────────────────────────────────
        if (!empty($options['with_justify']) && $tablist_sel) {
            $this->add_responsive_control("{$prefix}_justify", [
                'label'   => __('Alignement onglets', 'blacktenderscore'),
                'type'    => Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => ['title' => __('Début', 'blacktenderscore'),  'icon' => 'eicon-align-start-h'],
                    'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-align-center-h'],
                    'flex-end'   => ['title' => __('Fin', 'blacktenderscore'),    'icon' => 'eicon-align-end-h'],
                    'stretch'    => ['title' => __('Étirer', 'blacktenderscore'), 'icon' => 'eicon-align-stretch-h'],
                ],
                'default'   => 'flex-start',
                'selectors' => [$tablist_sel => 'justify-content: {{VALUE}}'],
            ]);

            // Stretch → each tab grows equally
            $this->add_responsive_control("{$prefix}_tab_grow", [
                'label'     => __('Étirement', 'blacktenderscore'),
                'type'      => Controls_Manager::HIDDEN,
                'default'   => '1',
                'selectors' => [$tab_sel => 'flex-grow: 1; text-align: center'],
                'condition' => ["{$prefix}_justify" => 'stretch'],
            ]);
        }

        // ── Horizontal scroll (responsive) ────────────────────────────────
        if (!empty($options['with_scroll']) && $tablist_sel) {
            $this->add_responsive_control("{$prefix}_scroll", [
                'label'   => __('Scroll horizontal', 'blacktenderscore'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'wrap'   => __('Retour à la ligne', 'blacktenderscore'),
                    'scroll' => __('Scroll horizontal', 'blacktenderscore'),
                ],
                'default'   => 'wrap',
                'selectors' => [
                    $tablist_sel => '{{VALUE}}',
                ],
                'selectors_dictionary' => [
                    'wrap'   => 'overflow-x: visible; flex-wrap: wrap',
                    'scroll' => 'overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; scrollbar-width: none',
                ],
            ]);
        }

        // ── Breakpoint accordion ──────────────────────────────────────────
        if (!empty($options['with_breakpoint'])) {
            $this->add_control("{$prefix}_breakpoint", [
                'label'   => __('Passer en accordéon', 'blacktenderscore'),
                'type'    => Controls_Manager::SELECT,
                'options' => [
                    'none'   => __('Jamais', 'blacktenderscore'),
                    'mobile' => __('Mobile (< 768px)', 'blacktenderscore'),
                    'tablet' => __('Tablette (< 1025px)', 'blacktenderscore'),
                ],
                'default'     => 'none',
                'description' => __('Sous ce breakpoint, les onglets passent en accordéon vertical.', 'blacktenderscore'),
                'render_type' => 'template',
                'prefix_class' => 'bt-tabs-bp-',
            ]);
        }

        $this->add_control("{$prefix}_layout_sep", ['type' => Controls_Manager::DIVIDER]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => "{$prefix}_typography",
            'selector' => $tab_sel,
        ]);

        $with_hover = !empty($options['with_hover']);
        $this->start_controls_tabs("{$prefix}_state_tabs");

        // ── Normal
        $this->start_controls_tab("{$prefix}_tab_normal", ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$tab_sel => 'color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$tab_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $tab_sel,
        ]);
        $this->end_controls_tab();

        // ── Survol (optionnel)
        if ($with_hover) {
            $hover_sel = $tab_sel . ':hover';
            $this->start_controls_tab("{$prefix}_tab_hover", ['label' => __('Survol', 'blacktenderscore')]);
            $this->add_control("{$prefix}_hover_color", [
                'label'     => __('Couleur', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$hover_sel => 'color: {{VALUE}}'],
            ]);
            $this->add_control("{$prefix}_hover_bg", [
                'label'     => __('Fond', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [$hover_sel => 'background-color: {{VALUE}}'],
            ]);
            $this->add_group_control(Group_Control_Border::get_type(), [
                'name'     => "{$prefix}_border_hover",
                'selector' => $hover_sel,
            ]);
            $this->end_controls_tab();
        }

        // ── Actif
        $this->start_controls_tab("{$prefix}_tab_active", ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control("{$prefix}_active_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_active_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border_active",
            'selector' => $active_sel,
        ]);
        $this->add_control("{$prefix}_active_border_color", [
            'label'     => __('Indicateur (bordure bas)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'border-bottom-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow_active",
            'selector' => $active_sel,
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding onglet', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$tab_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        if (!empty($options['with_radius'])) {
            $this->add_responsive_control("{$prefix}_radius", [
                'label'      => __('Border radius', 'blacktenderscore'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors'  => [$tab_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
            ]);
        }

        if ($tablist_sel) {
            $this->add_responsive_control("{$prefix}_gap", [
                'label'      => __('Espacement entre onglets', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 0, 'max' => 40]],
                'selectors'  => [$tablist_sel => 'gap: {{SIZE}}{{UNIT}}'],
            ]);

            $this->add_responsive_control("{$prefix}_tablist_spacing", [
                'label'      => __('Espace sous les onglets', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => ['px' => ['min' => 0, 'max' => 60]],
                'selectors'  => [$tablist_sel => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            ]);
        }

        if (!empty($options['with_indicator'])) {
            $this->add_control("{$prefix}_indicator_sep", ['type' => Controls_Manager::DIVIDER]);
            $this->add_control("{$prefix}_indicator_heading", [
                'label' => __('Épaisseur de l\'indicateur actif', 'blacktenderscore'),
                'type'  => Controls_Manager::HEADING,
            ]);
            $this->add_responsive_control("{$prefix}_indicator_size", [
                'label'      => __('Épaisseur', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 0, 'max' => 8]],
                'selectors'  => [$active_sel => 'border-bottom-width: {{SIZE}}{{UNIT}}'],
            ]);
        }

        if (!empty($options['with_panel']) && !empty($options['panel_sel'])) {
            $this->add_control("{$prefix}_panel_sep", ['type' => Controls_Manager::DIVIDER]);
            $this->add_control("{$prefix}_panel_heading", [
                'label' => __('Contenu du panneau', 'blacktenderscore'),
                'type'  => Controls_Manager::HEADING,
            ]);
            $this->add_responsive_control("{$prefix}_panel_padding", [
                'label'      => __('Padding du panneau', 'blacktenderscore'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [$options['panel_sel'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
            ]);
        }

        $this->end_controls_section();
    }

    protected function register_panel_style(
        string $prefix,
        string $label,
        string $selector,
        array  $condition = []
    ): void {
        $section_args = [
            'label' => $label,
            'tab'   => Controls_Manager::TAB_STYLE,
        ];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => "{$prefix}_typography",
            'selector' => $selector,
        ]);

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);

        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $selector,
        ]);

        $this->add_responsive_control("{$prefix}_border_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $selector,
        ]);

        $this->end_controls_section();
    }

    protected function register_item_3state_style(
        string  $prefix,
        string  $label,
        string  $item_sel,
        ?string $hover_sel  = null,
        ?string $active_sel = null,
        array   $condition  = []
    ): void {
        $hover_sel  = $hover_sel  ?? "{$item_sel}:hover";
        $active_sel = $active_sel ?? "{$item_sel}.{$prefix}--active";

        $section_args = [
            'label' => $label,
            'tab'   => Controls_Manager::TAB_STYLE,
        ];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->start_controls_tabs("{$prefix}_style_tabs");

        $this->start_controls_tab("{$prefix}_tab_normal", ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$item_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $item_sel,
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $item_sel,
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab("{$prefix}_tab_hover", ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control("{$prefix}_bg_hover", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$hover_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border_hover",
            'selector' => $hover_sel,
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow_hover",
            'selector' => $hover_sel,
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab("{$prefix}_tab_active", ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control("{$prefix}_bg_active", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border_active",
            'selector' => $active_sel,
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow_active",
            'selector' => $active_sel,
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control("{$prefix}_border_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [
                $item_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden',
            ],
        ]);

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$item_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    protected function register_button_style(
        string $prefix,
        string $label,
        string $selector,
        array  $defaults  = [],
        array  $condition = [],
        array  $options   = []
    ): void {
        $section_args = ['label' => $label, 'tab' => Controls_Manager::TAB_STYLE];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => "{$prefix}_typography",
            'selector' => $selector,
        ]);

        $this->start_controls_tabs("{$prefix}_state_tabs");

        $this->start_controls_tab("{$prefix}_tab_normal", ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['bg'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $selector,
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab("{$prefix}_tab_hover", ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control("{$prefix}_color_hover", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$selector}:hover" => 'color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_bg_hover", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$selector}:hover" => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border_hover",
            'selector' => "{$selector}:hover",
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control("{$prefix}_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        if (!empty($options['with_gap'])) {
            $this->add_responsive_control("{$prefix}_gap", [
                'label'      => __('Gap interne (icône / texte)', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range'      => ['px' => ['min' => 0, 'max' => 60]],
                'default'    => ['size' => $defaults['gap'] ?? 6, 'unit' => 'px'],
                'selectors'  => [$selector => 'gap: {{SIZE}}{{UNIT}}'],
                'separator'  => 'before',
            ]);
        }

        $this->end_controls_section();
    }
}
