<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Taxonomie Inclus/Exclus.
 *
 * Affiche les termes d'un champ ACF taxonomie au choix, avec un statut
 * inclus/exclus configurable au niveau du widget (pas par terme).
 * Chaque terme peut afficher son icône propre (taxomonies_icons / term_icon_class).
 */
class TaxonomyIncludes extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-taxonomy-includes',
            'title'    => 'BT — Taxonomie Inclus',
            'icon'     => 'eicon-bullet-list',
            'keywords' => ['taxonomie', 'inclus', 'exclu', 'liste', 'termes', 'bt'],
            'css'      => ['bt-included-excluded'], // réutilise le CSS existant
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

        $this->add_control('acf_field', [
            'label'       => __('Champ ACF taxonomie', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exp_included',
            'description' => __('Nom du champ ACF de type taxonomie sur le post (ex: exp_included, exp_to_excluded…).', 'blacktenderscore'),
        ]);

        $this->add_control('col_label', [
            'label'   => __('Titre du bloc', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Inclus', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Icône statut ──────────────────────────────────────────────────
        $this->start_controls_section('section_status', [
            'label' => __('Icône statut (inclus / exclus)', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_status_icon', [
            'label'        => __('Afficher l\'icône statut', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('status_icon_mode', [
            'label'     => __('Icône statut', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'included' => __('Inclus (✓)', 'blacktenderscore'),
                'excluded' => __('Exclus (✗)', 'blacktenderscore'),
            ],
            'default'   => 'included',
            'condition' => ['show_status_icon' => 'yes'],
        ]);

        $this->add_control('icon_included', [
            'label'     => __('Icône inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-check', 'library' => 'fa-solid'],
            'condition' => ['show_status_icon' => 'yes', 'status_icon_mode' => 'included'],
        ]);

        $this->add_control('icon_excluded', [
            'label'     => __('Icône exclus', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-times', 'library' => 'fa-solid'],
            'condition' => ['show_status_icon' => 'yes', 'status_icon_mode' => 'excluded'],
        ]);

        $this->end_controls_section();

        // ── Icône taxonomie ───────────────────────────────────────────────
        $this->start_controls_section('section_taxo_icon', [
            'label' => __('Icône taxonomie (par terme)', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_taxo_icon', [
            'label'        => __('Afficher l\'icône du terme', 'blacktenderscore'),
            'description'  => __('Lit le champ ACF taxomonies_icons (image) ou term_icon_class (classe FA) sur le terme.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('taxo_icon_fallback', [
            'label'     => __('Icône de remplacement', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_taxo_icon' => 'yes'],
        ]);

        $this->add_control('show_desc', [
            'label'        => __('Afficher la description du terme', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-inclexcl__section-title');

        // Style — Titre bloc
        $this->start_controls_section('style_col_title', [
            'label' => __('Style — Titre du bloc', 'blacktenderscore'),
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

        $this->add_control('status_icon_color', [
            'label'     => __('Couleur icône statut', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__icon--status'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__icon--status i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__icon--status svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_status_icon' => 'yes'],
        ]);

        $this->add_control('taxo_icon_color', [
            'label'     => __('Couleur icône terme', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__icon--taxo'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__icon--taxo i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__icon--taxo svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_taxo_icon' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icônes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-inclexcl__icon'     => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-inclexcl__icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
            ],
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

        $acf_field   = sanitize_text_field($s['acf_field'] ?: 'exp_included');
        $terms       = get_field($acf_field, $post_id);
        $col_label   = $s['col_label'] ?: __('Inclus', 'blacktenderscore');
        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';

        if (empty($terms)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(sprintf(__('Champ « %s » vide ou introuvable.', 'blacktenderscore'), $acf_field));
            }
            return;
        }

        // Icône statut active
        $status_icon = [];
        if ($s['show_status_icon'] === 'yes') {
            $status_icon = ($s['status_icon_mode'] === 'excluded')
                ? ($s['icon_excluded'] ?? [])
                : ($s['icon_included'] ?? []);
        }

        $col_modifier = ($s['status_icon_mode'] ?? 'included') === 'excluded'
            ? 'bt-inclexcl__col--excluded'
            : 'bt-inclexcl__col--included';

        echo '<div class="bt-inclexcl">';

        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-inclexcl__section-title');
        } else {
            $this->render_section_title($s, 'bt-inclexcl__section-title');
        }

        echo '<div class="bt-inclexcl__col ' . esc_attr($col_modifier) . '">';

        if ($col_label) {
            echo '<h5 class="bt-inclexcl__col-title">' . esc_html($col_label) . '</h5>';
        }

        echo '<ul class="bt-inclexcl__list">';

        foreach ((array) $terms as $term) {
            [$term_name, $term_desc, $term_obj] = $this->resolve_term($term);
            if (!$term_name) continue;

            echo '<li class="bt-inclexcl__item">';

            // Icône statut (même valeur pour tout le widget)
            if (!empty($status_icon['value'])) {
                echo '<span class="bt-inclexcl__icon bt-inclexcl__icon--status" aria-hidden="true">';
                \Elementor\Icons_Manager::render_icon($status_icon, ['aria-hidden' => 'true']);
                echo '</span>';
            }

            // Icône propre au terme (taxomonies_icons / term_icon_class)
            if ($s['show_taxo_icon'] === 'yes') {
                $this->render_taxo_icon($term_obj, $s['taxo_icon_fallback'] ?? []);
            }

            echo '<span class="bt-inclexcl__text">' . esc_html($term_name);
            if ($s['show_desc'] === 'yes' && $term_desc) {
                echo '<span class="bt-inclexcl__desc">' . esc_html($term_desc) . '</span>';
            }
            echo '</span>';

            echo '</li>';
        }

        echo '</ul>';
        echo '</div>'; // .bt-inclexcl__col

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }

        echo '</div>'; // .bt-inclexcl
    }

    /**
     * Affiche l'icône propre au terme : SVG/img (taxomonies_icons) > FA class (term_icon_class) > fallback Elementor.
     */
    private function render_taxo_icon(?\WP_Term $term_obj, array $fallback): void {
        $icon_url = '';
        $icon_fa  = '';

        if ($term_obj && function_exists('get_field')) {
            $icon_data = get_field('taxomonies_icons', $term_obj);
            if (is_array($icon_data))      $icon_url = $icon_data['url'] ?? '';
            elseif (is_string($icon_data)) $icon_url = $icon_data;

            if (!$icon_url) {
                $fa_raw = get_field('term_icon_class', $term_obj);
                if ($fa_raw && is_string($fa_raw)) $icon_fa = trim($fa_raw);
            }
        }

        if (!$icon_url && !$icon_fa && empty($fallback['value'])) return;

        echo '<span class="bt-inclexcl__icon bt-inclexcl__icon--taxo" aria-hidden="true">';

        if ($icon_url) {
            $ext = strtolower((string) pathinfo(wp_parse_url($icon_url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $svg = $this->fetch_svg_content($icon_url);
                echo $svg !== '' ? $this->kses_svg($svg) : '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            } else {
                echo '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            }
        } elseif ($icon_fa) {
            echo '<i class="' . esc_attr($icon_fa) . '"></i>';
        } elseif (!empty($fallback['value'])) {
            \Elementor\Icons_Manager::render_icon($fallback, ['aria-hidden' => 'true']);
        }

        echo '</span>';
    }

    // ── SVG helpers ───────────────────────────────────────────────────────────

    private function fetch_svg_content(string $url): string {
        $parsed_path = wp_parse_url($url, PHP_URL_PATH) ?: '';
        $path = ABSPATH . ltrim($parsed_path, '/');
        if (is_readable($path)) {
            $c = file_get_contents($path);
            return is_string($c) ? $c : '';
        }
        $r = wp_safe_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) return '';
        $body = wp_remote_retrieve_body($r);
        return is_string($body) ? $body : '';
    }

    private function kses_svg(string $html): string {
        $allowed = [
            'svg'      => ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true, 'aria-hidden' => true],
            'path'     => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true],
            'g'        => ['fill' => true, 'stroke' => true, 'class' => true, 'transform' => true],
            'circle'   => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'rect'     => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'line'     => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'class' => true],
            'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'polygon'  => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'ellipse'  => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'defs'     => [],
            'use'      => ['href' => true, 'xlink:href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true],
        ];
        return wp_kses($html, $allowed);
    }

    /**
     * Résout un terme ACF en [name, description, WP_Term|null].
     * Gère : WP_Term, term ID, array, plain string.
     *
     * @param mixed $term
     * @return array{0: string, 1: string, 2: \WP_Term|null}
     */
    private function resolve_term(mixed $term): array {
        if ($term instanceof \WP_Term) {
            return [$term->name, $term->description, $term];
        }
        if (is_numeric($term)) {
            $t = get_term((int) $term);
            if ($t && !is_wp_error($t)) return [$t->name, $t->description, $t];
            return ['', '', null];
        }
        if (is_array($term)) {
            if (isset($term['term_id'])) {
                $t = get_term((int) $term['term_id']);
                if ($t && !is_wp_error($t)) return [$t->name, $t->description, $t];
            }
            return [$term['name'] ?? $term['label'] ?? '', $term['description'] ?? '', null];
        }
        if (is_string($term) && $term !== '') {
            return [$term, '', null];
        }
        return ['', '', null];
    }
}
