<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Repeater;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Affichage Taxonomie v2.
 *
 * Multi-sources : taxonomies ET/OU champs ACF.
 * 4 templates visuels : pills, tags, grid-cards, inline.
 * Dédoublonnage automatique par term_id.
 */
class TaxonomyDisplay extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-taxonomy-display',
            'title'    => 'BT — Affichage Taxonomie',
            'icon'     => 'eicon-tags',
            'keywords' => ['taxonomie', 'terme', 'acf', 'champ', 'bt', 'pills', 'tags'],
            'css'      => ['bt-taxonomy-display'],
        ];
    }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ══════════════════════════════════════════════════════════════════════
        // TAB CONTENT
        // ══════════════════════════════════════════════════════════════════════

        $this->start_controls_section('section_sources', [
            'label' => __('Sources', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        // ── Repeater sources ───────────────────────────────────────────────────
        $repeater = new Repeater();

        $repeater->add_control('source_type', [
            'label'   => __('Type', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'taxonomy' => __('Taxonomie', 'blacktenderscore'),
                'acf'      => __('Champ ACF', 'blacktenderscore'),
            ],
            'default' => 'taxonomy',
        ]);

        $repeater->add_control('taxonomy_slug', [
            'label'       => __('Taxonomie', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => $this->get_taxonomies_options(),
            'default'     => '',
            'condition'   => ['source_type' => 'taxonomy'],
            'label_block' => true,
        ]);

        $repeater->add_control('acf_field', [
            'label'       => __('Champ ACF', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => static::acf_field_options('', ['exp_included' => 'exp_included', 'exp_to_excluded' => 'exp_to_excluded']),
            'default'     => '',
            'condition'   => ['source_type' => 'acf'],
            'label_block' => true,
        ]);

        $this->add_control('sources', [
            'label'       => __('Sources de données', 'blacktenderscore'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [
                ['source_type' => 'taxonomy', 'taxonomy_slug' => '', 'acf_field' => ''],
            ],
            'title_field' => '{{{ source_type === "taxonomy" ? "Taxo: " + taxonomy_slug : "ACF: " + acf_field }}}',
        ]);

        $this->end_controls_section();

        // ── Template & Affichage ───────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('template', [
            'label'   => __('Template', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'pills'      => __('A — Pills flat', 'blacktenderscore'),
                'tags'       => __('B — Tags accent', 'blacktenderscore'),
                'grid-cards' => __('C — Grid cards', 'blacktenderscore'),
                'inline'     => __('D — Inline', 'blacktenderscore'),
            ],
            'default' => 'pills',
        ]);

        // ── Contrôles Grid ──
        $this->add_responsive_control('grid_columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px'],
            'range'          => ['px' => ['min' => 2, 'max' => 8, 'step' => 1]],
            'default'        => ['size' => 4, 'unit' => 'px'],
            'tablet_default' => ['size' => 3, 'unit' => 'px'],
            'mobile_default' => ['size' => 2, 'unit' => 'px'],
            'selectors'      => [
                '{{WRAPPER}} .bt-taxdisp--grid-cards' => 'grid-template-columns: repeat({{SIZE}}, 1fr)',
            ],
            'condition' => ['template' => 'grid-cards'],
        ]);

        // ── Contrôles Inline ──
        $this->add_control('inline_separator', [
            'label'       => __('Séparateur', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '·',
            'placeholder' => '·',
            'condition'   => ['template' => 'inline'],
        ]);

        // ── Max à afficher ──
        $this->add_control('max_items', [
            'label'       => __('Max à afficher', 'blacktenderscore'),
            'type'        => Controls_Manager::NUMBER,
            'default'     => 0,
            'min'         => 0,
            'max'         => 50,
            'description' => __('0 = illimité', 'blacktenderscore'),
        ]);

        $this->add_control('show_icon', [
            'label'        => __('Afficher l\'icône', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_label', [
            'label'        => __('Afficher le nom', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('fallback_icon', [
            'label'     => __('Icône de remplacement', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Section title (optionnel) ──────────────────────────────────────────
        $this->start_controls_section('section_header', [
            'label' => __('En-tête (optionnel)', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();

        $this->add_control('section_description', [
            'label'   => __('Description de section', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'rows'    => 2,
            'default' => '',
            'dynamic' => ['active' => true],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════════
        // TAB STYLE
        // ══════════════════════════════════════════════════════════════════════

        // ── Style — Section title ─────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-taxdisp__section-title');

        // ── Style — Items ─────────────────────────────────────────────────────
        $this->start_controls_section('style_item', [
            'label' => __('Style — Items', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        // ── Espacement X/Y ──
        $this->add_responsive_control('item_gap_x', [
            'label'      => __('Espacement horizontal (X)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'default'    => ['size' => 10, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-taxdisp--pills'      => 'column-gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp--tags'       => 'column-gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp--grid-cards' => 'column-gap: {{SIZE}}{{UNIT}}',
            ],
            'condition' => ['template!' => 'inline'],
        ]);

        $this->add_responsive_control('item_gap_y', [
            'label'      => __('Espacement vertical (Y)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'default'    => ['size' => 10, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-taxdisp--pills'      => 'row-gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp--tags'       => 'row-gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp--grid-cards' => 'row-gap: {{SIZE}}{{UNIT}}',
            ],
            'condition' => ['template!' => 'inline'],
        ]);

        $this->add_control('heading_item_box', [
            'label'     => __('Box', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        // ── Background ──
        $this->add_control('item_bg_color', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxdisp__item' => 'background-color: {{VALUE}}'],
        ]);

        // ── Border (full control) ──
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-taxdisp__item',
        ]);

        // ── Border radius ──
        $this->add_responsive_control('item_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // ── Padding ──
        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // ── Box Shadow ──
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow',
            'selector' => '{{WRAPPER}} .bt-taxdisp__item',
        ]);

        // ── Typographie ──
        $this->add_control('heading_item_typo', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'selector' => '{{WRAPPER}} .bt-taxdisp__label',
        ]);

        $this->add_control('item_text_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-taxdisp__item'  => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxdisp__label' => 'color: {{VALUE}}',
            ],
        ]);

        // ── Description ──
        $this->add_control('heading_item_desc', [
            'label'     => __('Description', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'item_desc_typography',
            'selector'  => '{{WRAPPER}} .bt-taxdisp__desc',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_control('item_desc_color', [
            'label'     => __('Couleur description', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxdisp__desc' => 'color: {{VALUE}}'],
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Inline (séparateur + gap) ─────────────────────────────────
        $this->start_controls_section('style_inline', [
            'label'     => __('Style — Inline', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['template' => 'inline'],
        ]);

        $this->add_control('heading_inline_gap', [
            'label' => __('Espacement', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_responsive_control('inline_gap_x', [
            'label'      => __('Gap horizontal (X)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__list.bt-taxdisp--inline' => 'column-gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('inline_gap_y', [
            'label'      => __('Gap vertical (Y)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__list.bt-taxdisp--inline' => 'row-gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('heading_inline_sep', [
            'label'     => __('Séparateur', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('inline_sep_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxdisp--inline .bt-taxdisp__sep' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('inline_sep_spacing', [
            'label'      => __('Marge horizontale', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 24]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp--inline .bt-taxdisp__sep' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'inline_sep_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-taxdisp--inline .bt-taxdisp__sep',
        ]);

        $this->end_controls_section();

        // ── Style — Icônes ────────────────────────────────────────────────────
        $this->start_controls_section('style_icon', [
            'label'     => __('Style — Icônes', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 40]],
            'selectors'  => [
                '{{WRAPPER}} .bt-taxdisp__icon'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp__icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp__icon i'   => 'font-size: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('icon_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-taxdisp__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxdisp__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxdisp__icon svg' => 'fill: {{VALUE}}',
            ],
        ]);

        $this->end_controls_section();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    protected function render(): void {

        $s        = $this->get_settings_for_display();
        $sources  = $s['sources'] ?? [];
        $template = $s['template'] ?? 'pills';
        $show_icon  = ($s['show_icon'] ?? 'yes') === 'yes';
        $show_label = ($s['show_label'] ?? 'yes') === 'yes';
        $show_desc  = ($s['show_description'] ?? '') === 'yes';
        $fallback   = $s['fallback_icon'] ?? [];
        $max_items  = (int) ($s['max_items'] ?? 0);
        $inline_sep = $s['inline_separator'] ?? '·';

        if (empty($sources)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Ajoutez au moins une source.', 'blacktenderscore'));
            }
            return;
        }

        $post_id = (int) get_the_ID();

        // Collecter tous les items depuis toutes les sources
        $all_items = [];
        $seen_ids  = [];

        foreach ($sources as $src_index => $source) {
            $type = $source['source_type'] ?? 'taxonomy';
            $items_from_source = [];

            if ($type === 'taxonomy') {
                $tax_slug = $source['taxonomy_slug'] ?? '';
                if ($tax_slug !== '') {
                    $terms = get_the_terms($post_id, $tax_slug);
                    if (is_array($terms)) {
                        foreach ($terms as $term) {
                            $items_from_source[] = $this->term_to_item($term);
                        }
                    }
                }
            } else {
                // ACF field
                $acf_key = $source['acf_field'] ?? '';
                if ($acf_key !== '' && function_exists('get_field')) {
                    $raw = get_field($acf_key, $post_id);
                    if (!empty($raw)) {
                        $items_from_source = $this->resolve_acf_to_items($raw);
                    }
                }
            }

            // Dédoublonner par term_id (ou hash pour les non-termes)
            foreach ($items_from_source as $item) {
                $uid = $item['term_id'] ?? md5($item['name']);
                if (!isset($seen_ids[$uid])) {
                    $seen_ids[$uid] = true;
                    $item['_source'] = $src_index;
                    $all_items[] = $item;
                }
            }
        }

        if (empty($all_items)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucun terme trouvé pour ce post.', 'blacktenderscore'));
            }
            return;
        }

        // ── Output ─────────────────────────────────────────────────────────────
        echo '<div class="bt-taxdisp">';

        // Section title
        $this->render_section_title($s, 'bt-taxdisp__section-title');
        $this->render_section_description($s);

        // Appliquer le max_items
        if ($max_items > 0) {
            $all_items = array_slice($all_items, 0, $max_items);
        }

        // Template wrapper
        echo '<div class="bt-taxdisp__list bt-taxdisp--' . esc_attr($template) . '">';

        $item_index = 0;
        $total_items = count($all_items);

        foreach ($all_items as $item) {
            // Séparateur inline (entre les items, pas avant le premier)
            if ($template === 'inline' && $item_index > 0 && $inline_sep !== '') {
                echo '<span class="bt-taxdisp__sep" aria-hidden="true">' . esc_html($inline_sep) . '</span>';
            }

            echo '<span class="bt-taxdisp__item">';

            // Icône
            if ($show_icon) {
                $this->render_item_icon($item, $fallback);
            }

            // Body (label + desc)
            echo '<span class="bt-taxdisp__body">';
            if ($show_label && $item['name'] !== '') {
                echo '<span class="bt-taxdisp__label">' . esc_html($item['name']) . '</span>';
            }
            if ($show_desc && $item['desc'] !== '') {
                echo '<span class="bt-taxdisp__desc">' . esc_html($item['desc']) . '</span>';
            }
            echo '</span>';

            echo '</span>'; // .bt-taxdisp__item

            $item_index++;
        }

        echo '</div>'; // .bt-taxdisp__list
        echo '</div>'; // .bt-taxdisp
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Convertit un WP_Term en array item standardisé.
     */
    private function term_to_item(\WP_Term $term): array {
        return [
            'term_id'  => $term->term_id,
            'name'     => $term->name,
            'desc'     => $term->description,
            'term'     => $term,
            'icon_url' => $this->get_term_icon_url($term),
            'icon_fa'  => $this->get_term_icon_class($term),
        ];
    }

    /**
     * Résout une valeur ACF en liste d'items.
     */
    private function resolve_acf_to_items(mixed $raw): array {
        if (!is_array($raw)) $raw = [$raw];
        if (isset($raw['term_id'])) $raw = [$raw];

        $items = [];
        foreach ($raw as $r) {
            $term = $this->resolve_term($r);
            if ($term) {
                $items[] = $this->term_to_item($term);
            } elseif (is_scalar($r)) {
                $items[] = [
                    'term_id'  => null,
                    'name'     => (string) $r,
                    'desc'     => '',
                    'term'     => null,
                    'icon_url' => '',
                    'icon_fa'  => '',
                ];
            } elseif (is_array($r)) {
                $items[] = [
                    'term_id'  => null,
                    'name'     => $r['name'] ?? $r['label'] ?? '',
                    'desc'     => $r['description'] ?? '',
                    'term'     => null,
                    'icon_url' => $r['icon'] ?? '',
                    'icon_fa'  => '',
                ];
            }
        }
        return $items;
    }

    private function resolve_term(mixed $item): ?\WP_Term {
        if ($item instanceof \WP_Term) return $item;
        if (is_array($item) && isset($item['term_id'])) {
            $t = get_term((int) $item['term_id']);
            return $t instanceof \WP_Term ? $t : null;
        }
        if (is_numeric($item)) {
            $t = get_term((int) $item);
            return $t instanceof \WP_Term ? $t : null;
        }
        return null;
    }

    private function get_term_icon_url(?\WP_Term $term): string {
        if (!$term || !function_exists('get_field')) return '';
        $data = get_field('taxomonies_icons', $term);
        if (is_array($data)) return $data['url'] ?? '';
        if (is_string($data)) return $data;
        return '';
    }

    private function get_term_icon_class(?\WP_Term $term): string {
        if (!$term || !function_exists('get_field')) return '';
        $fa = get_field('term_icon_class', $term);
        return is_string($fa) ? trim($fa) : '';
    }

    /**
     * Affiche l'icône d'un item.
     */
    private function render_item_icon(array $item, array $fallback): void {
        $icon_url = $item['icon_url'] ?? '';
        $icon_fa  = $item['icon_fa'] ?? '';

        if (!$icon_url && !$icon_fa && empty($fallback['value'])) return;

        echo '<span class="bt-taxdisp__icon" aria-hidden="true">';

        if ($icon_url) {
            $ext = strtolower(pathinfo(wp_parse_url($icon_url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
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

    private function render_section_description(array $s): void {
        $desc = trim((string) ($s['section_description'] ?? ''));
        if ($desc === '') return;
        echo '<p class="bt-taxdisp__section-desc">' . esc_html($desc) . '</p>';
    }

    /**
     * Retourne les taxonomies publiques pour le SELECT.
     */
    private function get_taxonomies_options(): array {
        $taxonomies = get_taxonomies(['public' => true], 'objects');
        $opts = ['' => __('— Choisir —', 'blacktenderscore')];
        foreach ($taxonomies as $tax) {
            $opts[$tax->name] = $tax->label . ' (' . $tax->name . ')';
        }
        return $opts;
    }

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
        return wp_kses($html, [
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
        ]);
    }
}
