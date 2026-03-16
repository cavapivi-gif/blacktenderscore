<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

require_once __DIR__ . '/../traits/trait-bt-excursion-pricing.php';
require_once __DIR__ . '/../traits/trait-bt-pricing-shared.php';
require_once __DIR__ . '/../traits/trait-bt-quote-style-controls.php';

/**
 * Widget Elementor — Tarification excursion.
 *
 * Forfaits repeater ACF + Regiondo booking widget.
 * Layouts : onglets (tabs) | boutons pill.
 */
class ExcursionPricing extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtExcursionPricing;
    use \BlackTenders\Elementor\Traits\BtPricingShared;
    use \BlackTenders\Elementor\Traits\BtQuoteStyleControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-excursion-pricing',
            'title'    => 'BT — Tarifs Excursion',
            'icon'     => 'eicon-price-list',
            'keywords' => ['tarif', 'prix', 'excursion', 'forfait', 'regiondo', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-pricing-tabs', 'bt-quote-form'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Forfaits Excursion
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_exc_pricing', [
            'label' => __('Forfaits excursion', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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

        // ── Réservation Regiondo ─────────────────────────────────────────────
        $this->start_controls_section('section_exc_booking', [
            'label' => __('Réservation Regiondo', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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

        // ── Boutons layout — configuration ───────────────────────────────────
        $this->start_controls_section('section_exc_buttons_config', [
            'label'     => __('Boutons pill — Configuration', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['exc_layout' => 'buttons'],
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

        // ── Trigger excursion ────────────────────────────────────────────────
        $this->start_controls_section('section_exc_trigger', [
            'label' => __('Bouton « Réserver » et emplacement', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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

        $this->add_control('exc_reveal_target', [
            'label'   => __('Cible du contenu', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'body'   => __('Widget BT — Tarifs Body (auto)', 'blacktenderscore'),
                'inline' => __('Sous le bouton (en place)', 'blacktenderscore'),
                'custom' => __('ID personnalisé', 'blacktenderscore'),
            ],
            'default'   => 'body',
            'condition' => ['exc_trigger_mode' => 'reveal'],
        ]);

        $this->add_control('exc_reveal_target_id', [
            'label'       => __('ID du conteneur cible', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'booking-exc',
            'condition'   => ['exc_trigger_mode' => 'reveal', 'exc_reveal_target' => 'custom'],
        ]);

        $this->add_control('exc_reveal_hide_selector', [
            'label'       => __('Cacher un élément à l\'ouverture (mobile)', 'blacktenderscore'),
            'description' => __('Sélecteur CSS d\'un élément à masquer sur mobile quand le contenu est ouvert (ex: .exp-reservation). Vide = rien.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => '.exp-reservation',
            'condition'   => ['exc_trigger_mode' => 'reveal'],
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

        // ── Conteneur & boutons ─────────────────────────────────────────
        $this->start_controls_section('style_exc_btns', [
            'label' => __('⛵ Excursion — Onglets & boutons', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
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
        $this->start_controls_tab('exc_slot_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('exc_slot_color_active', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__slot--active' => 'color: {{VALUE}}']]);
        $this->add_control('exc_slot_bg_active', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-pricing__slot--active' => 'background-color: {{VALUE}}']]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), ['name' => 'exc_slot_border_active', 'selector' => '{{WRAPPER}} .bt-pricing__slot--active']);
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

        // ── Onglets — UN seul contrôleur pour TOUS les tabs du widget ──
        $all_tabs     = '{{WRAPPER}} .bt-bprice-wrapper__tab, {{WRAPPER}} .bt-pricing__tab';
        $all_active   = '{{WRAPPER}} .bt-bprice-wrapper__tab--active, {{WRAPPER}} .bt-pricing__tab--active';
        $all_tablist  = '{{WRAPPER}} .bt-bprice-wrapper__tablist, {{WRAPPER}} .bt-pricing__tablist';

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
                'panel_sel'       => '{{WRAPPER}} .bt-bprice-wrapper__panel--active, {{WRAPPER}} .bt-pricing__panel--active',
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

        $this->render_excursion_mode($s, $post_id);
    }

    /**
     * Mode excursion : trigger optionnel + forfaits + booking (+ wrapper devis si activé).
     */
    private function render_excursion_mode(array $s, int $post_id): void {
        $trigger_mode = $s['exc_trigger_mode'] ?? 'none';

        if ($trigger_mode !== 'none') {
            $this->render_trigger_open(
                $s,
                $trigger_mode,
                'exc_trigger_label',
                'Réserver',
                'exc_reveal_target',
                'exc_reveal_target_id',
                'exc_reveal_hide_selector',
                'exc_trigger_fullwidth',
                'bt-pricing-trigger'
            );
        }

        if (($s['show_quote_form'] ?? '') === 'yes') {
            $this->render_wrapper_open($s);
        }

        $this->render_excursion_pricing($s, $post_id);

        if (($s['show_quote_form'] ?? '') === 'yes') {
            $this->render_wrapper_between($s);
            $this->render_embedded_quote_form($s, $post_id);
            $this->render_wrapper_close();
        }

        if ($trigger_mode !== 'none') {
            $this->render_trigger_close($trigger_mode);
        }
    }
}
