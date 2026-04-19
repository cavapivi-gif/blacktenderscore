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
 * Widget Elementor — Boat Tarifs.
 *
 * Widget dédié à la tarification bateau avec devis multi-étapes.
 * Inclut l'étape excursion avec "Trajet sur mesure" en premier.
 *
 * Classes CSS préfixées .bt-btarifs pour éviter conflits avec Tarifs Body.
 *
 * @package BlackTenders\Elementor\Widgets
 */
class BoatTarifs extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtBoatPricing;
    use \BlackTenders\Elementor\Traits\BtPricingShared;
    use \BlackTenders\Elementor\Traits\BtQuoteStyleControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-boat-tarifs',
            'title'    => 'BT — Boat Tarifs',
            'icon'     => 'eicon-price-table',
            'keywords' => ['tarif', 'boat', 'bateau', 'prix', 'devis', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-pricing-tabs', 'bt-quote-form', 'bt-segmented-control', 'bt-quote-substep'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote', 'bt-segmented-control'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Mode & visibilité
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_btarifs_mode', [
            'label' => __('Mode & visibilité', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('body_initial_state', [
            'label'   => __('État initial', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'visible' => __('Visible', 'blacktenderscore'),
                'hidden'  => __('Masqué (attend un trigger)', 'blacktenderscore'),
            ],
            'default'     => 'visible',
            'description' => __('Ajoutez data-bt-pricing-trigger sur un bouton Elementor pour déclencher l\'affichage.', 'blacktenderscore'),
        ]);

        $this->add_control('devis_btn_hide_classes', [
            'label'       => __('Classes à cacher au clic', 'blacktenderscore'),
            'description' => __('Sélecteurs CSS masqués quand le body s\'ouvre. Ex: .ma-classe', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'separator'   => 'before',
        ]);

        $this->add_control('devis_btn_show_classes', [
            'label'       => __('Classes à afficher au clic', 'blacktenderscore'),
            'description' => __('Sélecteurs CSS affichés quand le body s\'ouvre.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Forfaits bateau
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_btarifs_boat', [
            'label' => __('Forfaits bateau', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
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
            'label'        => __('Prix / personne', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('per_person_label', [
            'label'     => __('Suffixe / pers.', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('/ pers.', 'blacktenderscore'),
            'condition' => ['show_per_person' => 'yes'],
        ]);

        $this->add_control('show_deposit', [
            'label'        => __('Caution', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
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
        ]);

        $this->add_control('show_price_note', [
            'label'        => __('Note tarifaire', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
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
                '{{WRAPPER}} .bt-btarifs .bt-forfaits__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)',
            ],
            'condition' => ['layout' => 'cards'],
        ]);

        $this->add_control('table_col_forfait', [
            'label'     => __('En-tête Forfait', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Forfait', 'blacktenderscore'),
            'condition' => ['layout' => 'table'],
        ]);

        $this->add_control('table_col_duration', [
            'label'     => __('En-tête Durée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Durée', 'blacktenderscore'),
            'condition' => ['layout' => 'table'],
        ]);

        $this->add_control('table_col_price', [
            'label'     => __('En-tête Prix', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Prix', 'blacktenderscore'),
            'condition' => ['layout' => 'table'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Tarifs par zone
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_btarifs_zones', [
            'label'     => __('Tarifs par zone', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['layout!' => 'tabs'],
        ]);

        $this->add_control('show_zones', [
            'label'        => __('Afficher tarifs par zone', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('zones_title', [
            'label'     => __('Titre tableau zones', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Tarifs par zone de départ', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_zone', [
            'label'     => __('En-tête Zone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Zone de navigation', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_half', [
            'label'     => __('En-tête ½ journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_full', [
            'label'     => __('En-tête journée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Journée', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Formulaire de devis
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_btarifs_quote', [
            'label' => __('Formulaire de devis', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_quote_form', [
            'label'        => __('Intégrer le formulaire de devis', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('quote_tab2_label', [
            'label'     => __('Label bouton devis', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demander un devis', 'blacktenderscore'),
            'condition' => ['show_quote_form' => 'yes'],
        ]);

        $this->add_control('quote_recipient', [
            'label'     => __('E-mail destinataire', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => get_option('admin_email'),
            'condition' => ['show_quote_form' => 'yes'],
        ]);

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

        $this->add_control('hide_cards_on_trigger', [
            'label'        => __('Cacher forfaits au trigger', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'separator'    => 'before',
            'condition'    => ['show_quote_form' => 'yes'],
        ]);

        // ── Option "Trajet sur mesure" ──
        $this->add_control('qt_custom_trip_heading', [
            'label'     => __('Option "Trajet sur mesure"', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['show_quote_form' => 'yes'],
        ]);

        $this->add_control('qt_show_custom_trip', [
            'label'        => __('Afficher "Trajet sur mesure"', 'blacktenderscore'),
            'description'  => __('Ajoute une option en premier dans la liste des excursions.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['show_quote_form' => 'yes'],
        ]);

        $this->add_control('qt_custom_trip_label', [
            'label'     => __('Label', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Trajet sur mesure', 'blacktenderscore'),
            'condition' => ['show_quote_form' => 'yes', 'qt_show_custom_trip' => 'yes'],
        ]);

        $this->add_control('qt_custom_trip_desc', [
            'label'     => __('Description', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Créez votre propre itinéraire', 'blacktenderscore'),
            'condition' => ['show_quote_form' => 'yes', 'qt_show_custom_trip' => 'yes'],
        ]);

        $this->add_control('qt_custom_trip_img', [
            'label'     => __('Image', 'blacktenderscore'),
            'type'      => Controls_Manager::MEDIA,
            'default'   => ['url' => 'https://dev.studiojae.fr/wp-content/uploads/2026/02/images.png'],
            'condition' => ['show_quote_form' => 'yes', 'qt_show_custom_trip' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══ STYLE ═══════════════════════════════════════════════════════════════

        // ── 1. Conteneur wrapper (.bt-btarifs) ────────────────────────────────
        $this->start_controls_section('style_btarifs_container', [
            'label' => __('Conteneur', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'btarifs_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-btarifs',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btarifs_border',
            'selector' => '{{WRAPPER}} .bt-btarifs',
        ]);

        $this->add_responsive_control('btarifs_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-btarifs' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btarifs_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-btarifs' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btarifs_shadow',
            'selector' => '{{WRAPPER}} .bt-btarifs',
        ]);

        $this->end_controls_section();

        // ── 2. Conteneur interne (.bt-btarifs__inner) ─────────────────────────
        $this->start_controls_section('style_btarifs_inner', [
            'label' => __('Conteneur interne', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'btarifs_inner_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-btarifs__inner',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btarifs_inner_border',
            'selector' => '{{WRAPPER}} .bt-btarifs__inner',
        ]);

        $this->add_responsive_control('btarifs_inner_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-btarifs__inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btarifs_inner_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-btarifs__inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btarifs_inner_shadow',
            'selector' => '{{WRAPPER}} .bt-btarifs__inner',
        ]);

        $this->end_controls_section();

        // ── 3. Cards — Forfaits ───────────────────────────────────────────────
        $this->start_controls_section('style_btarifs_cards', [
            'label' => __('Cards — Forfaits', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        // Alignement
        $this->add_responsive_control('btarifs_card_align_h', [
            'label'   => __('Alignement horizontal', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'flex-end'   => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => [
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card'          => 'align-items: {{VALUE}}',
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__body'    => 'align-items: {{VALUE}}',
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__pricing' => 'justify-content: {{VALUE}}',
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__meta'    => 'align-items: {{VALUE}}',
            ],
        ]);

        // Container card — Normal / Hover / Active
        $this->start_controls_tabs('btarifs_card_tabs');

        $this->start_controls_tab('btarifs_card_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'btarifs_card_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btarifs_card_border',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btarifs_card_shadow',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btarifs_card_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'btarifs_card_bg_hover',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card:hover',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btarifs_card_border_hover',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card:hover',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btarifs_card_shadow_hover',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card:hover',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btarifs_card_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'btarifs_card_bg_active',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card--active',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btarifs_card_border_active',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card--active',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'btarifs_card_shadow_active',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card--active',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // Dimensions
        $this->add_responsive_control('btarifs_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .bt-btarifs .bt-forfait-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btarifs_card_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-btarifs .bt-forfait-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // Typographies
        $this->add_control('btarifs_typo_heading', [
            'label'     => __('Typographies', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        // Titre
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'btarifs_title_typo',
            'label'    => __('Titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card__title',
        ]);
        $this->add_control('btarifs_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-btarifs .bt-forfait-card__title' => 'color: {{VALUE}}'],
        ]);

        // Sous-titre
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'btarifs_subtitle_typo',
            'label'    => __('Sous-titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card__subtitle',
        ]);
        $this->add_control('btarifs_subtitle_color', [
            'label'     => __('Couleur sous-titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-btarifs .bt-forfait-card__subtitle' => 'color: {{VALUE}}'],
        ]);

        // Prix
        $this->add_control('btarifs_price_heading', [
            'label'     => __('Prix', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'btarifs_price_typo',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card__price',
        ]);
        $this->add_control('btarifs_price_color', [
            'label'     => __('Couleur prix', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-btarifs .bt-forfait-card__price' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('btarifs_currency_color', [
            'label'     => __('Couleur devise + / pers.', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__currency' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__per'      => 'color: {{VALUE}}',
            ],
        ]);

        // Méta
        $this->add_control('btarifs_meta_heading', [
            'label'     => __('Méta (durée + pax)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'btarifs_meta_typo',
            'selector' => '{{WRAPPER}} .bt-btarifs .bt-forfait-card__meta-item',
        ]);
        $this->add_control('btarifs_meta_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__meta-item' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-btarifs .bt-forfait-card__icon'      => 'color: {{VALUE}}',
            ],
        ]);

        // Espacement
        $this->add_responsive_control('btarifs_devis_gap', [
            'label'      => __('Espacement avant le bouton devis', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'separator'  => 'before',
            'condition'  => ['show_quote_form' => 'yes'],
            'selectors'  => ['{{WRAPPER}} .bt-btarifs__devis-reveal' => 'margin-top: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── 4. Bouton "Demander un devis" ─────────────────────────────────────
        $this->register_button_style(
            'btarifs_devis_btn',
            __('Bouton — Devis', 'blacktenderscore'),
            '{{WRAPPER}} .bt-btarifs__devis-btn',
            [],
            ['show_quote_form' => 'yes']
        );

        // ── 5. Devis — toutes les sections ────────────────────────────────────
        $this->register_quote_style_controls(['show_quote_form' => 'yes']);
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID() ?: 0;

        // Type bateau "voile" → rien à afficher
        if ($post_id && has_term('voile', 'exp_boat_type', $post_id)) {
            return;
        }

        // Visibilité
        $is_edit = $this->is_edit_mode();
        $hidden  = !$is_edit && ($s['body_initial_state'] ?? 'visible') === 'hidden';

        $cls  = 'bt-btarifs';
        $cls .= $hidden ? ' bt-btarifs--hidden' : '';

        $hide_cls = trim((string) ($s['devis_btn_hide_classes'] ?? ''));
        $show_cls = trim((string) ($s['devis_btn_show_classes'] ?? ''));

        echo '<div class="' . esc_attr($cls) . '" data-bt-pricing-body'
           . ($hidden ? ' aria-hidden="true"' : '')
           . ($hide_cls !== '' ? ' data-bt-body-hide="' . esc_attr($hide_cls) . '"' : '')
           . ($show_cls !== '' ? ' data-bt-body-show="' . esc_attr($show_cls) . '"' : '')
           . '>';

        if (!$this->acf_required()) {
            echo '</div>';
            return;
        }

        echo '<div class="bt-btarifs__inner">';

        // Rendu des forfaits bateau (via trait BtBoatPricing)
        $this->render_pricing_content($s, $post_id);

        echo '</div>'; // .bt-btarifs__inner

        // Formulaire de devis
        $has_quote = ($s['show_quote_form'] ?? '') === 'yes';
        if ($has_quote) {
            $devis_label   = esc_html($s['quote_tab2_label'] ?: __('Demander un devis', 'blacktenderscore'));
            $icon_mode     = $s['devis_btn_icon_mode'] ?? 'none';
            $icon_position = $s['devis_btn_icon_position'] ?? 'before';
            $icon_html     = '';

            if ($icon_mode === 'icon') {
                $icon_val = $s['devis_btn_icon'] ?? [];
                if (is_array($icon_val) && !empty($icon_val['value'])) {
                    ob_start();
                    \Elementor\Icons_Manager::render_icon($icon_val, ['aria-hidden' => 'true', 'class' => 'bt-btarifs__devis-btn-icon']);
                    $icon_html = ob_get_clean();
                }
            }

            $btn_inner = $icon_position === 'after'
                ? '<span>' . $devis_label . '</span>' . $icon_html
                : $icon_html . '<span>' . $devis_label . '</span>';

            $data_hide_cls   = $hide_cls !== '' ? ' data-bt-reveal-hide-classes="' . esc_attr($hide_cls) . '"' : '';
            $data_show_cls   = $show_cls !== '' ? ' data-bt-reveal-show-classes="' . esc_attr($show_cls) . '"' : '';
            $data_hide_cards = ($s['hide_cards_on_trigger'] ?? '') === 'yes' ? ' data-bt-hide-cards' : '';

            echo '<div class="bt-btarifs__devis-reveal" data-bt-trigger="reveal" data-bt-reveal-inline' . $data_hide_cls . $data_show_cls . $data_hide_cards . '>';
            echo '<button type="button" class="bt-pricing__trigger bt-pricing__trigger--fullwidth bt-btarifs__devis-btn" aria-expanded="false">' . $btn_inner . '</button>';
            echo '<div class="bt-pricing__reveal-content"><div>';
            $this->render_embedded_quote_form($s, $post_id);
            echo '</div></div></div>';
        }

        echo '</div>'; // .bt-btarifs
    }
}
