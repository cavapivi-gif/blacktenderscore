<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Liste de taxonomie liée au post courant.
 *
 * Récupère les termes via un champ ACF (taxonomy field) ou directement
 * depuis get_the_terms(). Chaque terme peut afficher son icône (champ ACF
 * `taxomonies_icons` sur le terme) et sa description.
 */
class TaxonomyList extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-taxonomy-list'; }
    public function get_title():      string { return 'BT — Taxonomie'; }
    public function get_icon():       string { return 'eicon-tags'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['taxonomie', 'inclus', 'liste', 'terme', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Section Content ───────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'       => __('Champ ACF (taxonomy)', 'bt-regiondo'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'exp_included',
            'description' => __('Nom du champ ACF. Ex : exp_included, exp_to_excluded, exp_to_bring, boat_equipment_included', 'bt-regiondo'),
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de la section', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Ce qui est inclus', 'bt-regiondo'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p', 'span' => 'span'],
            'default' => 'h3',
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'list'   => __('Liste verticale', 'bt-regiondo'),
                'grid'   => __('Grille', 'bt-regiondo'),
                'inline' => __('Inline (puces)', 'bt-regiondo'),
            ],
            'default' => 'list',
        ]);

        $this->add_control('show_icon', [
            'label'        => __("Afficher l'icône du terme", 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'bt-regiondo'),
            'label_off'    => __('Non', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'bt-regiondo'),
            'label_off'    => __('Non', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('description_position', [
            'label'     => __('Position de la description', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'inline' => __('En ligne (après le nom)', 'bt-regiondo'),
                'below'  => __('En dessous', 'bt-regiondo'),
            ],
            'default'   => 'below',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Section Style — Titre ─────────────────────────────────────────
        $this->start_controls_section('style_title', [
            'label' => __('Style — Titre', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .bt-taxlist__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('title_spacing', [
            'label'      => __('Marge basse', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__title' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Section Style — Éléments ──────────────────────────────────────
        $this->start_controls_section('style_items', [
            'label' => __('Style — Éléments', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('item_gap', [
            'label'      => __('Espacement entre éléments', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 48]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'     => __('Colonnes (grille)', 'bt-regiondo'),
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
            'label'     => __('Couleur texte', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__item-name' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 12, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-taxlist__item-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
            ],
            'condition'  => ['show_icon' => 'yes'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'desc_typography',
            'label'     => __('Typographie description', 'bt-regiondo'),
            'selector'  => '{{WRAPPER}} .bt-taxlist__item-desc',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur description', 'bt-regiondo'),
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
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun terme trouvé pour le champ « ' . esc_html($s['acf_field']) . ' ». Vérifiez le nom du champ ACF.</p>';
            }
            return;
        }

        $tag       = esc_attr($s['title_tag'] ?: 'h3');
        $layout    = $s['layout'] ?: 'list';
        $show_icon = $s['show_icon'] === 'yes';
        $show_desc = $s['show_description'] === 'yes';
        $desc_pos  = $s['description_position'] ?: 'below';

        echo '<div class="bt-taxlist">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-taxlist__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        $list_class = 'bt-taxlist__list bt-taxlist__list--' . esc_attr($layout);
        echo "<ul class=\"{$list_class}\">";

        foreach ($terms as $term) {
            if (!($term instanceof \WP_Term)) continue;

            $icon_url = '';
            if ($show_icon && function_exists('get_field')) {
                $icon_data = get_field('taxomonies_icons', $term);
                if (is_array($icon_data))  $icon_url = $icon_data['url'] ?? '';
                elseif (is_string($icon_data)) $icon_url = $icon_data;
            }

            echo '<li class="bt-taxlist__item">';

            if ($show_icon) {
                echo '<span class="bt-taxlist__item-icon">';
                if ($icon_url) {
                    echo '<img src="' . esc_url($icon_url) . '" alt="' . esc_attr($term->name) . '" loading="lazy" />';
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
