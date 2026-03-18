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

        // ── Style : conteneur & cartes ───────────────────────────────────────
        $this->start_controls_section('style_boat_container', [
            'label' => __('🚤 Bateau — Conteneur', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('container_heading_outer', ['label' => __('Conteneur prix', 'blacktenderscore'), 'type' => Controls_Manager::HEADING]);
        $this->add_control('container_bg',     ['label' => __('Fond', 'blacktenderscore'),          'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(),    ['name' => 'container_border',  'selector' => '{{WRAPPER}} .bt-bprice']);
        $this->add_responsive_control('container_radius',  ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px','%','em'], 'selectors' => ['{{WRAPPER}} .bt-bprice' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('container_padding', ['label' => __('Padding', 'blacktenderscore'),       'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px','em'],     'selectors' => ['{{WRAPPER}} .bt-bprice' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'container_shadow', 'selector' => '{{WRAPPER}} .bt-bprice']);

        $this->add_responsive_control('cards_gap_extra', ['label' => __('Espacement entre cartes', 'blacktenderscore'), 'type' => Controls_Manager::SLIDER, 'separator' => 'before', 'size_units' => ['px'], 'default' => ['size' => 16, 'unit' => 'px'], 'selectors' => ['{{WRAPPER}} .bt-bprice__cards' => 'gap: {{SIZE}}{{UNIT}}'], 'condition' => ['layout' => 'cards']]);

        $this->add_control('card_heading', ['label' => __('Cartes', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before']);
        $this->add_control('card_bg',     ['label' => __('Fond carte', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(),    ['name' => 'card_border',  'selector' => '{{WRAPPER}} .bt-bprice__card']);
        $this->add_responsive_control('card_radius',  ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px','%','em'], 'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_responsive_control('card_padding', ['label' => __('Padding', 'blacktenderscore'),       'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px','em'],     'default' => ['top' => 24, 'right' => 24, 'bottom' => 24, 'left' => 24, 'unit' => 'px', 'isLinked' => true], 'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}']]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), ['name' => 'card_shadow', 'selector' => '{{WRAPPER}} .bt-bprice__card']);

        $this->end_controls_section();

        // ── Style : typographie ──────────────────────────────────────────────
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

        // ── Style : badges & bouton déclencheur ──────────────────────────────
        $this->start_controls_section('style_boat_badges_btns', [
            'label' => __('🚤 Bateau — Badges & boutons', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('deposit_color', ['label' => __('Couleur caution', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__deposit' => 'color: {{VALUE}}'], 'condition' => ['show_deposit' => 'yes']]);
        $this->add_control('year_heading',  ['label' => __('Année du bateau', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['show_boat_year' => 'yes']]);
        $this->add_control('year_color',    ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-bprice__year' => 'color: {{VALUE}}'], 'condition' => ['show_boat_year' => 'yes']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'year_typography', 'selector' => '{{WRAPPER}} .bt-bprice__year', 'condition' => ['show_boat_year' => 'yes']]);

        $this->add_control('trigger_heading', ['label' => __('Bouton déclencheur', 'blacktenderscore'), 'type' => Controls_Manager::HEADING, 'separator' => 'before', 'condition' => ['trigger_mode!' => 'none']]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), ['name' => 'trigger_btn_typography', 'selector' => '{{WRAPPER}} .bt-pricing__trigger', 'condition' => ['trigger_mode!' => 'none']]);

        $this->start_controls_tabs('trigger_btn_state_tabs', ['condition' => ['trigger_mode!' => 'none']]);
        $this->start_controls_tab('trigger_btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('trigger_btn_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger'       => 'color: {{VALUE}}']]);
        $this->add_control('trigger_btn_bg',    ['label' => __('Fond',    'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger'       => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->start_controls_tab('trigger_btn_tab_hover',  ['label' => __('Survol',  'blacktenderscore')]);
        $this->add_control('trigger_btn_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger:hover' => 'color: {{VALUE}}']]);
        $this->add_control('trigger_btn_bg_hover',    ['label' => __('Fond',    'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->add_responsive_control('trigger_btn_padding', ['label' => __('Padding',       'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px','em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['trigger_mode!' => 'none']]);
        $this->add_responsive_control('trigger_btn_radius',  ['label' => __('Border radius', 'blacktenderscore'), 'type' => Controls_Manager::DIMENSIONS, 'size_units' => ['px','%','em'], 'selectors' => ['{{WRAPPER}} .bt-pricing__trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'], 'condition' => ['trigger_mode!' => 'none']]);

        $this->end_controls_section();

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

        // ── Style : onglets enfants (forfaits bateau) ────────────────────────
        $this->register_tabs_nav_style(
            'child_tabs',
            __('📋 Onglets forfaits', 'blacktenderscore'),
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

        // ── Style : formulaire de devis ───────────────────────────────────────
        $this->register_quote_style_controls(['show_quote_form' => 'yes']);
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
