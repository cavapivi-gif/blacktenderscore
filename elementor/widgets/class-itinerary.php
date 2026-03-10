<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Programme / Itinéraire v3.
 *
 * Architecture : un seul <ol> contenant TOUT —
 * zone départ, hors-bord aller, étapes ACF, hors-bord retour, zone arrivée.
 *
 * Icônes transport : ICONS control Elementor → Icons_Manager::render_icon()
 *   → supporte Font Awesome ET SVG uploadé (rendu inline, pas <img>).
 *
 * Carte interactive : Google Maps Embed API (iframe, zéro JS).
 * Clé API : Elementor → Réglages → Intégrations → Google Maps.
 * Champs ACF nécessaires pour la carte :
 *   • Repeater sub-field : step_coords  (type ACF : Google Map)
 *                          OU step_lat + step_lng (type : Nombre)
 *   • Post fields        : exp_departure_coords, exp_arriving_coords (Google Map)
 */
class Itinerary extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-itinerary'; }
    public function get_title():      string { return 'BT — Programme / Itinéraire'; }
    public function get_icon():       string { return 'eicon-time-line'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['itinéraire', 'programme', 'timeline', 'étapes', 'carte', 'map', 'bt']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->section_content();
        $this->section_transport();
        $this->section_map_content();
        $this->section_style_timeline();
        $this->section_style_text();
        $this->section_style_transport();
        $this->section_style_map();
    }

    // ── Section Contenu ───────────────────────────────────────────────────────

    private function section_content(): void {
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
    }

    // ── Section Transport ─────────────────────────────────────────────────────

    private function section_transport(): void {
        $this->start_controls_section('section_transport', [
            'label' => __('Transport & zones', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_transport', [
            'label'        => __('Afficher départ / transport / arrivée', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        // ── Départ ────────────────────────────────────────────────────────────
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

        // ICONS control → FA picker + SVG uploader → Icons_Manager::render_icon()
        $this->add_control('departure_dot_icon', [
            'label'       => __('Icône départ', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid'],
            'skin'        => 'inline',
            'label_block' => false,
            'condition'   => ['show_transport' => 'yes'],
        ]);

        // ── Hors-bord ─────────────────────────────────────────────────────────
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
            'label'       => __('Icône hors-bord', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-ship', 'library' => 'fa-solid'],
            'skin'        => 'inline',
            'label_block' => false,
            'condition'   => ['show_transport' => 'yes'],
        ]);

        // ── Arrivée ───────────────────────────────────────────────────────────
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
            'label'       => __('Icône arrivée', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-anchor', 'library' => 'fa-solid'],
            'skin'        => 'inline',
            'label_block' => false,
            'condition'   => ['show_transport' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    // ── Section Carte ─────────────────────────────────────────────────────────

    private function section_map_content(): void {
        $this->start_controls_section('section_map', [
            'label' => __('Carte interactive', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_map', [
            'label'        => __('Afficher la carte', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('map_acf_notice', [
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => implode('', [
                '<strong>', __('Champs ACF à créer :', 'blacktenderscore'), '</strong><br>',
                '• Repeater → <code>step_coords</code> <em>(type : Google Map)</em><br>',
                '&nbsp;&nbsp;ou <code>step_lat</code> + <code>step_lng</code> <em>(Nombre)</em><br>',
                '• Post → <code>exp_departure_coords</code><br>',
                '• Post → <code>exp_arriving_coords</code><br>',
                '<em>', __('(type : Google Map)', 'blacktenderscore'), '</em>',
            ]),
            'content_classes' => 'elementor-descriptor',
            'condition'       => ['show_map' => 'yes'],
        ]);

        $this->add_control('map_position', [
            'label'     => __('Position de la carte', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'below'      => __('Sous la timeline', 'blacktenderscore'),
                'above'      => __('Au-dessus de la timeline', 'blacktenderscore'),
                'side-right' => __('À droite (50/50)', 'blacktenderscore'),
                'side-left'  => __('À gauche (50/50)', 'blacktenderscore'),
            ],
            'default'   => 'below',
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->add_responsive_control('map_col_ratio', [
            'label'       => __('Largeur carte (%)', 'blacktenderscore'),
            'description' => __('Uniquement en mode côte-à-côte. Sur mobile → pleine largeur.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SLIDER,
            'size_units'  => ['%'],
            'range'       => ['%' => ['min' => 25, 'max' => 75]],
            'default'     => ['size' => 50, 'unit' => '%'],
            'selectors'   => [
                '{{WRAPPER}} .bt-itin__layout--side' =>
                    '--bt-itin-map-col: {{SIZE}}{{UNIT}}',
            ],
            'condition' => [
                'show_map'     => 'yes',
                'map_position' => ['side-right', 'side-left'],
            ],
        ]);

        $this->add_responsive_control('map_height', [
            'label'      => __('Hauteur de la carte', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range'      => ['px' => ['min' => 150, 'max' => 900], 'vh' => ['min' => 20, 'max' => 80]],
            'default'    => ['size' => 400, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__map' => 'height: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_map' => 'yes'],
        ]);

        $this->add_responsive_control('map_gap', [
            'label'      => __('Espace timeline ↔ carte', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__layout--side' => 'gap: {{SIZE}}{{UNIT}}',
            ],
            'condition' => [
                'show_map'     => 'yes',
                'map_position' => ['side-right', 'side-left'],
            ],
        ]);

        $this->add_control('map_type', [
            'label'     => __('Type de vue', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'roadmap'   => __('Plan (roadmap)', 'blacktenderscore'),
                'satellite' => __('Satellite', 'blacktenderscore'),
            ],
            'default'   => 'roadmap',
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Timeline ──────────────────────────────────────────────

    private function section_style_timeline(): void {
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
                '{{WRAPPER}} .bt-itin__list' => 'gap: {{SIZE}}{{UNIT}}',
                // --bt-itin-gap consommé par ::before bottom: calc(-1 * var(--bt-itin-gap))
                '{{WRAPPER}} .bt-itin'       => '--bt-itin-gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        // ── Ligne de connexion ────────────────────────────────────────────────
        $this->add_control('heading_connector', [
            'label'     => __('Ligne de connexion', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_control('line_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin:not(.bt-itin--no-connector) .bt-itin__list > li:not(:last-child)::before' =>
                    'background-color: {{VALUE}}',
            ],
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_responsive_control('line_width', [
            'label'      => __('Épaisseur', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin:not(.bt-itin--no-connector) .bt-itin__list > li:not(:last-child)::before' =>
                    'width: {{SIZE}}{{UNIT}}',
            ],
            'condition'  => ['connector' => 'line'],
        ]);

        // ── Dots — Étapes ACF ─────────────────────────────────────────────────
        $this->add_control('heading_step_dots', [
            'label'     => __('Points — Étapes ACF', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('dot_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot' =>
                    'color: {{VALUE}}; background-color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 6, 'max' => 32]],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot' =>
                    'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('dot_icon_size', [
            'label'      => __('Taille icône dans le point', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 28]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot--icon' =>
                    'font-size: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('dot_gap', [
            'label'      => __('Espace dot ↔ contenu', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 48]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('return_dot_color', [
            'label'     => __('Couleur point — étape retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--return .bt-itin__dot' =>
                    'color: {{VALUE}}; background-color: {{VALUE}}',
            ],
        ]);

        // ── Dots — Transport (ICONS / SVG) ────────────────────────────────────
        $this->add_control('heading_transport_dots', [
            'label'     => __('Points — Transport (icônes)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('transport_dot_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__dot--transport' =>
                    'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('transport_dot_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 12, 'max' => 48]],
            'default'    => ['size' => 22, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__dot--transport' =>
                    'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: {{SIZE}}{{UNIT}}',
                // Aligne le connecteur sur le centre du dot transport
                '{{WRAPPER}} .bt-itin' =>
                    '--bt-itin-dot-center: calc({{SIZE}}{{UNIT}} / 2 - 1px)',
            ],
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Texte étapes ──────────────────────────────────────────

    private function section_style_text(): void {
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
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' =>
                    'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('step_content_indent', [
            'label'       => __('Décalage hiérarchique', 'blacktenderscore'),
            'description' => __('Indente le contenu des étapes ACF pour créer une hiérarchie visuelle sous les blocs transport.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 48]],
            'default'     => ['size' => 0, 'unit' => 'px'],
            'selectors'   => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' =>
                    'margin-left: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('step_bg', [
            'label'     => __('Fond des étapes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' =>
                    'background-color: {{VALUE}}',
            ],
            'separator' => 'before',
        ]);

        $this->add_control('return_step_bg', [
            'label'     => __('Fond — étape retour', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--return .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'step_border',
            'selector' => '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body',
        ]);

        $this->add_responsive_control('step_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' =>
                    'border-radius: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'step_shadow',
            'selector' => '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body',
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Zones transport ───────────────────────────────────────

    private function section_style_transport(): void {
        $this->start_controls_section('style_transport', [
            'label' => __('Style — Zones transport', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('transport_bg', [
            'label'     => __('Fond (tous blocs transport)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'transport_typo',
            'label'    => __('Typographie transport', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body',
        ]);

        $this->add_control('transport_color', [
            'label'     => __('Couleur texte transport', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('transport_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' =>
                    'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('transport_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body' => 'border-radius: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'transport_border',
            'selector' => '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'transport_shadow',
            'selector' => '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body',
        ]);

        $this->add_control('departure_bg', [
            'label'     => __('Override fond — Départ uniquement', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--departure .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
            'separator' => 'before',
        ]);

        $this->add_control('arrival_bg', [
            'label'     => __('Override fond — Arrivée uniquement', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--arrival .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('transport_label_color', [
            'label'     => __('Couleur label (DÉPART / ARRIVÉE)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__transport-label' => 'color: {{VALUE}}',
            ],
            'separator' => 'before',
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Carte ─────────────────────────────────────────────────

    private function section_style_map(): void {
        $this->start_controls_section('style_map', [
            'label'     => __('Style — Carte', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->add_responsive_control('map_margin', [
            'label'      => __('Marge', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__map-wrap' =>
                    'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('map_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__map'      => 'border-radius: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-itin__map-wrap' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'map_border',
            'selector' => '{{WRAPPER}} .bt-itin__map-wrap',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'map_shadow',
            'selector' => '{{WRAPPER}} .bt-itin__map-wrap',
        ]);

        $this->end_controls_section();
    }

    // ── Render ────────────────────────────────────────────────────────────────

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
                echo '<p class="bt-widget-placeholder">Aucune étape dans <code>' . esc_html($field_name) . '</code>.</p>';
            }
            return;
        }

        $tag            = esc_attr($s['title_tag'] ?: 'h3');
        $show_time      = ($s['show_time']        ?? '') === 'yes';
        $show_duration  = ($s['show_duration']    ?? '') === 'yes';
        $show_desc      = ($s['show_description'] ?? '') === 'yes';
        $show_transport = ($s['show_transport']   ?? '') === 'yes';
        $show_map       = ($s['show_map']         ?? '') === 'yes';
        $map_position   = $s['map_position'] ?? 'below';
        $connector_cls  = ($s['connector'] ?? 'line') === 'none' ? ' bt-itin--no-connector' : '';

        // Labels transport
        $lbl_dep     = esc_html($s['label_departure']       ?: __('Départ',          'blacktenderscore'));
        $lbl_out     = esc_html($s['label_outboard']        ?: __('Hors-bord',       'blacktenderscore'));
        $lbl_out_ret = esc_html($s['label_outboard_return'] ?: __('Retour hors-bord', 'blacktenderscore'));
        $lbl_arr     = esc_html($s['label_return']          ?: __('Arrivée',         'blacktenderscore'));

        // Champs transport ACF
        $departure_zone = $show_transport ? (string) get_field('exp_departure_zone',       $post_id) : '';
        $outboard       = $show_transport ? (int)    get_field('exp_outboard',             $post_id) : 0;
        $returning_zone = $show_transport ? (string) get_field('exp_returning_zone',       $post_id) : '';
        $returning_desc = $show_transport ? (string) get_field('exp_returning_description', $post_id) : '';

        $is_side = str_starts_with($map_position, 'side-');

        echo '<div class="bt-itin' . esc_attr($connector_cls) . '">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-itin__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        // Carte au-dessus (stacked)
        if ($show_map && $map_position === 'above') {
            $this->render_map($rows, $departure_zone, $returning_zone, $s, $post_id);
        }

        // Layout côte-à-côte : wrapper grid
        if ($show_map && $is_side) {
            $side_cls = $map_position === 'side-left' ? ' bt-itin__layout--map-left' : '';
            echo '<div class="bt-itin__layout bt-itin__layout--side' . esc_attr($side_cls) . '">';
        }

        // ── Colonne timeline ──────────────────────────────────────────────────
        if ($show_map && $is_side) {
            echo '<div class="bt-itin__col-timeline">';
        }

        echo '<ol class="bt-itin__list">';

        // [1] Zone de départ
        if ($show_transport && $departure_zone !== '') {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--departure">';
            $this->render_transport_dot($s['departure_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<span class="bt-itin__transport-label">' . $lbl_dep . '</span>';
            echo '<strong class="bt-itin__step-title">' . esc_html($departure_zone) . '</strong>';
            echo '</div></li>';
        }

        // [2] Hors-bord aller
        if ($show_transport && $outboard > 0) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--outboard">';
            $this->render_transport_dot($s['outboard_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            /* translators: %1$s = label, %2$d = minutes */
            echo '<strong class="bt-itin__step-title">' . esc_html(sprintf(__('%1$s — %2$d min', 'blacktenderscore'), $lbl_out, $outboard)) . '</strong>';
            echo '</div></li>';
        }

        // [3] Étapes ACF repeater
        foreach ($rows as $row) {
            $time      = $row['step_time']       ?? '';
            $title     = $row['step_title']      ?? '';
            $desc      = $row['step_desc']       ?? '';
            $duration  = isset($row['step_timethezone']) ? (int) $row['step_timethezone'] : 0;
            $icon_raw  = $row['step_icon']       ?? null;
            $is_return = !empty($row['step_is_return']);

            $step_cls = 'bt-itin__step' . ($is_return ? ' bt-itin__step--return' : '');
            echo '<li class="' . esc_attr($step_cls) . '">';

            // Dot : image ACF | classe FA | dot simple
            if (is_array($icon_raw) && !empty($icon_raw['url']) && (isset($icon_raw['sizes']) || isset($icon_raw['filename']))) {
                // Image ACF (discriminateur : présence de 'sizes' ou 'filename')
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true">';
                echo '<img src="' . esc_url($icon_raw['url']) . '" alt="' . esc_attr($icon_raw['alt'] ?? '') . '" loading="lazy" class="bt-itin__dot-img">';
                echo '</span>';
            } elseif (is_string($icon_raw) && trim($icon_raw) !== '') {
                // Classe FA (ex: "fas fa-anchor") entrée en texte libre dans ACF
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
                echo '<p class="bt-itin__step-desc">' . wp_kses_post($desc) . '</p>';
            }

            echo '</div></li>';
        }

        // [4] Hors-bord retour
        if ($show_transport && $outboard > 0) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--outboard-return">';
            $this->render_transport_dot($s['outboard_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<strong class="bt-itin__step-title">' . esc_html(sprintf(__('%1$s — %2$d min', 'blacktenderscore'), $lbl_out_ret, $outboard)) . '</strong>';
            echo '</div></li>';
        }

        // [5] Zone d'arrivée
        if ($show_transport && ($returning_zone !== '' || $returning_desc !== '')) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--arrival">';
            $this->render_transport_dot($s['arrival_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<span class="bt-itin__transport-label">' . $lbl_arr . '</span>';
            if ($returning_zone !== '') {
                echo '<strong class="bt-itin__step-title">' . esc_html($returning_zone) . '</strong>';
            }
            if ($returning_desc !== '') {
                echo '<p class="bt-itin__step-desc">' . wp_kses_post($returning_desc) . '</p>';
            }
            echo '</div></li>';
        }

        echo '</ol>';

        // Ferme la colonne timeline (mode side)
        if ($show_map && $is_side) {
            echo '</div>'; // .bt-itin__col-timeline
        }

        // Carte côte-à-côte ou en dessous
        if ($show_map && ($is_side || $map_position === 'below')) {
            if ($show_map && $is_side) {
                echo '<div class="bt-itin__col-map">';
            }
            $this->render_map($rows, $departure_zone, $returning_zone, $s, $post_id);
            if ($show_map && $is_side) {
                echo '</div>'; // .bt-itin__col-map
            }
        }

        // Ferme le layout grid (mode side)
        if ($show_map && $is_side) {
            echo '</div>'; // .bt-itin__layout
        }

        echo '</div>'; // .bt-itin
    }

    // ── Helpers privés ────────────────────────────────────────────────────────

    /**
     * Rend le dot d'une étape transport.
     *
     * Utilise Icons_Manager::render_icon() — la méthode officielle Elementor.
     * • FA icon  → <i class="fas fa-anchor"> (police FontAwesome)
     * • SVG uploadé → <svg>...</svg> inline (pas de <img>)
     *
     * @param array $icon_setting Valeur du control ICONS: ['value' => ..., 'library' => ...]
     */
    private function render_transport_dot(array $icon_setting): void {
        if (empty($icon_setting['value'])) {
            echo '<span class="bt-itin__dot" aria-hidden="true"></span>';
            return;
        }

        echo '<span class="bt-itin__dot bt-itin__dot--transport" aria-hidden="true">';
        \Elementor\Icons_Manager::render_icon($icon_setting, ['aria-hidden' => 'true']);
        echo '</span>';
    }

    /**
     * Rend la carte Google Maps (iframe Embed API).
     * Supporte deux formats de coordonnées ACF :
     *   1. ACF Google Map field → $row['step_coords'] = ['lat' => ..., 'lng' => ...]
     *   2. Deux champs Nombre   → $row['step_lat'] + $row['step_lng']
     *
     * Clé API : Elementor → Réglages → Intégrations → Google Maps.
     */
    private function render_map(array $rows, string $departure_zone, string $returning_zone, array $s, int $post_id): void {
        $points = [];

        // Départ
        $dep = get_field('exp_departure_coords', $post_id);
        if (is_array($dep) && !empty($dep['lat']) && !empty($dep['lng'])) {
            $points[] = [(float) $dep['lat'], (float) $dep['lng']];
        }

        // Étapes
        foreach ($rows as $row) {
            $coords = $row['step_coords'] ?? null;
            if (is_array($coords) && !empty($coords['lat'])) {
                $points[] = [(float) $coords['lat'], (float) $coords['lng']];
            } elseif (!empty($row['step_lat']) && !empty($row['step_lng'])) {
                $points[] = [(float) $row['step_lat'], (float) $row['step_lng']];
            }
        }

        // Arrivée
        $arr = get_field('exp_arriving_coords', $post_id);
        if (is_array($arr) && !empty($arr['lat']) && !empty($arr['lng'])) {
            $points[] = [(float) $arr['lat'], (float) $arr['lng']];
        }

        if (empty($points)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="bt-itin__map-wrap"><p class="bt-widget-placeholder">';
                echo __('Carte : aucune coordonnée GPS trouvée. Créez les champs ACF : <code>step_coords</code> (repeater), <code>exp_departure_coords</code>, <code>exp_arriving_coords</code> (type : Google Map).', 'blacktenderscore');
                echo '</p></div>';
            }
            return;
        }

        // Clé API : Elementor → Réglages → Intégrations → Google Maps
        $api_key = (string) get_option('elementor_google_maps_api_key', '');
        $maptype = $s['map_type'] ?? 'roadmap';

        if (empty($api_key)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<div class="bt-itin__map-wrap"><p class="bt-widget-placeholder">';
                echo __('Carte : clé API manquante. Renseignez-la dans <strong>Elementor → Réglages → Intégrations → Google Maps</strong>.', 'blacktenderscore');
                echo '</p></div>';
            }
            return;
        }

        // Construction URL embed
        $base_args = ['key' => $api_key, 'language' => 'fr', 'maptype' => $maptype];

        if (count($points) === 1) {
            $url = add_query_arg(
                array_merge($base_args, ['q' => $points[0][0] . ',' . $points[0][1]]),
                'https://www.google.com/maps/embed/v1/place'
            );
        } else {
            $middle = array_slice($points, 1, -1);
            $args   = array_merge($base_args, [
                'origin'      => $points[0][0] . ',' . $points[0][1],
                'destination' => end($points)[0] . ',' . end($points)[1],
                'mode'        => 'driving',
            ]);
            if ($middle) {
                $args['waypoints'] = implode('|', array_map(fn($p) => $p[0] . ',' . $p[1], $middle));
            }
            $url = add_query_arg($args, 'https://www.google.com/maps/embed/v1/directions');
        }

        echo '<div class="bt-itin__map-wrap">';
        printf(
            '<iframe class="bt-itin__map" src="%s" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>',
            esc_url($url)
        );
        echo '</div>';
    }
}
