<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

require_once __DIR__ . '/../traits/trait-bt-pricing-shared.php';

/**
 * Widget Elementor — Formulaire de devis multi-étapes standalone.
 *
 * Étapes configurables :
 *   1. Destination (excursion) — auto-détecté ou liste complète
 *   2. Bateau — auto-détecté, lié à l'excursion ou AJAX
 *   3. Dates — durée + datepicker + timeslot
 *   4. Coordonnées — nom, email, téléphone
 *   5. Envoi — récapitulatif + soumission AJAX
 *
 * Réutilise bt-boat-pricing-quote.js pour la logique front-end.
 */
require_once __DIR__ . '/../traits/trait-bt-quote-style-controls.php';

class QuoteForm extends AbstractBtWidget {
    use BtSharedControls;
    use \BlackTenders\Elementor\Traits\BtPricingShared;
    use \BlackTenders\Elementor\Traits\BtQuoteStyleControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-quote-form',
            'title'    => 'BT — Devis',
            'icon'     => 'eicon-form-horizontal',
            'keywords' => ['devis', 'formulaire', 'quote', 'contact', 'bt'],
            'css'      => ['bt-quote-form'],
            'js'       => ['bt-elementor', 'bt-boat-pricing-quote'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Étape 1 : Destination (excursion)
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_exc', [
            'label' => __('Devis — Excursion', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('step_exc_enable', [
            'label'        => __('Activer l\'étape Excursion', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('destination_mode', [
            'label'       => __('Mode destination', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => [
                'auto' => __('Auto (détection depuis la page)', 'blacktenderscore'),
                'all'  => __('Toujours afficher la liste complète', 'blacktenderscore'),
            ],
            'default'     => 'auto',
            'description' => __('Auto : sur une page excursion, pré-sélectionne l\'excursion courante. All : montre toujours la liste de toutes les excursions.', 'blacktenderscore'),
            'condition'   => ['step_exc_enable' => 'yes'],
        ]);

        $this->add_control('step_exc_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Votre excursion', 'blacktenderscore'),
            'condition' => ['step_exc_enable' => 'yes'],
        ]);

        $this->add_control('exc_loop_tpl', [
            'label'       => __('Template Loop excursion', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => self::get_elementor_templates_options(),
            'default'     => '',
            'description' => __('Template Elementor utilisé pour le rendu de chaque card excursion.', 'blacktenderscore'),
            'condition'   => ['step_exc_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Étape 2 : Bateau
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_boat', [
            'label' => __('Devis — Bateau', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('step_boat_enable', [
            'label'        => __('Activer l\'étape Bateau', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_boat_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Choix du bateau', 'blacktenderscore'),
            'condition' => ['step_boat_enable' => 'yes'],
        ]);

        $this->add_control('boat_loop_tpl', [
            'label'       => __('Template Loop bateau', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => self::get_elementor_templates_options(),
            'default'     => '',
            'description' => __('Template Elementor utilisé pour le rendu de chaque card bateau.', 'blacktenderscore'),
            'condition'   => ['step_boat_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Étape 3 : Dates
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_dates', [
            'label' => __('Devis — Dates', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('step_dates_enable', [
            'label'        => __('Activer l\'étape Dates', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_dates_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Dates de location', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_half', [
            'label'     => __('Label « Demi-journée »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_full', [
            'label'     => __('Label « Journée entière »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Journée entière', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_multi', [
            'label'     => __('Label « Plusieurs jours »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Plusieurs jours', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_opt_custom', [
            'label'     => __('Label « Demande spécifique »', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Demande spécifique', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_label_date', [
            'label'     => __('Label date unique', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Date souhaitée', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_label_start', [
            'label'     => __('Label date début', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Date de début', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_label_end', [
            'label'     => __('Label date fin', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Date de fin', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->add_control('step_dates_custom_placeholder', [
            'label'     => __('Placeholder demande spécifique', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Décrivez vos disponibilités...', 'blacktenderscore'),
            'condition' => ['step_dates_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Étape 4 : Coordonnées
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_contact', [
            'label' => __('Devis — Coordonnées', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('step_contact_enable', [
            'label'        => __('Activer l\'étape Coordonnées', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('step_contact_title', [
            'label'     => __('Titre de l\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Vos coordonnées', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_name_mode', [
            'label'     => __('Champ nom', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'single' => __('Un seul champ (Nom complet)', 'blacktenderscore'),
                'split'  => __('Deux champs (Nom + Prénom)', 'blacktenderscore'),
            ],
            'default'   => 'split',
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_label_name', [
            'label'     => __('Label nom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Nom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_ph_name', [
            'label'     => __('Placeholder nom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Votre nom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_label_firstname', [
            'label'     => __('Label prénom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Prénom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes', 'step_contact_name_mode' => 'split'],
        ]);

        $this->add_control('step_contact_ph_firstname', [
            'label'     => __('Placeholder prénom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Votre prénom', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes', 'step_contact_name_mode' => 'split'],
        ]);

        $this->add_control('step_contact_label_email', [
            'label'     => __('Label e-mail', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('E-mail', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_ph_email', [
            'label'     => __('Placeholder e-mail', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'votre@email.com',
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_label_phone', [
            'label'     => __('Label téléphone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Téléphone', 'blacktenderscore'),
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->add_control('step_contact_ph_phone', [
            'label'     => __('Placeholder téléphone', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '06 12 34 56 78',
            'condition' => ['step_contact_enable' => 'yes'],
        ]);

        $this->end_controls_section();

        // ─────────────────────────────────────────────────────────────────────
        //  CONTENU — Étape 5 : Envoi
        // ─────────────────────────────────────────────────────────────────────
        $this->start_controls_section('section_quote_submit', [
            'label' => __('Devis — Envoi', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('step_submit_title', [
            'label'   => __('Titre de l\'étape', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Confirmation', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_btn_label', [
            'label'   => __('Label bouton envoi', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Envoyer ma demande', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_email', [
            'label'       => __('E-mail destinataire', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => get_option('admin_email'),
            'description' => __('Adresse e-mail qui recevra les demandes de devis.', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_msg_success', [
            'label'   => __('Message de succès', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => __('Votre demande a bien été envoyée ! Nous vous recontacterons rapidement.', 'blacktenderscore'),
        ]);

        $this->add_control('step_submit_msg_error', [
            'label'   => __('Message d\'erreur', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => __('Une erreur est survenue. Veuillez réessayer.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ══ STYLE ═══════════════════════════════════════════════════════════════

        // ── Devis — Conteneur ────────────────────────────────────────────────
        $this->start_controls_section('style_quote_container', [
            'label' => __('Devis — Conteneur', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('quote_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            ['name' => 'quote_border', 'selector' => '{{WRAPPER}} .bt-quote']
        );

        $this->add_responsive_control('quote_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('quote_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            ['name' => 'quote_shadow', 'selector' => '{{WRAPPER}} .bt-quote']
        );

        $this->end_controls_section();

        // ── Devis — Étapes ───────────────────────────────────────────────────
        $this->start_controls_section('style_quote_steps', [
            'label' => __('Devis — Étapes', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('steps_gap', [
            'label'      => __('Espacement entre étapes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .bt-quote' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('step_inactive_opacity', [
            'label'     => __('Opacité étapes inactives', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['' => ['min' => 0.1, 'max' => 1, 'step' => 0.05]],
            'selectors' => ['{{WRAPPER}} .bt-quote-step:not(.bt-quote-step--active)' => 'opacity: {{SIZE}}'],
        ]);

        $this->add_control('step_transition', [
            'label'     => __('Durée transition (ms)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 300,
            'selectors' => ['{{WRAPPER}} .bt-quote-step' => 'transition: opacity {{VALUE}}ms ease, max-height {{VALUE}}ms ease'],
        ]);

        $this->add_control('step_number_heading', [
            'label'     => __('Numéro d\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('step_number_bg', [
            'label'     => __('Fond numéro', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__number' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('step_number_color', [
            'label'     => __('Couleur numéro', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__number' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('step_title_heading', [
            'label'     => __('Titre d\'étape', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            ['name' => 'step_title_typography', 'selector' => '{{WRAPPER}} .bt-quote-step__title']
        );

        $this->add_control('step_title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-step__title' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Devis — Bouton Suivant ───────────────────────────────────────────
        $this->start_controls_section('style_quote_next_btn', [
            'label' => __('Devis — Bouton Suivant', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            ['name' => 'next_btn_typography', 'selector' => '{{WRAPPER}} .bt-quote-step__next']
        );

        $this->start_controls_tabs('next_btn_state_tabs');

        $this->start_controls_tab('next_btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('next_btn_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'color: {{VALUE}}']]);
        $this->add_control('next_btn_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();

        $this->start_controls_tab('next_btn_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('next_btn_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next:hover' => 'color: {{VALUE}}']]);
        $this->add_control('next_btn_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-step__next:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('next_btn_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__next' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('next_btn_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-step__next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Devis — Champs ───────────────────────────────────────────────────
        $this->start_controls_section('style_quote_fields', [
            'label' => __('Devis — Champs', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('field_label_color', [
            'label'     => __('Couleur label', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__label, {{WRAPPER}} .bt-quote-datepicker__label' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            ['name' => 'field_label_typography', 'selector' => '{{WRAPPER}} .bt-quote-fields__label, {{WRAPPER}} .bt-quote-datepicker__label']
        );

        $this->add_control('field_bg', [
            'label'     => __('Fond champ', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            ['name' => 'field_border', 'selector' => '{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input']
        );

        $this->add_control('field_focus_border_color', [
            'label'     => __('Bordure focus', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-fields__input:focus, {{WRAPPER}} .bt-quote-datepicker__input:focus' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('field_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('field_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-fields__input, {{WRAPPER}} .bt-quote-datepicker__input' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Devis — Cards durée ──────────────────────────────────────────────
        $this->start_controls_section('style_quote_duration', [
            'label' => __('Devis — Cards durée', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('dur_card_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            ['name' => 'dur_card_border', 'selector' => '{{WRAPPER}} .bt-quote-duration-card']
        );

        $this->add_responsive_control('dur_card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-duration-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('dur_card_active_heading', [
            'label'     => __('État actif', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('dur_card_active_bg', [
            'label'     => __('Fond actif', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('dur_card_active_border_color', [
            'label'     => __('Bordure active', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"]' => 'border-color: {{VALUE}}'],
        ]);

        $this->add_control('dur_card_active_color', [
            'label'     => __('Texte actif', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-duration-card[aria-selected="true"] .bt-quote-duration-card__label' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Devis — Bouton envoi ─────────────────────────────────────────────
        $this->start_controls_section('style_quote_submit_btn', [
            'label' => __('Devis — Bouton envoi', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            ['name' => 'submit_btn_typography', 'selector' => '{{WRAPPER}} .bt-quote-submit']
        );

        $this->start_controls_tabs('submit_btn_state_tabs');

        $this->start_controls_tab('submit_btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('submit_btn_color', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'color: {{VALUE}}']]);
        $this->add_control('submit_btn_bg', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();

        $this->start_controls_tab('submit_btn_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('submit_btn_color_hover', ['label' => __('Couleur', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit:hover' => 'color: {{VALUE}}']]);
        $this->add_control('submit_btn_bg_hover', ['label' => __('Fond', 'blacktenderscore'), 'type' => Controls_Manager::COLOR, 'selectors' => ['{{WRAPPER}} .bt-quote-submit:hover' => 'background-color: {{VALUE}}']]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('submit_btn_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('submit_btn_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Devis — Messages résultat ────────────────────────────────────────
        $this->start_controls_section('style_quote_messages', [
            'label' => __('Devis — Messages résultat', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('msg_success_bg', [
            'label'     => __('Fond succès', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--success' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('msg_success_color', [
            'label'     => __('Couleur texte succès', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--success' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('msg_error_bg', [
            'label'     => __('Fond erreur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'separator' => 'before',
            'selectors' => ['{{WRAPPER}} .bt-quote-message--error' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('msg_error_color', [
            'label'     => __('Couleur texte erreur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-quote-message--error' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('msg_radius', [
            'label'      => __('Border radius messages', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('msg_padding', [
            'label'      => __('Padding messages', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-quote-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID() ?: 0;

        $this->render_quote_form($s, $post_id);
    }

    /**
     * Rendu du formulaire de devis multi-étapes.
     * Structure HTML identique à celle de BT — Tarifs pour compatibilité JS.
     */
    private function render_quote_form(array $s, int $post_id): void {
        $post_type     = $post_id ? get_post_type($post_id) : '';
        $is_excursion  = ($post_type === 'excursion');
        $is_boat       = ($post_type === 'boat');
        $dest_mode     = $s['destination_mode'] ?? 'auto';

        // Config JSON pour bt-boat-pricing-quote.js
        $config = [
            'duration_options' => [
                'half'   => $s['step_dates_opt_half']   ?: __('Demi-journée', 'blacktenderscore'),
                'full'   => $s['step_dates_opt_full']   ?: __('Journée entière', 'blacktenderscore'),
                'multi'  => $s['step_dates_opt_multi']  ?: __('Plusieurs jours', 'blacktenderscore'),
                'custom' => $s['step_dates_opt_custom'] ?: __('Demande spécifique', 'blacktenderscore'),
            ],
            'msg_success'   => $s['step_submit_msg_success'] ?: __('Votre demande a bien été envoyée !', 'blacktenderscore'),
            'msg_error'     => $s['step_submit_msg_error']   ?: __('Une erreur est survenue.', 'blacktenderscore'),
            'pricing_mode'  => 'standalone',
            'boat_loop_tpl' => (int) ($s['boat_loop_tpl'] ?? 0),
            'exc_loop_tpl'  => (int) ($s['exc_loop_tpl'] ?? 0),
            'recipient'     => $s['step_submit_email'] ?? '',
        ];

        echo '<div class="bt-quote" role="list"'
           . ' data-bt-quote'
           . ' data-ajax-url="' . esc_attr(admin_url('admin-ajax.php')) . '"'
           . ' data-nonce="' . esc_attr(wp_create_nonce('bt_quote_nonce')) . '"'
           . ' data-config="' . esc_attr(wp_json_encode($config)) . '">';

        $step_num = 0;

        // ── Step — Excursion ─────────────────────────────────────────────
        if (($s['step_exc_enable'] ?? 'yes') === 'yes') {
            $step_num++;
            $this->render_step_excursion($s, $post_id, $step_num, $is_excursion, $dest_mode);
        }

        // ── Step — Bateau ────────────────────────────────────────────────
        if (($s['step_boat_enable'] ?? 'yes') === 'yes') {
            $step_num++;
            $this->render_step_boat($s, $post_id, $step_num, $is_excursion, $is_boat);
        }

        // ── Step — Dates ─────────────────────────────────────────────────
        if (($s['step_dates_enable'] ?? 'yes') === 'yes') {
            $step_num++;
            $this->render_step_dates($s, $step_num);
        }

        // ── Step — Coordonnées ───────────────────────────────────────────
        if (($s['step_contact_enable'] ?? 'yes') === 'yes') {
            $step_num++;
            $this->render_step_contact($s, $step_num);
        }

        // ── Step — Envoi ─────────────────────────────────────────────────
        $step_num++;
        $this->render_step_submit($s, $step_num);

        echo '</div>'; // .bt-quote

        // ── Dialog popup bateau ──────────────────────────────────────────
        echo '<dialog class="bt-quote-popup" data-bt-quote-popup role="dialog" aria-modal="true">';
        echo '<button type="button" class="bt-quote-popup__close" aria-label="' . esc_attr__('Fermer', 'blacktenderscore') . '">&times;</button>';
        echo '<div class="bt-quote-popup__content" data-bt-quote-popup-content></div>';
        echo '</dialog>';
    }

    // ── Sous-méthodes de rendu des étapes ────────────────────────────────────

    /**
     * Étape excursion / destination.
     * Auto : pré-sélectionne si on est sur une page excursion.
     * All : affiche toujours la liste complète.
     */
    private function render_step_excursion(array $s, int $post_id, int $step_num, bool $is_excursion, string $dest_mode): void {
        $auto_selected = ($dest_mode === 'auto' && $is_excursion);

        $step_cls = 'bt-quote-step' . ($step_num === 1 ? ' bt-quote-step--active' : '');
        $aria_exp = $step_num === 1 ? 'true' : 'false';
        $aria_cur = $step_num === 1 ? ' aria-current="step"' : '';

        echo '<div class="' . esc_attr($step_cls) . '" role="listitem"'
           . $aria_cur . ' aria-expanded="' . $aria_exp . '" data-step="' . $step_num . '" data-step-type="excursion">';

        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_exc_title'] ?: __('Votre excursion', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';

        echo '<div class="bt-quote-step__content">';

        if ($auto_selected) {
            // Pré-sélection de l'excursion courante
            echo '<div class="bt-quote-exc-auto" data-exc-id="' . esc_attr($post_id) . '">';
            echo '<p class="bt-quote-exc-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
            echo '<input type="hidden" name="excursion_id" value="' . esc_attr($post_id) . '">';
            echo '</div>';

            // Choix : cette excursion OU sur mesure
            echo '<div class="bt-quote-exc-choice" data-bt-exc-choice>';
            echo '<button type="button" class="bt-quote-exc-choice__btn bt-quote-exc-choice__btn--selected" data-exc-choice="current" aria-selected="true">';
            echo esc_html__('Cette excursion', 'blacktenderscore');
            echo '</button>';
            echo '<button type="button" class="bt-quote-exc-choice__btn" data-exc-choice="custom" aria-selected="false">';
            echo esc_html__('Expérience sur mesure', 'blacktenderscore');
            echo '</button>';
            echo '</div>';

            // Zone texte sur mesure (cachée par défaut)
            echo '<div class="bt-quote-exc-custom" style="display:none">';
            echo '<textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="exc_custom_request" placeholder="' . esc_attr__('Décrivez votre projet...', 'blacktenderscore') . '" rows="3"></textarea>';
            echo '</div>';
        } else {
            // Liste de toutes les excursions + option sur mesure
            $this->render_excursion_cards_standalone($s);
        }

        echo '</div>'; // __content
        echo '<div class="bt-quote-step__actions">';
        echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '</div>'; // .bt-quote-step
    }

    /**
     * Étape bateau.
     * Auto-sélectionné si on est sur une page bateau.
     * Chargement statique si excursion sélectionnée, AJAX sinon.
     */
    private function render_step_boat(array $s, int $post_id, int $step_num, bool $is_excursion, bool $is_boat): void {
        $step_cls = 'bt-quote-step' . ($step_num === 1 ? ' bt-quote-step--active' : '');
        $aria_exp = $step_num === 1 ? 'true' : 'false';
        $aria_cur = $step_num === 1 ? ' aria-current="step"' : '';

        echo '<div class="' . esc_attr($step_cls) . '" role="listitem"'
           . $aria_cur . ' aria-expanded="' . $aria_exp . '" data-step="' . $step_num . '" data-step-type="boat">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_boat_title'] ?: __('Choix du bateau', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        if ($is_boat) {
            // Auto-select boat (on est sur une page bateau)
            echo '<div class="bt-quote-boat-auto" data-boat-id="' . esc_attr($post_id) . '">';
            echo '<p class="bt-quote-boat-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
            echo '<input type="hidden" name="boat_id" value="' . esc_attr($post_id) . '">';
            echo '</div>';
        } elseif ($is_excursion) {
            // Bateaux liés à l'excursion — chargement statique via le trait
            $this->render_linked_boat_cards_standalone($s, $post_id);
        } else {
            // Conteneur AJAX — chargé dynamiquement après sélection d'excursion
            echo '<div class="bt-quote-boat-cards" data-bt-quote-boats></div>';
        }

        echo '</div>'; // __content
        echo '<div class="bt-quote-step__actions">';
        echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '</div>'; // .bt-quote-step
    }

    /**
     * Étape dates : cards durée + datepickers + timeslot.
     */
    private function render_step_dates(array $s, int $step_num): void {
        $opt_half   = esc_html($s['step_dates_opt_half']   ?: __('Demi-journée', 'blacktenderscore'));
        $opt_full   = esc_html($s['step_dates_opt_full']   ?: __('Journée entière', 'blacktenderscore'));
        $opt_multi  = esc_html($s['step_dates_opt_multi']  ?: __('Plusieurs jours', 'blacktenderscore'));
        $opt_custom = esc_html($s['step_dates_opt_custom'] ?: __('Demande spécifique', 'blacktenderscore'));
        $lbl_date   = esc_html($s['step_dates_label_date']  ?: __('Date souhaitée', 'blacktenderscore'));
        $lbl_start  = esc_html($s['step_dates_label_start'] ?: __('Date de début', 'blacktenderscore'));
        $lbl_end    = esc_html($s['step_dates_label_end']   ?: __('Date de fin', 'blacktenderscore'));
        $ph_custom  = esc_attr($s['step_dates_custom_placeholder'] ?: __('Décrivez vos disponibilités...', 'blacktenderscore'));

        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="dates">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_dates_title'] ?: __('Dates de location', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        // Duration cards
        echo '<div class="bt-quote-duration-cards" data-bt-duration-select>';
        echo '<div class="bt-quote-duration-card" data-duration="half" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_half . '</span>';
        echo '</div>';
        echo '<div class="bt-quote-duration-card" data-duration="full" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_full . '</span>';
        echo '</div>';
        echo '<div class="bt-quote-duration-card" data-duration="multi" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_multi . '</span>';
        echo '</div>';
        echo '<div class="bt-quote-duration-card" data-duration="custom" tabindex="0" role="option" aria-selected="false">';
        echo '<span class="bt-quote-duration-card__label">' . $opt_custom . '</span>';
        echo '</div>';
        echo '</div>'; // .bt-quote-duration-cards

        // Single date picker (for half/full)
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--single" data-bt-datepicker data-range="0" style="display:none">';
        echo '<div class="bt-quote-datepicker__labels">';
        echo '<div class="bt-quote-datepicker__field">';
        echo '<label class="bt-quote-datepicker__label">' . $lbl_date . '</label>';
        echo '<input type="text" class="bt-quote-datepicker__input" name="date_start" readonly placeholder="jj/mm/aaaa">';
        echo '</div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker__calendar"></div>';

        // Matin / Après-midi (visible uniquement pour demi-journée)
        echo '<div class="bt-quote-timeslot" data-bt-timeslot style="display:none">';
        echo '<div class="bt-quote-timeslot__options">';
        echo '<button type="button" class="bt-quote-timeslot__btn" data-timeslot="matin" aria-selected="false">'
           . esc_html__('Matin', 'blacktenderscore') . '</button>';
        echo '<button type="button" class="bt-quote-timeslot__btn" data-timeslot="apres-midi" aria-selected="false">'
           . esc_html__('Après-midi', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '<input type="hidden" name="timeslot" value="">';
        echo '</div>';

        echo '</div>'; // .bt-quote-datepicker--single

        // Range date picker (for multi)
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--range" data-bt-datepicker data-range="1" style="display:none">';
        echo '<div class="bt-quote-datepicker__labels">';
        echo '<div class="bt-quote-datepicker__field">';
        echo '<label class="bt-quote-datepicker__label">' . $lbl_start . '</label>';
        echo '<input type="text" class="bt-quote-datepicker__input" name="date_start" readonly placeholder="jj/mm/aaaa">';
        echo '</div>';
        echo '<div class="bt-quote-datepicker__field">';
        echo '<label class="bt-quote-datepicker__label">' . $lbl_end . '</label>';
        echo '<input type="text" class="bt-quote-datepicker__input" name="date_end" readonly placeholder="jj/mm/aaaa">';
        echo '</div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker__calendar"></div>';
        echo '</div>'; // .bt-quote-datepicker--range

        // Custom textarea
        echo '<div class="bt-quote-custom-dates" style="display:none">';
        echo '<textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="date_custom" placeholder="' . $ph_custom . '" rows="3"></textarea>';
        echo '</div>';

        // Hidden input for duration type
        echo '<input type="hidden" name="duration_type" value="">';

        echo '</div>'; // __content
        echo '<div class="bt-quote-step__actions">';
        echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Étape coordonnées (nom, email, téléphone).
     */
    private function render_step_contact(array $s, int $step_num): void {
        $name_mode = $s['step_contact_name_mode'] ?: 'split';

        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="contact">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_contact_title'] ?: __('Vos coordonnées', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        echo '<div class="bt-quote-fields">';

        if ($name_mode === 'split') {
            echo '<div class="bt-quote-fields__row">';
            echo '<div class="bt-quote-fields__group">';
            echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_firstname'] ?: __('Prénom', 'blacktenderscore')) . '</label>';
            echo '<input type="text" class="bt-quote-fields__input" name="client_firstname" placeholder="' . esc_attr($s['step_contact_ph_firstname'] ?: __('Votre prénom', 'blacktenderscore')) . '" required>';
            echo '</div>';
            echo '<div class="bt-quote-fields__group">';
            echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_name'] ?: __('Nom', 'blacktenderscore')) . '</label>';
            echo '<input type="text" class="bt-quote-fields__input" name="client_name" placeholder="' . esc_attr($s['step_contact_ph_name'] ?: __('Votre nom', 'blacktenderscore')) . '" required>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="bt-quote-fields__group">';
            echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_name'] ?: __('Nom complet', 'blacktenderscore')) . '</label>';
            echo '<input type="text" class="bt-quote-fields__input" name="client_name" placeholder="' . esc_attr($s['step_contact_ph_name'] ?: __('Votre nom', 'blacktenderscore')) . '" required>';
            echo '</div>';
        }

        echo '<div class="bt-quote-fields__group">';
        echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_email'] ?: __('E-mail', 'blacktenderscore')) . '</label>';
        echo '<input type="email" class="bt-quote-fields__input" name="client_email" placeholder="' . esc_attr($s['step_contact_ph_email'] ?: 'votre@email.com') . '" required>';
        echo '</div>';

        echo '<div class="bt-quote-fields__group">';
        echo '<label class="bt-quote-fields__label">' . esc_html($s['step_contact_label_phone'] ?: __('Téléphone', 'blacktenderscore')) . '</label>';
        echo '<input type="tel" class="bt-quote-fields__input" name="client_phone" placeholder="' . esc_attr($s['step_contact_ph_phone'] ?: '06 12 34 56 78') . '">';
        echo '</div>';

        echo '</div>'; // .bt-quote-fields
        echo '</div>'; // __content

        echo '<div class="bt-quote-step__actions">';
        echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Étape de confirmation et envoi.
     */
    private function render_step_submit(array $s, int $step_num): void {
        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step_num . '" data-step-type="submit">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step_num . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html($s['step_submit_title'] ?: __('Confirmation', 'blacktenderscore')) . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';
        echo '<div class="bt-quote-recap" data-bt-quote-recap></div>';
        echo '<button type="button" class="bt-quote-submit" data-bt-quote-submit>';
        echo esc_html($s['step_submit_btn_label'] ?: __('Envoyer ma demande', 'blacktenderscore'));
        echo '</button>';
        echo '<div class="bt-quote-message" data-bt-quote-message></div>';
        echo '</div>'; // __content
        echo '</div>'; // step
    }

    // ── Card helpers (standalone versions using widget settings keys) ─────

    /**
     * Rendu des cards excursion pour le widget standalone.
     * Utilise exc_loop_tpl au lieu de step_exc_loop_tpl.
     */
    private function render_excursion_cards_standalone(array $s): void {
        $cache_key  = 'bt_exc_list_50';
        $excursions = get_transient($cache_key);
        if ($excursions === false) {
            $excursions = get_posts([
                'post_type'      => 'excursion',
                'posts_per_page' => 50,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);
            set_transient($cache_key, $excursions, 12 * HOUR_IN_SECONDS);
        }

        if (empty($excursions)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucune excursion disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        $tpl_id = (int) ($s['exc_loop_tpl'] ?? 0);

        echo '<div class="bt-quote-exc-cards">';
        foreach ($excursions as $exc) {
            echo '<div class="bt-quote-exc-card" data-exc-id="' . esc_attr($exc->ID) . '" tabindex="0" role="option" aria-selected="false">';
            echo $this->render_shared_loop_item($tpl_id, $exc);
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Rendu des cards bateau liés pour le widget standalone.
     * Utilise boat_loop_tpl au lieu de step_boat_loop_tpl.
     */
    private function render_linked_boat_cards_standalone(array $s, int $exc_id): void {
        $boat_ids = [];
        if (function_exists('get_field')) {
            $exp_boats = get_field('exp_boats', $exc_id);
            if (is_array($exp_boats)) {
                foreach ($exp_boats as $boat) {
                    $boat_ids[] = $boat instanceof \WP_Post ? $boat->ID : (int) $boat;
                }
            }
        }

        if (empty($boat_ids)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucun bateau disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        $tpl_id = (int) ($s['boat_loop_tpl'] ?? 0);

        echo '<div class="bt-quote-boat-cards">';
        foreach ($boat_ids as $bid) {
            $boat = get_post($bid);
            if (!$boat || $boat->post_status !== 'publish') continue;

            echo '<div class="bt-quote-boat-card" data-boat-id="' . esc_attr($bid) . '">';
            if ($tpl_id) {
                echo $this->render_shared_loop_item($tpl_id, $boat);
            } else {
                $this->render_default_boat_card($bid, $boat);
            }
            echo '</div>';
        }
        echo '</div>';
    }

    // ── Utilitaires ─────────────────────────────────────────────────────────

    /**
     * Retourne la liste des templates Elementor pour les contrôles SELECT.
     */
    private static function get_elementor_templates_options(): array {
        static $cache = null;
        if ($cache !== null) return $cache;

        $options   = ['' => __('— Aucun (fallback auto)', 'blacktenderscore')];
        $templates = get_posts([
            'post_type'      => 'elementor_library',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);
        foreach ($templates as $tpl) {
            $options[$tpl->ID] = $tpl->post_title;
        }
        $cache = $options;
        return $cache;
    }
}
