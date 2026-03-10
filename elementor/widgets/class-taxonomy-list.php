<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Liste de taxonomie liée au post courant.
 *
 * Récupère les termes via un champ ACF (taxonomy field) ou directement
 * depuis get_the_terms(). Chaque terme peut afficher son icône (champ ACF
 * `taxomonies_icons` sur le terme) et sa description.
 */
class TaxonomyList extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-taxonomy-list',
            'title'    => 'BT — Taxonomie',
            'icon'     => 'eicon-tags',
            'keywords' => ['taxonomie', 'inclus', 'liste', 'terme', 'bt'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Section Content ───────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF (taxonomy)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'exp_included'            => __('Ce qui est inclus (exp_included)', 'blacktenderscore'),
                'exp_to_excluded'         => __('Ce qui est exclu (exp_to_excluded)', 'blacktenderscore'),
                'exp_to_bring'            => __('Ce qu\'il faut apporter (exp_to_bring)', 'blacktenderscore'),
                'boat_equipment_included' => __('Équipements bateau inclus (boat_equipment_included)', 'blacktenderscore'),
                'boat_services_included'  => __('Services bateau inclus (boat_services_included)', 'blacktenderscore'),
                'boat_option_on_demand'   => __('Options sur demande (boat_option_on_demand)', 'blacktenderscore'),
            ],
            'default' => 'exp_included',
        ]);

        $this->register_section_title_controls(['title' => __('Ce qui est inclus', 'blacktenderscore')]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'list'   => __('Liste verticale', 'blacktenderscore'),
                'grid'   => __('Grille', 'blacktenderscore'),
                'inline' => __('Inline (puces)', 'blacktenderscore'),
            ],
            'default' => 'list',
        ]);

        $this->add_control('show_icon', [
            'label'        => __("Afficher l'icône du terme", 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('description_position', [
            'label'     => __('Position de la description', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'inline' => __('En ligne (après le nom)', 'blacktenderscore'),
                'below'  => __('En dessous', 'blacktenderscore'),
            ],
            'default'   => 'below',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->end_controls_section();

        $this->register_section_title_style('{{WRAPPER}} .bt-taxlist__title');

        // ── Section Style — Éléments ──────────────────────────────────────
        $this->start_controls_section('style_items', [
            'label' => __('Style — Éléments', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('item_gap', [
            'label'      => __('Espacement entre éléments', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 48]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'     => __('Colonnes (grille)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 6,
            'default'   => 2,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__list--grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition' => ['layout' => 'grid'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'selector' => '{{WRAPPER}} .bt-taxlist__item-name',
        ]);

        $this->add_control('item_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__item-name' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('item_bg', [
            'label'     => __('Fond des éléments', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__item' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('item_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__item' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding des éléments', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 12, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                // Bug fix : le conteneur ET l'img/i doivent suivre la taille
                '{{WRAPPER}} .bt-taxlist__item-icon'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxlist__item-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
            ],
            'condition'  => ['show_icon' => 'yes'],
        ]);

        $this->add_control('icon_color', [
            'label'     => __('Couleur icône (FA / fallback)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-taxlist__item-icon i'                     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxlist__item-icon-fallback'              => 'color: {{VALUE}}; opacity: 1',
            ],
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_control('fallback_icon', [
            'label'     => __('Icône de remplacement (si aucune sur le terme)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'desc_typography',
            'label'     => __('Typographie description', 'blacktenderscore'),
            'selector'  => '{{WRAPPER}} .bt-taxlist__item-desc',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur description', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__item-desc' => 'color: {{VALUE}}'],
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        $terms = $this->resolve_terms($s['acf_field'], $post_id);

        if (empty($terms)) {
            if ($this->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun terme trouvé pour le champ « ' . esc_html($s['acf_field']) . ' ». Vérifiez le nom du champ ACF.</p>';
            }
            return;
        }

        $layout        = $s['layout'] ?: 'list';
        $show_icon     = $s['show_icon'] === 'yes';
        $show_desc     = $s['show_description'] === 'yes';
        $desc_pos      = $s['description_position'] ?: 'below';
        $fallback_icon = $s['fallback_icon'] ?? [];

        echo '<div class="bt-taxlist">';

        $this->render_section_title($s, 'bt-taxlist__title');

        $list_class = 'bt-taxlist__list bt-taxlist__list--' . esc_attr($layout);
        echo "<ul class=\"{$list_class}\">";

        foreach ($terms as $term) {
            if (!($term instanceof \WP_Term)) continue;

            $icon_url  = '';
            $icon_fa   = '';
            if ($show_icon && function_exists('get_field')) {
                // Image icon (champ ACF image sur le terme)
                $icon_data = get_field('taxomonies_icons', $term);
                if (is_array($icon_data))       $icon_url = $icon_data['url'] ?? '';
                elseif (is_string($icon_data))  $icon_url = $icon_data;

                // FA class icon (champ ACF texte sur le terme, optionnel)
                if (!$icon_url) {
                    $fa_raw = get_field('term_icon_class', $term);
                    if ($fa_raw && is_string($fa_raw)) $icon_fa = trim($fa_raw);
                }
            }

            echo '<li class="bt-taxlist__item">';

            if ($show_icon) {
                echo '<span class="bt-taxlist__item-icon">';
                if ($icon_url) {
                    echo '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($term->name) . '" loading="lazy" />';
                } elseif ($icon_fa) {
                    echo '<i class="' . esc_attr($icon_fa) . '" aria-hidden="true"></i>';
                } elseif (!empty($fallback_icon['value'])) {
                    // Icône Elementor choisie dans le contrôle
                    \Elementor\Icons_Manager::render_icon($fallback_icon, ['aria-hidden' => 'true']);
                } else {
                    echo '<span class="bt-taxlist__item-icon-fallback" aria-hidden="true">✓</span>';
                }
                echo '</span>';
            }

            echo '<span class="bt-taxlist__item-text">';

            if ($desc_pos === 'inline' && $show_desc && !empty($term->description)) {
                echo '<span class="bt-taxlist__item-name">' . esc_html($term->name) . '</span>';
                echo ' <span class="bt-taxlist__item-desc bt-taxlist__item-desc--inline">' . esc_html($term->description) . '</span>';
            } else {
                echo '<span class="bt-taxlist__item-name">' . esc_html($term->name) . '</span>';
                if ($show_desc && !empty($term->description)) {
                    echo '<span class="bt-taxlist__item-desc bt-taxlist__item-desc--below">' . esc_html($term->description) . '</span>';
                }
            }

            echo '</span></li>';
        }

        echo '</ul></div>';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolve_terms(string $field, int $post_id): array {
        if (!$field || !function_exists('get_field')) return [];

        $value = get_field($field, $post_id);
        if (empty($value)) return [];

        // ACF taxonomy field peut retourner : int[], WP_Term[], int, WP_Term
        if (is_array($value)) {
            return array_filter(array_map(function ($v) {
                if ($v instanceof \WP_Term) return $v;
                if (is_numeric($v)) {
                    $t = get_term((int) $v);
                    return ($t && !is_wp_error($t)) ? $t : null;
                }
                return null;
            }, $value));
        }

        if ($value instanceof \WP_Term) return [$value];

        if (is_numeric($value)) {
            $t = get_term((int) $value);
            return ($t && !is_wp_error($t)) ? [$t] : [];
        }

        return [];
    }
}
