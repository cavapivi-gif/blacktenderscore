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

        // ── Étapes — Conteneur ─────────────────────────────────────────
        if (!in_array('style_quote_steps', $skip, true)):
        $this->start_controls_section('style_quote_steps', [
            'label'     => __('Étapes — Container', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        // ── Layout ──
        $this->add_responsive_control('qt_step_gap', [
            'label'      => __('Espacement étapes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_step_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_step_padding', [
            'label'      => __('Padding contenu', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_step_header_padding', [
            'label'      => __('Padding header', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_step_actions_padding', [
            'label'      => __('Padding actions', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__actions' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'qt_step_border',
            'selector' => '{{WRAPPER}} .bt-quote-step',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_step_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-step',
        ]);

        // ── Tabs : Normal / Active / Inactive / Done ──
        $this->add_control('qt_step_states_heading', [
            'label'     => __('États', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->start_controls_tabs('tabs_step_states');

        // Tab Normal
        $this->start_controls_tab('tab_step_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_step_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_step_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'border-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        // Tab Active
        $this->start_controls_tab('tab_step_active', ['label' => __('Active', 'blacktenderscore')]);
        $this->add_control('qt_step_active_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--active' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_step_active_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--active' => 'border-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_step_active_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-step--active',
        ]);
        $this->end_controls_tab();

        // Tab Inactive (future/collapsed)
        $this->start_controls_tab('tab_step_inactive', ['label' => __('Future', 'blacktenderscore')]);
        $this->add_control('qt_step_inactive_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--collapsed:not(.bt-quote-step--done)' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_step_inactive_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--collapsed:not(.bt-quote-step--done)' => 'border-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_step_inactive_opacity', [
            'label'      => __('Opacité', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [''],
            'range'      => ['' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'default'    => ['size' => 0.6],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step--collapsed:not(.bt-quote-step--active):not(.bt-quote-step--done)' => 'opacity: {{SIZE}}'],
        ]);
        $this->end_controls_tab();

        // Tab Done
        $this->start_controls_tab('tab_step_done', ['label' => __('Terminée', 'blacktenderscore')]);
        $this->add_control('qt_step_done_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--done:not(.bt-quote-step--active)' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_step_done_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--done:not(.bt-quote-step--active)' => 'border-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_step_done_opacity', [
            'label'      => __('Opacité', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [''],
            'range'      => ['' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'default'    => ['size' => 1],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step--done:not(.bt-quote-step--active)' => 'opacity: {{SIZE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Numéro d'étape ──
        $this->add_control('qt_step_number_heading', [
            'label'     => __('Numéro d\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->start_controls_tabs('tabs_step_number');

        $this->start_controls_tab('tab_step_number_normal', ['label' => __('Normal', 'blacktenderscore')]);
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
        $this->end_controls_tab();

        $this->start_controls_tab('tab_step_number_active', ['label' => __('Actif/Done', 'blacktenderscore')]);
        $this->add_control('qt_step_number_active_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-step--active .bt-quote-step__number' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-step--done .bt-quote-step__number'   => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_step_number_active_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-step--active .bt-quote-step__number' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-step--done .bt-quote-step__number'   => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_step_check_color', [
            'label'       => __('Couleur coche', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'selectors'   => ['{{WRAPPER}} .bt-quote-step__check' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Titre d'étape ──
        $this->add_control('qt_step_title_heading', [
            'label'     => __('Titre d\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_step_title_typo',
            'selector' => '{{WRAPPER}} .bt-quote-step__title',
        ]);

        $this->start_controls_tabs('tabs_step_title');

        $this->start_controls_tab('tab_step_title_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_step_title_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__title' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_step_title_collapsed', ['label' => __('Collapsed', 'blacktenderscore')]);
        $this->add_control('qt_step_title_collapsed_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step--collapsed .bt-quote-step__title' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // ── Résumé ──
        $this->add_control('qt_step_summary_heading', [
            'label'     => __('Résumé (sous header)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_step_summary_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__summary' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
        endif; // skip style_quote_steps

        // ══════════════════════════════════════════════════════════════════
        // EXCURSION COURANTE (bloc "Cette excursion")
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_exc_auto', [
            'label'     => __('Étape 1 — Excursion courante', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'qt_exc_auto_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-quote-exc-auto',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'qt_exc_auto_border',
            'selector' => '{{WRAPPER}} .bt-quote-exc-auto',
        ]);

        $this->add_responsive_control('qt_exc_auto_radius', [
            'label'      => __('Border Radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-auto' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_auto_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-auto' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_auto_margin', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-auto' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_auto_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-exc-auto__name',
        ]);

        $this->add_control('qt_exc_auto_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-auto__name' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // BOUTONS "Cette excursion / Sur mesure"
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_exc_choice', [
            'label'     => __('Étape 1 — Boutons choix', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_exc_choice_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-choice' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_choice_margin', [
            'label'      => __('Marge basse', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-choice' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_choice_typo',
            'selector' => '{{WRAPPER}} .bt-quote-exc-choice__btn',
        ]);

        $this->add_responsive_control('qt_exc_choice_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_choice_radius', [
            'label'      => __('Border Radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        // ── Tabs Normal / Hover / Sélectionné ────────────────────────────
        $this->start_controls_tabs('tabs_exc_choice_states');

        $this->start_controls_tab('tab_exc_choice_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_exc_choice_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_exc_choice_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn' => 'color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'qt_exc_choice_border',
            'selector' => '{{WRAPPER}} .bt-quote-exc-choice__btn',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_exc_choice_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('qt_exc_choice_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_exc_choice_hover_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('qt_exc_choice_hover_border', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'border-color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('qt_exc_choice_hover_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 10]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-choice__btn:hover' => 'border-width: {{SIZE}}{{UNIT}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_exc_choice_selected', ['label' => __('Sélectionné', 'blacktenderscore')]);
        $this->add_control('qt_exc_choice_sel_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_exc_choice_sel_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_exc_choice_sel_border', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'border-color: {{VALUE}}',
            ],
        ]);
        $this->add_responsive_control('qt_exc_choice_sel_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 10]],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-exc-choice__btn--selected' => 'border-width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]' => 'border-width: {{SIZE}}{{UNIT}}',
            ],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_exc_choice_sel_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-exc-choice__btn--selected, {{WRAPPER}} .bt-quote-exc-choice__btn[aria-selected="true"]',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // DROPDOWN — Style du sélecteur
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_dropdown', [
            'label'     => __('Étape 1/2 — Dropdown', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_dd_show_sub', [
            'label'        => __('Afficher sous-titre trigger', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'selectors'    => [
                '{{WRAPPER}} .bt-quote-dd__sub' => 'display: {{VALUE}}',
            ],
            'selectors_dictionary' => [
                'yes' => 'block',
                ''    => 'none',
            ],
        ]);

        $this->add_control('qt_dd_show_opt_sub', [
            'label'        => __('Afficher sous-titre options', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'selectors'    => [
                '{{WRAPPER}} .bt-quote-dd__opt-sub' => 'display: {{VALUE}}',
            ],
            'selectors_dictionary' => [
                'yes' => 'block',
                ''    => 'none',
            ],
        ]);

        $this->add_control('heading_dd_trigger', [
            'label'     => __('Trigger', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_dd_thumb_width', [
            'label'      => __('Largeur vignette', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 30, 'max' => 100]],
            'default'    => ['size' => 52, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-dd__thumb' => 'width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-dd__opt-thumb' => 'width: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('qt_dd_thumb_height', [
            'label'      => __('Hauteur vignette', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 20, 'max' => 80]],
            'default'    => ['size' => 38, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-dd__thumb' => 'height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-dd__opt-thumb' => 'height: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('qt_dd_thumb_radius', [
            'label'      => __('Radius vignette', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-dd__thumb' => 'border-radius: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-dd__opt-thumb' => 'border-radius: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('qt_dd_trigger_padding', [
            'label'      => __('Padding trigger', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-dd__trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_dd_trigger_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-dd__trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'qt_dd_trigger_border',
            'selector' => '{{WRAPPER}} .bt-quote-dd__trigger',
        ]);

        $this->add_control('qt_dd_trigger_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-dd__trigger' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_dd_name_typo',
            'label'    => __('Typo nom', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-dd__name',
        ]);

        $this->add_control('qt_dd_name_color', [
            'label'     => __('Couleur nom', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-dd__name' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dd_sub_color', [
            'label'     => __('Couleur sous-titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-dd__sub' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-dd__opt-sub' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('heading_dd_menu', [
            'label'     => __('Menu déroulant', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_dd_menu_radius', [
            'label'      => __('Border radius menu', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-dd__menu' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('qt_dd_menu_bg', [
            'label'     => __('Fond menu', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-dd__menu' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dd_opt_hover_bg', [
            'label'     => __('Fond option survol', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-dd__opt:hover' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dd_opt_sel_bg', [
            'label'     => __('Fond option sélectionnée', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-dd__opt--sel' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dd_check_color', [
            'label'     => __('Couleur checkmark', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-dd__check' => 'stroke: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_dd_menu_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-dd__menu',
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // CARDS EXCURSION — Layout & Grille
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_exc_grid', [
            'label'     => __('Étape 1 — Grille excursions', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_exc_cards_columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
            'default'        => '1',
            'tablet_default' => '1',
            'mobile_default' => '1',
            'selectors'      => ['{{WRAPPER}} .bt-quote-exc-cards' => 'display: grid; grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_responsive_control('qt_exc_cards_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-cards' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_card_direction', [
            'label'   => __('Position image', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'row'    => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'column' => ['title' => __('Haut', 'blacktenderscore'),   'icon' => 'eicon-v-align-top'],
            ],
            'default'        => 'row',
            'tablet_default' => 'row',
            'mobile_default' => 'row',
            'selectors'      => ['{{WRAPPER}} .bt-quote-exc-card' => 'flex-direction: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_exc_card_align', [
            'label'   => __('Alignement vertical', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Haut', 'blacktenderscore'),   'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'flex-end'   => ['title' => __('Bas', 'blacktenderscore'),    'icon' => 'eicon-v-align-bottom'],
            ],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card' => 'align-items: {{VALUE}}'],
            'condition'  => ['qt_exc_card_direction' => 'row'],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // CARDS EXCURSION — Conteneur
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_exc', [
            'label'     => __('Étape 1 — Card excursion', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_exc_card_padding', [
            'label'      => __('Padding card', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_body_padding', [
            'label'      => __('Padding body (zone texte)', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_body_gap', [
            'label'      => __('Espacement body', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card__body' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_card_radius', [
            'label'      => __('Border Radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_exc_card_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card',
        ]);

        // ── Tabs Normal / Hover / Sélectionné ────────────────────────────
        $this->start_controls_tabs('tabs_exc_card_states');

        // Tab Normal
        $this->start_controls_tab('tab_exc_card_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'qt_exc_card_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-quote-exc-card',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'qt_exc_card_border',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card',
        ]);
        $this->end_controls_tab();

        // Tab Hover
        $this->start_controls_tab('tab_exc_card_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('qt_exc_card_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_exc_card_hover_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card:hover' => 'border-color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('qt_exc_card_hover_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 10]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card:hover' => 'border-width: {{SIZE}}{{UNIT}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_exc_card_hover_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card:hover',
        ]);
        $this->end_controls_tab();

        // Tab Sélectionné
        $this->start_controls_tab('tab_exc_card_selected', ['label' => __('Sélectionné', 'blacktenderscore')]);
        $this->add_control('qt_exc_card_selected_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card--selected' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]' => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_exc_card_selected_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-exc-card--selected' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]' => 'border-color: {{VALUE}}',
            ],
        ]);
        $this->add_responsive_control('qt_exc_card_selected_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 10]],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-exc-card--selected' => 'border-width: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]' => 'border-width: {{SIZE}}{{UNIT}}',
            ],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_exc_card_selected_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card--selected, {{WRAPPER}} .bt-quote-exc-card[aria-selected="true"]',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // CARDS EXCURSION — Contenu
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_exc_content', [
            'label'     => __('Étape 1 — Card excursion contenu', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        // ── Titre ─────────────────────────────────────────────────────────
        $this->add_control('qt_exc_title_heading', [
            'label' => __('Titre', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_title_typo',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card__title',
        ]);

        $this->add_control('qt_exc_title_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__title' => 'color: {{VALUE}}'],
        ]);

        // ── Badge skipper ─────────────────────────────────────────────────
        $this->add_control('qt_exc_skipper_heading', [
            'label'     => __('Badge skipper', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_exc_skipper_typo',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card__skipper',
        ]);

        $this->add_control('qt_exc_skipper_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__skipper' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_skipper_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__skipper' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_exc_skipper_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card__skipper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_skipper_radius', [
            'label'      => __('Border Radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card__skipper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // CARDS EXCURSION — Image
        // ══════════════════════════════════════════════════════════════════

        $this->start_controls_section('style_quote_exc_img', [
            'label'     => __('Étape 1 — Card excursion image', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_exc_img_width', [
            'label'       => __('Largeur (mode horizontal)', 'blacktenderscore'),
            'description' => __('Utilisé quand l\'image est à gauche', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', '%'],
            'range'       => [
                'px' => ['min' => 60, 'max' => 400],
                '%'  => ['min' => 10, 'max' => 50],
            ],
            'selectors'   => ['{{WRAPPER}} .bt-quote-exc-card__img' => 'width: {{SIZE}}{{UNIT}}; min-width: {{SIZE}}{{UNIT}}; flex-shrink: 0'],
        ]);

        $this->add_responsive_control('qt_exc_img_height', [
            'label'       => __('Hauteur (mode vertical)', 'blacktenderscore'),
            'description' => __('Utilisé quand l\'image est en haut', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 80, 'max' => 300]],
            'selectors'   => ['{{WRAPPER}} .bt-quote-exc-card[style*="column"] .bt-quote-exc-card__img, {{WRAPPER}} .bt-quote-exc-card .bt-quote-exc-card__img' => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_exc_img_ratio', [
            'label'   => __('Ratio', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                ''       => __('Auto', 'blacktenderscore'),
                '1/1'    => '1:1',
                '4/3'    => '4:3',
                '16/9'   => '16:9',
                '3/2'    => '3:2',
            ],
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card__img' => 'aspect-ratio: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_exc_img_radius', [
            'label'      => __('Border Radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card__img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Css_Filter::get_type(), [
            'name'     => 'qt_exc_img_filter',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card__img img',
        ]);

        $this->end_controls_section();

        // ── 7. Card — Sur mesure (custom trip) ──────────────────────────────
        $this->start_controls_section('style_quote_exc_custom', [
            'label'     => __('Étape 1 — Card sur mesure', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_exc_custom_heading_normal', [
            'label' => __('État normal', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'qt_exc_custom_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-quote-exc-card--custom',
        ]);

        $this->add_control('qt_exc_custom_border_style', [
            'label'   => __('Style bordure', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'none'   => __('Aucune', 'blacktenderscore'),
                'solid'  => __('Solide', 'blacktenderscore'),
                'dashed' => __('Tirets', 'blacktenderscore'),
                'dotted' => __('Pointillés', 'blacktenderscore'),
            ],
            'default'   => 'dashed',
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom' => 'border-style: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 10]],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-exc-card--custom' => 'border-width: {{SIZE}}{{UNIT}}'],
            'condition'  => ['qt_exc_custom_border_style!' => 'none'],
        ]);

        $this->add_control('qt_exc_custom_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom' => 'border-color: {{VALUE}}'],
            'condition' => ['qt_exc_custom_border_style!' => 'none'],
        ]);

        $this->add_control('qt_exc_custom_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom .bt-quote-exc-card__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_desc_color', [
            'label'     => __('Couleur description', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom .bt-quote-exc-card__desc' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_heading_hover', [
            'label'     => __('État survol', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'qt_exc_custom_hover_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-quote-exc-card--custom:hover, {{WRAPPER}} .bt-quote-exc-card--custom:focus',
        ]);

        $this->add_control('qt_exc_custom_hover_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom:hover, {{WRAPPER}} .bt-quote-exc-card--custom:focus' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_heading_selected', [
            'label'     => __('État sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Background::get_type(), [
            'name'     => 'qt_exc_custom_sel_bg',
            'types'    => ['classic', 'gradient'],
            'selector' => '{{WRAPPER}} .bt-quote-exc-card--custom[aria-selected="true"]',
        ]);

        $this->add_control('qt_exc_custom_sel_border_style', [
            'label'   => __('Style bordure', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                ''       => __('Défaut (solide)', 'blacktenderscore'),
                'solid'  => __('Solide', 'blacktenderscore'),
                'dashed' => __('Tirets', 'blacktenderscore'),
                'dotted' => __('Pointillés', 'blacktenderscore'),
            ],
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom[aria-selected="true"]' => 'border-style: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_sel_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom[aria-selected="true"]' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_sel_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom[aria-selected="true"] .bt-quote-exc-card__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_exc_custom_sel_desc_color', [
            'label'     => __('Couleur description', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-exc-card--custom[aria-selected="true"] .bt-quote-exc-card__desc' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_exc_custom_sel_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-exc-card--custom[aria-selected="true"]',
        ]);

        $this->end_controls_section();

        // ══════════════════════════════════════════════════════════════════
        // CARDS BATEAU — Organisé par composant
        // ══════════════════════════════════════════════════════════════════

        // ── 1. Grille & Layout ────────────────────────────────────────────
        $this->start_controls_section('style_quote_boat_grid', [
            'label'     => __('Étape 2 — Grille bateaux', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_boat_cards_cols', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
            'default'        => '1',
            'tablet_default' => '1',
            'mobile_default' => '1',
            'selectors'      => ['{{WRAPPER}} .bt-quote-boat-cards' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_responsive_control('qt_boat_cards_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-cards' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_direction', [
            'label'   => __('Direction card', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'row'    => ['title' => __('Horizontal', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'column' => ['title' => __('Vertical', 'blacktenderscore'),   'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'row',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'flex-direction: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_valign', [
            'label'   => __('Alignement vertical', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Haut', 'blacktenderscore'),   'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'stretch'    => ['title' => __('Étirer', 'blacktenderscore'), 'icon' => 'eicon-v-align-stretch'],
            ],
            'default'   => 'flex-start',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'align-items: {{VALUE}}'],
            'condition' => ['qt_boat_card_direction' => 'row'],
        ]);

        $this->end_controls_section();

        // ── 2. Card — Conteneur ───────────────────────────────────────────
        $this->start_controls_section('style_quote_boat_card', [
            'label'     => __('Étape 2 — Card bateau', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_boat_card_min_h', [
            'label'      => __('Hauteur min', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 80, 'max' => 300]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'min-height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_padding', [
            'label'      => __('Padding card', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_body_padding', [
            'label'      => __('Padding body (zone texte)', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_body_gap', [
            'label'      => __('Espacement éléments body', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__body' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_boat_card_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card',
        ]);

        $this->start_controls_tabs('tabs_boat_card_states');

        $this->start_controls_tab('tab_boat_card_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_boat_card_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'           => 'qt_boat_card_border',
            'selector'       => '{{WRAPPER}} .bt-quote-boat-card',
            'fields_options' => [
                'border' => ['default' => 'solid'],
                'width'  => ['default' => ['top' => 1, 'right' => 1, 'bottom' => 1, 'left' => 1, 'unit' => 'px', 'isLinked' => true]],
                'color'  => ['default' => '#e5e5e5'],
            ],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_card_hover', ['label' => __('Hover', 'blacktenderscore')]);
        $this->add_control('qt_boat_card_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'qt_boat_card_hover_border',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card:hover',
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_boat_card_hover_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card:hover',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_boat_card_selected', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('qt_boat_card_selected_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card--selected' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'           => 'qt_boat_card_selected_border',
            'selector'       => '{{WRAPPER}} .bt-quote-boat-card--selected',
            'fields_options' => [
                'border' => ['default' => 'solid'],
                'width'  => ['default' => ['top' => 2, 'right' => 2, 'bottom' => 2, 'left' => 2, 'unit' => 'px', 'isLinked' => true]],
                'color'  => ['default' => '#0a0a0a'],
            ],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_boat_card_selected_shadow',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card--selected',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── 3. Card — Image ───────────────────────────────────────────────
        $this->start_controls_section('style_quote_boat_img', [
            'label'     => __('Étape 2 — Card bateau image', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_boat_img_width', [
            'label'      => __('Largeur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => ['%' => ['min' => 10, 'max' => 60], 'px' => ['min' => 50, 'max' => 400]],
            'default'    => ['size' => 30, 'unit' => '%'],
            'selectors'  => [
                // Applique uniquement en mode row (exclut column)
                '{{WRAPPER}} .bt-quote-boat-card:not([style*="column"]) .bt-quote-boat-card__bg' => 'flex: 0 0 {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}',
            ],
            'condition'  => ['qt_boat_card_direction' => 'row'],
        ]);

        $this->add_responsive_control('qt_boat_card_img_ratio', [
            'label'   => __('Ratio', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'auto' => __('Auto', 'blacktenderscore'),
                '16/9' => '16:9',
                '3/2'  => '3:2',
                '4/3'  => '4:3',
                '1'    => '1:1',
            ],
            'default'   => 'auto',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'aspect-ratio: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_img_max_h', [
            'label'      => __('Hauteur max', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 50, 'max' => 400]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'max-height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('qt_boat_card_img_fit', [
            'label'   => __('Ajustement', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'cover'   => __('Cover', 'blacktenderscore'),
                'contain' => __('Contain', 'blacktenderscore'),
            ],
            'default'   => 'cover',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'background-size: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_card_img_position', [
            'label'   => __('Position', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'center center' => __('Centre', 'blacktenderscore'),
                'center top'    => __('Haut', 'blacktenderscore'),
                'center bottom' => __('Bas', 'blacktenderscore'),
            ],
            'default'   => 'center center',
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'background-position: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_card_img_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__bg' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── 4. Card — Textes (titre, sous-titre, prix) ────────────────────
        $this->start_controls_section('style_quote_boat_text', [
            'label'     => __('Étape 2 — Card bateau textes', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        // Titre
        $this->add_control('qt_boat_title_heading', [
            'label' => __('Titre', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
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

        $this->add_responsive_control('qt_boat_title_spacing', [
            'label'      => __('Espacement bas', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__title' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // Badge "(Actuel)"
        $this->add_control('qt_boat_badge_heading', [
            'label'     => __('Badge (Actuel)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_boat_badge_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__badge' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_badge_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__badge' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_badge_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__badge' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_badge_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__badge' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        // Sous-titre
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

        $this->add_responsive_control('qt_boat_subtitle_spacing', [
            'label'      => __('Espacement bas', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__subtitle' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // Prix / pers
        $this->add_control('qt_boat_pp_heading', [
            'label'     => __('Prix / pers', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_pp_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__pp',
        ]);

        $this->add_control('qt_boat_pp_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__pp' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_per_color', [
            'label'     => __('Couleur "/ pers."', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__per' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── 5. Card — Pills forfaits ──────────────────────────────────────
        $this->start_controls_section('style_quote_boat_forfaits', [
            'label'     => __('Étape 2 — Card bateau forfaits', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_boat_forfaits_gap', [
            'label'      => __('Espacement pills', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfaits' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_forfaits_spacing', [
            'label'      => __('Espacement bas container', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfaits' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_forfait_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__forfait',
        ]);

        $this->add_responsive_control('qt_boat_forfait_padding', [
            'label'      => __('Padding pill', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_forfait_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__forfait' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        // Couleurs (affichage seul, pas de hover/active)
        $this->add_control('qt_boat_forfait_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait, {{WRAPPER}} .bt-quote-boat-card__forfait-tab' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait, {{WRAPPER}} .bt-quote-boat-card__forfait-tab' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('qt_boat_forfait_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__forfait, {{WRAPPER}} .bt-quote-boat-card__forfait-tab' => 'border-color: {{VALUE}}'],
        ]);

        // Label durée
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

        // Prix
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

        $this->end_controls_section();

        // ── 6. Card — Meta (pax, jockey) ──────────────────────────────────
        $this->start_controls_section('style_quote_boat_meta', [
            'label'     => __('Étape 2 — Card bateau meta', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_responsive_control('qt_boat_meta_gap', [
            'label'      => __('Espacement items', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__meta' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_meta_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__meta-item',
        ]);

        $this->add_control('qt_boat_meta_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__meta-item' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_meta_icon_heading', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('qt_boat_meta_icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 32]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('qt_boat_meta_icon_color', [
            'label'     => __('Couleur icône', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__icon' => 'stroke: {{VALUE}}'],
        ]);

        // Badge jockey (affiché uniquement si has_jockey = true)
        $this->add_control('qt_boat_jockey_heading', [
            'label'     => __('Badge siège jockey', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_jockey_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__meta-item--yes',
        ]);

        $this->add_control('qt_boat_jockey_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__meta-item--yes' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_boat_jockey_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__meta-item--yes' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_boat_jockey_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__meta-item--yes' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_jockey_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__meta-item--yes' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── 7. Card — Bouton "Plus d'infos" ───────────────────────────────
        $this->start_controls_section('style_quote_boat_more', [
            'label'     => __('Étape 2 — Card bateau bouton', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'qt_boat_more_typo',
            'selector' => '{{WRAPPER}} .bt-quote-boat-card__more',
        ]);

        $this->add_responsive_control('qt_boat_more_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_boat_more_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'selectors'  => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->start_controls_tabs('tabs_boat_more_states');

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
        $this->add_control('qt_boat_more_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more' => 'border-color: {{VALUE}}'],
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
        $this->add_control('qt_boat_more_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-boat-card__more:hover' => 'border-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── Calendrier (date souhaitée) ──────────────────────────────
        $this->start_controls_section('style_quote_calendar', [
            'label'     => __('Étape 3 — Calendrier', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_dp_cell_color', [
            'label'     => __('Texte jours', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__cell' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dp_cell_hover_bg', [
            'label'     => __('Fond hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__cell:hover:not(.bt-dp__cell--disabled):not(.bt-dp__cell--empty)' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dp_accent', [
            'label'       => __('Couleur sélection', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('Date sélectionnée + range', 'blacktenderscore'),
            'selectors'   => [
                '{{WRAPPER}} .bt-dp__cell--start, {{WRAPPER}} .bt-dp__cell--end' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-dp__cell--range' => 'background-color: color-mix(in srgb, {{VALUE}} 15%, transparent)',
            ],
        ]);

        $this->add_control('qt_dp_selected_color', [
            'label'     => __('Texte sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__cell--start, {{WRAPPER}} .bt-dp__cell--end' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('qt_dp_month_color', [
            'label'     => __('Couleur mois / année', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-dp__month-label' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Boutons durée / timeslot (exc-choice a sa propre section) ────────
        $this->start_controls_section('style_quote_selected', [
            'label'     => __('Étape 3 — Durées & créneaux', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->start_controls_tabs('tabs_sel_states');

        // Tab Normal
        $this->start_controls_tab('tab_sel_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('qt_btn_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn' => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_btn_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn' => 'border-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_btn_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn' => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        // Tab Hover
        $this->start_controls_tab('tab_sel_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('qt_hover_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_hover_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'border-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_hover_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card:hover' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn:hover' => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        // Tab Sélectionné
        $this->start_controls_tab('tab_sel_selected', ['label' => __('Sélectionné', 'blacktenderscore')]);
        $this->add_control('qt_sel_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'             => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'             => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_sel_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'             => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'             => 'border-color: {{VALUE}}',
            ],
        ]);
        $this->add_control('qt_sel_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-duration-card--selected'             => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn--selected'             => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'qt_sel_shadow',
            'label'    => __('Box-shadow', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"], {{WRAPPER}} .bt-quote-timeslot__btn[aria-selected="true"]',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        // Contrôles communs hors tabs
        $this->add_responsive_control('qt_btn_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'separator'  => 'before',
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-duration-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('qt_btn_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-quote-duration-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
                '{{WRAPPER}} .bt-quote-timeslot__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'qt_btn_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-duration-card__label, {{WRAPPER}} .bt-quote-timeslot__btn',
        ]);

        $this->end_controls_section();

        // ── Résumé de date ─────────────────────────────
        if (!in_array('style_quote_date_summary', $skip, true)):
        $this->start_controls_section('style_quote_date_summary', [
            'label'     => __('Résumé de date', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_summary_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-date-summary' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_summary_border', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-date-summary' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('qt_summary_meta_color', [
            'label'     => __('Couleur metas (grisé)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#525252',
            'selectors' => [
                '{{WRAPPER}} .bt-quote-date-summary__meta' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-quote-date-summary__sep'  => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_summary_value_color', [
            'label'     => __('Couleur valeurs (important)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#0a0a0a',
            'selectors' => ['{{WRAPPER}} .bt-quote-date-summary__value' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('qt_summary_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-date-summary' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('qt_summary_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-date-summary' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'qt_summary_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-quote-date-summary',
        ]);

        $this->end_controls_section();
        endif;

        // ── Step 4 — Champs coordonnées ─────────────────────────────
        if (!in_array('style_quote_fields', $skip, true)):
        $this->start_controls_section('style_quote_fields', [
            'label'     => __('Étape 4 — Champs', 'blacktenderscore'),
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
            'label'     => __('Étape 4 — Boutons', 'blacktenderscore'),
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

        // ── Options / Chips (substep) ─────────────────────────────────
        $this->start_controls_section('style_quote_chips', [
            'label'     => __('Étape 5 — Options (chips)', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => $condition,
        ]);

        $this->add_control('qt_chip_heading_default', [
            'label' => __('Chip — Normal', 'blacktenderscore'),
            'type'  => Controls_Manager::HEADING,
        ]);

        $this->add_control('qt_chip_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_chip_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
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

        $this->add_control('qt_chip_selected_border_color', [
            'label'     => __('Bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip--selected' => 'border-color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_chip_selected_color', [
            'label'     => __('Texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip--selected' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('qt_chip_heading_unselected', [
            'label'     => __('Chip — Non sélectionné', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('qt_chip_unselected_opacity', [
            'label'     => __('Opacité', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'default'   => ['size' => 0.55],
            'selectors' => [
                '{{WRAPPER}} .bt-quote-substep__chip:not(.bt-quote-substep__chip--selected)' => 'opacity: {{SIZE}}',
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
