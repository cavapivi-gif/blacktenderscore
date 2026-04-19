<?php
/**
 * ACF Badge Widget
 *
 * Widget Badge generique qui affiche un badge conditionnel base sur un champ ACF true/false.
 * Permet de choisir le champ ACF a checker via une liste deroulante.
 * Supporte les icones FA ET l'upload d'image.
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
use Elementor\Group_Control_Image_Size;
use Elementor\Icons_Manager;
use Elementor\Utils;

defined('ABSPATH') || exit;

class AcfBadge extends AbstractBtWidget {

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-acf-badge',
            'title'    => 'BT — Badge ACF',
            'icon'     => 'eicon-check-circle',
            'keywords' => ['badge', 'acf', 'true', 'false', 'conditionnel', 'bt', 'best seller', 'high demand', 'populaire'],
            'css'      => ['bt-acf-badge'],
        ];
    }

    /**
     * Recupere tous les champs ACF de type true_false.
     */
    private function get_acf_true_false_fields(): array {
        $options = ['' => __('-- Choisir un champ --', 'blacktenderscore')];

        if (!function_exists('acf_get_field_groups')) {
            return $options;
        }

        $field_groups = acf_get_field_groups();
        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['key']);
            if (!$fields) continue;

            foreach ($fields as $field) {
                if ($field['type'] === 'true_false') {
                    $options[$field['name']] = sprintf(
                        '%s (%s)',
                        $field['label'],
                        $field['name']
                    );
                }
            }
        }

        return $options;
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
     * Section Contenu.
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
            'acf_field',
            [
                'label'       => __('Champ ACF (True/False)', 'blacktenderscore'),
                'type'        => Controls_Manager::SELECT,
                'options'     => $this->get_acf_true_false_fields(),
                'default'     => '',
                'description' => __('Le badge s\'affiche si ce champ est TRUE.', 'blacktenderscore'),
            ]
        );

        $this->add_control(
            'acf_field_manual',
            [
                'label'       => __('Ou entrer le nom du champ manuellement', 'blacktenderscore'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => 'ex: best_seller, high_demand...',
                'description' => __('Utiliser si le champ n\'apparait pas dans la liste.', 'blacktenderscore'),
                'condition'   => [
                    'acf_field' => '',
                ],
            ]
        );

        $this->add_control(
            'divider_content_1',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_control(
            'badge_text',
            [
                'label'       => __('Texte du badge', 'blacktenderscore'),
                'type'        => Controls_Manager::TEXT,
                'default'     => 'Badge',
                'label_block' => true,
                'dynamic'     => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'divider_content_2',
            [
                'type' => Controls_Manager::DIVIDER,
            ]
        );

        $this->add_control(
            'icon_type',
            [
                'label'   => __('Type de visuel', 'blacktenderscore'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'icon',
                'options' => [
                    'none'  => __('Aucun', 'blacktenderscore'),
                    'icon'  => __('Icone (Font Awesome)', 'blacktenderscore'),
                    'image' => __('Image (upload)', 'blacktenderscore'),
                ],
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label'            => __('Icone', 'blacktenderscore'),
                'type'             => Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default'          => [
                    'value'   => 'fas fa-star',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'icon_type' => 'icon',
                ],
            ]
        );

        $this->add_control(
            'badge_image',
            [
                'label'   => __('Image', 'blacktenderscore'),
                'type'    => Controls_Manager::MEDIA,
                'default' => [
                    'url' => Utils::get_placeholder_image_src(),
                ],
                'condition' => [
                    'icon_type' => 'image',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Image_Size::get_type(),
            [
                'name'      => 'badge_image',
                'default'   => 'thumbnail',
                'condition' => [
                    'icon_type' => 'image',
                ],
            ]
        );

        $this->add_control(
            'visual_position',
            [
                'label'        => __('Position du visuel', 'blacktenderscore'),
                'type'         => Controls_Manager::CHOOSE,
                'default'      => 'before',
                'options'      => [
                    'before' => [
                        'title' => __('Avant le texte', 'blacktenderscore'),
                        'icon'  => 'eicon-h-align-left',
                    ],
                    'after'  => [
                        'title' => __('Apres le texte', 'blacktenderscore'),
                        'icon'  => 'eicon-h-align-right',
                    ],
                ],
                'toggle'       => false,
                'prefix_class' => 'bt-acf-badge--visual-',
                'condition'    => [
                    'icon_type!' => 'none',
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
                    '{{WRAPPER}} .bt-acf-badge__badge' => 'top: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bt-acf-badge__badge' => 'left: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} .bt-acf-badge__badge' => 'align-items: {{VALUE}};',
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
                'selector' => '{{WRAPPER}} .bt-acf-badge__badge',
                'fields_options' => [
                    'background' => [
                        'default' => 'classic',
                    ],
                    'color' => [
                        'default' => 'rgba(255, 255, 255, 0.9)',
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
                    '{{WRAPPER}} .bt-acf-badge__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'box_border',
                'selector' => '{{WRAPPER}} .bt-acf-badge__badge',
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
                    '{{WRAPPER}} .bt-acf-badge__badge' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'box_shadow',
                'selector' => '{{WRAPPER}} .bt-acf-badge__badge',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __('Couleur du texte', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#000000',
                'selectors' => [
                    '{{WRAPPER}} .bt-acf-badge__text' => 'color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section Style Icon/Image.
     */
    protected function register_style_icon_section(): void {
        $this->start_controls_section(
            'section_style_visual',
            [
                'label'     => __('Icone / Image', 'blacktenderscore'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'icon_type!' => 'none',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label'     => __('Couleur icone', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ffb743',
                'selectors' => [
                    '{{WRAPPER}} .bt-acf-badge__icon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .bt-acf-badge__icon svg' => 'fill: {{VALUE}};',
                ],
                'condition' => [
                    'icon_type' => 'icon',
                ],
            ]
        );

        $this->add_responsive_control(
            'visual_size',
            [
                'label'      => __('Taille', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em', 'rem'],
                'range'      => [
                    'px'  => ['min' => 8, 'max' => 100],
                    'em'  => ['min' => 0.5, 'max' => 5, 'step' => 0.1],
                    'rem' => ['min' => 0.5, 'max' => 5, 'step' => 0.1],
                ],
                'default'    => ['size' => 14, 'unit' => 'px'],
                'selectors'  => [
                    '{{WRAPPER}} .bt-acf-badge__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bt-acf-badge__icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bt-acf-badge__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .bt-acf-badge__image img' => 'width: {{SIZE}}{{UNIT}}; height: auto;',
                ],
            ]
        );

        $this->add_responsive_control(
            'visual_spacing',
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
                    '{{WRAPPER}}.bt-acf-badge--visual-before .bt-acf-badge__icon,
                     {{WRAPPER}}.bt-acf-badge--visual-before .bt-acf-badge__image' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}}.bt-acf-badge--visual-after .bt-acf-badge__icon,
                     {{WRAPPER}}.bt-acf-badge--visual-after .bt-acf-badge__image' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'image_border_radius',
            [
                'label'      => __('Border Radius (image)', 'blacktenderscore'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .bt-acf-badge__image img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition'  => [
                    'icon_type' => 'image',
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
                'selector' => '{{WRAPPER}} .bt-acf-badge__text',
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
        if (!$post_id || !function_exists('get_field')) {
            return;
        }

        $settings = $this->get_settings_for_display();

        // Determiner le champ ACF a checker
        $acf_field = !empty($settings['acf_field'])
            ? $settings['acf_field']
            : ($settings['acf_field_manual'] ?? '');

        if (empty($acf_field)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Selectionnez un champ ACF True/False.', 'blacktenderscore'));
            }
            return;
        }

        // Verifier le champ ACF
        if (!get_field($acf_field, $post_id)) {
            return;
        }

        $icon_type = $settings['icon_type'] ?? 'icon';
        $has_icon  = $icon_type === 'icon' && !empty($settings['selected_icon']['value']);
        $has_image = $icon_type === 'image' && !empty($settings['badge_image']['url']);
        $position  = $settings['visual_position'] ?? 'before';

        $this->add_render_attribute('wrapper', [
            'class' => 'bt-acf-badge',
            'role'  => 'region',
            'aria-label' => __('Badge', 'blacktenderscore'),
        ]);

        $this->add_render_attribute('badge', [
            'class' => 'bt-acf-badge__badge',
            'role'  => 'img',
            'aria-label' => esc_attr(wp_strip_all_tags($settings['badge_text'])),
        ]);

        $this->add_render_attribute('text', 'class', 'bt-acf-badge__text');
        ?>
        <div <?php $this->print_render_attribute_string('wrapper'); ?>>
            <div <?php $this->print_render_attribute_string('badge'); ?>>
                <?php if ($position === 'before') : ?>
                    <?php $this->render_visual($settings, $has_icon, $has_image); ?>
                <?php endif; ?>

                <span <?php $this->print_render_attribute_string('text'); ?>><?php echo wp_kses_post($settings['badge_text']); ?></span>

                <?php if ($position === 'after') : ?>
                    <?php $this->render_visual($settings, $has_icon, $has_image); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Affiche l'icone ou l'image.
     */
    private function render_visual(array $settings, bool $has_icon, bool $has_image): void {
        if ($has_icon) {
            echo '<span class="bt-acf-badge__icon elementor-icon">';
            Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true']);
            echo '</span>';
        } elseif ($has_image) {
            echo '<span class="bt-acf-badge__image">';
            echo Group_Control_Image_Size::get_attachment_image_html($settings, 'badge_image', 'badge_image');
            echo '</span>';
        }
    }

    /**
     * Template editeur (preview live).
     */
    protected function content_template(): void {
        ?>
        <#
        if (!settings.badge_text) return;

        var iconType = settings.icon_type || 'icon';
        var hasIcon = iconType === 'icon' && settings.selected_icon && settings.selected_icon.value;
        var hasImage = iconType === 'image' && settings.badge_image && settings.badge_image.url;
        var position = settings.visual_position || 'before';
        var iconHTML = hasIcon ? elementor.helpers.renderIcon(view, settings.selected_icon, { 'aria-hidden': true }, 'i', 'object') : null;

        function renderVisual() {
            if (hasIcon && iconHTML && iconHTML.rendered) {
                return '<span class="bt-acf-badge__icon elementor-icon">' + iconHTML.value + '</span>';
            } else if (hasImage) {
                var imageUrl = settings.badge_image.url;
                return '<span class="bt-acf-badge__image"><img src="' + imageUrl + '" alt="" /></span>';
            }
            return '';
        }
        #>
        <div class="bt-acf-badge" role="region" aria-label="<?php echo esc_attr__('Badge', 'blacktenderscore'); ?>">
            <div class="bt-acf-badge__badge" role="img" aria-label="{{ settings.badge_text }}">
                <# if (position === 'before') { #>{{{ renderVisual() }}}<# } #>
                <span class="bt-acf-badge__text">{{{ settings.badge_text }}}</span>
                <# if (position === 'after') { #>{{{ renderVisual() }}}<# } #>
            </div>
        </div>
        <?php
    }
}
