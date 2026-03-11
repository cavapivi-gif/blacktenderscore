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
 * Méthodes :
 *   register_tabs_nav_style($prefix, $label, $tab_sel, $active_sel, $tablist_sel, $condition)
 *   register_panel_style($prefix, $label, $selector, $condition)
 *   register_item_3state_style($prefix, $label, $item_sel, $hover_sel, $active_sel, $condition)
 */
trait BtNavControls {

    /**
     * Section de style pour une barre d'onglets — Normal, Survol (optionnel) et Actif.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography
     *   {prefix}_color / {prefix}_bg / {prefix}_border  (tab Normal)
     *   {prefix}_hover_color / {prefix}_hover_bg         (tab Survol, si $options['with_hover'])
     *   {prefix}_active_color / {prefix}_active_bg / {prefix}_active_border_color  (tab Actif)
     *   {prefix}_padding
     *   {prefix}_gap    (si $tablist_sel fourni)
     *   {prefix}_radius (si $options['with_radius'])
     *   {prefix}_indicator_size (si $options['with_indicator'])
     *   {prefix}_panel_padding  (si $options['with_panel'] + $options['panel_sel'])
     *
     * @param string $prefix      Préfixe IDs contrôles
     * @param string $label       Label section
     * @param string $tab_sel     Sélecteur onglet
     * @param string $active_sel  Sélecteur actif
     * @param string $tablist_sel Sélecteur liste (pour gap) — optionnel
     * @param array  $condition   Condition Elementor optionnelle
     * @param array  $options     Clés : 'with_hover', 'with_radius', 'with_indicator', 'with_panel', 'panel_sel'
     */
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
                'selectors'  => [$tablist_sel => 'gap: {{SIZE}}{{UNIT}}'],
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

    /**
     * Section de style pour un panneau de contenu (corps d'onglet, réponse...).
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography, {prefix}_color, {prefix}_bg
     *   {prefix}_padding, {prefix}_border, {prefix}_border_radius, {prefix}_shadow
     *
     * @param string $prefix    Préfixe IDs contrôles
     * @param string $label     Label section
     * @param string $selector  Sélecteur CSS
     * @param array  $condition Condition Elementor optionnelle
     */
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

    /**
     * Section de style pour un élément à 3 états : Normal / Survol / Actif.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_style_tabs (conteneur tabs)
     *   {prefix}_bg / {prefix}_border             (Normal)
     *   {prefix}_bg_hover / {prefix}_border_hover (Survol)
     *   {prefix}_bg_active / {prefix}_border_active (Actif)
     *   {prefix}_border_radius, {prefix}_padding, {prefix}_shadow (hors tabs)
     *
     * ⚠ Les IDs de controls ne doivent pas changer une fois des templates enregistrés.
     *
     * @param string      $prefix      Préfixe
     * @param string      $label       Label section
     * @param string      $item_sel    Sélecteur item normal
     * @param string|null $hover_sel   Sélecteur survol (défaut: $item_sel + ':hover')
     * @param string|null $active_sel  Sélecteur actif  (défaut: $item_sel + '.{prefix}--active')
     * @param array       $condition   Condition Elementor optionnelle
     */
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

        // ── Normal
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
        $this->end_controls_tab();

        // ── Survol
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
        $this->end_controls_tab();

        // ── Actif
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

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $item_sel,
        ]);

        $this->end_controls_section();
    }

    /**
     * Section Style bouton/CTA — Normal et Survol.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography
     *   {prefix}_color / {prefix}_bg / {prefix}_border  (Normal)
     *   {prefix}_color_hover / {prefix}_bg_hover        (Survol)
     *   {prefix}_padding   DIMENSIONS
     *   {prefix}_radius    DIMENSIONS
     *
     * @param string $prefix    Préfixe IDs
     * @param string $label     Label section
     * @param string $selector  Sélecteur CSS du bouton
     * @param array  $defaults  'color', 'bg' (valeurs par défaut)
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_button_style(
        string $prefix,
        string $label,
        string $selector,
        array  $defaults  = [],
        array  $condition = []
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

        $this->end_controls_section();
    }
}
