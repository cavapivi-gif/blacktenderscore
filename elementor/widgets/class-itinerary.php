<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Programme / Itinéraire v4.
 *
 * Architecture : un seul <ol> contenant TOUT —
 * zone départ, transport aller, étapes ACF, transport retour, zone arrivée.
 *
 * Nouveautés v4 (vs v3) :
 *   - Architecture : utilise register_section_title_controls/style + register_typography_section
 *     + register_box_style (BtSharedControls) au lieu de Group_Control_* manuels
 *   - Badges de type par étape (step_type : activity/transfer/free/meal)
 *   - Étape facultative (step_optional + step_fee)
 *   - Durée formatée via format_duration() : 90 → "1h30"
 *   - Durée totale des étapes affichable sous le titre
 *   - Mode accordéon (none/closed/open) pour les descriptions
 *   - Map↔timeline : data-lat/data-lng sur <li> + flyTo via bt-itinerary.js
 *   - Marqueurs départ (p.start) et arrivée (p.end) distincts sur Leaflet
 *   - exp_outboard_return : durée retour indépendante de l'aller
 *   - Bug fix : returning_desc respecte show_description
 *   - Bug fix : var(--var--beige-on-bg, #F5F0E8) fallback dans bt-itinerary.css
 *
 * ACF sub-fields repeater attendus (champ défini par le control 'acf_field') :
 *   step_time        text        — heure/moment (ex: "10:30")
 *   step_title       text        — titre de l'étape
 *   step_desc        wysiwyg     — description (optionnel)
 *   step_timethezone number      — durée en minutes (NE PAS RENOMMER : typo historique du champ)
 *   step_icon        mixed       — icône FA (string) ou image ACF (array)
 *   step_is_return   true_false  — marque les étapes de retour
 *   step_coords      google_map  — coordonnées GPS pour la carte Leaflet
 *   step_type        select      — activity|transfer|free|meal (optionnel, pour badges)
 *   step_optional    true_false  — étape facultative (optionnel)
 *   step_fee         text        — supplément tarifaire ex: "+15€" (optionnel)
 *
 * ACF post fields attendus :
 *   exp_departure_zone        text        — nom du lieu de départ
 *   exp_departure_coords      google_map  — coords GPS du départ (marqueur carte)
 *   exp_outboard              number      — durée transport aller (min)
 *   exp_outboard_return       number      — durée transport retour (min, fallback sur exp_outboard)
 *   exp_returning_zone        text        — nom du lieu d'arrivée
 *   exp_arriving_coords       google_map  — coords GPS de l'arrivée (marqueur carte)
 *   exp_returning_description wysiwyg     — description de l'arrivée
 */
class Itinerary extends AbstractBtWidget {
    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-itinerary',
            'title'    => 'BT — Programme / Itinéraire',
            'icon'     => 'eicon-time-line',
            'keywords' => ['itinéraire', 'programme', 'timeline', 'étapes', 'carte', 'map', 'bt'],
            'css'      => ['bt-itinerary'],
            'js'       => ['bt-itinerary'],
        ];
    }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->section_content();
        $this->section_transport();
        $this->section_map_content();

        // Style tab — SharedControls
        $this->register_section_title_style('{{WRAPPER}} .bt-itin__title');
        $this->section_style_timeline();
        $this->register_typography_section('itin_time',      __("Heure / moment",    'blacktenderscore'), '{{WRAPPER}} .bt-itin__time');
        $this->register_typography_section('itin_title',     __("Titre d'étape",     'blacktenderscore'), '{{WRAPPER}} .bt-itin__step-title');
        $this->register_typography_section('itin_desc',      __('Description',       'blacktenderscore'), '{{WRAPPER}} .bt-itin__step-desc');
        $this->register_box_style(
            'step',
            __('Style — Corps étapes', 'blacktenderscore'),
            '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body'
        );
        $this->register_typography_section('transport_text', __('Texte transport',        'blacktenderscore'), '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body');
        $this->register_box_style(
            'transport_body',
            __('Style — Zones transport', 'blacktenderscore'),
            '{{WRAPPER}} .bt-itin__step--transport .bt-itin__step-body',
            ['padding' => 10]
        );
        $this->section_style_overrides();
        $this->section_style_badges();
        $this->section_style_map();
    }

    // ── Section Contenu ───────────────────────────────────────────────────────

    private function section_content(): void {
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'exp_itinerary',
        ]);

        // register_section_title_controls génère 'section_title' + 'section_title_tag'
        $this->register_section_title_controls(['title' => __('Programme', 'blacktenderscore'), 'tag' => 'h3']);

        $this->add_control('show_time', [
            'label'        => __("Afficher l'heure / moment", 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
        ]);

        $this->add_control('show_duration', [
            'label'        => __("Afficher la durée de l'étape", 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_total_duration', [
            'label'        => __('Afficher la durée totale', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('show_type_badge', [
            'label'        => __('Afficher les badges de type', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('connector', [
            'label'     => __('Connecteur timeline', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'line' => __('Ligne verticale', 'blacktenderscore'),
                'none' => __('Aucun', 'blacktenderscore'),
            ],
            'default'   => 'line',
            'separator' => 'before',
        ]);

        $this->add_control('accordion_default', [
            'label'   => __('Mode accordéon', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'none'   => __('Aucun (tout visible)', 'blacktenderscore'),
                'closed' => __('Fermé par défaut', 'blacktenderscore'),
                'open'   => __('Ouvert par défaut', 'blacktenderscore'),
            ],
            'default' => 'none',
        ]);

        $this->end_controls_section();
    }

    // ── Section Transport ─────────────────────────────────────────────────────

    private function section_transport(): void {
        $this->start_controls_section('section_transport', [
            'label' => __('Transport & zones', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_transport', [
            'label'        => __('Afficher départ / transport / arrivée', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('transport_type', [
            'label'     => __('Type de transport', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'outboard' => __('Hors-bord', 'blacktenderscore'),
                'walk'     => __('Marche / transfert à pied', 'blacktenderscore'),
                'bus'      => __('Bus / navette', 'blacktenderscore'),
                'transfer' => __('Transfert générique', 'blacktenderscore'),
            ],
            'default'   => 'outboard',
            'condition' => ['show_transport' => 'yes'],
        ]);

        // ── Départ ────────────────────────────────────────────────────────────
        $this->add_control('heading_departure', [
            'label'     => __('Zone de départ', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_departure', [
            'label'     => __('Label départ', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Départ', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('departure_dot_icon', [
            'label'       => __('Icône départ', 'blacktenderscore'),
            'type'        => Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-map-marker-alt', 'library' => 'fa-solid'],
            'skin'        => 'inline',
            'label_block' => false,
            'condition'   => ['show_transport' => 'yes'],
        ]);

        // ── Transport aller ───────────────────────────────────────────────────
        $this->add_control('heading_outboard', [
            'label'     => __('Transport aller', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_outboard', [
            'label'     => __('Label transport aller', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Hors-bord', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_outboard_return', [
            'label'     => __('Label transport retour', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Retour hors-bord', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('outboard_dot_icon', [
            'label'       => __('Icône transport', 'blacktenderscore'),
            'type'        => Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-ship', 'library' => 'fa-solid'],
            'skin'        => 'inline',
            'label_block' => false,
            'condition'   => ['show_transport' => 'yes'],
        ]);

        // ── Arrivée ───────────────────────────────────────────────────────────
        $this->add_control('heading_arrival', [
            'label'     => __("Zone d'arrivée", 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('label_return', [
            'label'     => __('Label arrivée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Arrivée', 'blacktenderscore'),
            'condition' => ['show_transport' => 'yes'],
        ]);

        $this->add_control('arrival_dot_icon', [
            'label'       => __('Icône arrivée', 'blacktenderscore'),
            'type'        => Controls_Manager::ICONS,
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
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_map', [
            'label'        => __('Afficher la carte', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('map_engine', [
            'label'     => __('Moteur de carte', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'leaflet'    => __('Leaflet / OpenStreetMap (interactif, sans clé API)', 'blacktenderscore'),
                'static_api' => __('Google Maps Static (image, clé API requise)', 'blacktenderscore'),
            ],
            'default'   => 'leaflet',
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->add_control('map_line_color', [
            'label'     => __('Couleur de la route', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#0066cc',
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->add_control('map_acf_notice', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => implode('', [
                '<strong>', __('Champs ACF pour la carte :', 'blacktenderscore'), '</strong><br>',
                '• Repeater → <code>step_coords</code> <em>(type : Google Map)</em><br>',
                '• Post → <code>exp_departure_coords</code> <em>(départ)</em><br>',
                '• Post → <code>exp_arriving_coords</code> <em>(arrivée)</em><br>',
                '<em>', __('(type : Google Map)', 'blacktenderscore'), '</em>',
            ]),
            'content_classes' => 'elementor-descriptor',
            'condition'       => ['show_map' => 'yes'],
        ]);

        $this->add_control('map_position', [
            'label'     => __('Position de la carte', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
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
            'description' => __('Uniquement en mode côte-à-côte.', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['%'],
            'range'       => ['%' => ['min' => 25, 'max' => 75]],
            'default'     => ['size' => 50, 'unit' => '%'],
            'selectors'   => [
                '{{WRAPPER}} .bt-itin__layout--side' => '--bt-itin-map-col: {{SIZE}}{{UNIT}}',
            ],
            'condition' => [
                'show_map'     => 'yes',
                'map_position' => ['side-right', 'side-left'],
            ],
        ]);

        $this->add_responsive_control('map_height', [
            'label'      => __('Hauteur de la carte', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range'      => ['px' => ['min' => 150, 'max' => 900], 'vh' => ['min' => 20, 'max' => 80]],
            'default'    => ['size' => 400, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__map' => 'height: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_map' => 'yes'],
        ]);

        $this->add_responsive_control('map_gap', [
            'label'      => __('Espace timeline ↔ carte', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__layout--side' => 'gap: {{SIZE}}{{UNIT}}'],
            'condition'  => [
                'show_map'     => 'yes',
                'map_position' => ['side-right', 'side-left'],
            ],
        ]);

        $this->add_control('map_type', [
            'label'     => __('Type de vue', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'roadmap'   => __('Plan (roadmap)', 'blacktenderscore'),
                'satellite' => __('Satellite', 'blacktenderscore'),
            ],
            'default'   => 'roadmap',
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Timeline (dots + connecteur) ───────────────────────────

    private function section_style_timeline(): void {
        $this->start_controls_section('style_timeline', [
            'label' => __('Style — Timeline', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('steps_gap', [
            'label'      => __('Espacement entre étapes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 80]],
            'default'    => ['size' => 32, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__list' => 'gap: {{SIZE}}{{UNIT}}',
                // CSS var consommée par ::before bottom: calc(-1 * var(--bt-itin-gap))
                '{{WRAPPER}} .bt-itin'       => '--bt-itin-gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('step_content_indent', [
            'label'       => __('Décalage hiérarchique étapes', 'blacktenderscore'),
            'description' => __('Indente les étapes ACF sous les blocs transport.', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 48]],
            'default'     => ['size' => 0, 'unit' => 'px'],
            'selectors'   => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__step-body' =>
                    'margin-left: {{SIZE}}{{UNIT}}',
            ],
        ]);

        // ── Ligne de connexion ────────────────────────────────────────────────
        $this->add_control('heading_connector', [
            'label'     => __('Ligne de connexion', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_control('line_color', [
            'label'     => __('Couleur (étapes aller)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin' => '--bt-itin-line-color: {{VALUE}}'],
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_control('return_line_color', [
            'label'       => __('Couleur — étapes retour', 'blacktenderscore'),
            'description' => __('Si vide, reprend la couleur aller.', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'selectors'   => ['{{WRAPPER}} .bt-itin' => '--bt-itin-return-line-color: {{VALUE}}'],
            'condition'   => ['connector' => 'line'],
        ]);

        $this->add_responsive_control('line_width', [
            'label'      => __('Épaisseur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
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
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('dot_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step:not(.bt-itin__step--transport) .bt-itin__dot' =>
                    'color: {{VALUE}}; background-color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
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
            'type'       => Controls_Manager::SLIDER,
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
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 48]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('step_dot_icon', [
            'label'       => __('Icône par défaut des étapes', 'blacktenderscore'),
            'description' => __("Appliquée aux étapes sans icône individuelle. Vide = numéro de l'étape.", 'blacktenderscore'),
            'type'        => Controls_Manager::ICONS,
            'skin'        => 'inline',
            'label_block' => false,
            'separator'   => 'before',
        ]);

        $this->add_control('return_dot_color', [
            'label'     => __('Couleur point — étape retour', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--return .bt-itin__dot' =>
                    'color: {{VALUE}}; background-color: {{VALUE}}',
            ],
        ]);

        // ── Dots — Transport ──────────────────────────────────────────────────
        $this->add_control('heading_transport_dots', [
            'label'     => __('Points — Transport (icônes)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('transport_dot_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--transport .bt-itin__dot--transport' =>
                    'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('transport_dot_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
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

    // ── Section Style — Overrides Départ/Arrivée/Retour ───────────────────────

    private function section_style_overrides(): void {
        $this->start_controls_section('style_overrides', [
            'label' => __('Overrides — Départ / Arrivée / Retour', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('departure_bg', [
            'label'     => __('Fond — Départ uniquement', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--departure .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('arrival_bg', [
            'label'     => __('Fond — Arrivée uniquement', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--arrival .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('return_step_bg', [
            'label'     => __('Fond — Étapes retour', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__step--return .bt-itin__step-body' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('transport_label_color', [
            'label'     => __('Couleur label (DÉPART / ARRIVÉE)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-itin__transport-label' => 'color: {{VALUE}}',
            ],
            'separator' => 'before',
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Badges ────────────────────────────────────────────────

    private function section_style_badges(): void {
        $this->start_controls_section('style_badges', [
            'label'     => __('Style — Badges', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_type_badge' => 'yes'],
        ]);

        foreach ([
            'activity' => ['label' => __('Activité',      'blacktenderscore'), 'color' => '#0052cc', 'bg' => '#e9f2ff'],
            'transfer' => ['label' => __('Transport',     'blacktenderscore'), 'color' => '#974f0c', 'bg' => '#fff3e0'],
            'free'     => ['label' => __('Temps libre',   'blacktenderscore'), 'color' => '#1e6b41', 'bg' => '#e3fcef'],
            'meal'     => ['label' => __('Repas',         'blacktenderscore'), 'color' => '#6e1d91', 'bg' => '#f3e8ff'],
        ] as $type => $cfg) {
            $this->add_control("badge_{$type}_color", [
                'label'     => $cfg['label'] . ' — ' . __('Texte', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => $cfg['color'],
                'selectors' => ["{{WRAPPER}} .bt-itin__badge--{$type}" => 'color: {{VALUE}}'],
            ]);
            $this->add_control("badge_{$type}_bg", [
                'label'     => $cfg['label'] . ' — ' . __('Fond', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => $cfg['bg'],
                'selectors' => ["{{WRAPPER}} .bt-itin__badge--{$type}" => 'background-color: {{VALUE}}'],
            ]);
        }

        $this->add_control('optional_color', [
            'label'     => __('Couleur texte "Facultatif"', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#6b7280',
            'selectors' => ['{{WRAPPER}} .bt-itin__optional' => 'color: {{VALUE}}'],
            'separator' => 'before',
        ]);

        $this->end_controls_section();
    }

    // ── Section Style — Carte ─────────────────────────────────────────────────

    private function section_style_map(): void {
        $this->start_controls_section('style_map', [
            'label'     => __('Style — Carte', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_map' => 'yes'],
        ]);

        $this->add_responsive_control('map_margin', [
            'label'      => __('Marge', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__map-wrap' =>
                    'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('map_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
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

        if (!$this->acf_required()) return;

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_itinerary');
        $rows       = $this->get_acf_rows(
            $field_name,
            sprintf(__('Aucune étape dans le champ « %s ».', 'blacktenderscore'), $field_name)
        );
        if (!$rows) return;

        $show_time       = ($s['show_time']           ?? '') === 'yes';
        $show_duration   = ($s['show_duration']       ?? '') === 'yes';
        $show_desc       = ($s['show_description']    ?? '') === 'yes';
        $show_transport  = ($s['show_transport']      ?? '') === 'yes';
        $show_map        = ($s['show_map']            ?? '') === 'yes';
        $show_total_dur  = ($s['show_total_duration'] ?? '') === 'yes';
        $show_type_badge = ($s['show_type_badge']     ?? '') === 'yes';
        $map_position    = $s['map_position'] ?? 'below';
        $connector_cls   = ($s['connector'] ?? 'line') === 'none' ? ' bt-itin--no-connector' : '';
        $accordion       = $s['accordion_default'] ?? 'none';

        // Labels transport
        $lbl_dep     = esc_html($s['label_departure']       ?: __('Départ',          'blacktenderscore'));
        $lbl_out     = esc_html($s['label_outboard']        ?: __('Hors-bord',       'blacktenderscore'));
        $lbl_out_ret = esc_html($s['label_outboard_return'] ?: __('Retour hors-bord', 'blacktenderscore'));
        $lbl_arr     = esc_html($s['label_return']          ?: __('Arrivée',         'blacktenderscore'));

        // Champs transport ACF
        $departure_zone  = $show_transport ? (string) get_field('exp_departure_zone',        $post_id) : '';
        // step_timethezone est un nom de champ historique (typo "timezone" pour durée) — NE PAS RENOMMER
        $outboard        = $show_transport ? (int)    get_field('exp_outboard',              $post_id) : 0;
        // exp_outboard_return : durée retour indépendante, avec fallback sur la durée aller
        $outboard_return = $show_transport ? ((int) get_field('exp_outboard_return', $post_id) ?: $outboard) : 0;
        $returning_zone  = $show_transport ? (string) get_field('exp_returning_zone',        $post_id) : '';
        $returning_desc  = $show_transport ? (string) get_field('exp_returning_description', $post_id) : '';

        // Durée totale = somme de tous les step_timethezone du repeater
        $total_duration_min = 0;
        if ($show_total_dur) {
            foreach ($rows as $row) {
                $total_duration_min += isset($row['step_timethezone']) ? (int) $row['step_timethezone'] : 0;
            }
        }

        $is_side = str_starts_with($map_position, 'side-');

        echo '<div class="bt-itin' . esc_attr($connector_cls) . '">';

        // AbstractBtWidget::render_section_title utilise 'section_title' + 'section_title_tag' par défaut
        $this->render_section_title($s, 'bt-itin__title');

        // Durée totale sous le titre de section
        if ($show_total_dur && $total_duration_min > 0) {
            echo '<p class="bt-itin__total-duration">'
               . esc_html(sprintf(
                   /* translators: %s = durée formatée ex: "1h30" */
                   __('Durée totale : %s', 'blacktenderscore'),
                   $this->format_duration($total_duration_min)
               ))
               . '</p>';
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
            $dep_coords = $this->parse_coords(get_field('exp_departure_coords', $post_id));
            $geo_attr   = $dep_coords
                ? ' data-lat="' . esc_attr($dep_coords['lat']) . '" data-lng="' . esc_attr($dep_coords['lng']) . '"'
                : '';
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--departure"' . $geo_attr . '>';
            $this->render_transport_dot($s['departure_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<span class="bt-itin__transport-label">' . $lbl_dep . '</span>';
            echo '<strong class="bt-itin__step-title">' . esc_html($departure_zone) . '</strong>';
            echo '</div></li>';
        }

        // [2] Transport aller
        if ($show_transport && $outboard > 0) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--outboard">';
            $this->render_transport_dot($s['outboard_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<strong class="bt-itin__step-title">'
               . esc_html($lbl_out . ' — ' . $this->format_duration($outboard))
               . '</strong>';
            echo '</div></li>';
        }

        // [3] Étapes ACF repeater
        foreach ($rows as $row) {
            $time      = $row['step_time']  ?? '';
            $title     = $row['step_title'] ?? '';
            $desc      = $row['step_desc']  ?? '';
            // step_timethezone : typo historique du champ durée — NE PAS RENOMMER
            $duration  = isset($row['step_timethezone']) ? (int) $row['step_timethezone'] : 0;
            $icon_raw  = $row['step_icon']  ?? null;
            $is_return = !empty($row['step_is_return']);
            $step_type = (string) ($row['step_type'] ?? '');
            $optional  = !empty($row['step_optional']);
            $fee       = (string) ($row['step_fee'] ?? '');

            // Coordonnées GPS pour map↔timeline sync (data-lat/data-lng sur <li>)
            $coords = $this->parse_coords($row['step_coords'] ?? null);
            if (!$coords && !empty($row['step_lat']) && !empty($row['step_lng'])) {
                $coords = ['lat' => (float) $row['step_lat'], 'lng' => (float) $row['step_lng']];
            }
            $geo_attr = $coords
                ? ' data-lat="' . esc_attr($coords['lat']) . '" data-lng="' . esc_attr($coords['lng']) . '"'
                : '';

            $step_cls      = 'bt-itin__step' . ($is_return ? ' bt-itin__step--return' : '');
            // Accordéon actif seulement si le mode est défini ET qu'il y a quelque chose à montrer
            $use_accordion = $accordion !== 'none' && ($desc !== '' || $optional);
            $is_open       = $accordion === 'open';

            echo '<li class="' . esc_attr($step_cls) . '"' . $geo_attr . '>';

            // Dot : image ACF | classe FA | icône Elementor globale | counter numéroté
            if (is_array($icon_raw) && !empty($icon_raw['url']) && (isset($icon_raw['sizes']) || isset($icon_raw['filename']))) {
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true">';
                echo '<img src="' . esc_url($icon_raw['url']) . '" alt="' . esc_attr($icon_raw['alt'] ?? '') . '" loading="lazy" class="bt-itin__dot-img">';
                echo '</span>';
            } elseif (is_string($icon_raw) && trim($icon_raw) !== '') {
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true"><i class="' . esc_attr(trim($icon_raw)) . '"></i></span>';
            } elseif (!empty($s['step_dot_icon']['value'])) {
                echo '<span class="bt-itin__dot bt-itin__dot--icon" aria-hidden="true">';
                \Elementor\Icons_Manager::render_icon($s['step_dot_icon'], ['aria-hidden' => 'true']);
                echo '</span>';
            } else {
                echo '<span class="bt-itin__dot" aria-hidden="true"></span>';
            }

            echo '<div class="bt-itin__step-body">';

            // Méta : heure + durée
            $has_meta = ($show_time && $time !== '') || ($show_duration && $duration > 0);
            if ($has_meta) {
                echo '<div class="bt-itin__meta">';
                if ($show_time && $time !== '') {
                    echo '<span class="bt-itin__time">' . esc_html($time) . '</span>';
                }
                if ($show_duration && $duration > 0) {
                    echo '<span class="bt-itin__duration">(' . esc_html($this->format_duration($duration)) . ')</span>';
                }
                echo '</div>';
            }

            // Titre (avec trigger accordéon si activé)
            if ($use_accordion) {
                $expanded = $is_open ? 'true' : 'false';
                echo '<button class="bt-itin__step-trigger" aria-expanded="' . $expanded . '" type="button">';
            }

            if ($show_type_badge && $step_type !== '') {
                $badge_labels = [
                    'activity' => __('Activité',    'blacktenderscore'),
                    'transfer' => __('Transport',   'blacktenderscore'),
                    'free'     => __('Temps libre', 'blacktenderscore'),
                    'meal'     => __('Repas',       'blacktenderscore'),
                ];
                $badge_label = $badge_labels[$step_type] ?? $step_type;
                echo '<span class="bt-itin__badge bt-itin__badge--' . esc_attr($step_type) . '">'
                   . esc_html($badge_label) . '</span>';
            }

            if ($title !== '') {
                echo '<strong class="bt-itin__step-title">' . esc_html($title) . '</strong>';
            }

            if ($use_accordion) {
                echo '<span class="bt-itin__chevron" aria-hidden="true"></span>';
                echo '</button>';
            }

            // Panneau description (accordéon ou direct)
            if ($use_accordion) {
                $hidden_attr = $is_open ? '' : ' hidden';
                echo '<div class="bt-itin__step-panel"' . $hidden_attr . '>';
            }

            if ($show_desc && $desc !== '') {
                echo '<p class="bt-itin__step-desc">' . wp_kses_post($desc) . '</p>';
            }

            if ($optional) {
                $opt_text = __('Facultatif', 'blacktenderscore');
                if ($fee !== '') {
                    $opt_text .= ' · ' . $fee;
                }
                echo '<em class="bt-itin__optional">' . esc_html($opt_text) . '</em>';
            }

            if ($use_accordion) {
                echo '</div>'; // .bt-itin__step-panel
            }

            echo '</div></li>'; // .bt-itin__step-body + li
        }

        // [4] Transport retour
        if ($show_transport && $outboard_return > 0) {
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--outboard-return">';
            $this->render_transport_dot($s['outboard_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<strong class="bt-itin__step-title">'
               . esc_html($lbl_out_ret . ' — ' . $this->format_duration($outboard_return))
               . '</strong>';
            echo '</div></li>';
        }

        // [5] Zone d'arrivée
        if ($show_transport && ($returning_zone !== '' || $returning_desc !== '')) {
            $arr_coords = $this->parse_coords(get_field('exp_arriving_coords', $post_id));
            $geo_attr   = $arr_coords
                ? ' data-lat="' . esc_attr($arr_coords['lat']) . '" data-lng="' . esc_attr($arr_coords['lng']) . '"'
                : '';
            echo '<li class="bt-itin__step bt-itin__step--transport bt-itin__step--arrival"' . $geo_attr . '>';
            $this->render_transport_dot($s['arrival_dot_icon'] ?? []);
            echo '<div class="bt-itin__step-body">';
            echo '<span class="bt-itin__transport-label">' . $lbl_arr . '</span>';
            if ($returning_zone !== '') {
                echo '<strong class="bt-itin__step-title">' . esc_html($returning_zone) . '</strong>';
            }
            // Bug fix v4 : returning_desc respecte maintenant show_description
            if ($show_desc && $returning_desc !== '') {
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
            if ($is_side) {
                echo '<div class="bt-itin__col-map">';
            }
            $this->render_map($rows, $departure_zone, $returning_zone, $s, $post_id);
            if ($is_side) {
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
     * Formate une durée en minutes en chaîne lisible.
     *
     * 45 → "45 min" | 90 → "1h30" | 120 → "2h" | 65 → "1h05"
     *
     * @param int $min Durée en minutes
     */
    private function format_duration(int $min): string {
        if ($min <= 0) return '';
        if ($min < 60) return $min . ' min';
        $h = intdiv($min, 60);
        $m = $min % 60;
        return $m === 0 ? $h . 'h' : sprintf('%dh%02d', $h, $m);
    }

    /**
     * Parse des coordonnées GPS depuis un champ ACF Google Map (array) ou "lat,lng" (string).
     *
     * @param  mixed $raw Valeur brute ACF
     * @return array{lat: float, lng: float}|null  Null si non parseable ou coords nulles
     */
    private function parse_coords(mixed $raw): ?array {
        if (is_array($raw) && isset($raw['lat']) && (float) $raw['lat'] !== 0.0) {
            return ['lat' => (float) $raw['lat'], 'lng' => (float) $raw['lng']];
        }
        if (is_string($raw) && $raw !== '') {
            $parts = explode(',', $raw, 2);
            if (count($parts) === 2) {
                $lat = (float) trim($parts[0]);
                $lng = (float) trim($parts[1]);
                if ($lat !== 0.0 || $lng !== 0.0) {
                    return ['lat' => $lat, 'lng' => $lng];
                }
            }
        }
        return null;
    }

    /**
     * Rend le dot d'une étape transport via Icons_Manager::render_icon().
     * Supporte FA (<i>) et SVG uploadé (rendu inline).
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
     * Dispatche vers Leaflet (interactif) ou Google Maps Static (image).
     */
    private function render_map(array $rows, string $departure_zone, string $returning_zone, array $s, int $post_id): void {
        if (($s['map_engine'] ?? 'leaflet') === 'static_api') {
            $this->render_map_static($rows, $departure_zone, $returning_zone, $s, $post_id);
        } else {
            $this->render_map_leaflet($rows, $s, $post_id);
        }
    }

    /**
     * Carte Leaflet interactive (CartoDB Voyager, zéro clé API, RGPD-safe).
     *
     * Inclut maintenant les marqueurs départ (p.start=true) et arrivée (p.end=true)
     * depuis exp_departure_coords / exp_arriving_coords pour une route complète.
     * Le JS (bt-leaflet-init.js) stylise différemment ces marqueurs spéciaux.
     */
    private function render_map_leaflet(array $rows, array $s, int $post_id): void {
        $points = [];

        // Marqueur de départ depuis exp_departure_coords
        $dep_coords = $this->parse_coords(get_field('exp_departure_coords', $post_id));
        if ($dep_coords) {
            $dep_zone = (string) get_field('exp_departure_zone', $post_id);
            $points[] = [
                'lat'    => $dep_coords['lat'],
                'lng'    => $dep_coords['lng'],
                'title'  => $dep_zone ?: __('Départ', 'blacktenderscore'),
                'num'    => 0,
                'start'  => true,
                'return' => false,
            ];
        }

        // Étapes ACF repeater (utilise parse_coords pour gérer ACF array + "lat,lng" string)
        foreach ($rows as $idx => $row) {
            $coords = $this->parse_coords($row['step_coords'] ?? null);
            if (!$coords && !empty($row['step_lat']) && !empty($row['step_lng'])) {
                $coords = ['lat' => (float) $row['step_lat'], 'lng' => (float) $row['step_lng']];
            }
            if (!$coords) continue;
            $points[] = [
                'lat'    => $coords['lat'],
                'lng'    => $coords['lng'],
                'title'  => (string) ($row['step_title'] ?? ''),
                'num'    => $idx + 1,
                'return' => !empty($row['step_is_return']),
            ];
        }

        // Marqueur d'arrivée depuis exp_arriving_coords
        $arr_coords = $this->parse_coords(get_field('exp_arriving_coords', $post_id));
        if ($arr_coords) {
            $arr_zone = (string) get_field('exp_returning_zone', $post_id);
            $points[] = [
                'lat'    => $arr_coords['lat'],
                'lng'    => $arr_coords['lng'],
                'title'  => $arr_zone ?: __('Arrivée', 'blacktenderscore'),
                'num'    => count($points) + 1,
                'end'    => true,
                'return' => false,
            ];
        }

        if (empty($points)) {
            if ($this->is_edit_mode()) {
                echo '<div class="bt-itin__map-wrap"><p class="bt-widget-placeholder">';
                esc_html_e(
                    'Carte : aucune coordonnée GPS. Ajoutez step_coords dans le repeater ou exp_departure_coords / exp_arriving_coords sur le post.',
                    'blacktenderscore'
                );
                echo '</p></div>';
            }
            return;
        }

        wp_enqueue_style('bt-leaflet-css');
        wp_enqueue_script('bt-leaflet-init');

        $uid          = 'bt-map-' . esc_attr($this->get_id());
        $line_color   = esc_attr($s['map_line_color'] ?? '#0066cc');
        $return_color = esc_attr($s['return_line_color'] ?? '');

        echo '<div class="bt-itin__map-wrap">';
        echo '<div id="' . $uid . '" class="bt-itin__map bt-itin__map--leaflet"'
           . ' data-bt-points="' . esc_attr(wp_json_encode($points)) . '"'
           . ' data-bt-line-color="' . $line_color . '"'
           . ($return_color ? ' data-bt-return-color="' . $return_color . '"' : '')
           . '></div>';
        echo '</div>';
    }

    /**
     * Carte Google Maps Static API → simple <img>.
     * Résultat mis en cache (WP transient, invalidé à la sauvegarde du post).
     *
     * Clé API : Elementor → Réglages → Intégrations → Google Maps.
     * Activer "Maps Static API" dans Google Cloud Console.
     */
    private function render_map_static(array $rows, string $departure_zone, string $returning_zone, array $s, int $post_id): void {
        $points = [];

        foreach ($rows as $row) {
            $coords = $this->parse_coords($row['step_coords'] ?? null);
            if (!$coords && !empty($row['step_lat']) && !empty($row['step_lng'])) {
                $coords = ['lat' => (float) $row['step_lat'], 'lng' => (float) $row['step_lng']];
            }
            if ($coords) {
                $points[] = [$coords['lat'], $coords['lng']];
            }
        }

        if (empty($points)) {
            if ($this->is_edit_mode()) {
                echo '<div class="bt-itin__map-wrap"><p class="bt-widget-placeholder">';
                esc_html_e('Carte : aucune coordonnée GPS dans les étapes. Ajoutez step_coords (ACF Google Map) ou step_lat + step_lng dans le repeater.', 'blacktenderscore');
                echo '</p></div>';
            }
            return;
        }

        $api_key = (string) get_option('elementor_google_maps_api_key', '');
        if (empty($api_key)) {
            if ($this->is_edit_mode()) {
                echo '<div class="bt-itin__map-wrap"><p class="bt-widget-placeholder">';
                esc_html_e('Carte : clé API manquante. Renseignez-la dans Elementor → Réglages → Intégrations → Google Maps.', 'blacktenderscore');
                echo '</p></div>';
            }
            return;
        }

        $maptype    = $s['map_type'] ?? 'roadmap';
        $style_json = get_option('bt_map_style_json', '');
        $cache_key  = 'bt_map_' . md5($post_id . serialize($points) . $maptype . $style_json);
        $cached     = get_transient($cache_key);

        if (false !== $cached) {
            echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput
            return;
        }

        $total  = count($points);
        $params = 'key=' . rawurlencode($api_key)
                . '&size=1280x640&scale=2'
                . '&maptype=' . rawurlencode($maptype)
                . '&language=fr';

        foreach ($points as $i => [$lat, $lng]) {
            $color = match(true) {
                $i === 0          => 'green',
                $i === $total - 1 => 'red',
                default           => 'blue',
            };
            $label   = $i < 9 ? (string) ($i + 1) : chr(65 + $i - 9);
            $params .= '&markers=' . rawurlencode("color:{$color}|size:mid|label:{$label}|{$lat},{$lng}");
        }

        if ($total > 1) {
            $path    = implode('|', array_map(fn($p) => "{$p[0]},{$p[1]}", $points));
            $params .= '&path=' . rawurlencode('color:0x0066cccc|weight:3|' . $path);
        }

        if (!empty($style_json)) {
            $style_params = self::bt_map_json_to_static_params($style_json);
            if ($style_params) $params .= '&' . $style_params;
        }

        $url  = 'https://maps.googleapis.com/maps/api/staticmap?' . $params;
        $html = '<div class="bt-itin__map-wrap">'
              . '<img class="bt-itin__map" src="' . esc_url($url) . '" '
              . 'alt="' . esc_attr__("Carte de l'itinéraire", 'blacktenderscore') . '" '
              . 'loading="lazy" decoding="async">'
              . '</div>';

        set_transient($cache_key, $html, WEEK_IN_SECONDS);
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput
    }
}
