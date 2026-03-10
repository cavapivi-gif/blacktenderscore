<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

/**
 * BtLayoutControls — Grille responsive et box/container style.
 *
 * Méthodes :
 *   register_grid_layout_controls($container_sel, $defaults, $label)
 *   register_box_style($prefix, $label, $selector, $defaults, $condition)
 */
trait BtLayoutControls {

    /**
     * Section Mise en page : colonnes responsives + gap.
     *
     * Controls générés :
     *   columns  (RESPONSIVE SELECT 1-6, défaut 3 / 2 / 1)
     *   gap      (RESPONSIVE SLIDER px)
     *
     * @param string $container_sel  Sélecteur CSS de la grille
     * @param array  $defaults       'columns' (int, défaut 3), 'gap' (int px, défaut 24)
     * @param string $label          Label de la section (défaut: 'Mise en page')
     */
    protected function register_grid_layout_controls(
        string $container_sel,
        array  $defaults = [],
        string $label    = 'Mise en page'
    ): void {
        $this->start_controls_section('section_layout', [
            'label' => __($label, 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
            'default'        => (string) ($defaults['columns'] ?? 3),
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors'      => [$container_sel => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $gap = $defaults['gap'] ?? 24;
        $this->add_responsive_control('gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => $gap, 'unit' => 'px'],
            'selectors'  => [$container_sel => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    /**
     * Section Style complète pour un bloc/container.
     * bg + border + radius + padding + shadow.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_bg        COLOR → background-color
     *   {prefix}_border    GROUP Border
     *   {prefix}_radius    DIMENSIONS px/% → border-radius
     *   {prefix}_padding   DIMENSIONS px/em → padding
     *   {prefix}_shadow    GROUP Box Shadow
     *
     * @param string $prefix    Préfixe IDs contrôles
     * @param string $label     Label section
     * @param string $selector  Sélecteur CSS
     * @param array  $defaults  'padding' (int px), 'radius' (int px)
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_box_style(
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

        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $selector,
        ]);

        $this->add_responsive_control("{$prefix}_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $pad = $defaults['padding'] ?? null;
        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => $pad ? ['top' => $pad, 'right' => $pad, 'bottom' => $pad, 'left' => $pad, 'unit' => 'px', 'isLinked' => true] : [],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $selector,
        ]);

        $this->end_controls_section();
    }

    /**
     * Section Style pour un séparateur / diviseur visuel.
     *
     * Controls générés :
     *   {prefix}_color     COLOR → background-color (or border-color)
     *   {prefix}_width     SLIDER px → height (épaisseur)
     *   {prefix}_length    SLIDER % → width
     *   {prefix}_spacing   SLIDER px → margin (top+bottom)
     *
     * @param string $prefix   ex: 'separator'
     * @param string $label    ex: 'Séparateur'
     * @param string $selector ex: '{{WRAPPER}} .bt-widget__separator'
     * @param array  $defaults 'color' (hex), 'width' (px int), 'length' (% int)
     * @param array  $condition Elementor condition optionnelle
     */
    protected function register_separator_controls(
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

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $w = $defaults['width'] ?? 1;
        $this->add_responsive_control("{$prefix}_width", [
            'label'      => __('Épaisseur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 20]],
            'default'    => ['size' => $w, 'unit' => 'px'],
            'selectors'  => [$selector => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $l = $defaults['length'] ?? 100;
        $this->add_responsive_control("{$prefix}_length", [
            'label'      => __('Longueur (%)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => ['%' => ['min' => 10, 'max' => 100], 'px' => ['min' => 10, 'max' => 800]],
            'default'    => ['size' => $l, 'unit' => '%'],
            'selectors'  => [$selector => 'width: {{SIZE}}{{UNIT}}; display: block'],
        ]);

        $this->add_responsive_control("{$prefix}_spacing", [
            'label'      => __('Espacement (haut/bas)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [$selector => 'margin-block: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }
}
