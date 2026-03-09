<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Programme / Itinéraire.
 *
 * Lit le repeater ACF `exp_itinerary` (step_time, step_title, step_desc,
 * step_timethezone, step_icon, step_is_return) ainsi que les champs directs
 * de transport (exp_departure_zone, exp_outboard, exp_returning_zone,
 * exp_returning_description) et affiche une timeline verticale avec
 * lignes de transport en tête et en pied.
 */
class Itinerary extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-itinerary'; }
    public function get_title():      string { return 'BT — Programme / Itinéraire'; }
    public function get_icon():       string { return 'eicon-time-line'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['itinéraire', 'programme', 'timeline', 'étapes', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'exp_itinerary',
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Programme', 'blacktenderscore'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->add_control('show_time', [
            'label'        => __('Afficher l\'heure / moment', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_duration', [
            'label'        => __('Afficher la durée de l\'étape', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_transport', [
            'label'        => __('Afficher les blocs transport (hors-bord / zones)', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('connector', [
            'label'   => __('Connecteur timeline', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'line' => __('Ligne verticale', 'blacktenderscore'),
                'none' => __('Aucun', 'blacktenderscore'),
            ],
            'default' => 'line',
        ]);

        $this->end_controls_section();

        // ── Style — Timeline ──────────────────────────────────────────────
        $this->start_controls_section('style_timeline', [
            'label' => __('Style — Timeline', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'section_title_typo',
            'label'    => __('Typographie titre section', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__title',
        ]);

        $this->add_control('section_title_color', [
            'label'     => __('Couleur titre section', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('steps_gap', [
            'label'      => __('Espacement entre étapes', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 80]],
            'default'    => ['size' => 32, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('line_color', [
            'label'     => __('Couleur de la ligne', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step::before' => 'background-color: {{VALUE}}'],
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_responsive_control('line_width', [
            'label'      => __('Épaisseur de la ligne', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step::before' => 'width: {{SIZE}}{{UNIT}}'],
            'condition'  => ['connector' => 'line'],
        ]);

        $this->add_control('dot_color', [
            'label'     => __('Couleur du point', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__dot' => 'background-color: {{VALUE}}; border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille du point', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 32]],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__dot'          => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                // Centre la ligne sur le dot quelle que soit sa taille
                '{{WRAPPER}} .bt-itin__step::before' => 'left: calc({{SIZE}}{{UNIT}} / 2 - 1px)',
            ],
        ]);

        $this->add_responsive_control('dot_icon_size', [
            'label'      => __('Taille de l\'icône dans le point', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 28]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__dot--icon' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('return_dot_color', [
            'label'     => __('Couleur point — étape retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--return .bt-itin__dot' => 'background-color: {{VALUE}}; border-color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Texte ─────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte des étapes', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'time_typo',
            'label'    => __('Typographie heure', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__time',
        ]);

        $this->add_control('time_color', [
            'label'     => __('Couleur heure', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__time' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'step_title_typo',
            'label'    => __('Typographie titre étape', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__step-title',
        ]);

        $this->add_control('step_title_color', [
            'label'     => __('Couleur titre étape', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'desc_typo',
            'label'    => __('Typographie description', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__step-desc',
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur description', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step-desc' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('content_padding', [
            'label'      => __('Padding contenu étape', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('step_bg', [
            'label'     => __('Fond de l\'étape', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step-body' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('return_step_bg', [
            'label'     => __('Fond étape retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--return .bt-itin__step-body' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'step_border',
            'selector' => '{{WRAPPER}} .bt-itin__step-body',
        ]);

        $this->add_responsive_control('step_radius', [
            'label'      => __('Border radius étape', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step-body' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Transport ─────────────────────────────────────────────
        $this->start_controls_section('style_transport', [
            'label' => __('Style — Transport & zones', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('transport_bg', [
            'label'     => __('Fond du bloc transport', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__transport' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'transport_typo',
            'label'    => __('Typographie transport', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__transport',
        ]);

        $this->add_control('transport_color', [
            'label'     => __('Couleur texte transport', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__transport' => 'color: {{VALUE}}'],
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

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_itinerary');
        $rows       = get_field($field_name, $post_id);

        if (empty($rows)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucune étape dans le champ <code>' . esc_html($field_name) . '</code>.</p>';
            }
            return;
        }

        $tag            = esc_attr($s['title_tag'] ?: 'h3');
        $show_time      = $s['show_time']        === 'yes';
        $show_duration  = $s['show_duration']    === 'yes';
        $show_desc      = $s['show_description'] === 'yes';
        $show_transport = $s['show_transport']   === 'yes';
        $connector      = $s['connector'] ?: 'line';
        $connector_cls  = $connector === 'none' ? ' bt-itin--no-connector' : '';

        // Champs directs de transport
        $departure_zone = $show_transport ? get_field('exp_departure_zone', $post_id)       : '';
        $outboard       = $show_transport ? (int) get_field('exp_outboard', $post_id)       : 0;
        $returning_zone = $show_transport ? get_field('exp_returning_zone', $post_id)       : '';
        $returning_desc = $show_transport ? get_field('exp_returning_description', $post_id): '';

        echo '<div class="bt-itin' . $connector_cls . '">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-itin__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        // ── Bloc départ ──────────────────────────────────────────────────
        if ($show_transport && ($departure_zone || $outboard)) {
            echo '<div class="bt-itin__transport bt-itin__transport--departure">';
            if ($departure_zone) {
                echo '<span class="bt-itin__transport-label">' . esc_html__('Départ', 'blacktenderscore') . '</span>';
                echo '<span class="bt-itin__transport-zone">' . esc_html($departure_zone) . '</span>';
                echo '<span class="bt-itin__transport-sep" aria-hidden="true">·</span>';
            }
            if ($outboard) {
                /* translators: %d = durée en minutes */
                echo '<span class="bt-itin__transport-outboard">' . esc_html(sprintf(__('Hors-bord (%d min)', 'blacktenderscore'), $outboard)) . '</span>';
            }
            echo '</div>';
        }

        // ── Liste des étapes ─────────────────────────────────────────────
        echo '<ol class="bt-itin__list">';

        foreach ($rows as $row) {
            $time      = $row['step_time']        ?? '';
            $title     = $row['step_title']       ?? '';
            $desc      = $row['step_desc']        ?? '';
            $duration  = isset($row['step_timethezone']) ? (int) $row['step_timethezone'] : 0;
            $icon_cls  = trim($row['step_icon']   ?? '');
            $is_return = !empty($row['step_is_return']);

            $step_cls = 'bt-itin__step' . ($is_return ? ' bt-itin__step--return' : '');

            echo '<li class="' . esc_attr($step_cls) . '">';

            // Point ou icône personnalisée
            if ($icon_cls) {
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true"><i class="' . esc_attr($icon_cls) . '"></i></span>';
            } else {
                echo '<span class="bt-itin__dot" aria-hidden="true"></span>';
            }

            echo '<div class="bt-itin__step-body">';

            // Ligne méta : heure + durée
            $has_meta = ($show_time && $time) || ($show_duration && $duration);
            if ($has_meta) {
                echo '<div class="bt-itin__meta">';
                if ($show_time && $time) {
                    echo '<span class="bt-itin__time">' . esc_html($time) . '</span>';
                }
                if ($show_duration && $duration) {
                    echo '<span class="bt-itin__duration">(' . esc_html($duration) . '&nbsp;min)</span>';
                }
                echo '</div>';
            }

            if ($title) {
                echo '<strong class="bt-itin__step-title">' . esc_html($title) . '</strong>';
            }

            if ($show_desc && $desc) {
                echo '<p class="bt-itin__step-desc">' . esc_html($desc) . '</p>';
            }

            echo '</div></li>';
        }

        echo '</ol>';

        // ── Bloc retour ──────────────────────────────────────────────────
        if ($show_transport && ($outboard || $returning_zone || $returning_desc)) {
            echo '<div class="bt-itin__transport bt-itin__transport--return">';
            if ($outboard) {
                /* translators: %d = durée en minutes */
                echo '<span class="bt-itin__transport-outboard">' . esc_html(sprintf(__('Retour hors-bord (%d min)', 'blacktenderscore'), $outboard)) . '</span>';
                if ($returning_zone) {
                    echo '<span class="bt-itin__transport-sep" aria-hidden="true">·</span>';
                }
            }
            if ($returning_zone) {
                echo '<span class="bt-itin__transport-label">' . esc_html__('Arrivée', 'blacktenderscore') . '</span>';
                echo '<span class="bt-itin__transport-zone">' . esc_html($returning_zone) . '</span>';
            }
            if ($returning_desc) {
                echo '<p class="bt-itin__transport-desc">' . esc_html($returning_desc) . '</p>';
            }
            echo '</div>';
        }

        echo '</div>';
    }
}
