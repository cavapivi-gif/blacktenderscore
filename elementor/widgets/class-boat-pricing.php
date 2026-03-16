<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

require_once __DIR__ . '/../traits/trait-bt-pricing-shared.php';
require_once __DIR__ . '/../traits/trait-bt-quote-style-controls.php';

/**
 * Widget Elementor — Tarification bateau.
 *
 * Layouts tarifs : cartes cote a cote | tableau | onglets (tabs).
 * Donnees : ACF Pro (boat_price_half, boat_price_full, repeater par zone...).
 */
class BoatPricing extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtPricingShared;
    use \BlackTenders\Elementor\Traits\BtQuoteStyleControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-boat-pricing',
            'title'    => 'BT — Tarifs Bateau',
            'icon'     => 'eicon-price-list',
            'keywords' => ['tarif', 'prix', 'bateau', 'demi-journée', 'journée', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-pricing-tabs', 'bt-quote-form'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Tarifs bateau
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu tarifs bateau', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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

        // ── Options d'affichage ──────────────────────────────────────────────
        $this->start_controls_section('section_options', [
            'label' => __('Forfaits à afficher', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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

        // ── Tarifs par zone ──────────────────────────────────────────────────
        $this->start_controls_section('section_zones', [
            'label'     => __('Tarifs par zone de navigation', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['layout!' => 'tabs'],
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
        //  CONTENU — Bouton déclencheur et emplacement
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_trigger', [
            'label' => __('Bouton déclencheur et emplacement', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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

        $this->add_control('reveal_target', [
            'label'   => __('Cible du contenu', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'body'   => __('Widget BT — Tarifs Body (auto)', 'blacktenderscore'),
                'inline' => __('Sous le bouton (en place)', 'blacktenderscore'),
                'custom' => __('ID personnalisé', 'blacktenderscore'),
            ],
            'default'   => 'body',
            'condition' => ['trigger_mode' => 'reveal'],
        ]);

        $this->add_control('reveal_target_id', [
            'label'       => __('ID du conteneur cible', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'tarifs-bateau',
            'condition'   => ['trigger_mode' => 'reveal', 'reveal_target' => 'custom'],
        ]);

        $this->add_control('reveal_hide_selector', [
            'label'       => __('Cacher un élément à l\'ouverture (mobile)', 'blacktenderscore'),
            'description' => __('Sélecteur CSS d\'un élément à masquer sur mobile quand le contenu est ouvert (ex: .exp-reservation). Vide = rien.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '.exp-reservation',
            'condition'   => ['trigger_mode' => 'reveal'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Formulaire de devis intégré
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_embed', [
            'label' => __('Formulaire de devis', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_quote_form', [
            'label'        => __('Intégrer le formulaire de devis', 'blacktenderscore'),
            'description'  => __('Ajoute des onglets « Forfaits / Devis ». Sinon, utilisez le widget BT — Devis séparément.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('quote_tab1_label', [
            'label'     => __('Label onglet Forfaits', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Forfaits', 'blacktenderscore'),
            'condition' => ['show_quote_form' => 'yes'],
        ]);

        $this->add_control('quote_tab2_label', [
            'label'     => __('Label onglet Devis', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demande de devis', 'blacktenderscore'),
            'condition' => ['show_quote_form' => 'yes'],
        ]);

        $this->add_control('quote_recipient', [
            'label'     => __('E-mail destinataire', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => get_option('admin_email'),
            'condition' => ['show_quote_form' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══ STYLE ═══════════════════════════════════════════════════════════════

        // ── Titre ──────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-bprice__title');

        // ── Conteneur ─────────────────────────────────────────────────
        $this->start_controls_section('style_boat_container', [
            'label' => __('🚤 Bateau — Conteneur', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
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

        // ── Typographie ───────────────────────────────────────────────
        $this->start_controls_section('style_boat_typography', [
            'label' => __('🚤 Bateau — Typographie', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
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

        // ── Badges & boutons ──────────────────────────────────────────
        $this->start_controls_section('style_boat_badges_btns', [
            'label' => __('🚤 Bateau — Badges & boutons', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
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

        // ── Onglets — UN seul contrôleur pour TOUS les tabs du widget ──
        $all_tabs     = '{{WRAPPER}} .bt-bprice-wrapper__tab, {{WRAPPER}} .bt-bprice__tab';
        $all_active   = '{{WRAPPER}} .bt-bprice-wrapper__tab--active, {{WRAPPER}} .bt-bprice__tab--active';
        $all_tablist  = '{{WRAPPER}} .bt-bprice-wrapper__tablist, {{WRAPPER}} .bt-bprice__tablist';

        $this->register_tabs_nav_style(
            'all_tabs',
            '📋 Onglets',
            $all_tabs,
            $all_active,
            $all_tablist,
            [],
            [
                'with_hover'      => true,
                'with_radius'     => true,
                'with_indicator'  => true,
                'with_justify'    => true,
                'with_panel'      => true,
                'panel_sel'       => '{{WRAPPER}} .bt-bprice-wrapper__panel--active, {{WRAPPER}} .bt-bprice__panel--active',
            ]
        );

        // ── Styles formulaire de devis (toutes les steps) ────────────
        $this->register_quote_style_controls(['show_quote_form' => 'yes']);
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $this->render_boat_mode($s, $post_id);
    }

    /**
     * Mode bateau : trigger optionnel + tarifs (+ wrapper devis si activé).
     */
    private function render_boat_mode(array $s, int $post_id): void {
        $trigger_mode = $s['trigger_mode'] ?? 'none';

        if ($trigger_mode !== 'none') {
            $this->render_trigger_open(
                $s,
                $trigger_mode,
                'trigger_label',
                'Voir les tarifs',
                'reveal_target',
                'reveal_target_id',
                'reveal_hide_selector',
                'trigger_fullwidth',
                'bt-bprice-trigger'
            );
        }

        if (($s['show_quote_form'] ?? '') === 'yes') {
            $this->render_wrapper_open($s);
        }

        $this->render_pricing_content($s, $post_id);

        if (($s['show_quote_form'] ?? '') === 'yes') {
            $this->render_wrapper_between($s);
            $this->render_embedded_quote_form($s, $post_id);
            $this->render_wrapper_close();
        }

        if ($trigger_mode !== 'none') {
            $this->render_trigger_close($trigger_mode);
        }
    }

    // ── Contenu tarifs bateau ───────────────────────────────────────────────

    /**
     * Rendu du contenu tarification bateau (cartes/tabs/table + zones).
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

    // ── Helpers ─────────────────────────────────────────────────────────────

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
}
