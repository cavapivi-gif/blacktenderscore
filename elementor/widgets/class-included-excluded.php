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
 * Widget Elementor — Inclus / Exclus.
 *
 * Affiche jusqu'à trois colonnes (inclus / exclus / options) à partir de
 * sources multiples : taxonomies ET/OU champs ACF via Repeater.
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

        $this->end_controls_section();

        // ── Layout ───────────────────────────────────────────────────────
        $this->start_controls_section('section_layout', [
            'label' => __('Disposition', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'columns' => __('Colonnes côte à côte', 'blacktenderscore'),
                'mixed'   => __('Liste mixte (tout en une liste)', 'blacktenderscore'),
            ],
            'default' => 'columns',
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'     => __('Colonnes par ligne', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
            'default'   => '2',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)',
            ],
            'condition' => ['layout' => 'columns'],
        ]);

        $this->add_responsive_control('list_columns', [
            'label'     => __('Items par ligne', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
            'default'   => '1',
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__list' => 'display: grid; grid-template-columns: repeat({{VALUE}}, 1fr)',
            ],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // SOURCE 1 — INCLUS (Repeater multi-sources)
        // ══════════════════════════════════════════════════════════════════
        $this->start_controls_section('section_included', [
            'label' => __('Sources — Inclus', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_included', [
            'label'        => __('Activer', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('included_sources', [
            'label'       => __('Sources de données', 'blacktenderscore'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $this->get_source_repeater_fields(),
            'default'     => [],
            'title_field' => '{{{ source_type === "taxonomy" ? "Taxo: " + taxonomy_slug : "ACF: " + acf_field }}}',
            'condition'   => ['show_included' => 'yes'],
        ]);

        $this->add_control('included_label', [
            'label'     => __('Titre colonne', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Inclus',
            'condition' => ['show_included' => 'yes'],
        ]);

        $this->add_control('included_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '✓',
            'condition' => ['show_included' => 'yes', 'show_icons' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // SOURCE 2 — EXCLUS (Repeater multi-sources)
        // ══════════════════════════════════════════════════════════════════
        $this->start_controls_section('section_excluded', [
            'label' => __('Sources — Exclus', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_excluded', [
            'label'        => __('Activer', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('excluded_sources', [
            'label'       => __('Sources de données', 'blacktenderscore'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $this->get_source_repeater_fields(),
            'default'     => [],
            'title_field' => '{{{ source_type === "taxonomy" ? "Taxo: " + taxonomy_slug : "ACF: " + acf_field }}}',
            'condition'   => ['show_excluded' => 'yes'],
        ]);

        $this->add_control('excluded_label', [
            'label'     => __('Titre colonne', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Non inclus',
            'condition' => ['show_excluded' => 'yes'],
        ]);

        $this->add_control('excluded_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '✗',
            'condition' => ['show_excluded' => 'yes', 'show_icons' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // SOURCE 3 — OPTIONS SUR DEMANDE (Repeater multi-sources)
        // ══════════════════════════════════════════════════════════════════
        $this->start_controls_section('section_optional', [
            'label' => __('Sources — Options sur demande', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_optional', [
            'label'        => __('Activer', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('optional_sources', [
            'label'       => __('Sources de données', 'blacktenderscore'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $this->get_source_repeater_fields(),
            'default'     => [],
            'title_field' => '{{{ source_type === "taxonomy" ? "Taxo: " + taxonomy_slug : "ACF: " + acf_field }}}',
            'condition'   => ['show_optional' => 'yes'],
        ]);

        $this->add_control('optional_label', [
            'label'     => __('Titre colonne', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Options sur demande',
            'condition' => ['show_optional' => 'yes'],
        ]);

        $this->add_control('optional_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '◎',
            'condition' => ['show_optional' => 'yes', 'show_icons' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // OPTIONS GÉNÉRALES
        // ══════════════════════════════════════════════════════════════════
        $this->start_controls_section('section_options', [
            'label' => __('Options', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_desc', [
            'label'        => __('Afficher description des termes', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('show_icons', [
            'label'        => __('Afficher les icônes', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_responsive_control('icon_position', [
            'label'     => __('Position icône', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'row'         => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'row-reverse' => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
                'column'      => ['title' => __('Au-dessus', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'row',
            'condition' => ['show_icons' => 'yes'],
            'selectors_dictionary' => [
                'row'         => 'flex-direction: row; --bt-ie-text-align: left;',
                'row-reverse' => 'flex-direction: row-reverse; --bt-ie-text-align: right;',
                'column'      => 'flex-direction: column; --bt-ie-text-align: center;',
            ],
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__item' => '{{VALUE}}'],
        ]);

        $this->add_control('icon_valign', [
            'label'     => __('Alignement vertical icône', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('Haut', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'flex-end'   => ['title' => __('Bas', 'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'   => 'center',
            'condition' => ['show_icons' => 'yes', 'icon_position' => ['row', 'row-reverse']],
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__item' => 'align-items: {{VALUE}}'],
        ]);

        $this->add_control('show_taxonomy_icons', [
            'label'        => __('Utiliser l\'icône du terme', 'blacktenderscore'),
            'description'  => __('Affiche l\'icône ACF du terme si disponible.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_icons' => 'yes'],
        ]);

        $this->add_control('fallback_icon_included', [
            'label'     => __('Icône fallback (inclus)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icons' => 'yes', 'show_taxonomy_icons' => 'yes'],
        ]);

        $this->add_control('fallback_icon_excluded', [
            'label'     => __('Icône fallback (exclus)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icons' => 'yes', 'show_taxonomy_icons' => 'yes'],
        ]);

        $this->add_control('fallback_icon_optional', [
            'label'     => __('Icône fallback (options)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_icons' => 'yes', 'show_taxonomy_icons' => 'yes', 'show_optional' => 'yes'],
        ]);

        $this->add_control('separator_tags', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->add_control('column_title_tag', [
            'label'   => __('Balise titre colonne', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'h1'   => 'H1',
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'h6'   => 'H6',
                'p'    => 'p',
                'span' => 'span',
                'div'  => 'div',
            ],
            'default' => 'h5',
        ]);

        $this->add_control('item_title_tag', [
            'label'   => __('Balise texte items', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'h1'   => 'H1',
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'h6'   => 'H6',
                'p'    => 'p',
                'span' => 'span',
                'div'  => 'div',
            ],
            'default' => 'span',
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

        $this->add_control('optional_col_title_color', [
            'label'     => __('Couleur titre "Options"', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__col--optional .bt-inclexcl__col-title' => 'color: {{VALUE}}'],
            'condition' => ['show_optional' => 'yes'],
        ]);

        $this->end_controls_section();

        // Style — Items
        $this->start_controls_section('style_items', [
            'label' => __('Style — Items', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        // ── Espacement ──
        $this->add_responsive_control('items_gap', [
            'label'      => __('Espacement items', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Box ──
        $this->add_control('heading_item_box', [
            'label'     => __('Box', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('item_bg_color', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__item' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-inclexcl__item',
        ]);

        $this->add_responsive_control('item_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow',
            'selector' => '{{WRAPPER}} .bt-inclexcl__item',
        ]);

        // ── Typographie ──
        $this->add_control('heading_item_typo', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'item_typography',
            'selector' => '{{WRAPPER}} .bt-inclexcl__text',
        ]);

        $this->add_control('item_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__text' => 'color: {{VALUE}}'],
        ]);

        // ── Icônes ──
        $this->add_control('heading_item_icons', [
            'label'     => __('Icônes', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_icons' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 40]],
            'selectors'  => [
                '{{WRAPPER}} .bt-inclexcl__icon'     => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-inclexcl__icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
                '{{WRAPPER}} .bt-inclexcl__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
            ],
            'condition'  => ['show_icons' => 'yes'],
        ]);

        $this->add_responsive_control('icon_text_gap', [
            'label'      => __('Espace icône ↔ texte', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 24]],
            'condition'  => ['show_icons' => 'yes'],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__item' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('included_icon_color', [
            'label'     => __('Couleur icône — Inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon'      => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon i'    => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--included .bt-inclexcl__icon svg'  => 'fill: {{VALUE}}; color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--included .bt-inclexcl__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--included .bt-inclexcl__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--included .bt-inclexcl__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_icons' => 'yes'],
        ]);

        $this->add_control('excluded_icon_color', [
            'label'     => __('Couleur icône — Exclus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon'      => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon i'    => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--excluded .bt-inclexcl__icon svg'  => 'fill: {{VALUE}}; color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--excluded .bt-inclexcl__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--excluded .bt-inclexcl__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--excluded .bt-inclexcl__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_icons' => 'yes'],
        ]);

        $this->add_control('optional_icon_color', [
            'label'     => __('Couleur icône — Options', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-inclexcl__col--optional .bt-inclexcl__icon'      => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--optional .bt-inclexcl__icon i'    => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__col--optional .bt-inclexcl__icon svg'  => 'fill: {{VALUE}}; color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--optional .bt-inclexcl__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--optional .bt-inclexcl__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-inclexcl__item--optional .bt-inclexcl__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
            'condition' => ['show_icons' => 'yes', 'show_optional' => 'yes'],
        ]);

        $this->end_controls_section();

        // Style — Description
        $this->start_controls_section('style_desc', [
            'label'     => __('Style — Description', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_desc' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'desc_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-inclexcl__desc',
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-inclexcl__desc' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('desc_spacing', [
            'label'      => __('Espacement haut', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-inclexcl__desc' => 'margin-top: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Source repeater fields (shared by all 3 columns) ───────────────────────

    /**
     * Retourne les champs du Repeater pour les sources de données.
     * Structure identique à TaxonomyDisplay : type (taxonomy/acf) + sélection.
     */
    private function get_source_repeater_fields(): array {
        $repeater = new Repeater();

        $repeater->add_control('source_type', [
            'label'   => __('Type', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'taxonomy' => __('Taxonomie', 'blacktenderscore'),
                'acf'      => __('Champ ACF', 'blacktenderscore'),
            ],
            'default' => 'acf',
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
            'options'     => $this->get_acf_taxonomy_fields(),
            'default'     => '',
            'condition'   => ['source_type' => 'acf'],
            'label_block' => true,
        ]);

        return $repeater->get_controls();
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

    /**
     * Retourne les champs ACF de type taxonomy/relationship pour le SELECT.
     */
    private function get_acf_taxonomy_fields(): array {
        if (!function_exists('acf_get_field_groups')) {
            return ['' => __('ACF non disponible', 'blacktenderscore')];
        }

        $allowed_types = ['taxonomy', 'relationship', 'post_object', 'repeater', 'select', 'checkbox'];
        $opts = ['' => __('— Choisir —', 'blacktenderscore')];

        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group['key'] ?? '');
            if (!is_array($fields)) continue;

            foreach ($fields as $field) {
                $type = $field['type'] ?? '';
                if (!in_array($type, $allowed_types, true)) continue;

                $opts[$field['name']] = sprintf(
                    '%s → %s (%s) [%s]',
                    $group['title'],
                    $field['label'],
                    $field['name'],
                    $type
                );
            }
        }

        asort($opts);
        return $opts;
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = (int) get_the_ID();

        if (!$this->acf_required()) return;

        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';

        echo '<div class="bt-inclexcl">';

        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-inclexcl__section-title');
        } else {
            $this->render_section_title($s, 'bt-inclexcl__section-title');
        }

        $layout        = $s['layout'] ?? 'columns';
        $use_tax_icons = ($s['show_icons'] === 'yes' && !empty($s['show_taxonomy_icons']) && $s['show_taxonomy_icons'] === 'yes');
        $fallback_inc  = $s['fallback_icon_included'] ?? [];
        $fallback_exc  = $s['fallback_icon_excluded'] ?? [];
        $fallback_opt  = $s['fallback_icon_optional'] ?? [];

        // Collecter les items depuis les Repeaters
        $included_items = $this->collect_items_from_sources($s['included_sources'] ?? [], $post_id);
        $excluded_items = $this->collect_items_from_sources($s['excluded_sources'] ?? [], $post_id);
        $optional_items = $this->collect_items_from_sources($s['optional_sources'] ?? [], $post_id);

        if ($layout === 'mixed') {
            // ── Mode mixte : liste unique ──────────────────────────────────────
            echo '<ul class="bt-inclexcl__list bt-inclexcl__list--mixed">';

            // Inclus
            if ($s['show_included'] === 'yes' && !empty($included_items)) {
                $included_icon = $s['included_icon'] ?: '✓';
                foreach ($included_items as $item) {
                    $this->render_item($item, 'included', $s, $use_tax_icons, $included_icon, $fallback_inc);
                }
            }

            // Exclus
            if ($s['show_excluded'] === 'yes' && !empty($excluded_items)) {
                $excluded_icon = $s['excluded_icon'] ?: '✗';
                foreach ($excluded_items as $item) {
                    $this->render_item($item, 'excluded', $s, $use_tax_icons, $excluded_icon, $fallback_exc);
                }
            }

            // Options sur demande
            if (($s['show_optional'] ?? '') === 'yes' && !empty($optional_items)) {
                $optional_icon = $s['optional_icon'] ?: '◎';
                foreach ($optional_items as $item) {
                    $this->render_item($item, 'optional', $s, $use_tax_icons, $optional_icon, $fallback_opt);
                }
            }

            echo '</ul>';

        } else {
            // ── Mode colonnes ──────────────────────────────────────────────────
            echo '<div class="bt-inclexcl__grid">';

            // Colonne Inclus
            if ($s['show_included'] === 'yes') {
                $this->render_column_v2('included', $included_items, $s, $use_tax_icons, $fallback_inc);
            }

            // Colonne Exclus
            if ($s['show_excluded'] === 'yes') {
                $this->render_column_v2('excluded', $excluded_items, $s, $use_tax_icons, $fallback_exc);
            }

            // Colonne Options sur demande
            if (($s['show_optional'] ?? '') === 'yes') {
                $this->render_column_v2('optional', $optional_items, $s, $use_tax_icons, $fallback_opt);
            }

            echo '</div>'; // .bt-inclexcl__grid
        }

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }

        echo '</div>'; // .bt-inclexcl
    }

    /**
     * Collecte les items depuis toutes les sources d'un Repeater.
     * Dédoublonne par term_id ou hash.
     *
     * @param array $sources  Lignes du Repeater [{source_type, taxonomy_slug, acf_field}]
     * @param int   $post_id  ID du post courant
     * @return array<array{name: string, desc: string, term: \WP_Term|null, icon_url: string, icon_fa: string}>
     */
    private function collect_items_from_sources(array $sources, int $post_id): array {
        $all_items = [];
        $seen_ids  = [];

        foreach ($sources as $source) {
            $type  = $source['source_type'] ?? 'acf';
            $items = [];

            if ($type === 'taxonomy') {
                $tax_slug = $source['taxonomy_slug'] ?? '';
                if ($tax_slug !== '') {
                    $terms = get_the_terms($post_id, $tax_slug);
                    if (is_array($terms)) {
                        foreach ($terms as $term) {
                            $items[] = $this->term_to_item($term);
                        }
                    }
                }
            } else {
                // ACF field
                $acf_key = $source['acf_field'] ?? '';
                if ($acf_key !== '' && function_exists('get_field')) {
                    $raw = get_field($acf_key, $post_id);
                    if (!empty($raw)) {
                        $items = $this->resolve_acf_to_items($raw);
                    }
                }
            }

            // Dédoublonner
            foreach ($items as $item) {
                $uid = $item['term_id'] ?? md5($item['name']);
                if (!isset($seen_ids[$uid]) && $item['name'] !== '') {
                    $seen_ids[$uid] = true;
                    $all_items[]    = $item;
                }
            }
        }

        return $all_items;
    }

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
            $term = $this->resolve_term_obj($r);
            if ($term) {
                $items[] = $this->term_to_item($term);
            } elseif (is_scalar($r) && (string) $r !== '') {
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

    /**
     * Résout un élément en objet WP_Term.
     */
    private function resolve_term_obj(mixed $item): ?\WP_Term {
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
     * Affiche un item individuel (mode mixed).
     */
    private function render_item(array $item, string $type, array $s, bool $use_tax_icons, string $char_icon, array $fallback): void {
        // Tag dynamique avec whitelist
        $allowed_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div'];
        $item_tag = in_array($s['item_title_tag'] ?? 'span', $allowed_tags, true) ? $s['item_title_tag'] : 'span';

        echo '<li class="bt-inclexcl__item bt-inclexcl__item--' . esc_attr($type) . '">';

        if ($s['show_icons'] === 'yes') {
            $this->render_item_icon_v2($item, $use_tax_icons, $char_icon, $fallback);
        }

        echo '<' . $item_tag . ' class="bt-inclexcl__text">' . esc_html($item['name']);
        if ($s['show_desc'] === 'yes' && !empty($item['desc'])) {
            echo '<span class="bt-inclexcl__desc">' . esc_html($item['desc']) . '</span>';
        }
        echo '</' . $item_tag . '></li>';
    }

    /**
     * Affiche une colonne avec les items collectés (version v2 avec Repeater).
     */
    private function render_column_v2(string $type, array $items, array $s, bool $use_tax_icons, array $fallback): void {
        $icon  = $s[$type . '_icon'] ?? ($type === 'included' ? '✓' : ($type === 'excluded' ? '✗' : '◎'));
        $label = $s[$type . '_label'] ?? ucfirst($type);

        // Tags dynamiques avec whitelist
        $allowed_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div'];
        $col_tag  = in_array($s['column_title_tag'] ?? 'h5', $allowed_tags, true) ? $s['column_title_tag'] : 'h5';
        $item_tag = in_array($s['item_title_tag'] ?? 'span', $allowed_tags, true) ? $s['item_title_tag'] : 'span';

        if (!empty($items)) {
            echo '<div class="bt-inclexcl__col bt-inclexcl__col--' . esc_attr($type) . '">';
            echo '<' . $col_tag . ' class="bt-inclexcl__col-title">' . esc_html($label) . '</' . $col_tag . '>';
            echo '<ul class="bt-inclexcl__list">';

            foreach ($items as $item) {
                echo '<li class="bt-inclexcl__item">';
                if ($s['show_icons'] === 'yes') {
                    $this->render_item_icon_v2($item, $use_tax_icons, $icon, $fallback);
                }
                echo '<' . $item_tag . ' class="bt-inclexcl__text">' . esc_html($item['name']);
                if ($s['show_desc'] === 'yes' && !empty($item['desc'])) {
                    echo '<span class="bt-inclexcl__desc">' . esc_html($item['desc']) . '</span>';
                }
                echo '</' . $item_tag . '></li>';
            }

            echo '</ul></div>';
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-inclexcl__col bt-inclexcl__col--' . esc_attr($type) . '">';
            $this->render_placeholder(sprintf(__('Aucune source « %s » ou données vides.', 'blacktenderscore'), $label));
            echo '</div>';
        }
    }

    /**
     * Affiche l'icône d'un item (version v2 avec structure item array).
     */
    private function render_item_icon_v2(array $item, bool $use_tax_icons, string $char_icon, array $fallback_icon): void {
        $icon_url = $item['icon_url'] ?? '';
        $icon_fa  = $item['icon_fa'] ?? '';

        echo '<span class="bt-inclexcl__icon" aria-hidden="true">';

        if ($use_tax_icons && $icon_url) {
            $ext = strtolower((string) pathinfo(wp_parse_url($icon_url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $svg = $this->fetch_svg_content($icon_url);
                echo $svg !== '' ? $this->kses_svg($svg) : '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            } else {
                echo '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            }
        } elseif ($use_tax_icons && $icon_fa) {
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
}
