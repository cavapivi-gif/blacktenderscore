<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

require_once __DIR__ . '/../traits/trait-bt-boat-pricing.php';
require_once __DIR__ . '/../traits/trait-bt-pricing-shared.php';
require_once __DIR__ . '/../traits/trait-bt-quote-style-controls.php';

/**
 * Widget Elementor — Tarification bateau.
 *
 * Layouts tarifs : cartes côte à côte | tableau | onglets.
 * Données : ACF Pro (boat_price_half, boat_price_full, repeater zones…).
 * Le rendu est entièrement délégué aux traits BtBoatPricing + BtPricingShared.
 */
class BoatPricing extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtBoatPricing;
    use \BlackTenders\Elementor\Traits\BtPricingShared;
    use \BlackTenders\Elementor\Traits\BtQuoteStyleControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-boat-pricing',
            'title'    => 'BT — Tarifs Bateau (Legacy)',
            'icon'     => 'eicon-price-list',
            'keywords' => ['tarif', 'prix', 'bateau', 'demi-journée', 'journée', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-pricing-tabs', 'bt-quote-form'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ── Contenu : tarifs bateau ──────────────────────────────────────────
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
                'tabs'  => __('Onglets (tabs)',      'blacktenderscore'),
                'table' => __('Tableau',             'blacktenderscore'),
            ],
            'default' => 'cards',
        ]);

        $this->end_controls_section();

        // ── Contenu : forfaits à afficher ────────────────────────────────────
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

        $this->add_control('show_boat_year', [
            'label'        => __('Afficher l\'année du bateau', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Valeur dynamique : champ ACF <code>boat_year</code>', 'blacktenderscore'),
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

        // ── Contenu : tarifs par zone ────────────────────────────────────────
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

        // ── Contenu : bouton déclencheur ─────────────────────────────────────
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
            'description' => __('En mode "reveal", un bouton déclenche l\'affichage et le scroll vers l\'ancre.', 'blacktenderscore'),
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
                'inline' => __('Sous le bouton (en place)',      'blacktenderscore'),
                'custom' => __('ID personnalisé',                'blacktenderscore'),
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
            'description' => __('Sélecteur CSS d\'un élément à masquer sur mobile quand le contenu est ouvert. Vide = rien.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '.exp-reservation',
            'condition'   => ['trigger_mode' => 'reveal'],
        ]);

        $this->end_controls_section();

        // ── Contenu : formulaire de devis intégré ────────────────────────────
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

        // ══ Style ════════════════════════════════════════════════════════════

        $this->register_section_title_style('{{WRAPPER}} .bt-bprice__title');
        $this->register_style_common_controls();
        $this->register_style_cards_controls();
        $this->register_style_cards_content_controls();
        $this->register_style_tabs_nav_controls();
        $this->register_style_tabs_content_controls();
        $this->register_style_table_controls();
        $this->register_style_zones_controls();
        $this->register_style_trigger_controls();

        // ── Style : onglets parent Forfaits / Devis ───────────────────────────
        $this->register_tabs_nav_style(
            'wrapper_tabs',
            __('📋 Onglets Forfaits / Devis', 'blacktenderscore'),
            '{{WRAPPER}} .bt-bprice-wrapper__tab',
            '{{WRAPPER}} .bt-bprice-wrapper__tab--active',
            '{{WRAPPER}} .bt-bprice-wrapper__tablist',
            ['show_quote_form' => 'yes'],
            [
                'with_hover'     => true,
                'with_radius'    => true,
                'with_indicator' => true,
                'with_justify'   => true,
                'with_panel'     => true,
                'panel_sel'      => '{{WRAPPER}} .bt-bprice-wrapper__panel--active',
            ]
        );

        // ── Style : formulaire de devis ───────────────────────────────────────
        $this->register_quote_style_controls(['show_quote_form' => 'yes']);
    }

    // ══ Style Methods ═══════════════════════════════════════════════════════

    /**
     * 1. Conteneur .bt-bprice, caution, année.
     *
     * Migration (v1 → v2) — keys kept as-is :
     *   container_bg, container_border, container_radius, container_padding, container_shadow
     *   deposit_color, year_color, year_typography
     * Removed (HEADING-only, no data) :
     *   container_heading_outer → supprimé
     *   year_heading            → supprimé
     */
    protected function register_style_common_controls(): void {
        $this->start_controls_section('style_common', [
            'label' => __('Conteneur global', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        // ── Conteneur .bt-bprice ──
        $this->add_control('container_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'container_border',
            'selector' => '{{WRAPPER}} .bt-bprice',
        ]);
        $this->add_responsive_control('container_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-bprice' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('container_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-bprice' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'container_shadow',
            'selector' => '{{WRAPPER}} .bt-bprice',
        ]);

        // ── Caution ──
        $this->add_control('deposit_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('deposit_heading', [
            'label' => __('Caution', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'deposit_typography',
            'selector'  => '{{WRAPPER}} .bt-bprice__deposit',
            'condition' => ['show_deposit' => 'yes'],
        ]);
        $this->add_control('deposit_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__deposit' => 'color: {{VALUE}}'],
            'condition' => ['show_deposit' => 'yes'],
        ]);
        $this->add_responsive_control('deposit_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .bt-bprice__deposit' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_deposit' => 'yes'],
        ]);

        // ── Année ──
        $this->add_control('year_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('year_heading_ctrl', [
            'label' => __('Année du bateau', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'year_typography',
            'selector'  => '{{WRAPPER}} .bt-bprice__year',
            'condition' => ['show_boat_year' => 'yes'],
        ]);
        $this->add_control('year_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__year' => 'color: {{VALUE}}'],
            'condition' => ['show_boat_year' => 'yes'],
        ]);
        $this->add_responsive_control('year_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .bt-bprice__year' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_boat_year' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /**
     * 2. Grille .bt-forfaits__grid + carte .bt-forfait-card
     *    Normal / Hover / Active tabs (bg, border-color, text-color, box-shadow).
     *
     * Migration (v1 → v2) :
     *   cards_gap_extra → cards_gap_extra (kept)
     *   NEW : fcard_* (3-state tabs) — aucun ancien key
     */
    protected function register_style_cards_controls(): void {
        $this->start_controls_section('style_cards', [
            'label'     => __('Cartes forfait', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'cards'],
        ]);

        // ── Grille ──
        $this->add_responsive_control('cards_gap_extra', [
            'label'      => __('Espacement entre cartes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-forfaits__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('fcard_box_sep', ['type' => Controls_Manager::DIVIDER]);

        // ── 3-state tabs : Normal / Hover / Active ──
        $card = '{{WRAPPER}} .bt-forfait-card';

        $this->start_controls_tabs('fcard_state_tabs');

        // Normal
        $this->start_controls_tab('fcard_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('fcard_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$card => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$card => 'color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$card => 'border-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'fcard_shadow',
            'selector' => $card,
        ]);
        $this->end_controls_tab();

        // Hover
        $this->start_controls_tab('fcard_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('fcard_bg_hover', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$card}:hover" => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_color_hover', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$card}:hover" => 'color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_border_color_hover', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$card}:hover" => 'border-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'fcard_shadow_hover',
            'selector' => "{$card}:hover",
        ]);
        $this->end_controls_tab();

        // Active
        $this->start_controls_tab('fcard_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('fcard_bg_active', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$card}--active" => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_color_active', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$card}--active" => 'color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_border_color_active', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$card}--active" => 'border-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'fcard_shadow_active',
            'selector' => "{$card}--active",
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Card box (hors tabs) ──
        $this->add_responsive_control('fcard_padding', [
            'label'      => __('Padding carte', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'separator'  => 'before',
            'selectors'  => [$card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('fcard_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [$card => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    /**
     * 3. Contenu des cartes : image, titre, sous-titre, prix, currency, pp, note, meta.
     *    Chaque sous-bloc : typo + color + margin_bottom responsive.
     *
     * Migration (v1 → v2) — all NEW keys (fcard_title_*, fcard_subtitle_*, etc.)
     */
    protected function register_style_cards_content_controls(): void {
        $this->start_controls_section('style_cards_content', [
            'label'     => __('Contenu cartes forfait', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'cards'],
        ]);

        $w = '{{WRAPPER}}';

        // ── Image ──
        $this->add_control('fcard_image_heading', [
            'label' => __('Image', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_responsive_control('fcard_image_ratio', [
            'label'   => __('Ratio d\'image', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                '16 / 9' => '16:9',
                '4 / 3'  => '4:3',
                '3 / 2'  => '3:2',
                '1 / 1'  => '1:1',
            ],
            'default'   => '16 / 9',
            'selectors' => ["{$w} .bt-forfait-card__image" => 'aspect-ratio: {{VALUE}}'],
        ]);

        // ── Titre ──
        $this->add_control('fcard_title_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('fcard_title_heading', [
            'label' => __('Titre', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'fcard_title_typography',
            'selector' => "{$w} .bt-forfait-card__title",
        ]);
        $this->add_control('fcard_title_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__title" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('fcard_title_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-forfait-card__title" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Sous-titre ──
        $this->add_control('fcard_subtitle_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('fcard_subtitle_heading', [
            'label' => __('Sous-titre', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'fcard_subtitle_typography',
            'selector' => "{$w} .bt-forfait-card__subtitle",
        ]);
        $this->add_control('fcard_subtitle_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__subtitle" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('fcard_subtitle_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-forfait-card__subtitle" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Prix ──
        $this->add_control('fcard_price_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('fcard_price_heading', [
            'label' => __('Prix', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'fcard_price_typography',
            'selector' => "{$w} .bt-forfait-card__price",
        ]);
        $this->add_control('fcard_price_color', [
            'label'     => __('Couleur prix', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__price" => 'color: {{VALUE}}'],
        ]);
        $this->add_control('fcard_currency_color', [
            'label'     => __('Couleur devise', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__currency" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('fcard_price_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-forfait-card__pricing" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Prix / personne ──
        $this->add_control('fcard_pp_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('fcard_pp_heading', [
            'label'     => __('Prix / personne', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'fcard_pp_typography',
            'selector'  => "{$w} .bt-forfait-card__pp",
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_control('fcard_pp_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__pp" => 'color: {{VALUE}}; opacity: 1'],
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_responsive_control('fcard_pp_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-forfait-card__pp" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_per_person' => 'yes'],
        ]);

        // ── Note tarifaire ──
        $this->add_control('fcard_note_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('fcard_note_heading', [
            'label'     => __('Note tarifaire', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['show_price_note' => 'yes'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'fcard_note_typography',
            'selector'  => "{$w} .bt-forfait-card__note",
            'condition' => ['show_price_note' => 'yes'],
        ]);
        $this->add_control('fcard_note_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__note" => 'color: {{VALUE}}; opacity: 1'],
            'condition' => ['show_price_note' => 'yes'],
        ]);
        $this->add_responsive_control('fcard_note_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-forfait-card__note" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_price_note' => 'yes'],
        ]);

        // ── Meta (pax, durée) ──
        $this->add_control('fcard_meta_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('fcard_meta_heading', [
            'label' => __('Meta (pax, durée)', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'fcard_meta_typography',
            'selector' => "{$w} .bt-forfait-card__meta-item",
        ]);
        $this->add_control('fcard_meta_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                "{$w} .bt-forfait-card__meta-item" => 'color: {{VALUE}}',
                "{$w} .bt-forfait-card__icon"      => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('fcard_meta_border_color', [
            'label'     => __('Couleur séparateur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-forfait-card__meta" => 'border-top-color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('fcard_meta_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-forfait-card__meta" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    /**
     * 4. Onglets forfaits .bt-bprice__tab — delegates to register_tabs_nav_style().
     *
     * Migration (v1 → v2) :
     *   child_tabs_* → child_tabs_* (kept, same prefix via SharedControls)
     */
    protected function register_style_tabs_nav_controls(): void {
        $this->register_tabs_nav_style(
            'child_tabs',
            __('Onglets forfaits', 'blacktenderscore'),
            '{{WRAPPER}} .bt-bprice__tab',
            '{{WRAPPER}} .bt-bprice__tab--active',
            '{{WRAPPER}} .bt-bprice__tablist',
            ['layout' => 'tabs'],
            [
                'with_hover'     => true,
                'with_radius'    => true,
                'with_indicator' => true,
                'with_justify'   => true,
                'with_panel'     => true,
                'panel_sel'      => '{{WRAPPER}} .bt-bprice__panel--active',
            ]
        );
    }

    /**
     * 5. Panneau carte .bt-bprice__card : box + sous-éléments typo.
     *
     * Migration (v1 → v2) :
     *   card_bg      → card_bg (kept)
     *   card_border  → card_border (kept)
     *   card_radius  → card_radius (kept)
     *   card_padding → card_padding (kept)
     *   card_shadow  → card_shadow (kept)
     *   price_typography → price_typography (kept, selector now also covers cards)
     *   price_color      → price_color (kept)
     *   duration_typography → duration_typography (kept)
     *   duration_color      → duration_color (kept)
     */
    protected function register_style_tabs_content_controls(): void {
        $this->start_controls_section('style_tabs_content', [
            'label'     => __('Panneau onglet — contenu', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'tabs'],
        ]);

        $w = '{{WRAPPER}}';

        // ── Card box ──
        $this->add_control('card_bg', [
            'label'     => __('Fond carte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__card" => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => "{$w} .bt-bprice__card",
        ]);
        $this->add_responsive_control('card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ["{$w} .bt-bprice__card" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('card_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => 24, 'right' => 24, 'bottom' => 24, 'left' => 24, 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ["{$w} .bt-bprice__card" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => "{$w} .bt-bprice__card",
        ]);

        // ── Label forfait (shared key with table section — conditions are mutually exclusive) ──
        $this->add_control('tabs_label_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('tabs_label_heading', [
            'label' => __('Label forfait', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'card_label_typography',
            'selector' => "{$w} .bt-bprice__card-label",
        ]);
        $this->add_control('card_label_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__card-label" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('tabs_label_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__card-label" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Prix ──
        $this->add_control('tabs_price_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('tabs_price_heading', [
            'label' => __('Prix', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'price_typography',
            'selector' => "{$w} .bt-bprice__amount",
        ]);
        $this->add_control('price_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__amount" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('tabs_price_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__amount-block" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Durée ──
        $this->add_control('tabs_duration_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('tabs_duration_heading', [
            'label' => __('Durée', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'duration_typography',
            'selector' => "{$w} .bt-bprice__duration",
        ]);
        $this->add_control('duration_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__duration" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('tabs_duration_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__duration" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Note (dans tabs) ──
        $this->add_control('tabs_note_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('tabs_note_heading', [
            'label'     => __('Note', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['show_price_note' => 'yes'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'tabs_note_typography',
            'selector'  => "{$w} .bt-bprice__note",
            'condition' => ['show_price_note' => 'yes'],
        ]);
        $this->add_control('tabs_note_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__note" => 'color: {{VALUE}}'],
            'condition' => ['show_price_note' => 'yes'],
        ]);
        $this->add_responsive_control('tabs_note_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__note" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_price_note' => 'yes'],
        ]);

        // ── Prix / personne (dans tabs) ──
        $this->add_control('tabs_pp_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('tabs_pp_heading', [
            'label'     => __('Prix / personne', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'tabs_pp_typography',
            'selector'  => "{$w} .bt-bprice__per-person",
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_control('tabs_pp_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__per-person" => 'color: {{VALUE}}'],
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_responsive_control('tabs_pp_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__per-person" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_per_person' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /**
     * 6. Tableau .bt-bprice__table : en-têtes, label, prix, durée, prix/pers.
     *
     * Migration (v1 → v2) :
     *   card_label_typography → card_label_typography (kept, same key)
     *   card_label_color      → card_label_color (kept)
     */
    protected function register_style_table_controls(): void {
        $this->start_controls_section('style_table', [
            'label'     => __('Tableau', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'table'],
        ]);

        $w = '{{WRAPPER}}';

        // ── Conteneur tableau ──
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'table_border',
            'selector' => "{$w} .bt-bprice__table",
        ]);
        $this->add_responsive_control('table_cell_padding', [
            'label'      => __('Padding cellules', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ["{$w} .bt-bprice__table th, {$w} .bt-bprice__table td" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // ── En-têtes ──
        $this->add_control('table_thead_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('table_thead_heading', [
            'label' => __('En-têtes', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'table_thead_typography',
            'selector' => "{$w} .bt-bprice__table thead th",
        ]);
        $this->add_control('table_thead_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__table thead th" => 'color: {{VALUE}}'],
        ]);
        $this->add_control('table_thead_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__table thead th" => 'background-color: {{VALUE}}'],
        ]);

        // ── Label forfait ──
        $this->add_control('table_label_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('table_label_heading', [
            'label' => __('Label forfait', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'table_label_typography',
            'selector' => "{$w} .bt-bprice__table .bt-bprice__card-label",
        ]);
        $this->add_control('table_label_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__table .bt-bprice__card-label" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('table_label_margin_bottom', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__card-label" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Prix (table) ──
        $this->add_control('table_price_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('table_price_heading', [
            'label' => __('Prix', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'table_price_typography',
            'selector' => "{$w} .bt-bprice__table .bt-bprice__amount",
        ]);
        $this->add_control('table_price_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__table .bt-bprice__amount" => 'color: {{VALUE}}'],
        ]);

        // ── Durée (table) ──
        $this->add_control('table_duration_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('table_duration_heading', [
            'label' => __('Durée', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'table_duration_typography',
            'selector' => "{$w} .bt-bprice__table .bt-bprice__duration",
        ]);
        $this->add_control('table_duration_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__table .bt-bprice__duration" => 'color: {{VALUE}}'],
        ]);

        // ── Prix / personne (table) ──
        $this->add_control('table_pp_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_control('table_pp_heading', [
            'label'     => __('Prix / personne', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'      => 'table_pp_typography',
            'selector'  => "{$w} .bt-bprice__table .bt-bprice__per-person",
            'condition' => ['show_per_person' => 'yes'],
        ]);
        $this->add_control('table_pp_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__table .bt-bprice__per-person" => 'color: {{VALUE}}'],
            'condition' => ['show_per_person' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    /**
     * 7. Zones de navigation : titre, cellules.
     *
     * Migration (v1 → v2) — all NEW keys (zones_*)
     */
    protected function register_style_zones_controls(): void {
        $this->start_controls_section('style_zones', [
            'label'     => __('Tarifs par zone', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout!' => 'tabs', 'show_zones' => 'yes'],
        ]);

        $w = '{{WRAPPER}}';

        // ── Titre zones ──
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'zones_title_typography',
            'label'    => __('Titre', 'blacktenderscore'),
            'selector' => "{$w} .bt-bprice__zones-title",
        ]);
        $this->add_control('zones_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__zones-title" => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('zones_title_margin_bottom', [
            'label'      => __('Marge basse titre', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ["{$w} .bt-bprice__zones-title" => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Cellules zones ──
        $this->add_control('zones_cells_sep', ['type' => Controls_Manager::DIVIDER]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'zones_cell_typography',
            'label'    => __('Cellules', 'blacktenderscore'),
            'selector' => "{$w} .bt-bprice__zones .bt-bprice__table td",
        ]);
        $this->add_control('zones_cell_color', [
            'label'     => __('Couleur cellules', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$w} .bt-bprice__zones .bt-bprice__table td" => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
    }

    /**
     * 8. Bouton déclencheur .bt-pricing__trigger.
     *
     * Migration (v1 → v2) :
     *   trigger_btn_typography → trigger_btn_typography (kept)
     *   trigger_btn_color      → trigger_btn_color (kept)
     *   trigger_btn_bg         → trigger_btn_bg (kept)
     *   trigger_btn_color_hover → trigger_btn_color_hover (kept)
     *   trigger_btn_bg_hover    → trigger_btn_bg_hover (kept)
     *   trigger_btn_padding     → trigger_btn_padding (kept)
     *   trigger_btn_radius      → trigger_btn_radius (kept)
     */
    protected function register_style_trigger_controls(): void {
        $this->start_controls_section('style_trigger', [
            'label'     => __('Bouton déclencheur', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['trigger_mode!' => 'none'],
        ]);

        $sel = '{{WRAPPER}} .bt-pricing__trigger';

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'trigger_btn_typography',
            'selector' => $sel,
        ]);

        $this->start_controls_tabs('trigger_btn_state_tabs');

        $this->start_controls_tab('trigger_btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('trigger_btn_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$sel => 'color: {{VALUE}}'],
        ]);
        $this->add_control('trigger_btn_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$sel => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('trigger_btn_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('trigger_btn_color_hover', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$sel}:hover" => 'color: {{VALUE}}'],
        ]);
        $this->add_control('trigger_btn_bg_hover', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ["{$sel}:hover" => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('trigger_btn_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'separator'  => 'before',
            'selectors'  => [$sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('trigger_btn_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [$sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $this->render_pricing_layout($s, $post_id, [
            'mode'          => 'trigger_mode',
            'label'         => 'trigger_label',
            'label_default' => 'Voir les tarifs',
            'target'        => 'reveal_target',
            'target_id'     => 'reveal_target_id',
            'hide_sel'      => 'reveal_hide_selector',
            'fullwidth'     => 'trigger_fullwidth',
            'wrap_prefix'   => 'bt-bprice-trigger',
        ], fn($s, $pid) => $this->render_pricing_content($s, $pid));
    }
}
