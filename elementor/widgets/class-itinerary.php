<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Programme / Itinéraire v2.
 *
 * Architecture unifiée : un seul <ol> contient TOUT —
 * zone de départ, hors-bord aller, étapes ACF, hors-bord retour, zone d'arrivée.
 * La ligne verticale connecte l'ensemble via ::before sur les <li>.
 *
 * Dots SVG built-in : pin (localisation), boat (speedboat), anchor (ancre).
 * Les étapes ACF conservent le support FA class + image ACF.
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

        // ── Transport / zones ─────────────────────────────────────────────
        $this->start_controls_section('section_transport', [
            'label' => __('Transport & zones', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_transport', [
            'label'        => __('Afficher les zones de départ / arrivée', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        // ── Zone départ ───────────────────────────────────────────────────
        $this->add_control('heading_departure', [
            'label'     => __('Zone de départ', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_departure', [
            'label'     => __('Label départ', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Départ', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('departure_dot_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'pin'    => __('📍 Pin (localisation)', 'blacktenderscore'),
                'anchor' => __('⚓ Ancre', 'blacktenderscore'),
                'none'   => __('Dot simple', 'blacktenderscore'),
            ],
            'default'   => 'pin',
            'condition' => ['show_transport' => 'yes'],
        ]);

        // ── Hors-bord ─────────────────────────────────────────────────────
        $this->add_control('heading_outboard', [
            'label'     => __('Hors-bord', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_outboard', [
            'label'     => __('Label hors-bord aller', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Hors-bord', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_outboard_return', [
            'label'     => __('Label hors-bord retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Retour hors-bord', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('outboard_dot_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'boat'   => __('🚤 Bateau', 'blacktenderscore'),
                'anchor' => __('⚓ Ancre', 'blacktenderscore'),
                'none'   => __('Dot simple', 'blacktenderscore'),
            ],
            'default'   => 'boat',
            'condition' => ['show_transport' => 'yes'],
        ]);

        // ── Zone arrivée ──────────────────────────────────────────────────
        $this->add_control('heading_arrival', [
            'label'     => __('Zone d\'arrivée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_return', [
            'label'     => __('Label arrivée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Arrivée', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('arrival_dot_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'anchor' => __('⚓ Ancre', 'blacktenderscore'),
                'pin'    => __('📍 Pin (localisation)', 'blacktenderscore'),
                'none'   => __('Dot simple', 'blacktenderscore'),
            ],
            'default'   => 'anchor',
            'condition' => ['show_transport' => 'yes'],
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
            'selectors'  => [
                // La custom property --bt-itin-gap est consommée par ::before
                '{{WRAPPER}} .bt-itin__list' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-itin'       => '--bt-itin-gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('line_color', [
            'label'     => __('Couleur de la ligne', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__list > li::before' => 'background-color: {{VALUE}}'],
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_responsive_control('line_width', [
            'label'      => __('Épaisseur de la ligne', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__list > li::before' => 'width: {{SIZE}}{{UNIT}}'],
            'condition'  => ['connector' => 'line'],
        ]);

        // ── Dots étapes ACF ───────────────────────────────────────────────
        $this->add_control('heading_step_dots', [
            'label'     => __('Points — Étapes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('dot_color', [
            'label'     => __('Couleur du point', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot' => 'background-color: {{VALUE}}; border-color: {{VALUE}}; color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille du point', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 32]],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                // Aligne la ligne sur le centre du dot standard
                '{{WRAPPER}} .bt-itin__list > li::before' => 'left: calc({{SIZE}}{{UNIT}} / 2 - 1px)',
            ],
        ]);

        $this->add_responsive_control('dot_icon_size', [
            'label'      => __('Taille de l\'icône dans le point', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 28]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot--icon' => 'font-size: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('return_dot_color', [
            'label'     => __('Couleur point — étape retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--return .bt-itin__dot' => 'background-color: {{VALUE}}; border-color: {{VALUE}}; color: {{VALUE}}',
            ],
        ]);

        // ── Dots transport (SVG) ──────────────────────────────────────────
        $this->add_control('heading_transport_dots', [
            'label'     => __('Points SVG — Transport', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('transport_dot_color', [
            'label'     => __('Couleur icône SVG transport', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__dot--svg' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('transport_dot_size', [
            'label'      => __('Taille icône SVG transport', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 14, 'max' => 48]],
            'default'    => ['size' => 22, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__dot--svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Style — Texte étapes ──────────────────────────────────────────
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
            'selectors' => ['{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('return_step_bg', [
            'label'     => __('Fond étape retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--return .bt-itin__step-body' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'step_border',
            'selector' => '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body',
        ]);

        $this->add_responsive_control('step_radius', [
            'label'      => __('Border radius étape', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Zones transport ───────────────────────────────────────
        $this->start_controls_section('style_transport', [
            'label' => __('Style — Zones transport', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('transport_bg', [
            'label'     => __('Fond (tous blocs transport)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'transport_typo',
            'label'    => __('Typographie transport', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body',
        ]);

        $this->add_control('transport_color', [
            'label'     => __('Couleur texte transport', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('transport_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('transport_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'transport_border',
            'selector' => '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body',
        ]);

        $this->add_control('departure_bg', [
            'label'     => __('Override fond — Départ uniquement', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--departure .bt-itin__step-body' => 'background-color: {{VALUE}}'],
            'separator' => 'before',
        ]);

        $this->add_control('arrival_bg', [
            'label'     => __('Override fond — Arrivée uniquement', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step--arrival .bt-itin__step-body' => 'background-color: {{VALUE}}'],
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

        // Labels transport
        $lbl_dep     = esc_html($s['label_departure']       ?: __('Départ',          'blacktenderscore'));
        $lbl_out     = esc_html($s['label_outboard']        ?: __('Hors-bord',       'blacktenderscore'));
        $lbl_out_ret = esc_html($s['label_outboard_return'] ?: __('Retour hors-bord', 'blacktenderscore'));
        $lbl_arr     = esc_html($s['label_return']          ?: __('Arrivée',         'blacktenderscore'));

        // Icônes SVG transport
        $dep_icon = $s['departure_dot_icon']  ?: 'pin';
        $out_icon = $s['outboard_dot_icon']   ?: 'boat';
        $arr_icon = $s['arrival_dot_icon']    ?: 'anchor';

        // Champs transport ACF
        $departure_zone = $show_transport ? (string) get_field('exp_departure_zone',        $post_id) : '';
        $outboard       = $show_transport ? (int)    get_field('exp_outboard',               $post_id) : 0;
        $returning_zone = $show_transport ? (string) get_field('exp_returning_zone',         $post_id) : '';
        $returning_desc = $show_transport ? (string) get_field('exp_returning_description',  $post_id) : '';

        echo '<div class="bt-itin' . $connector_cls . '">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-itin__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        // ── Timeline unifiée — tout dans un seul <ol> ─────────────────────
        echo '<ol class="bt-itin__list">';

        // [1] Zone de départ
        if ($show_transport && $departure_zone !== '') {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--departure">';
            echo $this->get_dot_html($dep_icon);
            echo '<div class="bt-itin__step-body">';
            echo '<span class="bt-itin__transport-label">' . $lbl_dep . '</span>';
            echo '<strong class="bt-itin__step-title">' . esc_html($departure_zone) . '</strong>';
            echo '</div></li>';
        }

        // [2] Hors-bord aller
        if ($show_transport && $outboard > 0) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--outboard">';
            echo $this->get_dot_html($out_icon);
            echo '<div class="bt-itin__step-body">';
            /* translators: %s = label, %d = minutes */
            echo '<strong class="bt-itin__step-title">' . esc_html(sprintf('%s — %d min', $lbl_out, $outboard)) . '</strong>';
            echo '</div></li>';
        }

        // [3] Étapes ACF du repeater
        foreach ($rows as $row) {
            $time      = $row['step_time']   ?? '';
            $title     = $row['step_title']  ?? '';
            $desc      = $row['step_desc']   ?? '';
            $duration  = isset($row['step_timethezone']) ? (int) $row['step_timethezone'] : 0;
            $icon_raw  = $row['step_icon']   ?? null;
            $is_return = !empty($row['step_is_return']);

            $step_cls = 'bt-itin__step' . ($is_return ? ' bt-itin__step--return' : '');
            echo '<li class="' . esc_attr($step_cls) . '">';

            // Dot : image ACF | classe FA | dot simple
            if (is_array($icon_raw) && !empty($icon_raw['url'])) {
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true">';
                echo '<img src="' . esc_url($icon_raw['url']) . '" alt="' . esc_attr($icon_raw['alt'] ?? '') . '" loading="lazy" class="bt-itin__dot-img">';
                echo '</span>';
            } elseif (is_string($icon_raw) && trim($icon_raw) !== '') {
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true"><i class="' . esc_attr(trim($icon_raw)) . '"></i></span>';
            } else {
                echo '<span class="bt-itin__dot" aria-hidden="true"></span>';
            }

            echo '<div class="bt-itin__step-body">';

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

        // [4] Hors-bord retour
        if ($show_transport && $outboard > 0) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--outboard-return">';
            echo $this->get_dot_html($out_icon);
            echo '<div class="bt-itin__step-body">';
            /* translators: %s = label, %d = minutes */
            echo '<strong class="bt-itin__step-title">' . esc_html(sprintf('%s — %d min', $lbl_out_ret, $outboard)) . '</strong>';
            echo '</div></li>';
        }

        // [5] Zone d'arrivée
        if ($show_transport && ($returning_zone !== '' || $returning_desc !== '')) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--arrival">';
            echo $this->get_dot_html($arr_icon);
            echo '<div class="bt-itin__step-body">';
            echo '<span class="bt-itin__transport-label">' . $lbl_arr . '</span>';
            if ($returning_zone !== '') {
                echo '<strong class="bt-itin__step-title">' . esc_html($returning_zone) . '</strong>';
            }
            if ($returning_desc !== '') {
                echo '<p class="bt-itin__step-desc">' . esc_html($returning_desc) . '</p>';
            }
            echo '</div></li>';
        }

        echo '</ol>';
        echo '</div>';
    }

    // ── SVG Helpers ───────────────────────────────────────────────────────────

    /**
     * Retourne un SVG inline pour les dots transport.
     * `currentColor` → couleur pilotée par le control Elementor transport_dot_color.
     */
    private function get_svg(string $name): string {
        return match ($name) {
            'pin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>',
            'boat' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20 21c-1.39 0-2.78-.47-4-1.32-2.44 1.71-5.56 1.71-8 0C6.78 20.53 5.39 21 4 21H2v2h2c1.38 0 2.74-.35 4-.99 2.52 1.29 5.48 1.29 8 0 1.26.65 2.62.99 4 .99h2v-2h-2zM3.95 19H4c1.6 0 3.02-.88 4-2 .98 1.12 2.4 2 4 2s3.02-.88 4-2c.98 1.12 2.4 2 4 2h.05l1.89-6.68c.08-.26.06-.54-.06-.78s-.34-.42-.6-.5L20 10.62V6c0-1.1-.9-2-2-2h-3V1H9v3H6c-1.1 0-2 .9-2 2v4.62l-1.29.42c-.26.08-.48.26-.6.5s-.14.52-.06.78L3.95 19z"/></svg>',
            'anchor' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17 14h-4v7.61c3.37-.49 6.26-2.78 7.73-5.88L17 14zm-6 0H7l-3.73 1.73C4.74 18.83 7.63 21.12 11 21.61V14zm7-5h-5V7.72A3 3 0 1 0 9 7.72V9H4a1 1 0 0 0 0 2h5v2h6v-2h5a1 1 0 0 0 0-2zM12 5a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>',
            default => '',
        };
    }

    private function get_dot_html(string $svg_name): string {
        $svg = $this->get_svg($svg_name);
        if ($svg !== '') {
            return '<span class="bt-itin__dot bt-itin__dot--svg" aria-hidden="true">' . $svg . '</span>';
        }
        return '<span class="bt-itin__dot" aria-hidden="true"></span>';
    }
}
