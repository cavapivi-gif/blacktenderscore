<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * BtTypographyControls — Section typographie complète pour un élément textuel.
 *
 * Méthodes :
 *   register_typography_section($prefix, $label, $selector, $options, $defaults, $condition)
 */
trait BtTypographyControls {

    /**
     * Section Style typographie pour un élément textuel.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography   GROUP Typography
     *   {prefix}_color        COLOR
     *   {prefix}_hover_color  COLOR :hover  (si $options['with_hover'])
     *   {prefix}_align        CHOOSE left/center/right (si $options['with_align'])
     *   {prefix}_width        SLIDER px/%/vh responsive → width (si $options['with_width'])
     *   {prefix}_spacing      SLIDER margin-bottom (si $options['with_spacing'])
     *
     * @param string $prefix    Préfixe IDs
     * @param string $label     Label de la section
     * @param string $selector  Sélecteur CSS
     * @param array  $options   'with_hover' (bool), 'with_align' (bool), 'with_spacing' (bool)
     * @param array  $defaults  'color' (hex), 'hover_color' (hex), 'align' (left/center/right)
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_typography_section(
        string $prefix,
        string $label,
        string $selector,
        array  $options   = [],
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

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);

        if (!empty($options['with_hover'])) {
            $this->add_control("{$prefix}_hover_color", [
                'label'     => __('Couleur survol', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => $defaults['hover_color'] ?? '',
                'selectors' => ["{$selector}:hover" => 'color: {{VALUE}}'],
            ]);
        }

        if (!empty($options['with_align'])) {
            $this->add_responsive_control("{$prefix}_align", [
                'label'     => __('Alignement', 'blacktenderscore'),
                'type'      => Controls_Manager::CHOOSE,
                'default'   => $defaults['align'] ?? '',
                'options'   => [
                    'left'   => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                    'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                    'right'  => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
                ],
                'selectors' => [$selector => 'text-align: {{VALUE}}'],
            ]);
        }

        if (!empty($options['with_width'])) {
            $this->add_responsive_control("{$prefix}_width", [
                'label'      => __('Largeur', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%', 'vh'],
                'range'      => [
                    'px' => ['min' => 0, 'max' => 1200],
                    '%'  => ['min' => 0, 'max' => 100],
                    'vh' => ['min' => 0, 'max' => 100],
                ],
                'selectors'  => [$selector => 'width: {{SIZE}}{{UNIT}}'],
            ]);
        }

        if (!empty($options['with_spacing'])) {
            $this->add_responsive_control("{$prefix}_spacing", [
                'label'      => __('Espacement bas', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors'  => [$selector => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            ]);
        }

        $this->end_controls_section();
    }
}
