<?php
/**
 * High Demand Badge Widget
 *
 * Affiche un badge "Forte Demande" sur les produits marqués ACF high_demand.
 * Inspiré du widget Best Seller de StudioJaeCore.
 *
 * @package BlackTendersCore
 */

namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Icons_Manager;

defined('ABSPATH') || exit;

class HighDemandBadge extends AbstractBtWidget {

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-high-demand-badge',
            'title'    => 'BT — Badge Demande',
            'icon'     => 'eicon-flash',
            'keywords' => ['high demand', 'forte demande', 'badge', 'populaire', 'bt'],
            'css'      => ['bt-high-demand-badge'],
        ];
    }

    /**
     * Enregistre les controles du widget.
     */
    protected function register_controls(): void {
        $this->register_content_section();
        $this->register_style_box_section();
        $this->register_style_icon_section();
        $this->register_style_typography_section();
    }

    /**
     * Section Contenu (badge_text, icone, position).
     */
    protected function register_content_section(): void {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Contenu', 'blacktenderscore'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'badge_text',
            [
                'label'       => __('Texte du badge', 'blacktenderscore'),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'Forte Demande',
                'label_block' => true,
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label'            => __('Icone', 'blacktenderscore'),
                'type'             => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default'          => [
                    'value'   => 'fas fa-fire',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label'        => __('Position de l\'icone', 'blacktenderscore'),
                'type'         => Controls_Manager::CHOOSE,
                'default'      => 'before',
                'options'      => [
                    'before' => [
                        'title' => __('Avant', 'blacktenderscore'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'after'  => [
                        'title' => __('Apres', 'blacktenderscore'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'toggle'       => false,
                'prefix_class' => 'bt-high-demand--icon-',
                'condition'    => [
                    'selected_icon[value]!' => '',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section Style Box (badge container).
     */
    protected function register_style_box_section(): void {
        $this->start_controls_section(
            'section_style_box',
            [
                'label' => __('Badge', 'blacktenderscore'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'position_top',
            [
                'label'      => __('Position (haut)', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => -100, 'max' => 100],
                    '%'  => ['min' => -50, 'max' => 150],
                ],
                'default'    => ['size' => 5, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .bt-high-demand__badge' => 'top: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'position_left',
            [
                'label'      => __('Position (gauche)', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => -100, 'max' => 500],
                    '%'  => ['min' => -50, 'max' => 150],
                ],
                'default'    => ['size' => 5, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .bt-high-demand__badge' => 'left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'badge_align_items',
            [
                'label'                 => __('Alignement vertical', 'blacktenderscore'),
                'type'                  => Controls_Manager::CHOOSE,
                'options'               => [
                    'top'    => [
                        'title' => __('Haut', 'blacktenderscore'),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => __('Centre', 'blacktenderscore'),
                        'icon'  => 'eicon-v-align-middle',
                    ],
                    'bottom' => [
                        'title' => __('Bas', 'blacktenderscore'),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'default'               => 'center',
                'selectors'             => [
                    '{{WRAPPER}} .bt-high-demand__badge' => 'align-items: {{VALUE}};',
                ],
                'selectors_dictionary'  => [
                    'top'    => 'flex-start',
                    'center' => 'center',
                    'bottom' => 'flex-end',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            [
                'name'     => 'box_background',
                'types'    => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .bt-high-demand__badge',
                'fields_options' => [
                    'background' => [
                        'default' => 'classic',
                    ],
                    'color' => [
                        'default' => '#ff6b35',
                    ],
                ],
            ]
        );

        $this->add_responsive_control(
            'box_padding',
            [
                'label'      => __('Padding', 'blacktenderscore'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default'    => [
                    'top'    => 4,
                    'right'  => 10,
                    'bottom' => 4,
                    'left'   => 10,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bt-high-demand__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'box_border',
                'selector' => '{{WRAPPER}} .bt-high-demand__badge',
            ]
        );

        $this->add_responsive_control(
            'box_border_radius',
            [
                'label'      => __('Border Radius', 'blacktenderscore'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'default'    => [
                    'top'    => 5,
                    'right'  => 5,
                    'bottom' => 5,
                    'left'   => 5,
                    'unit'   => 'px',
                ],
                'selectors'  => [
                    '{{WRAPPER}} .bt-high-demand__badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'box_shadow',
                'selector' => '{{WRAPPER}} .bt-high-demand__badge',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __('Couleur du texte', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bt-high-demand__text' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section Style Icon.
     */
    protected function register_style_icon_section(): void {
        $this->start_controls_section(
            'section_style_icon',
            [
                'label'     => __('Icone', 'blacktenderscore'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'selected_icon[value]!' => '',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('Couleur', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .bt-high-demand__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bt-high-demand__icon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_align_items',
            [
                'label'                 => __('Alignement vertical', 'blacktenderscore'),
                'type'                  => Controls_Manager::CHOOSE,
                'options'               => [
                    'top'    => [
                        'title' => __('Haut', 'blacktenderscore'),
                        'icon'  => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => __('Centre', 'blacktenderscore'),
                        'icon'  => 'eicon-v-align-middle',
                    ],
                    'bottom' => [
                        'title' => __('Bas', 'blacktenderscore'),
                        'icon'  => 'eicon-v-align-bottom',
                    ],
                ],
                'default'               => 'center',
                'selectors'             => [
                    '{{WRAPPER}} .bt-high-demand__icon' => 'align-items: {{VALUE}};',
                ],
                'selectors_dictionary'  => [
                    'top'    => 'flex-start',
                    'center' => 'center',
                    'bottom' => 'flex-end',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label'      => __('Taille', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px'  => ['min' => 8, 'max' => 50],
                    'em'  => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
                    'rem' => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
                ],
                'default'    => ['size' => 14, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .bt-high-demand__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bt-high-demand__icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bt-high-demand__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'icon_spacing',
            [
                'label'      => __('Espacement', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px'  => ['min' => 0, 'max' => 50],
                    'em'  => ['min' => 0, 'max' => 3, 'step' => 0.1],
                    'rem' => ['min' => 0, 'max' => 3, 'step' => 0.1],
                ],
                'default'    => ['size' => 6, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}}.bt-high-demand--icon-before .bt-high-demand__icon' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.bt-high-demand--icon-after .bt-high-demand__icon'  => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section Style Typography.
     */
    protected function register_style_typography_section(): void {
        $this->start_controls_section(
            'section_style_typography',
            [
                'label' => __('Typographie', 'blacktenderscore'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'badge_typography',
                'selector' => '{{WRAPPER}} .bt-high-demand__text',
                'fields_options' => [
                    'font_size' => [
                        'default' => [
                            'unit' => 'px',
                            'size' => 12,
                        ],
                    ],
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Rendu frontend.
     */
    protected function render(): void {
        $post_id = get_the_ID();

        // Verifier le champ ACF high_demand
        if (!$post_id || !function_exists('get_field') || !get_field('high_demand', $post_id)) {
            return;
        }

        $settings = $this->get_settings_for_display();
        $has_icon = !empty($settings['selected_icon']['value']);

        $this->add_render_attribute('wrapper', [
            'class' => 'bt-high-demand',
            'role'  => 'region',
            'aria-label' => __('Badge Forte Demande', 'blacktenderscore'),
        ]);

        $this->add_render_attribute('badge', [
            'class' => 'bt-high-demand__badge',
            'role'  => 'img',
            'aria-label' => sprintf(__('Forte Demande: %s', 'blacktenderscore'), $settings['badge_text']),
        ]);

        $this->add_render_attribute('text', 'class', 'bt-high-demand__text');
        ?>
        <div <?php $this->print_render_attribute_string('wrapper'); ?>>
            <div <?php $this->print_render_attribute_string('badge'); ?>>
                <?php if ($has_icon && $settings['icon_position'] === 'before') : ?>
                    <span class="bt-high-demand__icon elementor-icon">
                        <?php Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']); ?>
                    </span>
                <?php endif; ?>
                <span <?php $this->print_render_attribute_string('text'); ?>><?php echo esc_html($settings['badge_text']); ?></span>
                <?php if ($has_icon && $settings['icon_position'] === 'after') : ?>
                    <span class="bt-high-demand__icon elementor-icon">
                        <?php Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Template editeur (preview live).
     */
    protected function content_template(): void {
        ?>
        <#
        if (!settings.badge_text) return;

        var hasIcon = settings.selected_icon && settings.selected_icon.value;
        var iconPos = settings.icon_position || 'before';
        var iconHTML = hasIcon ? elementor.helpers.renderIcon(view, settings.selected_icon, { 'aria-hidden': true }, 'i', 'object') : null;
        #>
        <div class="bt-high-demand" role="region" aria-label="<?php echo esc_attr__('Badge Forte Demande', 'blacktenderscore'); ?>">
            <div class="bt-high-demand__badge" role="img" aria-label="Forte Demande: {{ settings.badge_text }}">
                <# if (hasIcon && iconPos === 'before' && iconHTML && iconHTML.rendered) { #>
                    <span class="bt-high-demand__icon elementor-icon">{{{ iconHTML.value }}}</span>
                <# } #>
                <span class="bt-high-demand__text">{{{ settings.badge_text }}}</span>
                <# if (hasIcon && iconPos === 'after' && iconHTML && iconHTML.rendered) { #>
                    <span class="bt-high-demand__icon elementor-icon">{{{ iconHTML.value }}}</span>
                <# } #>
            </div>
        </div>
        <?php
    }
}
