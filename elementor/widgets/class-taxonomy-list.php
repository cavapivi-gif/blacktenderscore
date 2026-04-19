<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

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
            'css'      => ['bt-taxonomy-list'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Section Content ───────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        // Source : ACF taxonomy field OU taxonomie WP directe
        $this->add_control('source_type', [
            'label'   => __('Source', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'acf'      => __('Champ ACF (taxonomy)', 'blacktenderscore'),
                'taxonomy' => __('Taxonomie WordPress', 'blacktenderscore'),
            ],
            'default' => 'acf',
        ]);

        // Champs ACF de type "taxonomy" uniquement — fallback si ACF absent.
        $acf_opts = static::acf_taxonomy_field_options();
        $this->add_control('acf_field', [
            'label'     => __('Champ ACF (taxonomy)', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => $acf_opts,
            'default'   => array_key_first($acf_opts) ?: '',
            'condition' => ['source_type' => 'acf'],
        ]);

        // Taxonomie WP directe
        $this->add_control('taxonomy_slug', [
            'label'       => __('Taxonomie', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => $this->get_taxonomies_options(),
            'default'     => '',
            'condition'   => ['source_type' => 'taxonomy'],
            'label_block' => true,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'list'   => ['title' => __('Liste',   'blacktenderscore'), 'icon' => 'eicon-post-list'],
                'grid'   => ['title' => __('Grille',  'blacktenderscore'), 'icon' => 'eicon-gallery-grid'],
                'inline' => ['title' => __('Inline',  'blacktenderscore'), 'icon' => 'eicon-flex'],
            ],
            'default' => 'list',
            'toggle'  => false,
        ]);

        $this->add_control('show_icon', [
            'label'        => __("Afficher l'icône du terme", 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('description_position', [
            'label'     => __('Position de la description', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'inline' => __('En ligne (après le nom)', 'blacktenderscore'),
                'below'  => __('En dessous', 'blacktenderscore'),
            ],
            'default'   => 'below',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_control('link_to_archive', [
            'label'        => __('Lien vers l\'archive', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Chaque terme devient un lien vers sa page d\'archive.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('heading_prefix_suffix', [
            'label'     => __('Prefix / Suffix', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('term_prefix', [
            'label'       => __('Préfixe', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __('ex: « ', 'blacktenderscore'),
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('term_suffix', [
            'label'       => __('Suffixe', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __('ex:  »', 'blacktenderscore'),
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('inline_separator', [
            'label'       => __('Séparateur (inline)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => __('ex: · ou , ou |', 'blacktenderscore'),
            'condition'   => ['layout' => 'inline'],
        ]);

        $this->end_controls_section();

        // ── Style — Disposition ───────────────────────────────────────────
        $this->start_controls_section('style_layout', [
            'label' => __('Style — Disposition', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('item_gap', [
            'label'      => __('Espacement entre éléments', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 48]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'     => __('Colonnes (grille)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 6,
            'default'   => 2,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__list--grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition' => ['layout' => 'grid'],
        ]);

        $this->end_controls_section();

        // ── Style — Item ──────────────────────────────────────────────────
        $this->start_controls_section('style_item', [
            'label' => __('Style — Item', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Background::get_type(), [
            'name'     => 'item_background',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-taxlist__item',
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-taxlist__item',
        ]);

        $this->add_control('item_border_radius', [
            'label'      => __('Border Radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow',
            'selector' => '{{WRAPPER}} .bt-taxlist__item',
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-taxlist__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Texte ─────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'selector' => '{{WRAPPER}} .bt-taxlist__item-name',
        ]);

        $this->add_control('item_color', [
            'label'     => __('Couleur du nom', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__item-name' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'desc_typography',
            'label'     => __('Typographie description', 'blacktenderscore'),
            'selector'  => '{{WRAPPER}} .bt-taxlist__item-desc',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur description', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__item-desc' => 'color: {{VALUE}}'],
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Icône ─────────────────────────────────────────────────
        $this->start_controls_section('style_icon', [
            'label'     => __('Style — Icône', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 12, 'max' => 80]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-taxlist__item-icon'     => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxlist__item-icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
            ],
        ]);

        $this->add_control('icon_color', [
            'label'     => __('Couleur (FA / fallback)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-taxlist__item-icon i'        => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxlist__item-icon-fallback' => 'color: {{VALUE}}; opacity: 1',
            ],
        ]);

        $this->add_control('fallback_icon', [
            'label'   => __('Icône de remplacement (si aucune sur le terme)', 'blacktenderscore'),
            'type'    => Controls_Manager::ICONS,
            'default' => ['value' => '', 'library' => ''],
        ]);

        $this->end_controls_section();

        // ── Style — Prefix / Suffix ───────────────────────────────────────
        $this->start_controls_section('style_prefix_suffix', [
            'label' => __('Style — Prefix / Suffix', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('prefix_color', [
            'label'     => __('Couleur préfixe', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__prefix' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('suffix_color', [
            'label'     => __('Couleur suffixe', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__suffix' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('separator_color', [
            'label'     => __('Couleur séparateur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__sep' => 'color: {{VALUE}}'],
            'condition' => ['layout' => 'inline'],
        ]);

        $this->end_controls_section();

        // ── Style — Lien ──────────────────────────────────────────────────
        $this->start_controls_section('style_link', [
            'label'     => __('Style — Lien', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['link_to_archive' => 'yes'],
        ]);

        $this->add_control('link_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__link' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('link_hover_color', [
            'label'     => __('Couleur hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxlist__link:hover' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('link_decoration', [
            'label'   => __('Décoration', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'none'      => __('Aucune', 'blacktenderscore'),
                'underline' => __('Souligné', 'blacktenderscore'),
            ],
            'default'   => 'none',
            'selectors' => ['{{WRAPPER}} .bt-taxlist__link' => 'text-decoration: {{VALUE}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        $source_type = $s['source_type'] ?? 'acf';
        $terms       = ($source_type === 'taxonomy')
            ? $this->resolve_taxonomy_terms($s['taxonomy_slug'] ?? '', $post_id)
            : $this->resolve_acf_terms($s['acf_field'] ?? '', $post_id);

        if (empty($terms)) {
            if ($this->is_edit_mode()) {
                $source_label = ($source_type === 'taxonomy')
                    ? ($s['taxonomy_slug'] ?? '')
                    : ($s['acf_field'] ?? '');
                echo '<p class="bt-widget-placeholder">Aucun terme trouvé pour « ' . esc_html($source_label) . ' ».</p>';
            }
            return;
        }

        $layout        = $s['layout'] ?: 'list';
        $show_icon     = $s['show_icon'] === 'yes';
        $show_desc     = $s['show_description'] === 'yes';
        $desc_pos      = $s['description_position'] ?: 'below';
        $fallback_icon = $s['fallback_icon'] ?? [];
        $link_archive  = ($s['link_to_archive'] ?? '') === 'yes';
        $prefix        = $s['term_prefix'] ?? '';
        $suffix        = $s['term_suffix'] ?? '';
        $inline_sep    = $s['inline_separator'] ?? '';

        echo '<div class="bt-taxlist">';

        $list_class = 'bt-taxlist__list bt-taxlist__list--' . esc_attr($layout);
        echo "<ul class=\"{$list_class}\">";

        $index = 0;
        foreach ($terms as $term) {
            if (!($term instanceof \WP_Term)) continue;

            // Séparateur inline (entre les items, pas avant le premier)
            if ($layout === 'inline' && $index > 0 && $inline_sep !== '') {
                echo '<span class="bt-taxlist__sep" aria-hidden="true">' . esc_html($inline_sep) . '</span>';
            }

            $icon_url  = '';
            $icon_fa   = '';
            if ($show_icon && function_exists('get_field')) {
                $icon_data = get_field('taxomonies_icons', $term);
                if (is_array($icon_data))       $icon_url = $icon_data['url'] ?? '';
                elseif (is_string($icon_data))  $icon_url = $icon_data;

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
                    \Elementor\Icons_Manager::render_icon($fallback_icon, ['aria-hidden' => 'true']);
                } else {
                    echo '<span class="bt-taxlist__item-icon-fallback" aria-hidden="true">✓</span>';
                }
                echo '</span>';
            }

            echo '<span class="bt-taxlist__item-text">';

            // Lien vers l'archive du terme
            $archive_url = $link_archive ? get_term_link($term) : '';
            $has_link    = $link_archive && is_string($archive_url) && $archive_url !== '';

            // Nom avec prefix/suffix
            $name_html = '';
            if ($prefix !== '') {
                $name_html .= '<span class="bt-taxlist__prefix">' . esc_html($prefix) . '</span>';
            }
            $name_html .= esc_html($term->name);
            if ($suffix !== '') {
                $name_html .= '<span class="bt-taxlist__suffix">' . esc_html($suffix) . '</span>';
            }

            if ($has_link) {
                $name_html = '<a href="' . esc_url($archive_url) . '" class="bt-taxlist__link">' . $name_html . '</a>';
            }

            if ($desc_pos === 'inline' && $show_desc && !empty($term->description)) {
                echo '<span class="bt-taxlist__item-name">' . $name_html . '</span>';
                echo ' <span class="bt-taxlist__item-desc bt-taxlist__item-desc--inline">' . esc_html($term->description) . '</span>';
            } else {
                echo '<span class="bt-taxlist__item-name">' . $name_html . '</span>';
                if ($show_desc && !empty($term->description)) {
                    echo '<span class="bt-taxlist__item-desc bt-taxlist__item-desc--below">' . esc_html($term->description) . '</span>';
                }
            }

            echo '</span></li>';
            $index++;
        }

        echo '</ul></div>';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

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

    /**
     * Retourne les champs ACF de type "taxonomy" enregistrés sur le site.
     * Fallback sur la liste statique si ACF n'est pas disponible.
     */
    private static function acf_taxonomy_field_options(): array {
        if (!function_exists('acf_get_field_groups')) {
            return [
                'exp_included'            => 'exp_included',
                'exp_to_excluded'         => 'exp_to_excluded',
                'exp_to_bring'            => 'exp_to_bring',
                'boat_equipment_included' => 'boat_equipment_included',
                'boat_services_included'  => 'boat_services_included',
                'boat_option_on_demand'   => 'boat_option_on_demand',
            ];
        }

        $options = [];
        foreach (acf_get_field_groups() as $group) {
            foreach (acf_get_fields($group['key']) ?: [] as $field) {
                if ($field['type'] !== 'taxonomy') continue;
                $options[$field['name']] = sprintf('%s (%s)', $field['label'], $field['name']);
            }
        }

        return $options ?: ['exp_included' => 'exp_included'];
    }

    /**
     * Récupère les termes depuis une taxonomie WP directe (get_the_terms).
     */
    private function resolve_taxonomy_terms(string $taxonomy, int $post_id): array {
        if ($taxonomy === '') return [];
        $terms = get_the_terms($post_id, $taxonomy);
        return is_array($terms) ? $terms : [];
    }

    /**
     * Récupère les termes depuis un champ ACF taxonomy.
     */
    private function resolve_acf_terms(string $field, int $post_id): array {
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
