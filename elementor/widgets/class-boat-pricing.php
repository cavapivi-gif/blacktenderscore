<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

require_once __DIR__ . '/../traits/trait-bt-excursion-pricing.php';

/**
 * Widget Elementor — Tarification du bateau + formulaire de devis multi-étapes.
 *
 * Layouts tarifs : cartes côte à côte | tableau | onglets (tabs).
 * Wrapper parent : onglet "Forfaits" (contenu existant) + onglet "Devis" (formulaire).
 * Données : ACF Pro (boat_price_half, boat_price_full, repeater par zone…).
 */
class BoatPricing extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtExcursionPricing;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-boat-pricing',
            'title'    => 'BT — Tarifs',
            'icon'     => 'eicon-price-list',
            'keywords' => ['tarif', 'prix', 'bateau', 'excursion', 'forfait', 'demi-journée', 'journée', 'devis', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-quote-form', 'bt-pricing-tabs'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Mode de tarification
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_pricing_mode', [
            'label' => __('Mode de tarification', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('pricing_mode', [
            'label'   => __('Type de tarif', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'boat'      => __('Tarif Bateau (prix demi/journée + zones)', 'blacktenderscore'),
                'excursion' => __('Tarif Excursion (forfaits repeater + Regiondo)', 'blacktenderscore'),
            ],
            'default'     => 'boat',
            'description' => __('Bateau : lit boat_price_half/full. Excursion : lit le repeater tarification_par_forfait avec UUID Regiondo par onglet.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Wrapper Onglets
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_wrapper_tabs', [
            'label' => __('Onglets wrapper', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('wrapper_enable', [
            'label'        => __('Activer les onglets wrapper', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Enveloppe les tarifs dans un onglet "Forfaits" et ajoute un onglet "Devis".', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('wrapper_tab1_label', [
            'label'     => __('Label onglet 1', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Forfaits', 'blacktenderscore'),
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('wrapper_tab2_label', [
            'label'     => __('Label onglet 2', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demande de devis', 'blacktenderscore'),
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Tarifs (existant)
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label'     => __('Contenu tarifs bateau', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'boat'],
        ]);

        $this->register_section_title_controls(['title' => __('Tarifs', 'blacktenderscore')]);

        $this->add_control('currency', [
            'label'   => __('Symbole monnaie', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => '€',
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'cards' => __('Cartes côte à côte', 'blacktenderscore'),
                'tabs'  => __('Onglets (tabs)', 'blacktenderscore'),
                'table' => __('Tableau', 'blacktenderscore'),
            ],
            'default' => 'cards',
        ]);

        $this->end_controls_section();

        // ── Options d'affichage (existant) ──────────────────────────────────
        $this->start_controls_section('section_options', [
            'label'     => __('Forfaits à afficher', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'boat'],
        ]);

        $this->add_control('show_half', [
            'label'        => __('Demi-journée', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_half', [
            'label'     => __('Label demi-journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['show_half' => 'yes'],
        ]);

        $this->add_control('show_full', [
            'label'        => __('Journée complète', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_full', [
            'label'     => __('Label journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Journée complète', 'blacktenderscore'),
            'condition' => ['show_full' => 'yes'],
        ]);

        $this->add_control('show_per_person', [
            'label'        => __('Afficher le prix / personne', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Divise le prix par le nombre de passagers max (boat_pax_max).', 'blacktenderscore'),
        ]);

        $this->add_control('per_person_label', [
            'label'     => __('Suffixe prix / personne', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('/ pers.', 'blacktenderscore'),
            'condition' => ['show_per_person' => 'yes'],
        ]);

        $this->add_control('show_deposit', [
            'label'        => __('Caution', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_deposit', [
            'label'     => __('Label caution', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Caution', 'blacktenderscore'),
            'condition' => ['show_deposit' => 'yes'],
        ]);

        $this->add_control('show_fuel_badge', [
            'label'        => __('Badge carburant inclus', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_fuel_yes', [
            'label'     => __('Label carburant inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Carburant inclus', 'blacktenderscore'),
            'condition' => ['show_fuel_badge' => 'yes'],
        ]);

        $this->add_control('label_fuel_no', [
            'label'     => __('Label carburant non inclus', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Carburant en sus', 'blacktenderscore'),
            'condition' => ['show_fuel_badge' => 'yes'],
        ]);

        $this->add_control('show_price_note', [
            'label'        => __('Note tarifaire', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('table_col_forfait', [
            'label'     => __('En-tête col. Forfait', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Forfait', 'blacktenderscore'),
            'condition' => ['layout' => 'table'],
        ]);

        $this->add_control('table_col_duration', [
            'label'     => __('En-tête col. Durée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Durée', 'blacktenderscore'),
            'condition' => ['layout' => 'table'],
        ]);

        $this->add_control('table_col_price', [
            'label'     => __('En-tête col. Prix', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Prix', 'blacktenderscore'),
            'condition' => ['layout' => 'table'],
        ]);

        $this->end_controls_section();

        // ── Tarifs par zone (existant) ──────────────────────────────────────
        $this->start_controls_section('section_zones', [
            'label'     => __('Tarifs par zone de navigation', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'boat', 'layout!' => 'tabs'],
        ]);

        $this->add_control('show_zones', [
            'label'        => __('Afficher les tarifs par zone', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Lit le repeater ACF boat_custom_price_by_departure.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('zones_title', [
            'label'     => __('Titre du tableau par zone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Tarifs par zone de départ', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_zone', [
            'label'     => __('En-tête colonne Zone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Zone de navigation', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_half', [
            'label'     => __('En-tête colonne ½ journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_full', [
            'label'     => __('En-tête colonne journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Journée', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Forfaits Excursion (mode excursion)
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_exc_pricing', [
            'label'     => __('Forfaits excursion', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'excursion'],
        ]);

        $this->register_section_title_controls(['title' => __('Tarifs', 'blacktenderscore')]);

        $this->add_control('exc_section_description', [
            'label'   => __('Description', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => '',
            'rows'    => 3,
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('exc_repeater_slug', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'tarification_par_forfait',
        ]);

        $this->add_control('exc_layout', [
            'label'   => __('Format d\'affichage', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'tabs'    => __('Onglets (tabs)', 'blacktenderscore'),
                'buttons' => __('Boutons pill', 'blacktenderscore'),
            ],
            'default' => 'tabs',
        ]);

        $this->add_control('exc_currency', [
            'label'   => __('Symbole devise', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => '€',
        ]);

        $this->add_control('exc_tab_title_mode', [
            'label'   => __('Titre des onglets', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'forfait_et_prix' => __('Forfait + prix', 'blacktenderscore'),
                'prix_seul'       => __('Prix seul', 'blacktenderscore'),
            ],
            'default' => 'forfait_et_prix',
        ]);

        $this->add_control('exc_discount_subfield', [
            'label'   => __('Champ ACF remise (%)', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'is_a_discount',
        ]);

        $this->add_control('exc_show_price', [
            'label'        => __('Afficher le prix', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('exc_show_per_label', [
            'label'        => __('Afficher "/ pers."', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('exc_per_label', [
            'label'     => __('Libellé "par pers."', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('/ pers.', 'blacktenderscore'),
            'condition' => ['exc_show_per_label' => 'yes'],
        ]);

        $this->add_control('exc_show_deposit', [
            'label'        => __('Afficher l\'acompte', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('exc_deposit_label', [
            'label'     => __('Libellé "Acompte"', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Acompte :', 'blacktenderscore'),
            'condition' => ['exc_show_deposit' => 'yes'],
        ]);

        $this->add_control('exc_show_note', [
            'label'        => __('Afficher la note tarifaire', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Réservation Regiondo (mode excursion) ─────────────────────────
        $this->start_controls_section('section_exc_booking', [
            'label'     => __('Réservation Regiondo', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'excursion'],
        ]);

        $this->add_control('exc_show_booking', [
            'label'        => __('Afficher le widget de réservation', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('exc_booking_per_tab', [
            'label'        => __('UUID différent par forfait', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['exc_show_booking' => 'yes'],
        ]);

        $this->add_control('exc_booking_field', [
            'label'     => __('Champ UUID Regiondo (global)', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'exp_booking_short_url' => __('Forfait court (exp_booking_short_url)', 'blacktenderscore'),
                'exp_booking_long_url'  => __('Forfait long (exp_booking_long_url)', 'blacktenderscore'),
            ],
            'default'   => 'exp_booking_short_url',
            'condition' => ['exc_show_booking' => 'yes', 'exc_booking_per_tab!' => 'yes'],
        ]);

        $this->add_control('exc_booking_uuid_subfield', [
            'label'     => __('Nom du sous-champ UUID', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'exp_booking_uuid',
            'condition' => ['exc_show_booking' => 'yes', 'exc_booking_per_tab' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Boutons layout excursion — configuration ─────────────────────
        $this->start_controls_section('section_exc_buttons_config', [
            'label'     => __('Boutons pill — Configuration', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'excursion', 'exc_layout' => 'buttons'],
        ]);

        $this->add_control('exc_buttons_title', [
            'label'   => __('Titre au-dessus des boutons', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Choisissez votre forfait', 'blacktenderscore'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('exc_buttons_title_tag', [
            'label'   => __('Balise titre', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'p' => 'p', 'span' => 'span'],
            'default' => 'h4',
        ]);

        $this->add_control('exc_buttons_show_price', [
            'label'        => __('Afficher le prix dans le bouton', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();

        // ── Trigger excursion ────────────────────────────────────────────
        $this->start_controls_section('section_exc_trigger', [
            'label'     => __('Bouton « Réserver » et emplacement', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'excursion'],
        ]);

        $this->add_control('exc_trigger_mode', [
            'label'   => __('Mode d\'affichage', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'none'   => __('Désactivé — forfaits + résa visibles directement', 'blacktenderscore'),
                'reveal' => __('Bouton « Réserver » — clic révèle forfaits + résa', 'blacktenderscore'),
            ],
            'default' => 'none',
        ]);

        $this->add_control('exc_trigger_label', [
            'label'     => __('Texte du bouton', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Réserver', 'blacktenderscore'),
            'condition' => ['exc_trigger_mode!' => 'none'],
        ]);

        $this->add_control('exc_trigger_fullwidth', [
            'label'        => __('Pleine largeur', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['exc_trigger_mode!' => 'none'],
        ]);

        $this->add_control('exc_reveal_target_id', [
            'label'       => __('ID du conteneur cible', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'booking-exc',
            'condition'   => ['exc_trigger_mode' => 'reveal'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Devis : Étape 1 — Excursion
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_exc', [
            'label'     => __('Devis — Excursion', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('step_exc_enable', [
            'label'        => __('Activer l\'étape Excursion', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_exc_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Votre excursion', 'blacktenderscore'),
            'condition' => ['step_exc_enable' => 'yes'],
        ]);

        $this->add_control('step_exc_loop_tpl', [
            'label'       => __('Template Loop excursion', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => self::get_elementor_templates_options(),
            'default'     => '',
            'description' => __('Template Elementor utilisé pour le rendu de chaque card excursion. Le contexte post est défini pour chaque excursion.', 'blacktenderscore'),
            'condition'   => ['step_exc_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Devis : Étape 2 — Bateau
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_boat', [
            'label'     => __('Devis — Bateau', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_enable', [
            'label'        => __('Activer l\'étape Bateau', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_boat_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Choix du bateau', 'blacktenderscore'),
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_heading_repeater', [
            'label'     => __('Configuration repeater ACF', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_repeater_slug', [
            'label'     => __('Slug du repeater', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_custom_price_by_departure',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_rep_price_half', [
            'label'     => __('Sous-champ prix ½ journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_price_for_half_day',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_rep_price_full', [
            'label'     => __('Sous-champ prix journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_price_for_full_day',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_rep_nav_zone', [
            'label'     => __('Sous-champ zone navigation', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_navigation_zone',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_rep_duration', [
            'label'     => __('Sous-champ durée (taxonomie)', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_duration_taxonomy',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_rep_carburant', [
            'label'     => __('Sous-champ carburant (taxonomie)', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_carburant',
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('step_boat_loop_tpl', [
            'label'       => __('Template Loop bateau', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => self::get_elementor_templates_options(),
            'default'     => '',
            'description' => __('Template Elementor utilisé pour le rendu de chaque card bateau. Le contexte post est défini pour chaque bateau.', 'blacktenderscore'),
            'separator'   => 'before',
            'condition'   => ['step_boat_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Devis : Étape 3 — Dates
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_dates', [
            'label'     => __('Devis — Dates', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_enable', [
            'label'        => __('Activer l\'étape Dates', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_dates_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Dates de location', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_half', [
            'label'     => __('Label « Demi-journée »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_full', [
            'label'     => __('Label « Journée entière »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Journée entière', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_multi', [
            'label'     => __('Label « Plusieurs jours »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Plusieurs jours', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_custom', [
            'label'     => __('Label « Demande spécifique »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demande spécifique', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_label_date', [
            'label'     => __('Label date unique', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Date souhaitée', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_label_start', [
            'label'     => __('Label date début', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Date de début', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_label_end', [
            'label'     => __('Label date fin', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Date de fin', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_custom_placeholder', [
            'label'     => __('Placeholder demande spécifique', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Décrivez vos disponibilités...', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Devis : Étape 4 — Coordonnées
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_contact', [
            'label'     => __('Devis — Coordonnées', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_enable', [
            'label'        => __('Activer l\'étape Coordonnées', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_contact_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Vos coordonnées', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_name_mode', [
            'label'     => __('Champ nom', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'single' => __('Un seul champ (Nom complet)', 'blacktenderscore'),
                'split'  => __('Deux champs (Nom + Prénom)', 'blacktenderscore'),
            ],
            'default'   => 'split',
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_label_name', [
            'label'     => __('Label nom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Nom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_ph_name', [
            'label'     => __('Placeholder nom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Votre nom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_label_firstname', [
            'label'     => __('Label prénom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Prénom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes', 'step_contact_name_mode' => 'split'],
        ]);

        $this->add_control('step_contact_ph_firstname', [
            'label'     => __('Placeholder prénom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Votre prénom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes', 'step_contact_name_mode' => 'split'],
        ]);

        $this->add_control('step_contact_label_email', [
            'label'     => __('Label e-mail', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('E-mail', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_ph_email', [
            'label'     => __('Placeholder e-mail', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'votre@email.com',
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_label_phone', [
            'label'     => __('Label téléphone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Téléphone', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_ph_phone', [
            'label'     => __('Placeholder téléphone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '06 12 34 56 78',
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Devis : Étape 5 — Envoi
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_submit', [
            'label'     => __('Devis — Envoi', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('step_submit_title', [
            'label'   => __('Titre de l\'étape', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Confirmation', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_btn_label', [
            'label'   => __('Label bouton envoi', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Envoyer ma demande', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_email', [
            'label'       => __('E-mail destinataire', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => get_option('admin_email'),
            'description' => __('Adresse e-mail qui recevra les demandes de devis.', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_msg_success', [
            'label'   => __('Message de succès', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => __('Votre demande a bien été envoyée ! Nous vous recontacterons rapidement.', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_msg_error', [
            'label'   => __('Message d\'erreur', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => __('Une erreur est survenue. Veuillez réessayer.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Bouton déclencheur et emplacement
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_trigger', [
            'label'     => __('Bouton déclencheur et emplacement', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => 'boat'],
        ]);

        $this->add_control('trigger_mode', [
            'label'       => __('Mode d\'affichage', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => [
                'none'   => __('Désactivé — contenu visible directement', 'blacktenderscore'),
                'reveal' => __('Bouton — clic révèle le contenu (sous le bouton ou dans un conteneur)', 'blacktenderscore'),
            ],
            'default'     => 'none',
            'description' => __('Choisir comment afficher le widget. En mode "reveal", un bouton déclenche l\'affichage et le scroll vers l\'ancre.', 'blacktenderscore'),
        ]);

        $this->add_control('trigger_label', [
            'label'     => __('Texte du bouton', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Voir les tarifs', 'blacktenderscore'),
            'dynamic'   => ['active' => true],
            'condition' => ['trigger_mode!' => 'none'],
        ]);

        $this->add_control('trigger_fullwidth', [
            'label'        => __('Pleine largeur', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['trigger_mode!' => 'none'],
        ]);

        $this->add_control('reveal_target_id', [
            'label'       => __('ID du conteneur cible', 'blacktenderscore'),
            'description' => __('ID du conteneur Elementor où afficher le contenu (ex: tarifs-bateau). Donnez le même ID à votre colonne/conteneur dans Avancé. Vide = le contenu s\'ouvre sous le bouton.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'tarifs-bateau',
            'condition'   => ['trigger_mode' => 'reveal'],
        ]);

        $this->end_controls_section();

        // ══ STYLE ═══════════════════════════════════════════════════════════════

        // ── 🚤 BATEAU — Titre ──────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-bprice__title', null, ['pricing_mode' => 'boat']);

        // ── 🚤 BATEAU — Onglets ½j / Journée (tabs — non fusionné) ────
        $this->register_tabs_nav_style(
            'tab',
            '🚤 Bateau — Onglets ½j / Journée',
            '{{WRAPPER}} .bt-bprice__tab',
            '{{WRAPPER}} .bt-bprice__tab--active',
            '{{WRAPPER}} .bt-bprice__tablist',
            ['pricing_mode' => 'boat', 'layout' => 'tabs'],
            [
                'with_hover'      => true,
                'with_radius'     => true,
                'with_indicator'  => true,
                'with_direction'  => true,
                'with_justify'    => true,
                'with_scroll'     => true,
                'with_breakpoint' => true,
                'with_panel'      => true,
                'panel_sel'       => '{{WRAPPER}} .bt-bprice__panel--active',
            ]
        );

        // ── 🚤 Bateau — Conteneur ─────────────────────────────────────
        $this->start_controls_section('style_boat_container', [
            'label'     => __('🚤 Bateau — Conteneur', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['pricing_mode' => 'boat'],
        ]);

        $this->add_control('container_heading_outer', ['label' => __('Conteneur prix', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_control('container_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'container_border', 'selector' => '{{WRAPPER}} .bt-bprice']);
        $this->add_responsive_control('container_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-bprice' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('container_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-bprice' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'container_shadow', 'selector' => '{{WRAPPER}} .bt-bprice']);

        $this->add_responsive_control('cards_gap_extra', ['label' => __('Espacement entre cartes', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'separator' => 'before', 'size_units' => ['px'], 'default' => ['size' => 16, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .bt-bprice__cards' => 'gap: {{SIZE}}{{UNIT}}'], 'condition' => ['layout' => 'cards']]);

        $this->add_control('card_heading', ['label' => __('Cartes', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('card_bg', ['label' => __('Fond carte', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'card_border', 'selector' => '{{WRAPPER}} .bt-bprice__card']);
        $this->add_responsive_control('card_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('card_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'default' => ['top' => 24, 'right' => 24, 'bottom' => 24, 'left' => 24, 'unit' => 'px', 'isLinked' => true], 'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'card_shadow', 'selector' => '{{WRAPPER}} .bt-bprice__card']);

        $this->end_controls_section();

        // ── 🚤 Bateau — Typographie ───────────────────────────────────
        $this->start_controls_section('style_boat_typography', [
            'label'     => __('🚤 Bateau — Typographie', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['pricing_mode' => 'boat'],
        ]);

        $this->add_control('typo_heading_label', ['label' => __('Label forfait', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'card_label_typography', 'selector' => '{{WRAPPER}} .bt-bprice__card-label']);
        $this->add_control('card_label_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__card-label' => 'color: {{VALUE}}']]);

        $this->add_control('typo_heading_price', ['label' => __('Prix', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'price_typography', 'selector' => '{{WRAPPER}} .bt-bprice__amount']);
        $this->add_control('price_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__amount' => 'color: {{VALUE}}']]);

        $this->add_control('typo_heading_duration', ['label' => __('Durée', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'duration_typography', 'selector' => '{{WRAPPER}} .bt-bprice__duration']);
        $this->add_control('duration_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__duration' => 'color: {{VALUE}}']]);

        $this->end_controls_section();

        // ── 🚤 Bateau — Badges & boutons ──────────────────────────────
        $this->start_controls_section('style_boat_badges_btns', [
            'label'     => __('🚤 Bateau — Badges & boutons', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['pricing_mode' => 'boat'],
        ]);

        $this->add_control('badges_heading', ['label' => __('Badges carburant', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_control('deposit_color', ['label' => __('Couleur caution', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__deposit' => 'color: {{VALUE}}'], 'condition' => ['show_deposit' => 'yes']]);
        $this->add_control('fuel_yes_bg',   ['label' => __('Fond badge inclus', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--yes' => 'background-color: {{VALUE}}'], 'condition' => ['show_fuel_badge' => 'yes']]);
        $this->add_control('fuel_yes_color', ['label' => __('Texte badge inclus', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--yes' => 'color: {{VALUE}}'], 'condition' => ['show_fuel_badge' => 'yes']]);
        $this->add_control('fuel_no_bg',    ['label' => __('Fond badge en sus', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--no' => 'background-color: {{VALUE}}'], 'condition' => ['show_fuel_badge' => 'yes']]);
        $this->add_control('fuel_no_color', ['label' => __('Texte badge en sus', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--no' => 'color: {{VALUE}}'], 'condition' => ['show_fuel_badge' => 'yes']]);

        $this->add_control('trigger_heading', ['label' => __('Bouton déclencheur', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['trigger_mode!' => 'none']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'trigger_btn_typography', 'selector' => '{{WRAPPER}} .bt-pricing__trigger', 'condition' => ['trigger_mode!' => 'none']]);
        $this->start_controls_tabs('trigger_btn_state_tabs', ['condition' => ['trigger_mode!' => 'none']]);
        $this->start_controls_tab('trigger_btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('trigger_btn_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'color: {{VALUE}}']]);
        $this->add_control('trigger_btn_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->start_controls_tab('trigger_btn_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('trigger_btn_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger:hover' => 'color: {{VALUE}}']]);
        $this->add_control('trigger_btn_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('trigger_btn_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['trigger_mode!' => 'none']]);
        $this->add_responsive_control('trigger_btn_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['trigger_mode!' => 'none']]);

        $this->end_controls_section();

        // ── ⛵ Excursion — Onglets forfaits (tabs — non fusionné) ──────
        $this->register_tabs_nav_style(
            'exc_tab',
            '⛵ Excursion — Onglets forfaits',
            '{{WRAPPER}} .bt-pricing__tab',
            '{{WRAPPER}} .bt-pricing__tab--active',
            '{{WRAPPER}} .bt-pricing__tablist',
            ['pricing_mode' => 'excursion'],
            [
                'with_hover'      => true,
                'with_radius'     => true,
                'with_indicator'  => true,
                'with_direction'  => true,
                'with_justify'    => true,
                'with_scroll'     => true,
                'with_breakpoint' => true,
                'with_panel'      => true,
                'panel_sel'       => '{{WRAPPER}} .bt-pricing__panel--active',
            ]
        );

        // ── ⛵ Excursion — Onglets & boutons ──────────────────────────
        $this->start_controls_section('style_exc_btns', [
            'label'     => __('⛵ Excursion — Onglets & boutons', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['pricing_mode' => 'excursion'],
        ]);

        $this->add_control('exc_container_heading', ['label' => __('Conteneur forfaits', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_control('exc_container_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'exc_container_border', 'selector' => '{{WRAPPER}} .bt-pricing']);
        $this->add_responsive_control('exc_container_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('exc_container_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'exc_container_shadow', 'selector' => '{{WRAPPER}} .bt-pricing']);

        $this->add_control('exc_price_heading', ['label' => __('Prix', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'exc_price_typography', 'selector' => '{{WRAPPER}} .bt-pricing__price']);
        $this->add_control('exc_price_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__price' => 'color: {{VALUE}}']]);

        $this->add_control('exc_discount_heading', ['label' => __('Badge remise', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'exc_discount_typography', 'selector' => '{{WRAPPER}} .bt-pricing__discount']);
        $this->add_control('exc_discount_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__discount' => 'color: {{VALUE}}']]);
        $this->add_control('exc_discount_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__discount' => 'background-color: {{VALUE}}']]);

        $this->add_control('exc_slot_heading', ['label' => __('Boutons pill', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['exc_layout' => 'buttons']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'exc_slot_typography', 'selector' => '{{WRAPPER}} .bt-pricing__slot', 'condition' => ['exc_layout' => 'buttons']]);
        $this->start_controls_tabs('exc_slot_state_tabs', ['condition' => ['exc_layout' => 'buttons']]);
        $this->start_controls_tab('exc_slot_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('exc_slot_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__slot' => 'color: {{VALUE}}']]);
        $this->add_control('exc_slot_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__slot' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'exc_slot_border', 'selector' => '{{WRAPPER}} .bt-pricing__slot']);
        $this->end_controls_tab();
        $this->start_controls_tab('exc_slot_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('exc_slot_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__slot:hover' => 'color: {{VALUE}}']]);
        $this->add_control('exc_slot_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__slot:hover' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'exc_slot_border_hover', 'selector' => '{{WRAPPER}} .bt-pricing__slot:hover']);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('exc_slot_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__slot' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['exc_layout' => 'buttons']]);
        $this->add_responsive_control('exc_slot_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__slot' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['exc_layout' => 'buttons']]);

        $this->add_control('exc_trigger_heading', ['label' => __('Bouton Réserver', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['exc_trigger_mode!' => 'none']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'exc_trigger_btn_typography', 'selector' => '{{WRAPPER}} .bt-pricing__trigger', 'condition' => ['exc_trigger_mode!' => 'none']]);
        $this->start_controls_tabs('exc_trigger_btn_state_tabs', ['condition' => ['exc_trigger_mode!' => 'none']]);
        $this->start_controls_tab('exc_trigger_btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('exc_trigger_btn_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'color: {{VALUE}}']]);
        $this->add_control('exc_trigger_btn_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->start_controls_tab('exc_trigger_btn_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('exc_trigger_btn_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger:hover' => 'color: {{VALUE}}']]);
        $this->add_control('exc_trigger_btn_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('exc_trigger_btn_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['exc_trigger_mode!' => 'none']]);
        $this->add_responsive_control('exc_trigger_btn_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['exc_trigger_mode!' => 'none']]);
        $this->add_responsive_control('exc_trigger_align', ['label' => __('Alignement', 'blacktenderscore'), 'type' => Controls_Manager::CHOOSE, 'options' => ['flex-start' => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'], 'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-h-align-center'], 'flex-end' => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'], 'stretch' => ['title' => __('Pleine largeur', 'blacktenderscore'), 'icon' => 'eicon-h-align-stretch']], 'default' => 'flex-start', 'selectors' => ['{{WRAPPER}} .bt-pricing-trigger-wrap' => 'display: flex; justify-content: {{VALUE}}', '{{WRAPPER}} .bt-pricing-trigger-wrap .bt-pricing__trigger' => 'align-self: {{VALUE}}'], 'condition' => ['exc_trigger_mode!' => 'none']]);
        $this->add_responsive_control('exc_trigger_width', ['label' => __('Largeur bouton', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%'], 'range' => ['px' => ['min' => 100, 'max' => 800], '%' => ['min' => 10, 'max' => 100]], 'selectors' => ['{{WRAPPER}} .bt-pricing-trigger-wrap .bt-pricing__trigger' => 'width: {{SIZE}}{{UNIT}}'], 'condition' => ['exc_trigger_align!' => 'stretch', 'exc_trigger_mode!' => 'none']]);

        $this->end_controls_section();

        // ── 📋 Onglets Forfaits / Devis (tabs — non fusionné) ─────────
        $this->register_tabs_nav_style(
            'wrapper_tab',
            '📋 Onglets Forfaits / Devis',
            '{{WRAPPER}} .bt-bprice-wrapper__tab',
            '{{WRAPPER}} .bt-bprice-wrapper__tab--active',
            '{{WRAPPER}} .bt-bprice-wrapper__tablist',
            ['wrapper_enable' => 'yes'],
            [
                'with_hover'      => true,
                'with_radius'     => true,
                'with_indicator'  => true,
                'with_direction'  => true,
                'with_justify'    => true,
                'with_scroll'     => true,
                'with_breakpoint' => true,
                'with_panel'      => true,
                'panel_sel'       => '{{WRAPPER}} .bt-bprice-wrapper__panel--active',
            ]
        );

        // ── 📋 Devis — Étapes & formulaire ────────────────────────────
        $this->start_controls_section('style_quote_form', [
            'label'     => __('📋 Devis — Étapes & formulaire', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('step_heading_steps', ['label' => __('Étapes', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_control('step_closed_opacity', ['label' => __('Opacité étape fermée', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'size_units' => [''], 'range' => ['' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]], 'default' => ['size' => 0.75], 'selectors' => ['{{WRAPPER}} .bt-quote-step:not(.bt-quote-step--active)' => 'opacity: {{SIZE}}']]);
        $this->add_control('step_transition', ['label' => __('Durée transition (ms)', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'size_units' => [''], 'range' => ['' => ['min' => 100, 'max' => 800, 'step' => 50]], 'default' => ['size' => 300], 'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'transition: opacity {{SIZE}}ms ease, max-height {{SIZE}}ms ease']]);
        $this->add_responsive_control('step_gap', ['label' => __('Espacement étapes', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px'], 'range' => ['px' => ['min' => 0, 'max' => 40]], 'default' => ['size' => 16, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .bt-quote' => 'gap: {{SIZE}}{{UNIT}}']]);
        $this->add_control('step_number_bg', ['label' => __('Fond numéro étape', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__number' => 'background-color: {{VALUE}}']]);
        $this->add_control('step_number_color', ['label' => __('Couleur numéro étape', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__number' => 'color: {{VALUE}}']]);

        $this->add_control('quote_container_heading', ['label' => __('Conteneur', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('quote_container_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'quote_container_border', 'selector' => '{{WRAPPER}} .bt-quote']);
        $this->add_responsive_control('quote_container_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('quote_container_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'quote_container_shadow', 'selector' => '{{WRAPPER}} .bt-quote']);

        $this->add_control('quote_next_heading', ['label' => __('Bouton Suivant', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'quote_next_typography', 'selector' => '{{WRAPPER}} .bt-quote-step__next']);
        $this->start_controls_tabs('quote_next_state_tabs');
        $this->start_controls_tab('quote_next_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('quote_next_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'color: {{VALUE}}']]);
        $this->add_control('quote_next_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->start_controls_tab('quote_next_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('quote_next_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next:hover' => 'color: {{VALUE}}']]);
        $this->add_control('quote_next_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('quote_next_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('quote_next_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);

        $this->add_control('fields_heading', ['label' => __('Champs formulaire', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('field_label_color',  ['label' => __('Couleur labels', 'blacktenderscore'),  'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-fields__label, {{WRAPPER}} .bt-quote-datepicker__label' => 'color: {{VALUE}}']]);
        $this->add_control('field_bg',           ['label' => __('Fond champs', 'blacktenderscore'),     'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'background-color: {{VALUE}}']]);
        $this->add_control('field_border_color', ['label' => __('Bordure champs', 'blacktenderscore'),  'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'border-color: {{VALUE}}']]);
        $this->add_control('field_focus_color',  ['label' => __('Bordure focus', 'blacktenderscore'),   'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-fields__input:focus, {{WRAPPER}} .bt-quote-datepicker__input:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 2px color-mix(in srgb, {{VALUE}} 20%, transparent)']]);
        $this->add_responsive_control('field_radius',  ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px'], 'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('field_padding', ['label' => __('Padding', 'blacktenderscore'),       'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);

        $this->add_control('duration_cards_heading', ['label' => __('Cards durée', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('duration_card_border',        ['label' => __('Bordure', 'blacktenderscore'),            'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-duration-card' => 'border-color: {{VALUE}}']]);
        $this->add_control('duration_card_bg',            ['label' => __('Fond', 'blacktenderscore'),               'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-duration-card' => 'background-color: {{VALUE}}']]);
        $this->add_control('duration_card_active_border', ['label' => __('Bordure sélectionné', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}']]);
        $this->add_control('duration_card_active_bg',     ['label' => __('Fond sélectionné', 'blacktenderscore'),    'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'background-color: {{VALUE}}']]);
        $this->add_responsive_control('duration_card_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px'], 'selectors' => ['{{WRAPPER}} .bt-quote-duration-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);

        $this->end_controls_section();

        // ── 📋 Devis — Récap & popup ──────────────────────────────────
        $this->start_controls_section('style_quote_recap', [
            'label'     => __('📋 Devis — Récap & popup', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['wrapper_enable' => 'yes'],
        ]);

        $this->add_control('msg_heading', ['label' => __('Messages résultat', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_control('msg_success_bg',    ['label' => __('Fond succès', 'blacktenderscore'),  'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-message--success' => 'background-color: {{VALUE}}']]);
        $this->add_control('msg_success_color', ['label' => __('Texte succès', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-message--success' => 'color: {{VALUE}}']]);
        $this->add_control('msg_error_bg',      ['label' => __('Fond erreur', 'blacktenderscore'),  'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-message--error' => 'background-color: {{VALUE}}']]);
        $this->add_control('msg_error_color',   ['label' => __('Texte erreur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-message--error' => 'color: {{VALUE}}']]);

        $this->add_control('popup_heading', ['label' => __('Popup bateau', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['step_boat_enable' => 'yes']]);
        $this->add_responsive_control('popup_max_width', ['label' => __('Largeur max', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'size_units' => ['px', '%', 'vw'], 'range' => ['px' => ['min' => 300, 'max' => 1200]], 'default' => ['size' => 800, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .bt-quote-popup' => 'max-width: {{SIZE}}{{UNIT}}'], 'condition' => ['step_boat_enable' => 'yes']]);
        $this->add_responsive_control('popup_padding',   ['label' => __('Padding', 'blacktenderscore'),     'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote-popup' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['step_boat_enable' => 'yes']]);
        $this->add_responsive_control('popup_radius',    ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%'], 'selectors' => ['{{WRAPPER}} .bt-quote-popup' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['step_boat_enable' => 'yes']]);
        $this->add_control('popup_overlay_color', ['label' => __('Couleur overlay', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-popup::backdrop' => 'background-color: {{VALUE}}'], 'condition' => ['step_boat_enable' => 'yes']]);

        $this->add_control('submit_heading', ['label' => __('Bouton envoi', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'quote_submit_typography', 'selector' => '{{WRAPPER}} .bt-quote-submit']);
        $this->start_controls_tabs('quote_submit_state_tabs');
        $this->start_controls_tab('quote_submit_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('quote_submit_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'color: {{VALUE}}']]);
        $this->add_control('quote_submit_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->start_controls_tab('quote_submit_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('quote_submit_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit:hover' => 'color: {{VALUE}}']]);
        $this->add_control('quote_submit_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_responsive_control('quote_submit_padding', ['label' => __('Padding', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('quote_submit_radius', ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px', '%', 'em'], 'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);

        $this->end_controls_section();
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $mode = $s['pricing_mode'] ?? 'boat';

        if ($mode === 'excursion') {
            $this->render_excursion_mode($s, $post_id);
        } else {
            $this->render_boat_mode($s, $post_id);
        }
    }

    /**
     * Mode bateau : trigger + wrapper + tarifs + devis.
     */
    private function render_boat_mode(array $s, int $post_id): void {
        $trigger_mode = $s['trigger_mode'] ?? 'none';

        if ($trigger_mode !== 'none') {
            $this->render_trigger_open($s, $trigger_mode);
        }

        $this->render_inner_content($s, $post_id);

        if ($trigger_mode !== 'none') {
            $this->render_trigger_close($trigger_mode);
        }
    }

    /**
     * Mode excursion : même structure que le mode bateau.
     *
     * [Bouton Réserver] ← exc_trigger_mode (optionnel, wrape tout)
     *   └─ [Forfaits] [Devis] ← wrapper tabs (si wrapper_enable)
     *        ├─ Panel Forfaits : tabs/buttons forfait + booking Regiondo
     *        └─ Panel Devis : formulaire multi-étapes
     */
    private function render_excursion_mode(array $s, int $post_id): void {
        $trigger_mode = $s['exc_trigger_mode'] ?? 'none';

        if ($trigger_mode !== 'none') {
            $this->render_exc_trigger_open($s, $trigger_mode);
        }

        $this->render_exc_inner_content($s, $post_id);

        if ($trigger_mode !== 'none') {
            $this->render_trigger_close($trigger_mode);
        }
    }

    /**
     * Contenu interne mode excursion :
     * Wrapper tabs [Forfaits | Devis] → Panel 1 : forfaits → Panel 2 : formulaire.
     * Même pattern que render_inner_content() du mode bateau.
     */
    private function render_exc_inner_content(array $s, int $post_id): void {
        $wrapper_enabled = (($s['wrapper_enable'] ?? '') === 'yes');

        if ($wrapper_enabled) {
            $this->render_wrapper_open($s);
        }

        // Panel 1 : Tarifs excursion (tabs ou buttons + booking Regiondo)
        $this->render_excursion_pricing($s, $post_id);

        if ($wrapper_enabled) {
            $this->render_wrapper_between($s);
            $this->render_quote_form($s, $post_id);
            $this->render_wrapper_close($s);
        }
    }

    /**
     * Rendu interne mode bateau (wrapper + tarifs + devis).
     */
    private function render_inner_content(array $s, int $post_id): void {
        $wrapper_enabled = ($s['wrapper_enable'] === 'yes');

        if ($wrapper_enabled) {
            $this->render_wrapper_open($s);
        }

        // ── Panel 1 : Tarifs (contenu existant) ─────────────────────────────
        $this->render_pricing_content($s, $post_id);

        if ($wrapper_enabled) {
            $this->render_wrapper_between($s);
            $this->render_quote_form($s, $post_id);
            $this->render_wrapper_close($s);
        }
    }

    // ── Trigger (bouton reveal) ─────────────────────────────────────────────

    /**
     * Ouvre le wrapper trigger/reveal (pattern identique à PricingTabs).
     * Le JS existant (initPricingTrigger dans bt-elementor.js) gère déjà
     * data-bt-trigger="reveal" + data-bt-reveal-target.
     */
    private function render_trigger_open(array $s, string $mode): void {
        $trigger_label   = esc_html($s['trigger_label'] ?: __('Voir les tarifs', 'blacktenderscore'));
        $reveal_target   = $mode === 'reveal' ? trim((string) ($s['reveal_target_id'] ?? '')) : '';
        $wrap_id         = 'bt-bprice-trigger-' . $this->get_id();
        $data_reveal_tgt = $reveal_target !== '' ? ' data-bt-reveal-target="' . esc_attr($reveal_target) . '"' : '';
        $fullwidth_cls   = ($s['trigger_fullwidth'] ?? '') === 'yes' ? ' bt-pricing__trigger--fullwidth' : '';

        echo '<div class="bt-bprice-trigger-wrap" id="' . esc_attr($wrap_id) . '"'
           . ' data-bt-trigger="' . esc_attr($mode) . '"' . $data_reveal_tgt . '>';
        echo '<button type="button" class="bt-pricing__trigger' . $fullwidth_cls . '" aria-expanded="false">'
           . $trigger_label . '</button>';

        echo '<div class="bt-pricing__reveal-content">';
        echo '<div>'; // inner block for animation
    }

    /**
     * Ferme le wrapper trigger/reveal.
     */
    private function render_trigger_close(string $mode): void {
        echo '</div>'; // inner block
        echo '</div>'; // .bt-pricing__reveal-content
        echo '</div>'; // .bt-bprice-trigger-wrap
    }

    /**
     * Ouvre le trigger pour le mode excursion.
     */
    private function render_exc_trigger_open(array $s, string $mode): void {
        $trigger_label   = esc_html($s['exc_trigger_label'] ?: __('Réserver', 'blacktenderscore'));
        $reveal_target   = $mode === 'reveal' ? trim((string) ($s['exc_reveal_target_id'] ?? '')) : '';
        $wrap_id         = 'bt-pricing-trigger-' . $this->get_id();
        $data_reveal_tgt = $reveal_target !== '' ? ' data-bt-reveal-target="' . esc_attr($reveal_target) . '"' : '';
        $fullwidth_cls   = ($s['exc_trigger_fullwidth'] ?? '') === 'yes' ? ' bt-pricing__trigger--fullwidth' : '';

        echo '<div class="bt-pricing-trigger-wrap" id="' . esc_attr($wrap_id) . '"'
           . ' data-bt-trigger="' . esc_attr($mode) . '"' . $data_reveal_tgt . '>';
        echo '<button type="button" class="bt-pricing__trigger' . $fullwidth_cls . '" aria-expanded="false">'
           . $trigger_label . '</button>';

        echo '<div class="bt-pricing__reveal-content">';
        echo '<div>'; // inner block for animation
    }

    // ── Excursion Pricing (porté de PricingTabs) ────────────────────────────

    /**
     * Rendu des forfaits excursion : tabs ou buttons + booking Regiondo.
     * Porté depuis PricingTabs avec les settings préfixés exc_.
     */
    // ── Excursion Pricing ──────────────────────────────────────────────────
    // Provided by BtExcursionPricing trait

    // ── Wrapper ─────────────────────────────────────────────────────────────

    /**
     * Ouvre le wrapper parent avec les onglets Forfaits / Devis.
     */
    private function render_wrapper_open(array $s): void {
        $uid = 'bt-bpw-' . $this->get_id();

        echo '<div class="bt-bprice-wrapper" data-bt-tabs data-bt-panel-class="bt-bprice-wrapper__panel">';

        // Tablist
        echo '<div class="bt-bprice-wrapper__tablist" role="tablist">';

        $tab1_label = esc_html($s['wrapper_tab1_label'] ?: __('Forfaits', 'blacktenderscore'));
        $tab2_label = esc_html($s['wrapper_tab2_label'] ?: __('Demande de devis', 'blacktenderscore'));

        echo '<button class="bt-bprice-wrapper__tab bt-bprice-wrapper__tab--active" id="' . esc_attr($uid) . '-tab-0" role="tab"'
           . ' aria-selected="true" aria-controls="' . esc_attr($uid) . '-panel-0" tabindex="0">'
           . $tab1_label . '</button>';

        echo '<button class="bt-bprice-wrapper__tab" id="' . esc_attr($uid) . '-tab-1" role="tab"'
           . ' aria-selected="false" aria-controls="' . esc_attr($uid) . '-panel-1" tabindex="-1">'
           . $tab2_label . '</button>';

        echo '</div>'; // tablist

        // Panel 1 open
        echo '<div class="bt-bprice-wrapper__panel bt-bprice-wrapper__panel--active"'
           . ' id="' . esc_attr($uid) . '-panel-0" role="tabpanel"'
           . ' aria-labelledby="' . esc_attr($uid) . '-tab-0">';
    }

    /**
     * Ferme le panel 1, ouvre le panel 2.
     */
    private function render_wrapper_between(array $s): void {
        $uid = 'bt-bpw-' . $this->get_id();

        echo '</div>'; // close panel-0

        echo '<div class="bt-bprice-wrapper__panel"'
           . ' id="' . esc_attr($uid) . '-panel-1" role="tabpanel"'
           . ' aria-labelledby="' . esc_attr($uid) . '-tab-1">';
    }

    /**
     * Ferme le panel 2 et le wrapper.
     */
    private function render_wrapper_close(array $s): void {
        echo '</div>'; // close panel-1
        echo '</div>'; // close .bt-bprice-wrapper
    }

    // ── Contenu tarifs existant ──────────────────────────────────────────────

    /**
     * Rendu du contenu tarification (inchangé — extrait de l'ancien render()).
     */
    private function render_pricing_content(array $s, int $post_id): void {
        $currency   = esc_html($s['currency'] ?: '€');
        $price_note = (string) get_field('boat_price_note', $post_id);
        $fuel_incl  = (bool)  get_field('boat_fuel_included', $post_id);
        $deposit    = (float)(get_field('boat_deposit',        $post_id) ?? 0);
        $price_half = (float)(get_field('boat_price_half',     $post_id) ?? 0);
        $half_time  = get_field('boat_half_day_time', $post_id);
        $price_full = (float)(get_field('boat_price_full',     $post_id) ?? 0);
        $full_time  = get_field('boat_full_day_time', $post_id);
        $zones      = get_field('boat_custom_price_by_departure', $post_id);

        $pax_max = $s['show_per_person'] === 'yes' ? (int) get_field('boat_pax_max', $post_id) : 0;

        $has_content = ($s['show_half'] === 'yes' && $price_half)
                    || ($s['show_full'] === 'yes' && $price_full)
                    || ($s['show_zones'] === 'yes' && !empty($zones));

        if (!$has_content) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucun tarif bateau trouvé. Vérifiez que les champs ACF (boat_price_half, boat_price_full) sont remplis sur ce post.', 'blacktenderscore'));
            }
            return;
        }

        echo '<div class="bt-bprice">';

        $this->render_section_title($s, 'bt-bprice__title');

        $layout = $s['layout'] ?: 'cards';

        $cards = [];
        if ($s['show_half'] === 'yes' && $price_half) {
            $cards[] = [
                'label'    => $s['label_half'] ?: __('Demi-journée', 'blacktenderscore'),
                'price'    => (float) $price_half,
                'duration' => $half_time ? "{$half_time} h" : '',
            ];
        }
        if ($s['show_full'] === 'yes' && $price_full) {
            $cards[] = [
                'label'    => $s['label_full'] ?: __('Journée complète', 'blacktenderscore'),
                'price'    => (float) $price_full,
                'duration' => $full_time ? "{$full_time} h" : '',
            ];
        }

        if (!empty($cards)) {
            if ($layout === 'tabs') {
                $this->render_tabs($cards, $s, $currency, $price_note, $deposit, $fuel_incl, $pax_max);
            } elseif ($layout === 'table') {
                $this->render_table($cards, $s, $currency, $price_note, $deposit, $fuel_incl, $pax_max);
            } else {
                $this->render_cards($cards, $s, $currency, $price_note, $deposit, $fuel_incl, $pax_max);
            }
        }

        if ($s['show_zones'] === 'yes' && $layout !== 'tabs' && !empty($zones)) {
            $this->render_zones($zones, $s, $currency);
        }

        echo '</div>'; // .bt-bprice
    }

    // ── Formulaire de devis multi-étapes ─────────────────────────────────────

    /**
     * Rendu du formulaire de devis (onglet 2 du wrapper).
     */
    private function render_quote_form(array $s, int $post_id): void {
        $pricing_mode  = $s['pricing_mode'] ?? 'boat';

        // Config JSON pour le JS (noms de champs, labels…)
        $config = [
            'repeater_slug'    => $s['step_boat_repeater_slug'] ?? 'boat_custom_price_by_departure',
            'rep_price_half'   => $s['step_boat_rep_price_half'] ?? 'boat_price_for_half_day',
            'rep_price_full'   => $s['step_boat_rep_price_full'] ?? 'boat_price_for_full_day',
            'rep_nav_zone'     => $s['step_boat_rep_nav_zone'] ?? 'boat_navigation_zone',
            'rep_duration'     => $s['step_boat_rep_duration'] ?? 'boat_duration_taxonomy',
            'rep_carburant'    => $s['step_boat_rep_carburant'] ?? 'boat_carburant',
            'currency'         => ($pricing_mode === 'excursion' ? ($s['exc_currency'] ?? '€') : ($s['currency'] ?? '€')) ?: '€',
            'duration_options' => [
                'half'   => $s['step_dates_opt_half'] ?: __('Demi-journée', 'blacktenderscore'),
                'full'   => $s['step_dates_opt_full'] ?: __('Journée entière', 'blacktenderscore'),
                'multi'  => $s['step_dates_opt_multi'] ?: __('Plusieurs jours', 'blacktenderscore'),
                'custom' => $s['step_dates_opt_custom'] ?: __('Demande spécifique', 'blacktenderscore'),
            ],
            'msg_success'      => $s['step_submit_msg_success'] ?: __('Votre demande a bien été envoyée !', 'blacktenderscore'),
            'msg_error'        => $s['step_submit_msg_error'] ?: __('Une erreur est survenue.', 'blacktenderscore'),
            'pricing_mode'     => $pricing_mode,
            'boat_loop_tpl'    => (int) ($s['step_boat_loop_tpl'] ?? 0),
            'exc_loop_tpl'     => (int) ($s['step_exc_loop_tpl'] ?? 0),
        ];

        echo '<div class="bt-quote" role="list"'
           . ' data-bt-quote'
           . ' data-ajax-url="' . esc_attr(admin_url('admin-ajax.php')) . '"'
           . ' data-nonce="' . esc_attr(wp_create_nonce('bt_quote_nonce')) . '"'
           . ' data-config="' . esc_attr(wp_json_encode($config)) . '">';

        $step_num  = 0;
        $post_type = get_post_type($post_id);
        $is_excursion = ($post_type === 'excursion');

        // Step — Excursion
        if ($pricing_mode === 'excursion') {
            if ($is_excursion) {
                $step_num++;
                $this->render_quote_step_excursion($s, $post_id, $step_num);
            }
        } else {
            if ($s['step_exc_enable'] === 'yes') {
                $step_num++;
                $this->render_quote_step_excursion($s, $post_id, $step_num);
            }
        }

        // Step — Bateau
        if ($pricing_mode === 'excursion') {
            if (($s['step_boat_enable'] ?? 'yes') === 'yes') {
                $step_num++;
                $this->render_quote_step_boat($s, $post_id, $step_num);
            }
        } else {
            if ($s['step_boat_enable'] === 'yes') {
                $step_num++;
                $this->render_quote_step_boat($s, $post_id, $step_num);
            }
        }

        // Step — Dates
        if ($s['step_dates_enable'] === 'yes') {
            $step_num++;
            $this->render_quote_step_dates($s, $step_num);
        }

        // Step — Coordonnées
        if ($s['step_contact_enable'] === 'yes') {
            $step_num++;
            $this->render_quote_step_contact($s, $step_num);
        }

        // Step — Envoi
        $step_num++;
        $this->render_quote_step_submit($s, $step_num);

        echo '</div>'; // .bt-quote

        // ── Dialog popup bateau ──────────────────────────────────────────
        echo '<dialog class="bt-quote-popup" data-bt-quote-popup role="dialog" aria-modal="true">';
        echo '<button type="button" class="bt-quote-popup__close" aria-label="' . esc_attr__('Fermer', 'blacktenderscore') . '">&times;</button>';
        echo '<div class="bt-quote-popup__content" data-bt-quote-popup-content></div>';
        echo '</dialog>';
    }

    // ── Sous-méthodes de rendu des étapes du formulaire de devis ─────────────

    /**
     * Rendu de l'étape excursion.
     * Mode excursion : affiche l'excursion courante + choix sur mesure.
     * Mode bateau : affiche un sélecteur d'excursion ou auto-select.
     */
    private function render_quote_step_excursion(array $s, int $post_id, int $step_num): void {
        $post_type    = get_post_type($post_id);
        $pricing_mode = $s['pricing_mode'] ?? 'boat';
        $is_excursion = ($post_type === 'excursion');

        if ($pricing_mode === 'excursion') {
            // ── Mode excursion ──────────────────────────────────────────────
            $step_cls = 'bt-quote-step' . ($step_num === 1 ? ' bt-quote-step--active' : '');
            $aria_exp = $step_num === 1 ? 'true' : 'false';
            $aria_cur = $step_num === 1 ? ' aria-current="step"' : '';

            echo '<div class="' . esc_attr($step_cls) . '" role="listitem"'
               . $aria_cur . ' aria-expanded="' . $aria_exp . '" data-step="' . $step_num . '" data-step-type="excursion">';
            echo '<div class="bt-quote-step__header">';
            echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
            echo '<span class="bt-quote-step__title">' . esc_html($s['step_exc_title'] ?? __('Votre excursion', 'blacktenderscore')) . '</span>';
            echo '<span class="bt-quote-step__summary"></span>';
            echo '</div>';
            echo '<div class="bt-quote-step__content">';

            // Excursion courante
            echo '<div class="bt-quote-exc-auto" data-exc-id="' . esc_attr($post_id) . '">';
            echo '<p class="bt-quote-exc-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
            echo '<input type="hidden" name="excursion_id" value="' . esc_attr($post_id) . '">';
            echo '</div>';

            // Choix : cette excursion OU sur mesure
            echo '<div class="bt-quote-exc-choice" data-bt-exc-choice>';
            echo '<button type="button" class="bt-quote-exc-choice__btn bt-quote-exc-choice__btn--selected" data-exc-choice="current" aria-selected="true">';
            echo esc_html__('Cette excursion', 'blacktenderscore');
            echo '</button>';
            echo '<button type="button" class="bt-quote-exc-choice__btn" data-exc-choice="custom" aria-selected="false">';
            echo esc_html__('Expérience sur mesure', 'blacktenderscore');
            echo '</button>';
            echo '</div>';

            // Zone texte sur mesure (cachée par défaut)
            echo '<div class="bt-quote-exc-custom" style="display:none">';
            echo '<textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="exc_custom_request" placeholder="' . esc_attr__('Décrivez votre projet...', 'blacktenderscore') . '" rows="3"></textarea>';
            echo '</div>';

            echo '</div>'; // __content
            echo '<div class="bt-quote-step__actions">';
            echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
            echo '</div>';
            echo '</div>'; // .bt-quote-step
        } else {
            // ── Mode bateau ─────────────────────────────────────────────────
            $auto_selected = $is_excursion;
            $step_cls = 'bt-quote-step' . ($step_num === 1 ? ' bt-quote-step--active' : '');
            $aria_exp = $step_num === 1 ? 'true' : 'false';
            $aria_cur = $step_num === 1 ? ' aria-current="step"' : '';

            echo '<div class="' . esc_attr($step_cls) . '" role="listitem"'
               . $aria_cur . ' aria-expanded="' . $aria_exp . '" data-step="' . $step_num . '" data-step-type="excursion">';

            echo '<div class="bt-quote-step__header">';
            echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
            echo '<span class="bt-quote-step__title">' . esc_html($s['step_exc_title'] ?: __('Choix de l\'excursion', 'blacktenderscore')) . '</span>';
            echo '<span class="bt-quote-step__summary"></span>';
            echo '</div>';

            echo '<div class="bt-quote-step__content">';

            if ($auto_selected) {
                echo '<div class="bt-quote-exc-auto" data-exc-id="' . esc_attr($post_id) . '">';
                echo '<p class="bt-quote-exc-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
                echo '<input type="hidden" name="excursion_id" value="' . esc_attr($post_id) . '">';
                echo '</div>';
            } else {
                $this->render_excursion_cards($s);
            }

            echo '</div>'; // __content
            echo '<div class="bt-quote-step__actions">';
            echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
            echo '</div>';
            echo '</div>'; // .bt-quote-step
        }
    }

    /**
     * Rendu de l'étape bateau.
     * Mode excursion : affiche les bateaux liés ou auto-select.
     * Mode bateau : affiche un conteneur AJAX pour le chargement dynamique.
     */
    private function render_quote_step_boat(array $s, int $post_id, int $step_num): void {
        $post_type    = get_post_type($post_id);
        $pricing_mode = $s['pricing_mode'] ?? 'boat';
        $is_excursion = ($post_type === 'excursion');
        $is_boat      = ($post_type === 'boat');

        if ($pricing_mode === 'excursion') {
            // ── Mode excursion ──────────────────────────────────────────────
            $auto_boat = $is_boat;
            $step_cls  = 'bt-quote-step' . ($step_num === 1 ? ' bt-quote-step--active' : '');
            $aria_exp  = $step_num === 1 ? 'true' : 'false';
            $aria_cur  = $step_num === 1 ? ' aria-current="step"' : '';

            echo '<div class="' . esc_attr($step_cls) . '" role="listitem"'
               . $aria_cur . ' aria-expanded="' . $aria_exp . '" data-step="' . $step_num . '" data-step-type="boat">';
            echo '<div class="bt-quote-step__header">';
            echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
            echo '<span class="bt-quote-step__title">' . esc_html($s['step_boat_title'] ?: __('Choix du bateau', 'blacktenderscore')) . '</span>';
            echo '<span class="bt-quote-step__summary"></span>';
            echo '</div>';
            echo '<div class="bt-quote-step__content">';

            if ($auto_boat) {
                // Auto-select boat (on est sur une page bateau)
                echo '<div class="bt-quote-boat-auto" data-boat-id="' . esc_attr($post_id) . '">';
                echo '<p class="bt-quote-boat-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
                echo '<input type="hidden" name="boat_id" value="' . esc_attr($post_id) . '">';
                echo '</div>';
            } elseif ($is_excursion) {
                // Bateaux liés à l'excursion — chargés statiquement
                $this->render_linked_boat_cards($s, $post_id);
            } else {
                // Chargés via AJAX
                echo '<div class="bt-quote-boat-cards" data-bt-quote-boats></div>';
            }

            echo '</div>'; // __content
            echo '<div class="bt-quote-step__actions">';
            echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
            echo '</div>';
            echo '</div>'; // .bt-quote-step
        } else {
            // ── Mode bateau ─────────────────────────────────────────────────
            echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="boat">';
            echo '<div class="bt-quote-step__header">';
            echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
            echo '<span class="bt-quote-step__title">' . esc_html($s['step_boat_title'] ?: __('Choix du bateau', 'blacktenderscore')) . '</span>';
            echo '<span class="bt-quote-step__summary"></span>';
            echo '</div>';
            echo '<div class="bt-quote-step__content">';
            echo '<div class="bt-quote-boat-cards" data-bt-quote-boats></div>';
            echo '</div>';
            echo '<div class="bt-quote-step__actions">';
            echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
            echo '</div>';
            echo '</div>';
        }
    }

    /**
     * Rendu de l'étape dates (duration funnel, date pickers, timeslot).
     */
    private function render_quote_step_dates(array $s, int $step_num): void {
        $opt_half   = esc_html($s['step_dates_opt_half']   ?: __('Demi-journée', 'blacktenderscore'));
        $opt_full   = esc_html($s['step_dates_opt_full']   ?: __('Journée entière', 'blacktenderscore'));
        $opt_multi  = esc_html($s['step_dates_opt_multi']  ?: __('Plusieurs jours', 'blacktenderscore'));
        $opt_custom = esc_html($s['step_dates_opt_custom'] ?: __('Demande spécifique', 'blacktenderscore'));
        $lbl_date   = esc_html($s['step_dates_label_date']  ?: __('Date souhaitée', 'blacktenderscore'));
        $lbl_start  = esc_html($s['step_dates_label_start'] ?: __('Date de début', 'blacktenderscore'));
        $lbl_end    = esc_html($s['step_dates_label_end']   ?: __('Date de fin', 'blacktenderscore'));
        $ph_custom  = esc_attr($s['step_dates_custom_placeholder'] ?: __('Décrivez vos disponibilités...', 'blacktenderscore'));

        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="dates">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_dates_title'] ?: __('Dates de location', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        // Duration cards
        echo '<div class="bt-quote-duration-cards" data-bt-duration-select>';
        echo '<div class="bt-quote-duration-card" data-duration="half" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_half . '</span>';
        echo '</div>';
        echo '<div class="bt-quote-duration-card" data-duration="full" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_full . '</span>';
        echo '</div>';
        echo '<div class="bt-quote-duration-card" data-duration="multi" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_multi . '</span>';
        echo '</div>';
        echo '<div class="bt-quote-duration-card" data-duration="custom" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_custom . '</span>';
        echo '</div>';
        echo '</div>'; // .bt-quote-duration-cards

        // Single date picker (for half/full) — hidden by default
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--single" data-bt-datepicker data-range="0" style="display:none">';
        echo '<div class="bt-quote-datepicker__labels">';
        echo '<div class="bt-quote-datepicker__field">';
        echo '<label class="bt-quote-datepicker__label">' . $lbl_date . '</label>';
        echo '<input type="text" class="bt-quote-datepicker__input" name="date_start" readonly placeholder="jj/mm/aaaa">';
        echo '</div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker__calendar"></div>';

        // Matin / Après-midi (visible uniquement pour demi-journée)
        echo '<div class="bt-quote-timeslot" data-bt-timeslot style="display:none">';
        echo '<div class="bt-quote-timeslot__options">';
        echo '<button type="button" class="bt-quote-timeslot__btn" data-timeslot="matin" aria-selected="false">'
           . esc_html__('Matin', 'blacktenderscore') . '</button>';
        echo '<button type="button" class="bt-quote-timeslot__btn" data-timeslot="apres-midi" aria-selected="false">'
           . esc_html__('Après-midi', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '<input type="hidden" name="timeslot" value="">';
        echo '</div>';

        echo '</div>'; // .bt-quote-datepicker--single

        // Range date picker (for multi) — hidden by default
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--range" data-bt-datepicker data-range="1" style="display:none">';
        echo '<div class="bt-quote-datepicker__labels">';
        echo '<div class="bt-quote-datepicker__field">';
        echo '<label class="bt-quote-datepicker__label">' . $lbl_start . '</label>';
        echo '<input type="text" class="bt-quote-datepicker__input" name="date_start" readonly placeholder="jj/mm/aaaa">';
        echo '</div>';
        echo '<div class="bt-quote-datepicker__field">';
        echo '<label class="bt-quote-datepicker__label">' . $lbl_end . '</label>';
        echo '<input type="text" class="bt-quote-datepicker__input" name="date_end" readonly placeholder="jj/mm/aaaa">';
        echo '</div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker__calendar"></div>';
        echo '</div>'; // .bt-quote-datepicker--range

        // Custom textarea — hidden by default
        echo '<div class="bt-quote-custom-dates" style="display:none">';
        echo '<textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="date_custom" placeholder="' . $ph_custom . '" rows="3"></textarea>';
        echo '</div>';

        // Hidden input to store selected duration type
        echo '<input type="hidden" name="duration_type" value="">';

        echo '</div>'; // __content
        echo '<div class="bt-quote-step__actions">';
        echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Rendu de l'étape coordonnées (nom, email, téléphone).
     */
    private function render_quote_step_contact(array $s, int $step_num): void {
        $name_mode = $s['step_contact_name_mode'] ?: 'split';

        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="contact">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_contact_title'] ?: __('Vos coordonnées', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        echo '<div class="bt-quote-fields">';

        if ($name_mode === 'split') {
            echo '<div class="bt-quote-fields__row">';
            echo '<div class="bt-quote-fields__group">';
            echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_firstname'] ?: __('Prénom', 'blacktenderscore')) . '</label>';
            echo '<input type="text" class="bt-quote-fields__input" name="client_firstname" placeholder="' . esc_attr($s['step_contact_ph_firstname'] ?: __('Votre prénom', 'blacktenderscore')) . '" required>';
            echo '</div>';
            echo '<div class="bt-quote-fields__group">';
            echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_name'] ?: __('Nom', 'blacktenderscore')) . '</label>';
            echo '<input type="text" class="bt-quote-fields__input" name="client_name" placeholder="' . esc_attr($s['step_contact_ph_name'] ?: __('Votre nom', 'blacktenderscore')) . '" required>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="bt-quote-fields__group">';
            echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_name'] ?: __('Nom complet', 'blacktenderscore')) . '</label>';
            echo '<input type="text" class="bt-quote-fields__input" name="client_name" placeholder="' . esc_attr($s['step_contact_ph_name'] ?: __('Votre nom', 'blacktenderscore')) . '" required>';
            echo '</div>';
        }

        echo '<div class="bt-quote-fields__group">';
        echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_email'] ?: __('E-mail', 'blacktenderscore')) . '</label>';
        echo '<input type="email" class="bt-quote-fields__input" name="client_email" placeholder="' . esc_attr($s['step_contact_ph_email'] ?: 'votre@email.com') . '" required>';
        echo '</div>';

        echo '<div class="bt-quote-fields__group">';
        echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_phone'] ?: __('Téléphone', 'blacktenderscore')) . '</label>';
        echo '<input type="tel" class="bt-quote-fields__input" name="client_phone" placeholder="' . esc_attr($s['step_contact_ph_phone'] ?: '06 12 34 56 78') . '">';
        echo '</div>';

        echo '</div>'; // .bt-quote-fields
        echo '</div>'; // __content

        echo '<div class="bt-quote-step__actions">';
        echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Rendu de l'étape de confirmation et envoi.
     */
    private function render_quote_step_submit(array $s, int $step_num): void {
        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="submit">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_submit_title'] ?: __('Confirmation', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';
        echo '<div class="bt-quote-recap" data-bt-quote-recap></div>';
        echo '<button type="button" class="bt-quote-submit" data-bt-quote-submit>';
        echo esc_html($s['step_submit_btn_label'] ?: __('Envoyer ma demande', 'blacktenderscore'));
        echo '</button>';
        echo '<div class="bt-quote-message" data-bt-quote-message></div>';
        echo '</div>'; // __content
        echo '</div>'; // step
    }

    /**
     * Rendu des cards excursion via Loop template Elementor.
     * Chaque excursion est rendue avec le contexte post défini.
     */
    // render_excursion_cards() — Provided by BtExcursionPricing trait

    // render_linked_boat_cards() — Provided by BtExcursionPricing trait

    // render_default_boat_card() — Provided by BtExcursionPricing trait

    // ── Loop template helper ─────────────────────────────────────────────────
    // Provided by BtExcursionPricing trait

    // ── Helpers existants (inchangés) ────────────────────────────────────────

    private function render_deposit_html(float $deposit, array $s, string $currency): string {
        if ($s['show_deposit'] !== 'yes' || $deposit <= 0) return '';
        $dep_lbl = esc_html($s['label_deposit'] ?: __('Caution', 'blacktenderscore'));
        return '<p class="bt-bprice__deposit">' . $dep_lbl . ' : <strong>' . $this->format_price($deposit, $currency) . '</strong></p>';
    }

    private function format_price(float $price, string $currency): string {
        return esc_html(number_format($price, 0, ',', ' ') . ' ' . $currency);
    }

    private function per_person_html(float $price, int $pax_max, array $s, string $currency): string {
        if ($s['show_per_person'] !== 'yes' || $pax_max <= 0) return '';
        $pp  = $price / $pax_max;
        $lbl = esc_html($s['per_person_label'] ?: __('/ pers.', 'blacktenderscore'));
        return ' <span class="bt-bprice__per-person">(' . $this->format_price($pp, $currency) . ' ' . $lbl . ')</span>';
    }

    private function fuel_badge_html(bool $fuel_incl, array $s): string {
        if ($s['show_fuel_badge'] !== 'yes') return '';
        $cls = $fuel_incl ? 'bt-bprice__fuel--yes' : 'bt-bprice__fuel--no';
        $lbl = $fuel_incl
            ? esc_html($s['label_fuel_yes'] ?: __('Carburant inclus', 'blacktenderscore'))
            : esc_html($s['label_fuel_no']  ?: __('Carburant en sus', 'blacktenderscore'));
        return '<span class="bt-bprice__fuel ' . $cls . '">' . $lbl . '</span>';
    }

    private function card_body_html(array $card, array $s, string $currency, string $note, float $deposit, int $pax_max): string {
        $out = '';
        if ($s['show_price_note'] === 'yes' && $note) {
            $out .= '<span class="bt-bprice__note">' . esc_html($note) . '</span>';
        }
        $out .= '<div class="bt-bprice__amount-block">';
        $out .= '<span class="bt-bprice__amount">' . $this->format_price($card['price'], $currency) . '</span>';
        $out .= $this->per_person_html($card['price'], $pax_max, $s, $currency);
        if ($card['duration']) {
            $out .= ' <span class="bt-bprice__duration">— ' . esc_html($card['duration']) . '</span>';
        }
        $out .= '</div>';
        $out .= $this->render_deposit_html($deposit, $s, $currency);
        return $out;
    }

    // ── Render : Tabs ───────────────────────────────────────────────────────

    private function render_tabs(array $cards, array $s, string $currency, string $note, float $deposit, bool $fuel_incl, int $pax_max): void {
        $uid = 'bt-bprice-' . $this->get_id();

        echo '<div class="bt-bprice__tabs" data-bt-tabs>';

        echo '<div class="bt-bprice__tablist-wrap">';
        echo '<div class="bt-bprice__tablist" role="tablist">';
        foreach ($cards as $i => $card) {
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-panel-{$i}";
            $active = $i === 0 ? ' bt-bprice__tab--active' : '';
            $sel    = $i === 0 ? 'true' : 'false';
            $tabi   = $i === 0 ? '0' : '-1';
            echo "<button class=\"bt-bprice__tab{$active}\" id=\"{$tab_id}\" role=\"tab\" aria-selected=\"{$sel}\" aria-controls=\"{$pan_id}\" tabindex=\"{$tabi}\">";
            echo esc_html($card['label']);
            echo '</button>';
        }
        echo '</div>';
        echo '</div>'; // .bt-bprice__tablist-wrap

        foreach ($cards as $i => $card) {
            $tab_id     = "{$uid}-tab-{$i}";
            $pan_id     = "{$uid}-panel-{$i}";
            $active_cls = $i === 0 ? ' bt-bprice__panel--active' : '';

            echo "<div class=\"bt-bprice__panel{$active_cls}\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\">";
            echo '<div class="bt-bprice__card">';
            echo $this->card_body_html($card, $s, $currency, $note, $deposit, $pax_max);
            echo '</div>';
            echo '</div>';
        }

        echo $this->fuel_badge_html($fuel_incl, $s);

        echo '</div>'; // .bt-bprice__tabs
    }

    // ── Render : Cartes ─────────────────────────────────────────────────────

    private function render_cards(array $cards, array $s, string $currency, string $note, float $deposit, bool $fuel_incl, int $pax_max): void {
        echo '<div class="bt-bprice__cards">';
        foreach ($cards as $card) {
            echo '<div class="bt-bprice__card">';
            echo '<span class="bt-bprice__card-label">' . esc_html($card['label']) . '</span>';
            echo $this->card_body_html($card, $s, $currency, $note, $deposit, $pax_max);
            echo '</div>';
        }
        echo $this->fuel_badge_html($fuel_incl, $s);
        echo '</div>';
    }

    // ── Render : Tableau ────────────────────────────────────────────────────

    private function render_table(array $cards, array $s, string $currency, string $note, float $deposit, bool $fuel_incl, int $pax_max): void {
        $col_forfait  = $s['table_col_forfait']  ?: __('Forfait', 'blacktenderscore');
        $col_duration = $s['table_col_duration'] ?: __('Durée', 'blacktenderscore');
        $col_price    = $s['table_col_price']    ?: __('Prix', 'blacktenderscore');

        echo '<div class="bt-bprice__table-wrap"><table class="bt-bprice__table">';
        echo '<thead><tr><th>' . esc_html($col_forfait) . '</th><th>' . esc_html($col_duration) . '</th><th>' . esc_html($col_price) . '</th></tr></thead><tbody>';
        foreach ($cards as $card) {
            echo '<tr>';
            echo '<td class="bt-bprice__card-label">' . esc_html($card['label']) . '</td>';
            echo '<td class="bt-bprice__duration">' . esc_html($card['duration']) . '</td>';
            echo '<td class="bt-bprice__amount">' . $this->format_price($card['price'], $currency) . $this->per_person_html($card['price'], $pax_max, $s, $currency) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';

        echo $this->render_deposit_html($deposit, $s, $currency);
        echo $this->fuel_badge_html($fuel_incl, $s);
    }

    // ── Render : Zones ──────────────────────────────────────────────────────

    private function render_zones(array $zones, array $s, string $currency): void {
        $zones_title = $s['zones_title'] ?: __('Tarifs par zone de départ', 'blacktenderscore');
        echo '<div class="bt-bprice__zones">';
        echo '<h4 class="bt-bprice__zones-title">' . esc_html($zones_title) . '</h4>';
        echo '<div class="bt-bprice__table-wrap"><table class="bt-bprice__table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html($s['zones_col_zone'] ?: __('Zone', 'blacktenderscore')) . '</th>';
        echo '<th>' . esc_html($s['zones_col_half'] ?: __('½ journée', 'blacktenderscore')) . '</th>';
        echo '<th>' . esc_html($s['zones_col_full'] ?: __('Journée', 'blacktenderscore')) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($zones as $row) {
            $zone_terms = $row['boat_navigation_zone'] ?? null;
            $zone_label = '';
            if ($zone_terms) {
                $zone_ids   = is_array($zone_terms) ? $zone_terms : [$zone_terms];
                $zone_names = [];
                foreach ($zone_ids as $tid) {
                    $t = is_numeric($tid) ? get_term((int) $tid) : ($tid instanceof \WP_Term ? $tid : null);
                    if ($t && !is_wp_error($t)) $zone_names[] = $t->name;
                }
                $zone_label = implode(', ', $zone_names);
            }

            $p_half = $row['boat_price_for_half_day'] ?? '';
            $p_full = $row['boat_price_for_full_day'] ?? '';

            echo '<tr>';
            echo '<td class="bt-bprice__card-label">' . esc_html($zone_label) . '</td>';
            echo '<td class="bt-bprice__amount">' . ($p_half ? $this->format_price((float) $p_half, $currency) : '—') . '</td>';
            echo '<td class="bt-bprice__amount">' . ($p_full ? $this->format_price((float) $p_full, $currency) : '—') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div></div>';
    }

    // ── Utilitaires ─────────────────────────────────────────────────────────

    /**
     * Retourne la liste des templates Elementor pour le contrôle SELECT popup.
     */
    private static function get_elementor_templates_options(): array {
        $options = ['' => __('— Aucun (fallback auto)', 'blacktenderscore')];
        $templates = get_posts([
            'post_type'      => 'elementor_library',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        foreach ($templates as $tpl) {
            $options[$tpl->ID] = $tpl->post_title;
        }
        return $options;
    }
}
