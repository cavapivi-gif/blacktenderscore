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
     */
    protected function register_quote_style_controls(array $condition = []): void {

        // ── 📋 Étapes — Général ─────────────────────────────────────────
        $this->start_controls_section('style_quote_steps', [
            'label'     => __('📋 Devis — Étapes', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_step_border_color', [
            'label'     => __('Bordure étape', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_step_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
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

        // ── 📋 Step 1 — Excursion (choix + cards) ──────────────────────
        $this->start_controls_section('style_quote_exc', [
            'label'     => __('📋 Devis — Choix excursion', 'blacktenderscore'),
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

        $this->end_controls_section();

        // ── 📋 Step 2 — Cards bateau ────────────────────────────────────
        $this->start_controls_section('style_quote_boat', [
            'label'     => __('📋 Devis — Cards bateau', 'blacktenderscore'),
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

        $this->add_control('qt_boat_card_selected', [
            'label'     => __('Bordure sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card--selected' => 'border-color: {{VALUE}}; box-shadow: 0 0 0 1px {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('qt_boat_title_heading', [
            'label'     => __('Contenu card', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_title_typo',
            'label'    => __('Typo titre bateau', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__title',
        ]);

        $this->add_control('qt_boat_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_meta_color', [
            'label'     => __('Couleur méta (pax, prix, type)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-boat-card__pax' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card__price' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-boat-card__type' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_boat_show_pax', [
            'label'        => __('Afficher passagers', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'selectors'    => ['{{WRAPPER}} .bt-quote-boat-card__pax' => 'display: {{VALUE === "yes" ? "block" : "none"}}'],
        ]);

        $this->add_control('qt_boat_show_price', [
            'label'        => __('Afficher prix / personne', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('qt_boat_show_fuel', [
            'label'        => __('Afficher badge carburant', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── 📋 Step 3 — Durée & dates ──────────────────────────────────
        $this->start_controls_section('style_quote_dates', [
            'label'     => __('📋 Devis — Durée & dates', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_dur_border', [
            'label'     => __('Bordure cards durée', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card, {{WRAPPER}} .bt-quote-timeslot__btn' => 'border-color: {{VALUE}}'],
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

        // ── 📋 Step 4 — Champs coordonnées ─────────────────────────────
        $this->start_controls_section('style_quote_fields', [
            'label'     => __('📋 Devis — Champs', 'blacktenderscore'),
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

        // ── 📋 Boutons (Suivant + Envoi) ────────────────────────────────
        $this->start_controls_section('style_quote_buttons', [
            'label'     => __('📋 Devis — Boutons', 'blacktenderscore'),
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

        // ── 📋 Messages résultat ────────────────────────────────────────
        $this->start_controls_section('style_quote_messages', [
            'label'     => __('📋 Devis — Messages', 'blacktenderscore'),
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
    }
}
