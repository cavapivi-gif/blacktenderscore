<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Trait BtQuoteStyleControls — contrôles de style pour le formulaire de devis.
 *
 * Chaque step a sa propre section Elementor pour un contrôle granulaire.
 * Toutes les sections sont conditionnées par le paramètre fourni (ex. show_quote_form).
 */
trait BtQuoteStyleControls {

    /**
     * Enregistre toutes les sections de style du formulaire de devis.
     *
     * @param array $condition Condition d'affichage (ex: ['show_quote_form' => 'yes'])
     * @param array $skip      IDs de sections à ignorer (ex: ['style_quote_steps'])
     *                          pour éviter les doublons quand le widget a ses propres sections.
     */
    protected function register_quote_style_controls(array $condition = [], array $skip = []): void {

        // ── Étapes — Général ─────────────────────────────────────────
        if (!in_array('style_quote_steps', $skip, true)):
        $this->start_controls_section('style_quote_steps', [
            'label'     => __('Devis — Étapes', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_step_bg', [
            'label'     => __('Fond étape', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_active_bg', [
            'label'     => __('Fond étape active', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--active' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_inactive_bg', [
            'label'     => __('Fond étape inactive', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step:not(.bt-quote-step--active)' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_border_color', [
            'label'     => __('Bordure étape', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_active_border', [
            'label'     => __('Bordure étape active', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--active' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_step_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_step_padding', [
            'label'      => __('Padding étape', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_step_gap', [
            'label'      => __('Espacement étapes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('qt_step_inactive_opacity', [
            'label'      => __('Opacité étape inactive', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [''],
            'range'      => ['' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'default'    => ['size' => 0.75],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step:not(.bt-quote-step--active)' => 'opacity: {{SIZE}}'],
        ]);

        $this->add_control('qt_step_number_heading', [
            'label'     => __('Numéro d\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_step_number_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__number' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_number_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__number' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_title_heading', [
            'label'     => __('Titre d\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_step_title_typo',
            'label'    => __('Typographie titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-step__title',
        ]);

        $this->add_control('qt_step_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__title' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
        endif; // skip style_quote_steps

        // ── Step 1 — Excursion (choix + cards) ──────────────────────
        $this->start_controls_section('style_quote_exc', [
            'label'     => __('Devis — Choix excursion', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_exc_choice_heading', [
            'label' => __('Boutons "Cette excursion / Sur mesure"', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_choice_typo',
            'selector' => '{{WRAPPER}} .bt-quote-exc-choice__btn',
        ]);

        $this->add_control('qt_exc_choice_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_choice_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_choice_active_bg', [
            'label'     => __('Fond sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'background-color: {{VALUE}}; border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_choice_active_color', [
            'label'     => __('Texte sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_exc_choice_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('qt_exc_card_heading', [
            'label'     => __('Cards excursion', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_exc_cards_columns', [
            'label'     => __('Colonnes', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                ''  => __('Liste (1 par ligne)', 'blacktenderscore'),
                '2' => '2',
                '3' => '3',
                '4' => '4',
            ],
            'default'   => '',
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-cards' => '{{VALUE}}',
            ],
            'selectors_dictionary' => [
                ''  => 'display: flex; flex-direction: column;',
                '2' => 'display: grid; grid-template-columns: repeat(2, 1fr);',
                '3' => 'display: grid; grid-template-columns: repeat(3, 1fr);',
                '4' => 'display: grid; grid-template-columns: repeat(4, 1fr);',
            ],
        ]);

        $this->add_responsive_control('qt_exc_cards_gap', [
            'label'      => __('Espacement cards', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-cards' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('qt_exc_card_direction', [
            'label'   => __('Direction card', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'row'    => __('Horizontal (image à gauche)', 'blacktenderscore'),
                'column' => __('Vertical (image en haut)', 'blacktenderscore'),
            ],
            'default'   => 'row',
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card' => 'flex-direction: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('qt_exc_card_img_size', [
            'label'      => __('Largeur image (%)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%'],
            'range'      => ['%' => ['min' => 10, 'max' => 60]],
            'default'    => ['size' => 30, 'unit' => '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-exc-card__img' => 'flex: 0 0 {{SIZE}}%; max-width: {{SIZE}}%',
            ],
            'condition'  => ['qt_exc_card_direction' => 'row'],
        ]);

        $this->add_responsive_control('qt_exc_card_img_ratio', [
            'label'      => __('Ratio image', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [''],
            'range'      => ['' => ['min' => 0.3, 'max' => 2.5, 'step' => 0.05]],
            'default'    => ['size' => 1.78],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-exc-card__img' => 'aspect-ratio: {{SIZE}}; flex: none; max-width: 100%',
            ],
            'condition'  => ['qt_exc_card_direction' => 'column'],
        ]);

        $this->add_control('qt_exc_card_bg', [
            'label'     => __('Fond card', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_border', [
            'label'     => __('Bordure card', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_hover_border', [
            'label'     => __('Bordure hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card:hover' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_selected_border', [
            'label'     => __('Bordure sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_selected_bg', [
            'label'     => __('Fond sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_exc_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('qt_exc_card_content_heading', [
            'label'     => __('Contenu card excursion', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_exc_card_show_price', [
            'label'   => __('Afficher le prix', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__price' => 'display: {{VALUE}}'],
            'selectors_dictionary' => ['yes' => 'block', 'no' => 'none'],
        ]);

        $this->add_control('qt_exc_card_show_discount', [
            'label'   => __('Afficher la remise', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__discount' => 'display: {{VALUE}}'],
            'selectors_dictionary' => ['yes' => 'inline-block', 'no' => 'none'],
        ]);

        $this->add_control('qt_exc_card_show_meta', [
            'label'   => __('Afficher meta (pax, horaires, langues)', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__meta' => 'display: {{VALUE}}'],
            'selectors_dictionary' => ['yes' => 'flex', 'no' => 'none'],
        ]);

        $this->add_control('qt_exc_card_show_departure', [
            'label'   => __('Afficher zone de depart', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__departure' => 'display: {{VALUE}}'],
            'selectors_dictionary' => ['yes' => 'inline-block', 'no' => 'none'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_card_title_typo',
            'label'    => __('Typo titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-exc-card__title',
        ]);

        $this->add_control('qt_exc_card_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_card_pax_typo',
            'label'    => __('Typo passagers', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-exc-card__pax',
        ]);

        $this->add_control('qt_exc_card_pax_color', [
            'label'     => __('Couleur passagers', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__pax' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_tag_bg', [
            'label'     => __('Fond tags', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__tag' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_tag_color', [
            'label'     => __('Couleur tags', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__tag' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Step 2 — Cards bateau ────────────────────────────────────
        $this->start_controls_section('style_quote_boat', [
            'label'     => __('Devis — Cards bateau', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_boat_card_border', [
            'label'     => __('Bordure card', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_card_bg', [
            'label'     => __('Fond card', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_card_hover_border', [
            'label'     => __('Bordure hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card:hover' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_card_hover_bg', [
            'label'     => __('Fond hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card:hover' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_card_selected', [
            'label'     => __('Bordure sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card--selected' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_card_selected_bg', [
            'label'     => __('Fond sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card--selected' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // ── Contenu card bateau — Toggles ──

        $this->add_control('qt_boat_card_show_price', [
            'label'   => __('Afficher le prix', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__price' => 'display: {{VALUE}}'],
            'selectors_dictionary' => ['yes' => 'flex', 'no' => 'none'],
        ]);

        $this->add_control('qt_boat_card_show_year', [
            'label'                => __('Afficher l\'année du bateau', 'blacktenderscore'),
            'type'                 => Controls_Manager::SELECT,
            'options'              => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default'              => 'yes',
            'selectors'            => ['{{WRAPPER}} .bt-quote-boat-card__year' => 'display: {{VALUE}}'],
            'selectors_dictionary' => ['yes' => 'inline-block', 'no' => 'none'],
        ]);

        // ── Contenu card bateau — Titre ──

        $this->add_control('qt_boat_title_heading', [
            'label'     => __('Titre bateau', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_title_typo',
            'label'    => __('Typo titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__title',
        ]);

        $this->add_control('qt_boat_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__title' => 'color: {{VALUE}}'],
        ]);

        // ── Contenu card bateau — Passagers ──

        $this->add_control('qt_boat_pax_heading', [
            'label'     => __('Passagers', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_pax_typo',
            'label'    => __('Typo passagers', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__pax',
        ]);

        $this->add_control('qt_boat_pax_color', [
            'label'     => __('Couleur passagers', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__pax' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_show_pax', [
            'label'   => __('Afficher passagers', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'block' => __('Oui', 'blacktenderscore'),
                'none'  => __('Non', 'blacktenderscore'),
            ],
            'default'   => 'block',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__pax' => 'display: {{VALUE}}'],
        ]);

        // ── Contenu card bateau — Prix ──

        $this->add_control('qt_boat_price_heading', [
            'label'     => __('Prix', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_price_amount_typo',
            'label'    => __('Typo montant prix', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__price-amount',
        ]);

        $this->add_control('qt_boat_price_amount_color', [
            'label'     => __('Couleur montant prix', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__price-amount' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_price_suffix_typo',
            'label'    => __('Typo suffixe prix', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__price-suffix',
        ]);

        $this->add_control('qt_boat_price_suffix_color', [
            'label'     => __('Couleur suffixe prix', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__price-suffix' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_show_price', [
            'label'   => __('Afficher prix', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'flex' => __('Oui', 'blacktenderscore'),
                'none' => __('Non', 'blacktenderscore'),
            ],
            'default'   => 'flex',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__price' => 'display: {{VALUE}}'],
        ]);

        // ── Contenu card bateau — Type badge ──

        $this->add_control('qt_boat_type_heading', [
            'label'     => __('Badge type', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_boat_type_bg', [
            'label'     => __('Fond badge type', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__type' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_type_color', [
            'label'     => __('Couleur badge type', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__type' => 'color: {{VALUE}}'],
        ]);

        // ── Contenu card bateau — Année (badge) ──

        $this->add_control('qt_boat_year_heading', [
            'label'     => __('Année du bateau (badge)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_boat_year_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__year' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_year_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__year' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_year_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__year' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_year_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__year' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_year_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__year',
        ]);

        // ── Contenu card bateau — Tags taxonomie ──

        $this->add_control('qt_boat_tags_heading', [
            'label'     => __('Tags taxonomie (pills)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_boat_tag_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__tag' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_tag_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__tag' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_tag_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__tag' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_tag_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__tag',
        ]);

        // ── Contenu card bateau — Bouton "Plus d'infos" ──

        $this->add_control('qt_boat_more_heading', [
            'label'     => __('Bouton "Plus d\'infos"', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_boat_more_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_more_hover_bg', [
            'label'     => __('Fond hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more:hover' => 'background-color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Step 3 — Durée & dates ──────────────────────────────────
        $this->start_controls_section('style_quote_dates', [
            'label'     => __('Devis — Durée & dates', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_dur_bg', [
            'label'     => __('Fond cards durée', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dur_border', [
            'label'     => __('Bordure cards durée', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dur_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_dur_typo',
            'label'    => __('Typographie durée', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-duration-card__label, {{WRAPPER}} .bt-quote-timeslot__btn',
        ]);

        $this->add_control('qt_dur_hover_heading', [
            'label'     => __('Hover', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_dur_hover_bg', [
            'label'     => __('Fond hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_hover_border', [
            'label'     => __('Bordure hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_hover_color', [
            'label'     => __('Texte hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_active_heading', [
            'label'     => __('Sélectionné / Actif', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_dur_active_bg', [
            'label'     => __('Fond sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_active_color', [
            'label'     => __('Texte sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('qt_dur_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-duration-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_control('qt_dp_heading', [
            'label'     => __('Calendrier', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_dp_accent', [
            'label'       => __('Couleur accent calendrier', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('Date sélectionnée + range', 'blacktenderscore'),
            'selectors'   => [
                '{{WRAPPER}} .bt-dp__cell--start, {{WRAPPER}} .bt-dp__cell--end' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-dp__cell--range' => 'background-color: color-mix(in srgb, {{VALUE}} 15%, transparent)',
            ],
        ]);

        $this->end_controls_section();

        // ── Date souhaitée — Labels & Inputs ──────────────────────────
        $this->start_controls_section('style_quote_date_wished', [
            'label'     => __('Devis — Date souhaitée', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_date_label_heading', [
            'label' => __('Labels (Date début / fin)', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_date_label_typo',
            'label'    => __('Typographie label', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-datepicker__label',
        ]);

        $this->add_control('qt_date_label_color', [
            'label'     => __('Couleur label', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-datepicker__label' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_date_input_heading', [
            'label'     => __('Champs date', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_date_input_typo',
            'label'    => __('Typographie champ', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-datepicker__input',
        ]);

        $this->add_control('qt_date_input_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-datepicker__input' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_date_input_bg', [
            'label'     => __('Fond champ', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-datepicker__input' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_date_input_border', [
            'label'     => __('Bordure champ', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-datepicker__input' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_date_input_focus', [
            'label'     => __('Bordure focus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-datepicker__input:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 2px color-mix(in srgb, {{VALUE}} 20%, transparent)'],
        ]);

        $this->add_responsive_control('qt_date_input_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-datepicker__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_date_input_padding', [
            'label'      => __('Padding champ', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-datepicker__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('qt_dp_month_heading', [
            'label'     => __('Navigation calendrier', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_dp_month_typo',
            'label'    => __('Typo mois / année', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-dp__month-label',
        ]);

        $this->add_control('qt_dp_month_color', [
            'label'     => __('Couleur mois / année', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__month-label' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dp_cell_heading', [
            'label'     => __('Cellules jour', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_dp_cell_color', [
            'label'     => __('Couleur texte jour', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__cell' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dp_cell_hover_bg', [
            'label'     => __('Fond hover jour', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__cell:hover:not(.bt-dp__cell--disabled):not(.bt-dp__cell--empty)' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dp_selected_color', [
            'label'     => __('Texte date sélectionnée', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__cell--start, {{WRAPPER}} .bt-dp__cell--end' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── État sélectionné — unifié [aria-selected="true"] ────────
        $this->start_controls_section('style_quote_selected', [
            'label'     => __('Devis — État sélectionné', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_sel_bg', [
            'label'     => __('Fond sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'      => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                 => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_sel_border', [
            'label'     => __('Bordure sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'      => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                 => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_sel_color', [
            'label'     => __('Texte sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'      => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                 => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('qt_sel_radius', [
            'label'      => __('Border radius sélectionné', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'        => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]'   => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('qt_sel_padding', [
            'label'      => __('Padding sélectionné', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]'   => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'qt_sel_typo',
            'label'    => __('Typographie sélectionné', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"] .bt-quote-duration-card__label, {{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"], {{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_sel_shadow',
            'label'    => __('Box-shadow sélectionné', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} [aria-selected="true"], {{WRAPPER}} .bt-quote-boat-card--selected',
        ]);

        $this->add_control('qt_hover_heading', [
            'label'     => __('État survol', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_hover_bg', [
            'label'     => __('Fond survol', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card:hover'      => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card:hover'     => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_hover_border', [
            'label'     => __('Bordure survol', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card:hover'      => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card:hover'     => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_hover_color', [
            'label'     => __('Texte survol', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card:hover'      => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card:hover'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_hover_shadow',
            'label'    => __('Box-shadow survol', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-exc-card:hover, {{WRAPPER}} .bt-quote-boat-card:hover, {{WRAPPER}} .bt-quote-duration-card:hover, {{WRAPPER}} .bt-quote-timeslot__btn:hover, {{WRAPPER}} .bt-quote-exc-choice__btn:hover',
        ]);

        $this->end_controls_section();

        // ── Step 4 — Champs coordonnées ─────────────────────────────
        if (!in_array('style_quote_fields', $skip, true)):
        $this->start_controls_section('style_quote_fields', [
            'label'     => __('Devis — Champs', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_field_label_color', [
            'label'     => __('Couleur labels', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__label, {{WRAPPER}} .bt-quote-datepicker__label' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_field_label_typo',
            'label'    => __('Typo labels', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-fields__label',
        ]);

        $this->add_control('qt_field_bg', [
            'label'     => __('Fond champs', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_field_border', [
            'label'     => __('Bordure champs', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_field_focus', [
            'label'     => __('Bordure focus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-fields__input:focus, {{WRAPPER}} .bt-quote-datepicker__input:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 2px color-mix(in srgb, {{VALUE}} 20%, transparent)',
            ],
        ]);

        $this->add_responsive_control('qt_field_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_field_padding', [
            'label'      => __('Padding champs', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
        endif; // skip style_quote_fields

        // ── Boutons (Suivant + Envoi) ────────────────────────────────
        $this->start_controls_section('style_quote_buttons', [
            'label'     => __('Devis — Boutons', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_btn_next_heading', [
            'label' => __('Bouton "Suivant"', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_btn_next_typo',
            'selector' => '{{WRAPPER}} .bt-quote-step__next',
        ]);

        $this->add_control('qt_btn_next_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_next_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_btn_next_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_btn_next_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('qt_btn_submit_heading', [
            'label'     => __('Bouton "Envoyer"', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_btn_submit_typo',
            'selector' => '{{WRAPPER}} .bt-quote-submit',
        ]);

        $this->add_control('qt_btn_submit_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_submit_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_btn_submit_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_btn_submit_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Messages résultat ────────────────────────────────────────
        if (!in_array('style_quote_messages', $skip, true)):
        $this->start_controls_section('style_quote_messages', [
            'label'     => __('Devis — Messages', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_msg_success_bg', [
            'label'     => __('Fond succès', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--success' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_msg_success_color', [
            'label'     => __('Texte succès', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--success' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_msg_error_bg', [
            'label'     => __('Fond erreur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--error' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_msg_error_color', [
            'label'     => __('Texte erreur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--error' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
        endif; // skip style_quote_messages
    }
}
