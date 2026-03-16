<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Fiche technique du bateau.
 *
 * Affiche une grille de spécifications issues des champs ACF du post type `boat`.
 * Chaque spec est configurable (label, icône, visibilité).
 */
class BoatSpecs extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-boat-specs',
            'title'    => 'BT — Fiche technique bateau',
            'icon'     => 'eicon-info-box',
            'keywords' => ['bateau', 'specs', 'technique', 'caractéristiques', 'bt'],
            'css'      => ['bt-boat-specs'],
        ];
    }

    // ── Définition des specs disponibles ─────────────────────────────────────
    // 'no_zero' => true : masque la spec si la valeur numérique est vide ou <= 0

    private function specs_definition(): array {
        return [
            'pax_max'         => ['field' => 'boat_pax_max',         'default_label' => 'Passagers max (légal)', 'default_icon' => '👥', 'suffix' => 'pax', 'no_zero' => true],
            'pax_comfort'     => ['field' => 'boat_pax_comfort',     'default_label' => 'Passagers confort',      'default_icon' => '🪑', 'suffix' => 'pax', 'no_zero' => true],
            'cabins'          => ['field' => 'boat_cabins',          'default_label' => 'Cabines',                'default_icon' => '🛏',  'suffix' => '', 'no_zero' => true],
            'bathrooms'       => ['field' => 'boat_bathrooms',       'default_label' => 'Salles de bain',         'default_icon' => '🚿',  'suffix' => '', 'no_zero' => true],
            'captain'         => ['field' => 'boat_captain',         'default_label' => 'Capitaine à bord',       'default_icon' => '⚓',  'suffix' => '', 'type' => 'bool'],
            'crew'            => ['field' => 'boat_crew_number',     'default_label' => 'Crew à disposition',     'default_icon' => '👨‍✈️', 'suffix' => '', 'no_zero' => true],
            'enginepower'     => ['field' => 'boat_enginepower',     'default_label' => 'Motorisation',           'default_icon' => '⚡',  'suffix' => 'CV', 'no_zero' => true],
            'speed'           => ['field' => 'boat_speed',           'default_label' => 'Vitesse max',            'default_icon' => '💨',  'suffix' => 'nœuds', 'no_zero' => true],
            'cruising_speed'  => ['field' => 'boat_cruising_speed',  'default_label' => 'Vitesse croisière',      'default_icon' => '🚢',  'suffix' => 'nœuds', 'no_zero' => true],
            'fuel'            => ['field' => 'boat_fuel',            'default_label' => 'Carburant',              'default_icon' => '⛽',  'suffix' => '', 'type' => 'taxonomy'],
            'fuel_consumption'=> ['field' => 'boat_fuel_consumption','default_label' => 'Consommation',           'default_icon' => '🔋',  'suffix' => 'L/h', 'no_zero' => true],
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
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls(['title' => __('Caractéristiques techniques', 'blacktenderscore')]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'grid' => ['title' => __('Grille', 'blacktenderscore'), 'icon' => 'eicon-gallery-grid'],
                'list' => ['title' => __('Liste',  'blacktenderscore'), 'icon' => 'eicon-post-list'],
            ],
            'default' => 'grid',
            'toggle'  => false,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 6,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 2,
            'selectors'      => ['{{WRAPPER}} .bt-bspecs__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition'      => ['layout' => 'grid'],
        ]);

        $this->add_control('bool_yes_label', [
            'label'   => __('Label "Oui"', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Oui', 'blacktenderscore'),
        ]);

        $this->add_control('bool_no_label', [
            'label'   => __('Label "Non"', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Non', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Section par spec ──────────────────────────────────────────────
        $this->start_controls_section('section_specs', [
            'label' => __('Spécifications à afficher', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        foreach ($specs as $key => $def) {
            $this->add_control("show_{$key}", [
                'label'        => $def['default_label'],
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default'      => 'yes',
            ]);

            $this->add_control("icon_{$key}", [
                'label'     => __('Icône (emoji ou texte)', 'blacktenderscore'),
                'type'      => Controls_Manager::TEXT,
                'default'   => $def['default_icon'],
                'condition' => ["show_{$key}" => 'yes'],
            ]);

            $this->add_control("label_{$key}", [
                'label'     => __('Label', 'blacktenderscore'),
                'type'      => Controls_Manager::TEXT,
                'default'   => $def['default_label'],
                'condition' => ["show_{$key}" => 'yes'],
            ]);

            $this->add_control("suffix_{$key}", [
                'label'     => __('Suffixe unité', 'blacktenderscore'),
                'type'      => Controls_Manager::TEXT,
                'default'   => $def['suffix'],
                'condition' => ["show_{$key}" => 'yes'],
            ]);
        }

        $this->end_controls_section();

        // ── STYLE ─────────────────────────────────────────────────────────

        $this->register_section_title_style('{{WRAPPER}} .bt-bspecs__title');

        $this->register_box_style('item', 'Style — Cartes éléments', '{{WRAPPER}} .bt-bspecs__item', ['padding' => 16]);

        $this->start_controls_section('style_items', [
            'label' => __('Style — Éléments', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('grid_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 64]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-bspecs__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-bspecs__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 12, 'max' => 60]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-bspecs__item-icon' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // Typographies via composants globaux
        $this->register_typography_section(
            'label',
            'Style — Label',
            '{{WRAPPER}} .bt-bspecs__item-label'
        );

        $this->register_typography_section(
            'value',
            'Style — Valeur',
            '{{WRAPPER}} .bt-bspecs__item-value'
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

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

            $display = $this->resolve_display_value($raw, $type, $yes_lbl, $no_lbl, $suffix, $def);
            if ($display === null) continue;

            $items[] = compact('label', 'icon', 'display');
        }

        if (empty($items)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucune spécification trouvée — vérifiez que ce post est de type boat et que les champs ACF sont remplis.', 'blacktenderscore'));
            }
            return;
        }

        $layout   = $s['layout'] ?: 'grid';
        $wrap_cls = $layout === 'list' ? 'bt-bspecs__list' : 'bt-bspecs__grid';

        echo '<div class="bt-bspecs">';

        $this->render_section_title($s, 'bt-bspecs__title');

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

    private function resolve_display_value($raw, string $type, string $yes_lbl, string $no_lbl, string $suffix, array $def): ?string {
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
                if ($val === '') return null;
                // Masquer les valeurs <= 0 pour les specs marquées no_zero (pax, cabines, etc.)
                if (!empty($def['no_zero']) && is_numeric($val) && (float) $val <= 0) return null;
                return esc_html($val . ($suffix ? ' ' . $suffix : ''));
        }
    }
}
