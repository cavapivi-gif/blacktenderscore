<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

require_once __DIR__ . '/../traits/trait-bt-excursion-pricing.php';
require_once __DIR__ . '/../traits/trait-bt-boat-pricing.php';
require_once __DIR__ . '/../traits/trait-bt-pricing-shared.php';
require_once __DIR__ . '/../traits/trait-bt-quote-style-controls.php';

/**
 * Widget Elementor — Tarifs Body.
 *
 * Widget principal de tarification. Rend directement le contenu
 * (forfaits + devis) avec tous les contrôles content + style.
 * Détecte automatiquement le type de post (excursion / bateau).
 *
 * Déclenchement externe : n'importe quel bouton Elementor avec
 * l'attribut custom data-bt-pricing-trigger toggle la visibilité.
 *
 * Preview éditeur : affiche le vrai contenu du post en cours.
 */
class PricingBody extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtExcursionPricing;
    use \BlackTenders\Elementor\Traits\BtBoatPricing;
    use \BlackTenders\Elementor\Traits\BtPricingShared;
    use \BlackTenders\Elementor\Traits\BtQuoteStyleControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-pricing-body',
            'title'    => 'BT — Tarifs Body',
            'icon'     => 'eicon-price-list',
            'keywords' => ['tarif', 'body', 'prix', 'forfait', 'devis', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-pricing-tabs', 'bt-quote-form', 'bt-segmented-control', 'bt-quote-substep'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote', 'bt-segmented-control'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ── Mode & visibilité ──────────────────────────────────────────────
        $this->start_controls_section('section_body_mode', [
            'label' => __('Mode & visibilité', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('pricing_mode', [
            'label'   => __('Type de tarification', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'auto'       => __('Auto-détection (post courant)', 'blacktenderscore'),
                'excursion'  => __('Excursion', 'blacktenderscore'),
                'boat'       => __('Bateau', 'blacktenderscore'),
                'devis_only' => __('Devis uniquement', 'blacktenderscore'),
            ],
            'default' => 'auto',
        ]);

        $this->add_control('body_initial_state', [
            'label'   => __('État initial', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'visible' => __('Visible', 'blacktenderscore'),
                'hidden'  => __('Masqué (attend un trigger)', 'blacktenderscore'),
            ],
            'default'     => 'hidden',
            'description' => __('Ajoutez data-bt-pricing-trigger sur n\'importe quel bouton Elementor pour déclencher l\'affichage.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Forfaits excursion ─────────────────────────────────────────────
        $this->start_controls_section('section_exc_pricing', [
            'label'     => __('Forfaits excursion', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => ['auto', 'excursion']],
        ]);

        $this->register_section_title_controls(['title' => __('Tarifs', 'blacktenderscore')]);

        $this->add_control('exc_section_description', [
            'label' => __('Description', 'blacktenderscore'),
            'type'  => Controls_Manager::TEXTAREA,
            'rows'  => 3,
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('exc_repeater_slug', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'tarification_par_forfait',
        ]);

        $this->add_control('exc_layout', [
            'label'   => __('Layout forfaits', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'grid'   => __('Grille (2+ colonnes)', 'blacktenderscore'),
                'inline' => __('Liste (1 colonne)', 'blacktenderscore'),
            ],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('exc_cards_columns', [
            'label'   => __('Colonnes', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                '1' => '1',
                '2' => '2',
                '3' => '3',
            ],
            'default'        => '2',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => [
                '{{WRAPPER}} .bt-pricing .bt-forfaits__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)',
            ],
            'condition' => ['exc_layout' => 'grid'],
        ]);

        $this->add_control('exc_currency', [
            'label'   => __('Symbole devise', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => '€',
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

        $this->add_control('exc_show_duration', [
            'label'        => __('Afficher la durée', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Sous-champ repeater : exc_timeinbot (par forfait)', 'blacktenderscore'),
        ]);

        $this->add_control('exc_show_landing', [
            'label'        => __('Afficher le lieu de départ', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Champ ACF post-level : exp_landing_point', 'blacktenderscore'),
        ]);

        $this->add_control('exc_show_badge', [
            'label'        => __('Afficher les badges', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
            'description'  => __('Badges "Populaire" (ACF is_popular) et "Promo" (ACF is_a_discount)', 'blacktenderscore'),
        ]);

        $this->add_control('exc_popular_badge_label', [
            'label'     => __('Label badge populaire', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Populaire', 'blacktenderscore'),
            'condition' => ['exc_show_badge' => 'yes'],
        ]);

        $this->add_control('exc_discount_badge_label', [
            'label'     => __('Label badge promo', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Promo', 'blacktenderscore'),
            'condition' => ['exc_show_badge' => 'yes'],
        ]);

        $this->add_control('exc_show_discount', [
            'label'        => __('Afficher prix barré + remise', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Prix original barré et badge -X% quand le champ remise est renseigné', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Réservation Regiondo ───────────────────────────────────────────
        $this->start_controls_section('section_exc_booking', [
            'label'     => __('Réservation Regiondo', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => ['auto', 'excursion']],
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

        // ── Forfaits bateau ────────────────────────────────────────────────
        $this->start_controls_section('section_boat_content', [
            'label'     => __('Forfaits bateau', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => ['auto', 'boat']],
        ]);

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

        $this->add_control('show_half', [
            'label'       => __('Demi-journée', 'blacktenderscore'),
            'type'        => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'     => 'yes',
            'description' => __('Uniquement si les champs plats <code>boat_price_half</code> sont utilisés (sans repeater <code>boat_price</code>).', 'blacktenderscore'),
        ]);
        $this->add_control('label_half', [
            'label' => __('Label demi-journée', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Demi-journée', 'blacktenderscore'), 'condition' => ['show_half' => 'yes'],
        ]);
        $this->add_control('show_full', [
            'label'        => __('Journée complète', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Uniquement si le champ plat <code>boat_price_full</code> est utilisé (sans repeater <code>boat_price</code>).', 'blacktenderscore'),
        ]);
        $this->add_control('label_full', [
            'label' => __('Label journée', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Journée complète', 'blacktenderscore'), 'condition' => ['show_full' => 'yes'],
        ]);
        $this->add_control('show_per_person', [
            'label' => __('Prix / personne', 'blacktenderscore'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
        ]);
        $this->add_control('per_person_label', [
            'label' => __('Suffixe / pers.', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('/ pers.', 'blacktenderscore'), 'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_control('show_deposit', [
            'label' => __('Caution', 'blacktenderscore'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
        ]);
        $this->add_control('label_deposit', [
            'label' => __('Label caution', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Caution', 'blacktenderscore'), 'condition' => ['show_deposit' => 'yes'],
        ]);
        $this->add_control('show_boat_year', [
            'label'        => __('Afficher l\'année du bateau', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Valeur dynamique : champ ACF <code>boat_year</code>', 'blacktenderscore'),
        ]);
        $this->add_control('show_price_note', [
            'label' => __('Note tarifaire', 'blacktenderscore'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => 'yes',
        ]);
        $this->add_responsive_control('boat_cards_columns', [
            'label'   => __('Colonnes forfaits', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                '1' => __('1 (horizontal)', 'blacktenderscore'),
                '2' => __('2', 'blacktenderscore'),
                '3' => __('3', 'blacktenderscore'),
            ],
            'default'        => '2',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors' => [
                '{{WRAPPER}} .bt-bprice .bt-forfaits__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)',
            ],
            'condition' => ['pricing_mode' => ['auto', 'boat'], 'layout' => 'cards'],
        ]);

        $this->add_control('table_col_forfait', [
            'label' => __('En-tête Forfait', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Forfait', 'blacktenderscore'), 'condition' => ['layout' => 'table'],
        ]);
        $this->add_control('table_col_duration', [
            'label' => __('En-tête Durée', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Durée', 'blacktenderscore'), 'condition' => ['layout' => 'table'],
        ]);
        $this->add_control('table_col_price', [
            'label' => __('En-tête Prix', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Prix', 'blacktenderscore'), 'condition' => ['layout' => 'table'],
        ]);

        $this->end_controls_section();

        // ── Tarifs par zone ────────────────────────────────────────────────
        $this->start_controls_section('section_zones', [
            'label'     => __('Tarifs par zone de navigation', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['pricing_mode' => ['auto', 'boat'], 'layout!' => 'tabs'],
        ]);

        $this->add_control('show_zones', [
            'label' => __('Afficher tarifs par zone', 'blacktenderscore'), 'type' => Controls_Manager::SWITCHER, 'return_value' => 'yes', 'default' => '',
        ]);
        $this->add_control('zones_title', [
            'label' => __('Titre tableau zones', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Tarifs par zone de départ', 'blacktenderscore'), 'condition' => ['show_zones' => 'yes'],
        ]);
        $this->add_control('zones_col_zone', [
            'label' => __('En-tête Zone', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Zone de navigation', 'blacktenderscore'), 'condition' => ['show_zones' => 'yes'],
        ]);
        $this->add_control('zones_col_half', [
            'label' => __('En-tête ½ journée', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Demi-journée', 'blacktenderscore'), 'condition' => ['show_zones' => 'yes'],
        ]);
        $this->add_control('zones_col_full', [
            'label' => __('En-tête journée', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Journée', 'blacktenderscore'), 'condition' => ['show_zones' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Formulaire de devis ────────────────────────────────────────────
        $this->start_controls_section('section_quote_embed', [
            'label' => __('Formulaire de devis', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_quote_form', [
            'label'        => __('Intégrer le formulaire de devis', 'blacktenderscore'),
            'description'  => __('Ajoute des onglets « Forfaits / Devis ».', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);
        $this->add_control('quote_tab1_label', [
            'label' => __('Label onglet Forfaits', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Forfaits', 'blacktenderscore'), 'condition' => ['show_quote_form' => 'yes'],
        ]);
        $this->add_control('quote_tab2_label', [
            'label' => __('Label onglet Devis', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => __('Demande de devis', 'blacktenderscore'), 'condition' => ['show_quote_form' => 'yes'],
        ]);
        $this->add_control('quote_recipient', [
            'label' => __('E-mail destinataire', 'blacktenderscore'), 'type' => Controls_Manager::TEXT, 'default' => get_option('admin_email'), 'condition' => ['show_quote_form' => 'yes'],
        ]);

        // ── Icône du bouton "Demander un devis" ───────────────────────────
        $this->add_control('devis_btn_icon_mode', [
            'label'     => __('Icône bouton devis', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => ['none' => __('Aucune', 'blacktenderscore'), 'icon' => __('Afficher une icône', 'blacktenderscore')],
            'default'   => 'none',
            'condition' => ['show_quote_form' => 'yes'],
        ]);
        $this->add_control('devis_btn_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'condition' => ['show_quote_form' => 'yes', 'devis_btn_icon_mode' => 'icon'],
        ]);
        $this->add_control('devis_btn_icon_position', [
            'label'     => __('Position icône', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => ['before' => __('Avant le texte', 'blacktenderscore'), 'after' => __('Après le texte', 'blacktenderscore')],
            'default'   => 'before',
            'condition' => ['show_quote_form' => 'yes', 'devis_btn_icon_mode' => 'icon'],
        ]);

        $this->add_control('devis_btn_hide_classes', [
            'label'       => __('Classes à cacher au clic du bouton devis', 'blacktenderscore'),
            'description' => __('Sélecteurs CSS séparés par virgule. Ex: .ma-classe, .autre-bloc', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'condition'   => ['show_quote_form' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══ STYLE ═══════════════════════════════════════════════════════════════

        // ── 1. Conteneur wrapper (.bt-pricing-body) ──────────────────────
        $this->start_controls_section('style_body_container', [
            'label' => __('Conteneur', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'body_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-pricing-body',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name' => 'body_border', 'selector' => '{{WRAPPER}} .bt-pricing-body',
        ]);
        $this->add_responsive_control('body_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-pricing-body' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('body_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-pricing-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name' => 'body_shadow', 'selector' => '{{WRAPPER}} .bt-pricing-body',
        ]);

        $this->end_controls_section();

        // ── 2. Conteneur interne (.bt-pricing + .bt-bprice — fusionnés) ──
        // Excursion = .bt-pricing  |  Bateau = .bt-bprice
        $this->start_controls_section('style_inner_container', [
            'label' => __('Conteneur interne', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'inner_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-pricing, {{WRAPPER}} .bt-bprice',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'inner_border',
            'selector' => '{{WRAPPER}} .bt-pricing, {{WRAPPER}} .bt-bprice',
        ]);
        $this->add_responsive_control('inner_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-pricing' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-bprice'  => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);
        $this->add_responsive_control('inner_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => [
                '{{WRAPPER}} .bt-pricing' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-bprice'  => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'inner_shadow',
            'selector' => '{{WRAPPER}} .bt-pricing, {{WRAPPER}} .bt-bprice',
        ]);

        $this->end_controls_section();

        // ── Segmented Control (Réserver / Devis) ─────────────────────────
        $this->start_controls_section('style_seg_control', [
            'label'     => __('Onglets Forfaits / Devis', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_quote_form' => 'yes'],
        ]);

        $this->add_control('seg_track_bg', [
            'label'     => __('Fond track', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#f3f3f3',
            'selectors' => ['{{WRAPPER}} .bt-seg' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('seg_active_bg', [
            'label'     => __('Fond actif', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .bt-seg__indicator' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('seg_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 10, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-seg'           => 'border-radius: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-seg__indicator' => 'border-radius: calc({{SIZE}}{{UNIT}} - 4px)',
                '{{WRAPPER}} .bt-seg__btn'       => 'border-radius: calc({{SIZE}}{{UNIT}} - 4px)',
            ],
        ]);
        $this->add_responsive_control('seg_padding', [
            'label'      => __('Padding track', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 2, 'max' => 8]],
            'default'    => ['size' => 4, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-seg'            => 'padding: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-seg__indicator' => 'top: {{SIZE}}{{UNIT}}; bottom: {{SIZE}}{{UNIT}}; left: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'seg_typography',
            'selector' => '{{WRAPPER}} .bt-seg__btn',
        ]);

        $this->start_controls_tabs('seg_state_tabs');
        $this->start_controls_tab('seg_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('seg_text_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-seg__btn' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();
        $this->start_controls_tab('seg_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('seg_text_color_active', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-seg__btn--active' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control('seg_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .bt-seg' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── 4. Cards — Forfaits ───────────────────────────────────────────
        $this->start_controls_section('style_forfait_cards', [
            'label' => __('Cards — Forfaits', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        // ── Alignement du contenu ──
        $this->add_responsive_control('fcard_align_h', [
            'label'   => __('Alignement horizontal', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center'     => ['title' => __('Centre',  'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'flex-end'   => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => [
                '{{WRAPPER}} .bt-forfait-card'          => 'align-items: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__content' => 'align-items: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__pricing' => 'justify-content: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__meta'    => 'align-items: {{VALUE}}',
            ],
        ]);
        $this->add_responsive_control('fcard_align_v', [
            'label'   => __('Alignement vertical', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Haut',   'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'flex-end'   => ['title' => __('Bas',    'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'selectors' => [
                '{{WRAPPER}} .bt-forfait-card'          => 'justify-content: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__content' => 'justify-content: {{VALUE}}',
            ],
        ]);

        // ── Container card — Normal / Survol / Actif ──
        $this->start_controls_tabs('fcard_state_tabs');

        $this->start_controls_tab('fcard_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'fcard_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-forfait-card',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name' => 'fcard_border', 'selector' => '{{WRAPPER}} .bt-forfait-card',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name' => 'fcard_shadow', 'selector' => '{{WRAPPER}} .bt-forfait-card',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('fcard_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'fcard_bg_hover',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-forfait-card:hover',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name' => 'fcard_border_hover', 'selector' => '{{WRAPPER}} .bt-forfait-card:hover',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name' => 'fcard_shadow_hover', 'selector' => '{{WRAPPER}} .bt-forfait-card:hover',
        ]);
        $this->add_control('fcard_opacity_hover', [
            'label'     => __('Opacité', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'selectors' => ['{{WRAPPER}} .bt-forfait-card:hover' => 'opacity: {{SIZE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('fcard_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'fcard_bg_active',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-forfait-card--active',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name' => 'fcard_border_active', 'selector' => '{{WRAPPER}} .bt-forfait-card--active',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name' => 'fcard_shadow_active', 'selector' => '{{WRAPPER}} .bt-forfait-card--active',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Dimensions ──
        $this->add_responsive_control('fcard_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .bt-forfait-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('fcard_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-forfait-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // ── Image (mode 1 col) ──
        $this->add_responsive_control('fcard_img_width', [
            'label'      => __('Largeur image (mode 1 col)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 60, 'max' => 300], '%' => ['min' => 10, 'max' => 60]],
            'default'    => ['size' => 110, 'unit' => 'px'],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .bt-forfaits__grid--inline .bt-forfait-card__image' => '--bt-fcard-img-width: {{SIZE}}{{UNIT}}'],
        ]);

        // ══ Typographies ═══════════════════════════════════════════════════
        $this->add_control('fcard_typo_heading', [
            'label'     => __('Typographies', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        // Nom forfait
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'fcard_name_typo', 'selector' => '{{WRAPPER}} .bt-forfait-card__name',
            'label' => __('Nom forfait', 'blacktenderscore'),
        ]);
        $this->add_control('fcard_name_color', [
            'label' => __('Couleur nom', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__name' => 'color: {{VALUE}}'],
        ]);

        // Titre + sous-titre (bateaux)
        $this->add_control('fcard_title_heading', [
            'label' => __('Titre / Sous-titre (bateaux)', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING, 'separator' => 'before',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'fcard_title_typo', 'selector' => '{{WRAPPER}} .bt-forfait-card__title',
        ]);
        $this->add_control('fcard_title_color', [
            'label' => __('Couleur titre', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__title' => 'color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'fcard_subtitle_typo', 'selector' => '{{WRAPPER}} .bt-forfait-card__subtitle',
        ]);
        $this->add_control('fcard_subtitle_color', [
            'label' => __('Couleur sous-titre', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__subtitle' => 'color: {{VALUE}}'],
        ]);

        // Prix
        $this->add_control('fcard_price_heading', [
            'label' => __('Prix', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'fcard_price_typo', 'selector' => '{{WRAPPER}} .bt-forfait-card__price',
        ]);
        $this->add_control('fcard_price_color', [
            'label' => __('Couleur prix', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__price' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_currency_color', [
            'label' => __('Couleur devise + / pers.', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-forfait-card__currency' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__per'      => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('fcard_original_color', [
            'label' => __('Prix barré', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__original' => 'color: {{VALUE}}'],
        ]);

        // Badge remise
        $this->add_control('fcard_discount_heading', [
            'label' => __('Badge remise', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before',
        ]);
        $this->add_control('fcard_discount_bg', [
            'label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__discount' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_discount_color', [
            'label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__discount' => 'color: {{VALUE}}'],
        ]);

        // Badge promo (coin supérieur)
        $this->add_control('fcard_badge_heading', [
            'label' => __('Badge promo', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'fcard_badge_typo', 'selector' => '{{WRAPPER}} .bt-forfait-card__badge',
        ]);
        $this->add_control('fcard_badge_bg', [
            'label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__badge' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_badge_color', [
            'label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-forfait-card__badge' => 'color: {{VALUE}}'],
        ]);

        // Méta (durée + pax)
        $this->add_control('fcard_meta_heading', [
            'label' => __('Méta (durée + pax)', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name' => 'fcard_meta_typo', 'selector' => '{{WRAPPER}} .bt-forfait-card__meta-item',
        ]);
        $this->add_control('fcard_meta_color', [
            'label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-forfait-card__meta-item' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__icon'      => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('fcard_separator_color', [
            'label' => __('Couleur séparateur / ligne méta', 'blacktenderscore'), 'type' => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-forfait-card__separator' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-forfait-card__meta'      => 'border-top-color: {{VALUE}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Bouton "Demander un devis" ────────────────────────────────────
        $this->register_button_style(
            'devis_btn',
            __('Bouton — Devis', 'blacktenderscore'),
            '{{WRAPPER}} .bt-bprice__devis-btn',
            [],
            ['show_quote_form' => 'yes', 'pricing_mode' => ['auto', 'boat']]
        );

        // ── Devis — toutes les sections ────────────────────────────────────
        $this->register_quote_style_controls(['show_quote_form' => 'yes']);
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID() ?: 0;

        // Détection du mode
        $mode = $s['pricing_mode'] ?? 'auto';
        if ($mode === 'auto') {
            $post_type = $post_id ? get_post_type($post_id) : '';
            $mode = ($post_type === 'excursion') ? 'excursion' : 'boat';
        }

        // Visibilité : en éditeur toujours visible
        $is_edit = $this->is_edit_mode();
        $hidden  = !$is_edit && ($s['body_initial_state'] ?? 'hidden') === 'hidden';

        $cls  = 'bt-pricing-body';
        $cls .= $hidden ? ' bt-pricing-body--hidden' : '';

        echo '<div class="' . esc_attr($cls) . '" data-bt-pricing-body'
           . ($hidden ? ' aria-hidden="true"' : '') . '>';

        // Mode devis uniquement — pas de forfaits, juste le formulaire
        if ($mode === 'devis_only') {
            $this->render_embedded_quote_form($s, $post_id);
            echo '</div>';
            return;
        }

        $has_quote = ($s['show_quote_form'] ?? '') === 'yes';

        if ($mode === 'excursion') {
            // Excursion : segmented control [Réserver | Devis] + panels
            if ($has_quote) {
                $this->render_wrapper_open($s);
            }
            if (!$this->acf_required()) { echo '</div>'; return; }
            $this->render_excursion_pricing($s, $post_id);
            if ($has_quote) {
                $this->render_wrapper_between($s);
                $this->render_embedded_quote_form($s, $post_id);
                $this->render_wrapper_close();
            }
        } else {
            // Bateau de type "voile" → rien à afficher
            if ($post_id && has_term('voile', 'exp_boat_type', $post_id)) {
                echo '</div>';
                return;
            }
            // Bateau : pricing content, puis inline reveal "Demander un devis"
            // qui ouvre le formulaire embarqué juste en dessous.
            if (!$this->acf_required()) { echo '</div>'; return; }
            $this->render_pricing_content($s, $post_id);
            if ($has_quote) {
                $devis_label    = esc_html($s['quote_tab2_label'] ?: __('Demander un devis', 'blacktenderscore'));
                $icon_mode      = $s['devis_btn_icon_mode'] ?? 'none';
                $icon_position  = $s['devis_btn_icon_position'] ?? 'before';
                $icon_html      = '';
                if ($icon_mode === 'icon') {
                    $icon_val = $s['devis_btn_icon'] ?? [];
                    if (is_array($icon_val) && !empty($icon_val['value'])) {
                        ob_start();
                        \Elementor\Icons_Manager::render_icon($icon_val, ['aria-hidden' => 'true', 'class' => 'bt-bprice__devis-btn-icon']);
                        $icon_html = ob_get_clean();
                    }
                }
                $btn_inner = $icon_position === 'after'
                    ? '<span>' . $devis_label . '</span>' . $icon_html
                    : $icon_html . '<span>' . $devis_label . '</span>';
                $hide_classes = trim((string) ($s['devis_btn_hide_classes'] ?? ''));
                $data_hide_cls = $hide_classes !== '' ? ' data-bt-reveal-hide-classes="' . esc_attr($hide_classes) . '"' : '';
                echo '<div class="bt-bprice-devis-reveal" data-bt-trigger="reveal" data-bt-reveal-inline' . $data_hide_cls . '>';
                echo '<button type="button" class="bt-pricing__trigger bt-pricing__trigger--fullwidth bt-bprice__devis-btn" aria-expanded="false">' . $btn_inner . '</button>';
                echo '<div class="bt-pricing__reveal-content"><div>';
                $this->render_embedded_quote_form($s, $post_id);
                echo '</div></div></div>';
            }
        }

        echo '</div>';
    }
}
