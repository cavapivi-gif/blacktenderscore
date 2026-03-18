<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Horaires de départ.
 *
 * Lit le repeater ACF `exp_departure_times` (time, season) et
 * affiche les créneaux disponibles, avec filtrage optionnel par saison.
 * Peut aussi afficher le point de départ (taxonomie city) et un lien Maps.
 */
class DepartureTimes extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-departure-times',
            'title'    => 'BT — Horaires de départ',
            'icon'     => 'eicon-clock-o',
            'keywords' => ['horaires', 'départ', 'temps', 'saison', 'bt'],
            'css'      => ['bt-departure-times'],
        ];
    }

    // ── Saisons disponibles ───────────────────────────────────────────────────
    private function season_options(): array {
        return [
            'all'       => __('Toute l\'année', 'blacktenderscore'),
            'summer'    => __('Été (juin–sept)', 'blacktenderscore'),
            'offseason' => __('Hors saison', 'blacktenderscore'),
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF horaires (repeater)', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'exp_departure_times',
        ]);

        $this->add_control('subfield_time', [
            'label'       => __('Sous-champ heure', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'time',
            'description' => __('Nom du sous-champ ACF contenant l\'heure dans le repeater.', 'blacktenderscore'),
        ]);

        $this->add_control('subfield_season', [
            'label'       => __('Sous-champ saison', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'season',
            'description' => __('Nom du sous-champ ACF contenant la saison dans le repeater.', 'blacktenderscore'),
        ]);

        $this->register_section_title_controls(['title' => __('Horaires de départ', 'blacktenderscore')]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'grid'   => ['title' => __('Grille',  'blacktenderscore'), 'icon' => 'eicon-gallery-grid'],
                'inline' => ['title' => __('Inline',  'blacktenderscore'), 'icon' => 'eicon-flex'],
                'list'   => ['title' => __('Liste',   'blacktenderscore'), 'icon' => 'eicon-post-list'],
            ],
            'default' => 'grid',
            'toggle'  => false,
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::NUMBER,
            'min'            => 2,
            'max'            => 8,
            'default'        => 4,
            'tablet_default' => 3,
            'mobile_default' => 2,
            'selectors'      => ['{{WRAPPER}} .bt-deptimes__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition'      => ['layout' => 'grid'],
        ]);

        $this->end_controls_section();

        // ── Filtres saison ────────────────────────────────────────────────
        $this->start_controls_section('section_season', [
            'label' => __('Filtre saison', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('filter_season', [
            'label'        => __('Filtrer par saison', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Affiche uniquement les horaires de la saison sélectionnée.', 'blacktenderscore'),
        ]);

        $this->add_control('active_season', [
            'label'     => __('Saison active', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => $this->season_options(),
            'default'   => 'summer',
            'condition' => ['filter_season' => 'yes'],
        ]);

        $this->add_control('show_season_badge', [
            'label'        => __('Afficher le badge saison', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_season_all', [
            'label'     => __('Label "Toute l\'année"', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Toute l\'année', 'blacktenderscore'),
            'condition' => ['show_season_badge' => 'yes'],
        ]);

        $this->add_control('label_season_summer', [
            'label'     => __('Label "Été"', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Été', 'blacktenderscore'),
            'condition' => ['show_season_badge' => 'yes'],
        ]);

        $this->add_control('label_season_offseason', [
            'label'     => __('Label "Hors saison"', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Hors saison', 'blacktenderscore'),
            'condition' => ['show_season_badge' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Durée en navigation ───────────────────────────────────────────
        $this->start_controls_section('section_duration', [
            'label' => __('Durée en navigation', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_duration', [
            'label'        => __('Afficher le temps', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('duration_field', [
            'label'       => __('Champ ACF durée (repeater)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'tarification_par_forfait',
            'description' => __('Repeater ACF contenant le sous-champ durée (ex: tarification_par_forfait). Les lignes sont associées par index aux horaires.', 'blacktenderscore'),
            'condition'   => ['show_duration' => 'yes'],
        ]);

        $this->add_control('duration_subfield', [
            'label'       => __('Sous-champ durée', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exc_timeinbot',
            'description' => __('Nom du sous-champ texte contenant la durée (ex: 1h30).', 'blacktenderscore'),
            'condition'   => ['show_duration' => 'yes'],
        ]);

        $this->add_control('duration_label_before', [
            'label'     => __('Texte avant la durée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('pour une durée de', 'blacktenderscore'),
            'condition' => ['show_duration' => 'yes'],
        ]);

        $this->add_control('duration_label_after', [
            'label'     => __('Texte après la durée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '',
            'condition' => ['show_duration' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Point de départ ───────────────────────────────────────────────
        $this->start_controls_section('section_departure_point', [
            'label' => __('Point de départ', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_departure_point', [
            'label'        => __('Afficher le point de départ', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Lit le champ ACF exp_departure_point (taxonomie city).', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('departure_icon', [
            'label'     => __('Icône départ', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid'],
            'condition' => ['show_departure_point' => 'yes'],
        ]);

        $this->add_responsive_control('departure_icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 10, 'max' => 40]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-deptimes__departure-icon' => 'font-size: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_departure_point' => 'yes'],
        ]);

        $this->add_control('departure_icon_color', [
            'label'     => __('Couleur icône', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__departure-icon' => 'color: {{VALUE}}'],
            'condition' => ['show_departure_point' => 'yes'],
        ]);

        $this->add_control('show_map_link', [
            'label'        => __('Afficher un lien Google Maps', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Lit exp_departure_coords (lat, lon) et génère un lien Maps.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['show_departure_point' => 'yes'],
        ]);

        $this->add_control('map_link_label', [
            'label'     => __('Texte du lien Maps', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Voir sur la carte', 'blacktenderscore'),
            'condition' => ['show_departure_point' => 'yes', 'show_map_link' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══ STYLE ══════════════════════════════════════════════════════════════

        $this->register_section_title_style('{{WRAPPER}} .bt-deptimes__title');

        // Badge horaire : box style (fond, border, radius, padding, shadow)
        $this->register_box_style('badge', 'Style — Badges horaires', '{{WRAPPER}} .bt-deptimes__badge', ['padding' => 8]);

        // Espacement + badge saison (petite section custom)
        $this->start_controls_section('style_badges_extra', [
            'label' => __('Style — Espacement & saison', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->register_gap_control(
            'badges_gap',
            __('Espacement badges', 'blacktenderscore'),
            ['{{WRAPPER}} .bt-deptimes__grid', '{{WRAPPER}} .bt-deptimes__inline', '{{WRAPPER}} .bt-deptimes__list'],
            8
        );

        $this->register_badge_colors(
            'season_badge',
            __('Badge saison', 'blacktenderscore'),
            '{{WRAPPER}} .bt-deptimes__season-badge',
            [],
            ['show_season_badge' => 'yes']
        );

        $this->end_controls_section();

        // Heure : typographie complète
        $this->register_typography_section(
            'time',
            'Style — Heure',
            '{{WRAPPER}} .bt-deptimes__time'
        );

        // Durée : typographie
        $this->register_typography_section(
            'duration',
            'Style — Durée',
            '{{WRAPPER}} .bt-deptimes__duration',
            [],
            [],
            ['show_duration' => 'yes']
        );

        // Point de départ : typographie + lien Maps
        $this->register_typography_section(
            'point',
            'Style — Point de départ',
            '{{WRAPPER}} .bt-deptimes__point',
            [],
            [],
            ['show_departure_point' => 'yes']
        );

        $this->start_controls_section('style_map_link', [
            'label'     => __('Style — Lien Maps', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_departure_point' => 'yes', 'show_map_link' => 'yes'],
        ]);

        $this->add_control('map_link_color', [
            'label'     => __('Couleur lien Maps', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__map-link' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('map_link_color_hover', [
            'label'     => __('Couleur au survol', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__map-link:hover' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $field_name    = sanitize_text_field($s['acf_field'] ?: 'exp_departure_times');
        $sf_time       = sanitize_key($s['subfield_time']   ?: 'time');
        $sf_season     = sanitize_key($s['subfield_season'] ?: 'season');
        $rows_raw      = get_field($field_name, $post_id) ?: [];

        // Filtrer par saison en préservant les index originaux (pour correspondance avec les durées)
        if ($s['filter_season'] === 'yes') {
            $active = $s['active_season'] ?: 'summer';
            $rows   = array_filter($rows_raw, function ($r) use ($active, $sf_season) {
                $season = $r[$sf_season] ?? 'all';
                return $season === $active || $season === 'all';
            });
        } else {
            $rows = $rows_raw;
        }

        // Pré-charger les durées (repeater indexé pareil que les horaires)
        $duration_rows = [];
        $sf_duration   = '';
        if ($s['show_duration'] === 'yes') {
            $dur_field     = sanitize_text_field($s['duration_field'] ?: 'tarification_par_forfait');
            $sf_duration   = sanitize_key($s['duration_subfield'] ?: 'exc_timeinbot');
            $duration_rows = get_field($dur_field, $post_id) ?: [];
        }

        // Récupérer le point de départ
        $departure_name   = '';
        $departure_coords = '';
        if ($s['show_departure_point'] === 'yes') {
            $dep_field = get_field('exp_departure_point', $post_id);
            if ($dep_field) {
                $dep_ids = is_array($dep_field) ? $dep_field : [$dep_field];
                $names   = [];
                foreach ($dep_ids as $tid) {
                    $t = is_numeric($tid) ? get_term((int) $tid, 'city') : ($tid instanceof \WP_Term ? $tid : null);
                    if ($t && !is_wp_error($t)) $names[] = $t->name;
                }
                $departure_name = implode(', ', $names);
            }
            $departure_coords = (string) get_field('exp_departure_coords', $post_id);
        }

        $has_times  = !empty($rows);
        $has_depart = $departure_name !== '';

        if (!$has_times && !$has_depart) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucun horaire ni point de départ trouvé.', 'blacktenderscore'));
            }
            return;
        }

        $layout = $s['layout'] ?: 'grid';

        $season_labels = [
            'all'       => $s['label_season_all']       ?: __('Toute l\'année', 'blacktenderscore'),
            'summer'    => $s['label_season_summer']    ?: __('Été', 'blacktenderscore'),
            'offseason' => $s['label_season_offseason'] ?: __('Hors saison', 'blacktenderscore'),
        ];

        echo '<div class="bt-deptimes">';

        $this->render_section_title($s, 'bt-deptimes__title');

        // Point de départ
        if ($has_depart) {
            echo '<div class="bt-deptimes__departure-info">';

            $icon_html = $this->capture_icon($s['departure_icon'], ['aria-hidden' => 'true', 'class' => 'bt-deptimes__departure-icon']);

            echo '<span class="bt-deptimes__point">' . $icon_html . ' ' . esc_html($departure_name) . '</span>';

            if ($s['show_map_link'] === 'yes' && $departure_coords) {
                $coords  = preg_replace('/\s+/', '', $departure_coords);
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($coords);
                $lbl     = esc_html($s['map_link_label'] ?: __('Voir sur la carte', 'blacktenderscore'));
                echo ' <a href="' . esc_url($map_url) . '" class="bt-deptimes__map-link" target="_blank" rel="noopener noreferrer">' . $lbl . '</a>';
            }

            echo '</div>';
        }

        // Horaires
        if ($has_times) {
            $wrap_cls = match ($layout) {
                'inline' => 'bt-deptimes__inline',
                'list'   => 'bt-deptimes__list',
                default  => 'bt-deptimes__grid',
            };

            echo "<ul class=\"{$wrap_cls}\">";

            foreach ($rows as $orig_idx => $row) {
                $time   = $row[$sf_time]   ?? '';
                $season = $row[$sf_season] ?? 'all';

                if (!$time) continue;

                // Durée correspondante par index (non-repeater dans la row, une seule valeur)
                $duration_val = '';
                if ($s['show_duration'] === 'yes' && isset($duration_rows[$orig_idx][$sf_duration])) {
                    $duration_val = trim($duration_rows[$orig_idx][$sf_duration]);
                }

                echo '<li class="bt-deptimes__badge">';
                echo '<span class="bt-deptimes__time">' . esc_html($time) . '</span>';

                if ($s['show_season_badge'] === 'yes' && isset($season_labels[$season])) {
                    echo '<span class="bt-deptimes__season-badge bt-deptimes__season--' . esc_attr($season) . '">';
                    echo esc_html($season_labels[$season]);
                    echo '</span>';
                }

                if ($duration_val !== '') {
                    $lbl_before = $s['duration_label_before'] ?? '';
                    $lbl_after  = $s['duration_label_after']  ?? '';
                    echo '<span class="bt-deptimes__duration">';
                    if ($lbl_before !== '') echo '<span class="bt-deptimes__duration-before">' . esc_html($lbl_before) . ' </span>';
                    echo '<span class="bt-deptimes__duration-value">' . esc_html($duration_val) . '</span>';
                    if ($lbl_after  !== '') echo '<span class="bt-deptimes__duration-after"> ' . esc_html($lbl_after) . '</span>';
                    echo '</span>';
                }

                echo '</li>';
            }

            echo '</ul>';
        }

        echo '</div>';
    }
}
