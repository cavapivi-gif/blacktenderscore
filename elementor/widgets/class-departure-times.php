<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Horaires de départ.
 *
 * Lit le repeater ACF `exp_departure_times` (time, season) et
 * affiche les créneaux disponibles, avec filtrage optionnel par saison.
 * Peut aussi afficher le point de départ (taxonomie city) et un lien Maps.
 */
class DepartureTimes extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-departure-times'; }
    public function get_title():      string { return 'BT — Horaires de départ'; }
    public function get_icon():       string { return 'eicon-clock-o'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['horaires', 'départ', 'temps', 'saison', 'bt']; }

    // ── Saisons disponibles ───────────────────────────────────────────────────
    private function season_options(): array {
        return [
            'all'       => __('Toute l\'année', 'bt-regiondo'),
            'summer'    => __('Été (juin–sept)', 'bt-regiondo'),
            'offseason' => __('Hors saison', 'bt-regiondo'),
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF horaires (repeater)', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'exp_departure_times',
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Horaires de départ', 'bt-regiondo'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'grid'   => __('Grille', 'bt-regiondo'),
                'inline' => __('Inline', 'bt-regiondo'),
                'list'   => __('Liste', 'bt-regiondo'),
            ],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'     => __('Colonnes', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 2,
            'max'       => 8,
            'default'   => 4,
            'tablet_default' => 3,
            'mobile_default' => 2,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition' => ['layout' => 'grid'],
        ]);

        $this->end_controls_section();

        // ── Filtres saison ────────────────────────────────────────────────
        $this->start_controls_section('section_season', [
            'label' => __('Filtre saison', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('filter_season', [
            'label'        => __('Filtrer par saison', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Affiche uniquement les horaires de la saison sélectionnée.', 'bt-regiondo'),
        ]);

        $this->add_control('active_season', [
            'label'     => __('Saison active', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => $this->season_options(),
            'default'   => 'summer',
            'condition' => ['filter_season' => 'yes'],
        ]);

        $this->add_control('show_season_badge', [
            'label'        => __('Afficher le badge saison', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_season_all', [
            'label'     => __('Label "Toute l\'année"', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Toute l\'année', 'bt-regiondo'),
            'condition' => ['show_season_badge' => 'yes'],
        ]);

        $this->add_control('label_season_summer', [
            'label'     => __('Label "Été"', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Été', 'bt-regiondo'),
            'condition' => ['show_season_badge' => 'yes'],
        ]);

        $this->add_control('label_season_offseason', [
            'label'     => __('Label "Hors saison"', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Hors saison', 'bt-regiondo'),
            'condition' => ['show_season_badge' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Point de départ ───────────────────────────────────────────────
        $this->start_controls_section('section_departure_point', [
            'label' => __('Point de départ', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_departure_point', [
            'label'        => __('Afficher le point de départ', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'description'  => __('Lit le champ ACF exp_departure_point (taxonomie city).', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('departure_icon', [
            'label'     => __('Icône départ', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '📍',
            'condition' => ['show_departure_point' => 'yes'],
        ]);

        $this->add_control('show_map_link', [
            'label'        => __('Afficher un lien Google Maps', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'description'  => __('Lit exp_departure_coords (lat, lon) et génère un lien Maps.', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['show_departure_point' => 'yes'],
        ]);

        $this->add_control('map_link_label', [
            'label'     => __('Texte du lien Maps', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Voir sur la carte', 'bt-regiondo'),
            'condition' => ['show_departure_point' => 'yes', 'show_map_link' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Badges horaires ───────────────────────────────────────
        $this->start_controls_section('style_badges', [
            'label' => __('Style — Badges horaires', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'label'    => __('Typographie titre', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-deptimes__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('badges_gap', [
            'label'      => __('Espacement badges', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-deptimes__grid'   => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-deptimes__inline' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-deptimes__list'   => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'time_typo',
            'label'    => __('Typographie heure', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-deptimes__time',
        ]);

        $this->add_control('time_color', [
            'label'     => __('Couleur heure', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__time' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('badge_bg', [
            'label'     => __('Fond du badge', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__badge' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'badge_border',
            'selector' => '{{WRAPPER}} .bt-deptimes__badge',
        ]);

        $this->add_responsive_control('badge_radius', [
            'label'      => __('Border radius badge', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-deptimes__badge' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('badge_padding', [
            'label'      => __('Padding badge', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '8', 'right' => '12', 'bottom' => '8', 'left' => '12', 'unit' => 'px', 'isLinked' => false],
            'selectors'  => ['{{WRAPPER}} .bt-deptimes__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('season_badge_bg', [
            'label'     => __('Fond badge saison', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__season-badge' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('season_badge_color', [
            'label'     => __('Texte badge saison', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__season-badge' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Point de départ ───────────────────────────────────────
        $this->start_controls_section('style_point', [
            'label' => __('Style — Point de départ', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'point_typo',
            'selector' => '{{WRAPPER}} .bt-deptimes__point',
        ]);

        $this->add_control('point_color', [
            'label'     => __('Couleur', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__point' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('map_link_color', [
            'label'     => __('Couleur lien Maps', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-deptimes__map-link' => 'color: {{VALUE}}'],
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

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_departure_times');
        $rows       = get_field($field_name, $post_id);

        // Filtrer par saison si activé
        if ($s['filter_season'] === 'yes' && !empty($rows)) {
            $active = $s['active_season'] ?: 'summer';
            $rows   = array_filter($rows, function ($r) use ($active) {
                $season = $r['season'] ?? 'all';
                return $season === $active || $season === 'all';
            });
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
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun horaire ni point de départ trouvé.</p>';
            }
            return;
        }

        $tag    = esc_attr($s['title_tag'] ?: 'h3');
        $layout = $s['layout'] ?: 'grid';

        $season_labels = [
            'all'       => $s['label_season_all']       ?: __('Toute l\'année', 'bt-regiondo'),
            'summer'    => $s['label_season_summer']    ?: __('Été', 'bt-regiondo'),
            'offseason' => $s['label_season_offseason'] ?: __('Hors saison', 'bt-regiondo'),
        ];

        echo '<div class="bt-deptimes">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-deptimes__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        // Point de départ
        if ($has_depart) {
            $icon = esc_html($s['departure_icon'] ?: '📍');
            echo '<div class="bt-deptimes__departure-info">';
            echo '<span class="bt-deptimes__point">' . $icon . ' ' . esc_html($departure_name) . '</span>';

            if ($s['show_map_link'] === 'yes' && $departure_coords) {
                // Convertir "43.5125, 6.9487" en lien Google Maps
                $coords = preg_replace('/\s+/', '', $departure_coords);
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($coords);
                $lbl     = esc_html($s['map_link_label'] ?: __('Voir sur la carte', 'bt-regiondo'));
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

            foreach ($rows as $row) {
                $time   = $row['time']   ?? '';
                $season = $row['season'] ?? 'all';

                if (!$time) continue;

                echo '<li class="bt-deptimes__badge">';
                echo '<span class="bt-deptimes__time">' . esc_html($time) . '</span>';

                if ($s['show_season_badge'] === 'yes' && isset($season_labels[$season])) {
                    echo '<span class="bt-deptimes__season-badge bt-deptimes__season--' . esc_attr($season) . '">';
                    echo esc_html($season_labels[$season]);
                    echo '</span>';
                }

                echo '</li>';
            }

            echo '</ul>';
        }

        echo '</div>';
    }
}
