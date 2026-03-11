<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Inclus / Exclus.
 *
 * Affiche deux colonnes (inclus / non inclus) à partir de champs ACF
 * de type taxonomie ou repeater.
 */
class IncludedExcluded extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-included-excluded',
            'title'    => 'BT — Inclus / Exclus',
            'icon'     => 'eicon-check-circle-o',
            'keywords' => ['inclus', 'exclu', 'compris', 'liste', 'bt'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();

        $this->add_control('show_included', [
            'label'        => __('Afficher colonne Inclus', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('included_field', [
            'label'   => __('Champ inclus', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'exp_included'             => __('Inclus (exp_included)', 'blacktenderscore'),
                'boat_equipment_included'  => __('Équipements bateau (boat_equipment_included)', 'blacktenderscore'),
                'boat_services_included'   => __('Services bateau (boat_services_included)', 'blacktenderscore'),
            ],
            'default' => 'exp_included',
        ]);

        $this->add_control('included_label', [
            'label'   => __('Titre colonne inclus', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Inclus',
        ]);

        $this->add_control('included_icon', [
            'label'   => __('Icône inclus', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '✓',
        ]);

        $this->add_control('show_excluded', [
            'label'        => __('Afficher colonne Exclus', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('excluded_field', [
            'label'   => __('Champ exclus', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'exp_to_excluded'       => __('Exclus (exp_to_excluded)', 'blacktenderscore'),
                'boat_option_on_demand' => __('Options sur demande (boat_option_on_demand)', 'blacktenderscore'),
            ],
            'default' => 'exp_to_excluded',
        ]);

        $this->add_control('excluded_label', [
            'label'   => __('Titre colonne exclus', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Non inclus',
        ]);

        $this->add_control('excluded_icon', [
            'label'   => __('Icône exclus', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '✗',
        ]);

        $this->add_control('show_desc', [
            'label'        => __('Afficher description des termes', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('show_icons', [
            'label'        => __('Afficher les icônes ✓/✗', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-inclexcl__section-title');

        // Style — Colonnes
        $this->start_controls_section('style_cols', [
            'label' => __('Style — Colonnes', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('cols_gap', [
            'label'      => __('Espacement colonnes', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 32, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // Style — Titre colonne
        $this->start_controls_section('style_col_title', [
            'label' => __('Style — Titre colonne', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'col_title_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-inclexcl__col-title',
        ]);

        $this->add_control('col_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('included_col_title_color', [
            'label'     => __('Couleur titre "Inclus"', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('excluded_col_title_color', [
            'label'     => __('Couleur titre "Exclus"', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // Style — Items
        $this->start_controls_section('style_items', [
            'label' => __('Style — Items', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-inclexcl__text',
        ]);

        $this->add_control('item_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__text' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('included_icon_color', [
            'label'     => __('Couleur icône inclus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('excluded_icon_color', [
            'label'     => __('Couleur icône exclus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__icon' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('items_gap', [
            'label'      => __('Espacement items', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        echo '<div class="bt-inclexcl">';

        $this->render_section_title($s, 'bt-inclexcl__section-title');

        echo '<div class="bt-inclexcl__grid">';

        // Colonne Inclus
        if ($s['show_included'] === 'yes') {
            $included_field = sanitize_text_field($s['included_field'] ?: 'exp_included');
            $included_terms = get_field($included_field, $post_id);
            $included_icon  = $s['included_icon'] ?: '✓';
            $included_label = $s['included_label'] ?: 'Inclus';

            if (!empty($included_terms)) {
                echo '<div class="bt-inclexcl__col bt-inclexcl__col--included">';
                echo '<h5 class="bt-inclexcl__col-title">' . esc_html($included_label) . '</h5>';
                echo '<ul class="bt-inclexcl__list">';

                foreach ((array) $included_terms as $term) {
                    [$term_name, $term_desc] = $this->resolve_term($term);
                    if (!$term_name) continue;

                    echo '<li class="bt-inclexcl__item">';
                    if ($s['show_icons'] === 'yes') {
                        echo '<span class="bt-inclexcl__icon" aria-hidden="true">' . esc_html($included_icon) . '</span>';
                    }
                    echo '<span class="bt-inclexcl__text">' . esc_html($term_name);
                    if ($s['show_desc'] === 'yes' && $term_desc) {
                        echo '<span class="bt-inclexcl__desc">' . esc_html($term_desc) . '</span>';
                    }
                    echo '</span>';
                    echo '</li>';
                }

                echo '</ul>';
                echo '</div>'; // .bt-inclexcl__col--included
            } elseif ($this->is_edit_mode()) {
                echo '<div class="bt-inclexcl__col bt-inclexcl__col--included">';
                $this->render_placeholder(sprintf(__('Champ « %s » vide ou introuvable.', 'blacktenderscore'), $included_field));
                echo '</div>';
            }
        }

        // Colonne Exclus
        if ($s['show_excluded'] === 'yes') {
            $excluded_field = sanitize_text_field($s['excluded_field'] ?: 'exp_to_excluded');
            $excluded_terms = get_field($excluded_field, $post_id);
            $excluded_icon  = $s['excluded_icon'] ?: '✗';
            $excluded_label = $s['excluded_label'] ?: 'Non inclus';

            if (!empty($excluded_terms)) {
                echo '<div class="bt-inclexcl__col bt-inclexcl__col--excluded">';
                echo '<h5 class="bt-inclexcl__col-title">' . esc_html($excluded_label) . '</h5>';
                echo '<ul class="bt-inclexcl__list">';

                foreach ((array) $excluded_terms as $term) {
                    [$term_name, $term_desc] = $this->resolve_term($term);
                    if (!$term_name) continue;

                    echo '<li class="bt-inclexcl__item">';
                    if ($s['show_icons'] === 'yes') {
                        echo '<span class="bt-inclexcl__icon" aria-hidden="true">' . esc_html($excluded_icon) . '</span>';
                    }
                    echo '<span class="bt-inclexcl__text">' . esc_html($term_name);
                    if ($s['show_desc'] === 'yes' && $term_desc) {
                        echo '<span class="bt-inclexcl__desc">' . esc_html($term_desc) . '</span>';
                    }
                    echo '</span>';
                    echo '</li>';
                }

                echo '</ul>';
                echo '</div>'; // .bt-inclexcl__col--excluded
            } elseif ($this->is_edit_mode()) {
                echo '<div class="bt-inclexcl__col bt-inclexcl__col--excluded">';
                $this->render_placeholder(sprintf(__('Champ « %s » vide ou introuvable.', 'blacktenderscore'), $excluded_field));
                echo '</div>';
            }
        }

        echo '</div>'; // .bt-inclexcl__grid
        echo '</div>'; // .bt-inclexcl
    }

    /**
     * Résout un terme ACF en [name, description].
     * Gère : WP_Term, term ID (int/numeric string), array, plain string.
     *
     * @param mixed $term
     * @return array{0: string, 1: string} [name, description]
     */
    private function resolve_term(mixed $term): array {
        // WP_Term object (return format "Term Object")
        if ($term instanceof \WP_Term) {
            return [$term->name, $term->description];
        }

        // Term ID (return format "Term ID") — int or numeric string
        if (is_numeric($term)) {
            $t = get_term((int) $term);
            if ($t && !is_wp_error($t)) {
                return [$t->name, $t->description];
            }
            return ['', ''];
        }

        // Array (repeater sub-row or term-like array)
        if (is_array($term)) {
            // Could be a term array with 'term_id'
            if (isset($term['term_id'])) {
                $t = get_term((int) $term['term_id']);
                if ($t && !is_wp_error($t)) {
                    return [$t->name, $t->description];
                }
            }
            return [
                $term['name'] ?? $term['label'] ?? '',
                $term['description'] ?? '',
            ];
        }

        // Plain string
        if (is_string($term) && $term !== '') {
            return [$term, ''];
        }

        return ['', ''];
    }
}
