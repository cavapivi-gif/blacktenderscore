<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Fiche technique du bateau.
 *
 * Affiche une grille de spécifications issues des champs ACF du post type `boat`.
 * Chaque spec est configurable (label, icône, visibilité).
 */
class BoatSpecs extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-boat-specs'; }
    public function get_title():      string { return 'BT — Fiche technique bateau'; }
    public function get_icon():       string { return 'eicon-info-box'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['bateau', 'specs', 'technique', 'caractéristiques', 'bt']; }

    // ── Définition des specs disponibles ─────────────────────────────────────

    private function specs_definition(): array {
        return [
            'pax_max'         => ['field' => 'boat_pax_max',         'default_label' => 'Passagers max (légal)', 'default_icon' => '👥', 'suffix' => 'pax'],
            'pax_comfort'     => ['field' => 'boat_pax_comfort',     'default_label' => 'Passagers confort',      'default_icon' => '🪑', 'suffix' => 'pax'],
            'cabins'          => ['field' => 'boat_cabins',          'default_label' => 'Cabines',                'default_icon' => '🛏',  'suffix' => ''],
            'bathrooms'       => ['field' => 'boat_bathrooms',       'default_label' => 'Salles de bain',         'default_icon' => '🚿',  'suffix' => ''],
            'captain'         => ['field' => 'boat_captain',         'default_label' => 'Capitaine à bord',       'default_icon' => '⚓',  'suffix' => '', 'type' => 'bool'],
            'crew'            => ['field' => 'boat_crew_number',     'default_label' => 'Crew à disposition',     'default_icon' => '👨‍✈️', 'suffix' => ''],
            'enginepower'     => ['field' => 'boat_enginepower',     'default_label' => 'Motorisation',           'default_icon' => '⚡',  'suffix' => 'CV'],
            'speed'           => ['field' => 'boat_speed',           'default_label' => 'Vitesse max',            'default_icon' => '💨',  'suffix' => 'nœuds'],
            'cruising_speed'  => ['field' => 'boat_cruising_speed',  'default_label' => 'Vitesse croisière',      'default_icon' => '🚢',  'suffix' => 'nœuds'],
            'fuel'            => ['field' => 'boat_fuel',            'default_label' => 'Carburant',              'default_icon' => '⛽',  'suffix' => '', 'type' => 'taxonomy'],
            'fuel_consumption'=> ['field' => 'boat_fuel_consumption','default_label' => 'Consommation',           'default_icon' => '🔋',  'suffix' => 'L/h'],
            'fuel_included'   => ['field' => 'boat_fuel_included',   'default_label' => 'Carburant inclus',       'default_icon' => '✅',  'suffix' => '', 'type' => 'bool'],
            'year'            => ['field' => 'boat_year',            'default_label' => 'Année de construction',  'default_icon' => '📅',  'suffix' => ''],
            'length'          => ['field' => 'boat_length',          'default_label' => 'Longueur',               'default_icon' => '📏',  'suffix' => '', 'type' => 'taxonomy'],
            'beam'            => ['field' => 'boat_beam',            'default_label' => 'Largeur',                'default_icon' => '↔️',  'suffix' => '', 'type' => 'taxonomy'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        $specs = $this->specs_definition();

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Caractéristiques techniques', 'blacktenderscore'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p', 'span' => 'span'],
            'default' => 'h3',
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'grid' => __('Grille', 'blacktenderscore'),
                'list' => __('Liste', 'blacktenderscore'),
            ],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('columns', [
            'label'     => __('Colonnes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 6,
            'default'   => 3,
            'tablet_default' => 2,
            'mobile_default' => 2,
            'selectors' => ['{{WRAPPER}} .bt-bspecs__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition' => ['layout' => 'grid'],
        ]);

        $this->add_control('bool_yes_label', [
            'label'   => __('Label "Oui"', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Oui', 'blacktenderscore'),
        ]);

        $this->add_control('bool_no_label', [
            'label'   => __('Label "Non"', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Non', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Section par spec ──────────────────────────────────────────────
        $this->start_controls_section('section_specs', [
            'label' => __('Spécifications à afficher', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        foreach ($specs as $key => $def) {
            $this->add_control("show_{$key}", [
                'label'        => $def['default_label'],
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]);

            $this->add_control("icon_{$key}", [
                'label'     => __('Icône (emoji ou texte)', 'blacktenderscore'),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => $def['default_icon'],
                'condition' => ["show_{$key}" => 'yes'],
            ]);

            $this->add_control("label_{$key}", [
                'label'     => __('Label', 'blacktenderscore'),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => $def['default_label'],
                'condition' => ["show_{$key}" => 'yes'],
            ]);

            $this->add_control("suffix_{$key}", [
                'label'     => __('Suffixe unité', 'blacktenderscore'),
                'type'      => \Elementor\Controls_Manager::TEXT,
                'default'   => $def['suffix'],
                'condition' => ["show_{$key}" => 'yes'],
            ]);
        }

        $this->end_controls_section();

        // ── Style — Wrapper ───────────────────────────────────────────────
        $this->start_controls_section('style_wrapper', [
            'label' => __('Style — Titre', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'selector' => '{{WRAPPER}} .bt-bspecs__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bspecs__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('title_spacing', [
            'label'      => __('Marge basse titre', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-bspecs__title' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Items ─────────────────────────────────────────────────
        $this->start_controls_section('style_items', [
            'label' => __('Style — Éléments', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('grid_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 64]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-bspecs__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-bspecs__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('item_bg', [
            'label'     => __('Fond des éléments', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bspecs__item' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-bspecs__item',
        ]);

        $this->add_responsive_control('item_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-bspecs__item' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-bspecs__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow',
            'selector' => '{{WRAPPER}} .bt-bspecs__item',
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 12, 'max' => 60]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-bspecs__item-icon' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'label_typography',
            'label'    => __('Typographie label', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-bspecs__item-label',
        ]);

        $this->add_control('label_color', [
            'label'     => __('Couleur label', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bspecs__item-label' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'value_typography',
            'label'    => __('Typographie valeur', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-bspecs__item-value',
        ]);

        $this->add_control('value_color', [
            'label'     => __('Couleur valeur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bspecs__item-value' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!function_exists('get_field')) {
            echo '<p class="bt-widget-placeholder">ACF Pro requis.</p>';
            return;
        }

        $specs   = $this->specs_definition();
        $items   = [];
        $yes_lbl = esc_html($s['bool_yes_label'] ?: __('Oui', 'blacktenderscore'));
        $no_lbl  = esc_html($s['bool_no_label']  ?: __('Non', 'blacktenderscore'));

        foreach ($specs as $key => $def) {
            if ($s["show_{$key}"] !== 'yes') continue;

            $raw    = get_field($def['field'], $post_id);
            $type   = $def['type'] ?? 'scalar';
            $suffix = trim($s["suffix_{$key}"] ?? $def['suffix']);
            $label  = esc_html($s["label_{$key}"] ?: $def['default_label']);
            $icon   = esc_html($s["icon_{$key}"]  ?: $def['default_icon']);

            $display = $this->resolve_display_value($raw, $type, $yes_lbl, $no_lbl, $suffix);
            if ($display === null) continue;

            $items[] = compact('label', 'icon', 'display');
        }

        if (empty($items)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucune spécification trouvée — vérifiez que ce post est de type <code>boat</code> et que les champs ACF sont remplis.</p>';
            }
            return;
        }

        $layout   = $s['layout'] ?: 'grid';
        $tag      = esc_attr($s['title_tag'] ?: 'h3');
        $wrap_cls = 'bt-bspecs__grid';
        if ($layout === 'list') $wrap_cls = 'bt-bspecs__list';

        echo '<div class="bt-bspecs">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-bspecs__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        echo "<div class=\"{$wrap_cls}\">";
        foreach ($items as $item) {
            echo '<div class="bt-bspecs__item">';
            echo '<span class="bt-bspecs__item-icon" aria-hidden="true">' . $item['icon'] . '</span>';
            echo '<div class="bt-bspecs__item-body">';
            echo '<span class="bt-bspecs__item-label">' . $item['label'] . '</span>';
            echo '<span class="bt-bspecs__item-value">' . $item['display'] . '</span>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>'; // grid/list
        echo '</div>'; // .bt-bspecs
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolve_display_value($raw, string $type, string $yes_lbl, string $no_lbl, string $suffix): ?string {
        if ($raw === null || $raw === '') return null;

        switch ($type) {
            case 'bool':
                return $raw ? $yes_lbl : $no_lbl;

            case 'taxonomy':
                $terms = is_array($raw) ? $raw : [$raw];
                $names = [];
                foreach ($terms as $t) {
                    if ($t instanceof \WP_Term) {
                        $names[] = $t->name;
                    } elseif (is_numeric($t)) {
                        $term = get_term((int) $t);
                        if ($term && !is_wp_error($term)) $names[] = $term->name;
                    }
                }
                return empty($names) ? null : esc_html(implode(', ', $names));

            default: // scalar (number, text)
                $val = (string) $raw;
                if ($val === '' || $val === '0' && $suffix === 'pax') return null;
                return esc_html($val . ($suffix ? ' ' . $suffix : ''));
        }
    }
}
