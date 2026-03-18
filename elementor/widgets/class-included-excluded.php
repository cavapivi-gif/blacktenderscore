<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

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
            'css'      => ['bt-included-excluded'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();
        $this->register_collapsible_section_control();

        $this->add_control('show_included', [
            'label'        => __('Afficher colonne Inclus', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('included_field', [
            'label'     => __('Champ inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'exp_included'             => __('Inclus (exp_included)', 'blacktenderscore'),
                'boat_equipment_included'  => __('Équipements bateau (boat_equipment_included)', 'blacktenderscore'),
                'boat_services_included'   => __('Services bateau (boat_services_included)', 'blacktenderscore'),
            ],
            'default'   => 'exp_included',
            'condition' => ['show_included' => 'yes'],
        ]);

        $this->add_control('included_label', [
            'label'     => __('Titre colonne inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Inclus',
            'condition' => ['show_included' => 'yes'],
        ]);

        $this->add_control('included_icon', [
            'label'     => __('Icône inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '✓',
            'condition' => ['show_included' => 'yes', 'show_icons' => 'yes'],
        ]);

        $this->add_control('show_excluded', [
            'label'        => __('Afficher colonne Exclus', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('excluded_field', [
            'label'     => __('Champ exclus', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'exp_to_excluded'       => __('Exclus (exp_to_excluded)', 'blacktenderscore'),
                'boat_option_on_demand' => __('Options sur demande (boat_option_on_demand)', 'blacktenderscore'),
            ],
            'default'   => 'exp_to_excluded',
            'condition' => ['show_excluded' => 'yes'],
        ]);

        $this->add_control('excluded_label', [
            'label'     => __('Titre colonne exclus', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Non inclus',
            'condition' => ['show_excluded' => 'yes'],
        ]);

        $this->add_control('excluded_icon', [
            'label'     => __('Icône exclus', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '✗',
            'condition' => ['show_excluded' => 'yes', 'show_icons' => 'yes'],
        ]);

        $this->add_control('show_desc', [
            'label'        => __('Afficher description des termes', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('show_icons', [
            'label'        => __('Afficher les icônes ✓/✗', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_taxonomy_icons', [
            'label'        => __('Inclure l\'icône de la taxonomie', 'blacktenderscore'),
            'description'  => __('Affiche l\'icône du terme (taxomonies_icons) à la place de ✓/✗ si disponible.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_icons' => 'yes'],
        ]);

        $this->add_control('fallback_icon_included', [
            'label'     => __('Icône de remplacement (inclus)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icons' => 'yes', 'show_taxonomy_icons' => 'yes'],
        ]);

        $this->add_control('fallback_icon_excluded', [
            'label'     => __('Icône de remplacement (exclus)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icons' => 'yes', 'show_taxonomy_icons' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-inclexcl__section-title');

        // Style — Colonnes
        $this->start_controls_section('style_cols', [
            'label' => __('Style — Colonnes', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('cols_gap', [
            'label'      => __('Espacement colonnes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 32, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // Style — Titre colonne
        $this->start_controls_section('style_col_title', [
            'label' => __('Style — Titre colonne', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'col_title_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-inclexcl__col-title',
        ]);

        $this->add_control('col_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('included_col_title_color', [
            'label'     => __('Couleur titre "Inclus"', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('excluded_col_title_color', [
            'label'     => __('Couleur titre "Exclus"', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // Style — Items
        $this->start_controls_section('style_items', [
            'label' => __('Style — Items', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-inclexcl__text',
        ]);

        $this->add_control('item_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__text' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('included_icon_color', [
            'label'     => __('Couleur icône inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_icons' => 'yes'],
        ]);

        $this->add_control('excluded_icon_color', [
            'label'     => __('Couleur icône exclus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_icons' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-inclexcl__icon'     => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-inclexcl__icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
            ],
            'condition'  => ['show_icons' => 'yes'],
        ]);

        $this->add_responsive_control('items_gap', [
            'label'      => __('Espacement items', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
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

        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';

        echo '<div class="bt-inclexcl">';

        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-inclexcl__section-title');
        } else {
            $this->render_section_title($s, 'bt-inclexcl__section-title');
        }

        echo '<div class="bt-inclexcl__grid">';

        $use_tax_icons    = ($s['show_icons'] === 'yes' && !empty($s['show_taxonomy_icons']) && $s['show_taxonomy_icons'] === 'yes');
        $fallback_inc     = $s['fallback_icon_included'] ?? [];
        $fallback_exc     = $s['fallback_icon_excluded'] ?? [];

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
                    [$term_name, $term_desc, $term_obj] = $this->resolve_term($term);
                    if (!$term_name) continue;

                    echo '<li class="bt-inclexcl__item">';
                    if ($s['show_icons'] === 'yes') {
                        $this->render_item_icon($term_obj, $use_tax_icons, $included_icon, $fallback_inc);
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
                    [$term_name, $term_desc, $term_obj] = $this->resolve_term($term);
                    if (!$term_name) continue;

                    echo '<li class="bt-inclexcl__item">';
                    if ($s['show_icons'] === 'yes') {
                        $this->render_item_icon($term_obj, $use_tax_icons, $excluded_icon, $fallback_exc);
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

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }

        echo '</div>'; // .bt-inclexcl
    }

    /**
     * Affiche l'icône d'un item : icône taxonomie (SVG inline / img) > FA class > fallback Elementor > caractère ✓/✗.
     */
    private function render_item_icon(?\WP_Term $term_obj, bool $use_tax_icons, string $char_icon, array $fallback_icon): void {
        $icon_url = '';
        $icon_fa  = '';

        // Tenter de récupérer l'icône de la taxonomie
        if ($use_tax_icons && $term_obj && function_exists('get_field')) {
            $icon_data = get_field('taxomonies_icons', $term_obj);
            if (is_array($icon_data))      $icon_url = $icon_data['url'] ?? '';
            elseif (is_string($icon_data)) $icon_url = $icon_data;

            if (!$icon_url) {
                $fa_raw = get_field('term_icon_class', $term_obj);
                if ($fa_raw && is_string($fa_raw)) $icon_fa = trim($fa_raw);
            }
        }

        echo '<span class="bt-inclexcl__icon" aria-hidden="true">';

        if ($icon_url) {
            // SVG → injection inline pour pouvoir piloter fill/color via CSS
            $ext = strtolower((string) pathinfo(wp_parse_url($icon_url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $svg = $this->fetch_svg_content($icon_url);
                echo $svg !== '' ? $this->kses_svg($svg) : '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            } else {
                echo '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            }
        } elseif ($icon_fa) {
            echo '<i class="' . esc_attr($icon_fa) . '"></i>';
        } elseif ($use_tax_icons && !empty($fallback_icon['value'])) {
            \Elementor\Icons_Manager::render_icon($fallback_icon, ['aria-hidden' => 'true']);
        } else {
            echo esc_html($char_icon);
        }

        echo '</span>';
    }

    // ── SVG helpers ──────────────────────────────────────────────────────────

    private function fetch_svg_content(string $url): string {
        $parsed_path = wp_parse_url($url, PHP_URL_PATH) ?: '';
        $path = ABSPATH . ltrim($parsed_path, '/');
        if (is_readable($path)) {
            $c = file_get_contents($path);
            return is_string($c) ? $c : '';
        }
        $r = wp_safe_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) {
            return '';
        }
        $body = wp_remote_retrieve_body($r);
        return is_string($body) ? $body : '';
    }

    private function kses_svg(string $html): string {
        $allowed = [
            'svg'    => ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true, 'aria-hidden' => true],
            'path'   => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true],
            'g'      => ['fill' => true, 'stroke' => true, 'class' => true, 'transform' => true],
            'circle' => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'rect'   => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'line'   => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'class' => true],
            'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'polygon'  => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'ellipse'  => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'defs'   => [],
            'use'    => ['href' => true, 'xlink:href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true],
        ];
        return wp_kses($html, $allowed);
    }

    /**
     * Résout un terme ACF en [name, description, WP_Term|null].
     * Gère : WP_Term, term ID (int/numeric string), array, plain string.
     *
     * @param mixed $term
     * @return array{0: string, 1: string, 2: \WP_Term|null} [name, description, term_object]
     */
    private function resolve_term(mixed $term): array {
        // WP_Term object (return format "Term Object")
        if ($term instanceof \WP_Term) {
            return [$term->name, $term->description, $term];
        }

        // Term ID (return format "Term ID") — int or numeric string
        if (is_numeric($term)) {
            $t = get_term((int) $term);
            if ($t && !is_wp_error($t)) {
                return [$t->name, $t->description, $t];
            }
            return ['', '', null];
        }

        // Array (repeater sub-row or term-like array)
        if (is_array($term)) {
            // Could be a term array with 'term_id'
            if (isset($term['term_id'])) {
                $t = get_term((int) $term['term_id']);
                if ($t && !is_wp_error($t)) {
                    return [$t->name, $t->description, $t];
                }
            }
            return [
                $term['name'] ?? $term['label'] ?? '',
                $term['description'] ?? '',
                null,
            ];
        }

        // Plain string
        if (is_string($term) && $term !== '') {
            return [$term, '', null];
        }

        return ['', '', null];
    }
}
