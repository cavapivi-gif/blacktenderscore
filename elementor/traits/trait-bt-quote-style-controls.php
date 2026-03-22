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
            'label'     => __('Wizard — Navigation & étapes', 'blacktenderscore'),
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

        $this->add_responsive_control('qt_step_header_padding', [
            'label'      => __('Padding header étape', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('qt_step_actions_padding', [
            'label'      => __('Padding actions étape', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-step__actions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
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
            'label'      => __('Opacité étape future (collapsed)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [''],
            'range'      => ['' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'default'    => ['size' => 0.6],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step--collapsed:not(.bt-quote-step--active):not(.bt-quote-step--done)' => 'opacity: {{SIZE}}'],
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

        $this->add_control('qt_step_title_collapsed_color', [
            'label'     => __('Couleur titre collapsed', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--collapsed .bt-quote-step__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_number_active_heading', [
            'label'     => __('Numéro — actif / complété', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_step_number_active_bg', [
            'label'     => __('Fond numéro actif/done', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-step--active .bt-quote-step__number' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-step--done .bt-quote-step__number'   => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_step_number_active_color', [
            'label'     => __('Couleur numéro actif/done', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-step--active .bt-quote-step__number' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-step--done .bt-quote-step__number'   => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_step_check_color', [
            'label'       => __('Couleur coche (done)', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('SVG stroke — hérité par currentColor', 'blacktenderscore'),
            'selectors'   => ['{{WRAPPER}} .bt-quote-step__check' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_step_summary_color', [
            'label'     => __('Couleur résumé (sous header)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__summary' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
        endif; // skip style_quote_steps

        // ── Step 1 — Excursion (choix + cards) ──────────────────────
        $this->start_controls_section('style_quote_exc', [
            'label'     => __('Cards excursion', 'blacktenderscore'),
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
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]'   => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected'               => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_exc_choice_active_color', [
            'label'     => __('Texte sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected'             => 'color: {{VALUE}}',
            ],
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
            'label'     => __('Ratio image', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'auto' => __('Auto (ratio naturel)', 'blacktenderscore'),
                '16/9' => '16:9',
                '3/2'  => '3:2',
                '4/3'  => '4:3',
                '1'    => '1:1',
                '3/4'  => '3:4 (portrait)',
            ],
            'default'   => 'auto',
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card__img'     => 'flex: none; max-width: 100%',
                '{{WRAPPER}} .bt-quote-exc-card__img img' => 'position: static; width: 100%; height: auto; aspect-ratio: {{VALUE}}; object-fit: cover',
            ],
            'condition' => ['qt_exc_card_direction' => 'column'],
        ]);

        $this->add_responsive_control('qt_exc_card_img_min_h', [
            'label'      => __('Hauteur min image', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 500]],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-exc-card__img img' => 'min-height: {{SIZE}}{{UNIT}}; object-fit: cover',
            ],
            'condition'  => ['qt_exc_card_direction' => 'column'],
        ]);

        $this->start_controls_tabs('tabs_exc_card_colors');

        $this->start_controls_tab('tab_exc_card_normal', ['label' => __('Normal', 'blacktenderscore')]);

        $this->add_control('qt_exc_card_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card' => 'border-color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('tab_exc_card_hover', ['label' => __('Hover', 'blacktenderscore')]);

        $this->add_control('qt_exc_card_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card:hover' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_card_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card:hover' => 'background-color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->end_controls_tabs();

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

        $this->add_control('qt_exc_card_show_skipper', [
            'label'   => __('Afficher skipper (taxo boat_skipper)', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['yes' => __('Oui', 'blacktenderscore'), 'no' => __('Non', 'blacktenderscore')],
            'default' => 'yes',
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__skipper' => 'display: {{VALUE}}'],
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

        $this->add_control('qt_exc_card_discount_heading', [
            'label'     => __('Badge remise', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);
        $this->add_control('qt_exc_card_discount_bg', [
            'label'     => __('Fond badge remise', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__discount' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_exc_card_discount_color', [
            'label'     => __('Texte badge remise', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__discount' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Step 2 — Cards bateau ────────────────────────────────────
        $this->start_controls_section('style_quote_boat', [
            'label'     => __('Cards bateau — Layout & style', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        // ── Grille de sélection ───────────────────────────────────────
        $this->add_control('qt_boat_cards_grid_heading', [
            'label' => __('Grille de sélection', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_responsive_control('qt_boat_cards_cols', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3'],
            'default'        => '1',
            'tablet_default' => '1',
            'mobile_default' => '1',
            'selectors'      => ['{{WRAPPER}} .bt-quote-boat-cards' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_responsive_control('qt_boat_cards_gap', [
            'label'      => __('Espacement cards', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-cards' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Direction de la card ──────────────────────────────────────
        $this->add_control('qt_boat_card_dir_heading', [
            'label'     => __('Disposition de la card', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_boat_card_direction', [
            'label'   => __('Direction', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'row'    => ['title' => __('Horizontal (image gauche)', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'column' => ['title' => __('Vertical (image en haut)',  'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'row',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'flex-direction: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_img_ratio', [
            'label'   => __('Ratio image', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'auto' => __('Auto (ratio naturel)', 'blacktenderscore'),
                '16/9' => '16:9',
                '3/2'  => '3:2',
                '4/3'  => '4:3',
                '1'    => '1:1',
                '3/4'  => '3:4 (portrait)',
            ],
            'default'   => 'auto',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'aspect-ratio: {{VALUE}}'],
            'condition' => ['qt_boat_card_direction' => 'column'],
        ]);

        $this->add_responsive_control('qt_boat_card_img_min_h', [
            'label'      => __('Hauteur min image', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 500]],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-boat-card__bg' => 'min-height: {{SIZE}}{{UNIT}}',
            ],
            'condition'  => ['qt_boat_card_direction' => 'column'],
        ]);

        // ── Card container ────────────────────────────────────────────
        $this->add_responsive_control('qt_boat_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->start_controls_tabs('tabs_boat_card_colors');

        $this->start_controls_tab('tab_boat_card_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_boat_card_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_card_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'border-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_card_hover', ['label' => __('Hover', 'blacktenderscore')]);
        $this->add_control('qt_boat_card_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_card_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card:hover' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_card_selected', ['label' => __('Sélectionné', 'blacktenderscore')]);
        $this->add_control('qt_boat_card_selected_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card--selected' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_card_selected', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card--selected' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Image de fond ─────────────────────────────────────────────
        $this->add_control('qt_boat_img_heading', [
            'label'     => __('Image de fond', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_boat_img_width', [
            'label'      => __('Largeur image', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => ['%' => ['min' => 10, 'max' => 100], 'px' => ['min' => 50, 'max' => 600]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'width: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Titre ─────────────────────────────────────────────────────
        $this->add_control('qt_boat_title_heading', [
            'label'     => __('Titre', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_title_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__title',
        ]);

        $this->add_control('qt_boat_title_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__title' => 'color: {{VALUE}}'],
        ]);

        // ── Sous-titre ────────────────────────────────────────────────
        $this->add_control('qt_boat_subtitle_heading', [
            'label'     => __('Sous-titre', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_subtitle_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__subtitle',
        ]);

        $this->add_control('qt_boat_subtitle_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__subtitle' => 'color: {{VALUE}}'],
        ]);

        // ── Prix (fallback sans forfaits) ─────────────────────────────
        $this->add_control('qt_boat_price_heading', [
            'label'     => __('Prix (fallback sans forfaits)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_price_amount_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__price, {{WRAPPER}} .bt-quote-boat-card__pp',
        ]);

        $this->add_control('qt_boat_price_amount_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-boat-card__price' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card__pp'    => 'color: {{VALUE}}',
            ],
        ]);

        // ── Méta (passagers, équipement…) ─────────────────────────────
        $this->add_control('qt_boat_meta_heading', [
            'label'     => __('Méta (passagers, équipement…)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_meta_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__meta',
        ]);

        $this->add_control('qt_boat_meta_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__meta' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_meta_opacity', [
            'label'      => __('Opacité', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__meta' => 'opacity: {{SIZE}}'],
        ]);

        $this->end_controls_section();

        // ── Step 3 — Durée & dates ──────────────────────────────────
        $this->start_controls_section('style_quote_dates', [
            'label'     => __('Étape — Durée & dates', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_dur_typo',
            'label'    => __('Typographie durée', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-duration-card__label, {{WRAPPER}} .bt-quote-timeslot__btn',
        ]);

        $this->start_controls_tabs('tabs_dur_colors');

        $this->start_controls_tab('tab_dur_normal', ['label' => __('Normal', 'blacktenderscore')]);

        $this->add_control('qt_dur_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dur_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dur_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('tab_dur_hover', ['label' => __('Hover', 'blacktenderscore')]);

        $this->add_control('qt_dur_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'color: {{VALUE}}',
            ],
        ]);

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('qt_dur_active_heading', [
            'label'     => __('Sélectionné / Actif', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_dur_active_bg', [
            'label'     => __('Fond sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]'   => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'               => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]'   => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'               => 'background-color: {{VALUE}}; border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_dur_active_color', [
            'label'     => __('Texte sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'             => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'             => 'color: {{VALUE}}',
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

        // ── Date souhaitée — Calendrier ──────────────────────────────
        $this->start_controls_section('style_quote_date_wished', [
            'label'     => __('Étape — Date souhaitée', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        // Calendrier — couleurs
        $this->add_control('qt_dp_cell_color', [
            'label'     => __('Texte jours', 'blacktenderscore'),
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

        $this->add_control('qt_dp_range_bg', [
            'label'       => __('Fond entre les dates (range)', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('Couleur de fond pour les jours entre la date de début et la date de fin.', 'blacktenderscore'),
            'selectors'   => [
                '{{WRAPPER}} .bt-dp__cell--range' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-dp__cell--start' => 'background: linear-gradient(90deg, transparent 0%, {{VALUE}} 100%)',
                '{{WRAPPER}} .bt-dp__cell--end'   => 'background: linear-gradient(90deg, {{VALUE}} 0%, transparent 100%)',
            ],
        ]);

        $this->add_control('qt_dp_month_color', [
            'label'     => __('Couleur mois / année', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__month-label' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── État sélectionné — unifié [aria-selected="true"] ────────
        $this->start_controls_section('style_quote_selected', [
            'label'     => __('Étape — Élément sélectionné', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->start_controls_tabs('tabs_sel_states');

        $this->start_controls_tab('tab_sel_selected', ['label' => __('Sélectionné', 'blacktenderscore')]);

        $this->add_control('qt_sel_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'        => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                   => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]'   => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'               => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]'   => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'               => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected'             => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_sel_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'        => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                   => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]'   => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'               => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]'   => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'               => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected'             => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_sel_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]'        => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card--selected'                   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'               => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'               => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected'             => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_sel_shadow',
            'label'    => __('Box-shadow', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} [aria-selected="true"], {{WRAPPER}} .bt-quote-boat-card--selected',
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('tab_sel_hover', ['label' => __('Survol', 'blacktenderscore')]);

        $this->add_control('qt_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card:hover'        => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card:hover'       => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card:hover'   => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover'   => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card:hover'        => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card:hover'       => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card:hover'   => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover'   => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card:hover'        => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card:hover'       => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card:hover'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_hover_shadow',
            'label'    => __('Box-shadow', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-exc-card:hover, {{WRAPPER}} .bt-quote-boat-card:hover, {{WRAPPER}} .bt-quote-duration-card:hover, {{WRAPPER}} .bt-quote-timeslot__btn:hover, {{WRAPPER}} .bt-quote-exc-choice__btn:hover',
        ]);

        $this->end_controls_tab();

        $this->end_controls_tabs();

        // Radius / padding / typo sélectionné — hors tabs (pas de variante hover)
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

        $this->end_controls_section();

        // ── Step 4 — Champs coordonnées ─────────────────────────────
        if (!in_array('style_quote_fields', $skip, true)):
        $this->start_controls_section('style_quote_fields', [
            'label'     => __('Formulaire — Champs', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_field_label_color', [
            'label'     => __('Couleur labels', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__label' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_field_label_typo',
            'label'    => __('Typo labels', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-fields__label',
        ]);

        $this->add_control('qt_field_bg', [
            'label'     => __('Fond champs', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__input' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_field_border', [
            'label'     => __('Bordure champs', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__input' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_field_focus', [
            'label'     => __('Bordure focus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-fields__input:focus' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 2px color-mix(in srgb, {{VALUE}} 20%, transparent)',
            ],
        ]);

        $this->add_responsive_control('qt_field_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-fields__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_field_padding', [
            'label'      => __('Padding champs', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-fields__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
        endif; // skip style_quote_fields

        // ── Boutons (Suivant + Envoi) ────────────────────────────────
        $this->start_controls_section('style_quote_buttons', [
            'label'     => __('Formulaire — Boutons & submit', 'blacktenderscore'),
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

        $this->start_controls_tabs('tabs_btn_next_colors');

        $this->start_controls_tab('tab_btn_next_normal', ['label' => __('Normal', 'blacktenderscore')]);

        $this->add_control('qt_btn_next_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_next_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_next_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'border-color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('tab_btn_next_hover', ['label' => __('Hover', 'blacktenderscore')]);

        $this->add_control('qt_btn_next_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next:hover' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_next_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__next:hover' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control('qt_btn_submit_heading', [
            'label'     => __('Bouton "Envoyer"', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_btn_submit_typo',
            'selector' => '{{WRAPPER}} .bt-quote-submit',
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

        $this->start_controls_tabs('tabs_btn_submit_colors');

        $this->start_controls_tab('tab_btn_submit_normal', ['label' => __('Normal', 'blacktenderscore')]);

        $this->add_control('qt_btn_submit_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_submit_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->start_controls_tab('tab_btn_submit_hover', ['label' => __('Hover', 'blacktenderscore')]);

        $this->add_control('qt_btn_submit_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-submit:hover' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_btn_submit_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-submit:hover' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── Forfaits bateau (pills) ───────────────────────────────────
        $this->start_controls_section('style_quote_boat_forfaits', [
            'label'     => __('Cards bateau — Forfaits & pills', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        // ── Grille ────────────────────────────────────────────────────
        $this->add_control('qt_boat_forfaits_grid_heading', [
            'label' => __('Grille de forfaits', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_responsive_control('qt_boat_forfaits_cols', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3'],
            'default'        => '2',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors'      => ['{{WRAPPER}} .bt-quote-boat-card__forfaits' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_responsive_control('qt_boat_forfaits_gap', [
            'label'      => __('Espacement entre pills', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 32]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfaits' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Pill ──────────────────────────────────────────────────────
        $this->add_control('qt_boat_forfait_pill_heading', [
            'label'     => __('Pill forfait', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_boat_forfait_align', [
            'label'                => __('Alignement contenu', 'blacktenderscore'),
            'type'                 => Controls_Manager::CHOOSE,
            'options'              => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'),  'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'),  'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'),  'icon' => 'eicon-text-align-right'],
            ],
            'default'              => 'left',
            'selectors_dictionary' => ['left' => 'start', 'center' => 'center', 'right' => 'end'],
            'selectors'            => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'align-items: {{VALUE}}; text-align: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_forfait_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_forfait_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->start_controls_tabs('tabs_boat_forfait');

        $this->start_controls_tab('tab_boat_forfait_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_boat_forfait_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'border-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_forfait_hover', ['label' => __('Hover', 'blacktenderscore')]);
        $this->add_control('qt_boat_forfait_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait:hover' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait:hover' => 'border-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_forfait_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('qt_boat_forfait_active_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait--active' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_active_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait--active' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_active_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait--active' => 'border-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Prix ──────────────────────────────────────────────────────
        $this->add_control('qt_boat_forfait_price_heading', [
            'label'     => __('Prix', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_forfait_price_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__forfait-price',
        ]);

        $this->add_control('qt_boat_forfait_price_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait-price' => 'color: {{VALUE}}'],
        ]);

        // ── Label durée ───────────────────────────────────────────────
        $this->add_control('qt_boat_forfait_label_heading', [
            'label'     => __('Label durée', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_forfait_label_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__forfait-label',
        ]);

        $this->add_control('qt_boat_forfait_label_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait-label' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_forfait_label_opacity', [
            'label'      => __('Opacité', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfait-label' => 'opacity: {{SIZE}}'],
        ]);

        // ── Bouton "Plus d'infos" ──────────────────────────────────────
        $this->add_control('qt_boat_more_gap', [
            'label'      => __('Espace avant le bouton', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'separator'  => 'before',
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'margin-top: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('qt_boat_more_heading', [
            'label'     => __('Bouton "Plus d\'infos"', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_more_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__more',
        ]);

        $this->start_controls_tabs('tabs_boat_more');

        $this->start_controls_tab('tab_boat_more_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_boat_more_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_more_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_more_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'border-top-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_more_hover', ['label' => __('Hover', 'blacktenderscore')]);
        $this->add_control('qt_boat_more_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_more_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more:hover' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── Options / Chips (substep) ─────────────────────────────────
        $this->start_controls_section('style_quote_chips', [
            'label'     => __('Étape options — Chips', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_chip_heading_default', [
            'label' => __('Chip — Normal', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_control('qt_chip_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_chip_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_chip_heading_selected', [
            'label'     => __('Chip — Sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_chip_selected_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip--selected' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_chip_selected_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip--selected' => 'color: {{VALUE}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Messages résultat ────────────────────────────────────────
        if (!in_array('style_quote_messages', $skip, true)):
        $this->start_controls_section('style_quote_messages', [
            'label'     => __('Messages & alertes', 'blacktenderscore'),
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
