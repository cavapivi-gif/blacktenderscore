<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Fixed CTA.
 *
 * Bouton fixe hors flux avec avatar + dot de statut (en ligne / hors ligne)
 * basé sur les horaires du CPT store de studiojaecore.
 * Le statut est récupéré en AJAX pour compatibilité cache full-page.
 */
class FixedCta extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'btc-fixed-cta',
            'title'    => 'BT — Fixed CTA',
            'icon'     => 'eicon-call-to-action',
            'keywords' => ['cta', 'fixed', 'contact', 'appel', 'store', 'bt'],
            'css'      => ['bt-fixed-cta'],
            'js'       => ['bt-fixed-cta'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ─────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('avatar_image', [
            'label'   => __('Avatar', 'blacktenderscore'),
            'type'    => Controls_Manager::MEDIA,
            'default' => ['url' => ''],
        ]);

        $this->add_control('avatar_initials', [
            'label'       => __('Initiales (si pas d\'image)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'YT',
            'description' => __('Affichées si aucun avatar n\'est défini.', 'blacktenderscore'),
        ]);

        $this->add_control('author_name', [
            'label'   => __('Nom', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'Yoann Thénot',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('author_job', [
            'label'   => __('Titre / Poste', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'Fondateur',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('cta_text', [
            'label'   => __('Texte du bouton', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'Appel de découverte',
        ]);

        $this->add_control('cta_link', [
            'label'       => __('Lien du bouton', 'blacktenderscore'),
            'type'        => Controls_Manager::URL,
            'dynamic'     => ['active' => true],
            'placeholder' => 'https://calendly.com/...',
        ]);

        $this->add_control('separator_store', ['type' => Controls_Manager::DIVIDER]);

        $this->add_control('store_id', [
            'label'   => __('Store (horaires)', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => $this->get_store_options(),
            'default' => '',
            'description' => __('Sélectionnez un store pour activer le statut en ligne / hors ligne.', 'blacktenderscore'),
        ]);

        $this->add_control('text_online', [
            'label'     => __('Texte si en ligne', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Nous appeler',
            'condition' => ['store_id!' => ''],
        ]);

        $this->add_control('text_offline', [
            'label'     => __('Texte si hors ligne', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'Nous contacter',
            'condition' => ['store_id!' => ''],
        ]);

        $this->add_control('show_status_dot', [
            'label'        => __('Afficher le dot de statut', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_close_btn', [
            'label'        => __('Bouton fermer (X)', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
        ]);

        $this->end_controls_section();

        // ── Position & Layout ───────────────────────────────────────────────
        $this->start_controls_section('section_position', [
            'label' => __('Position & Layout', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('position_type', [
            'label'   => __('Type de positionnement', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'fixed'    => __('Fixe (viewport)', 'blacktenderscore'),
                'sticky'   => __('Collant (sticky)', 'blacktenderscore'),
                'absolute' => __('Absolu (dans le parent)', 'blacktenderscore'),
                'relative' => __('Dans le flux (relative)', 'blacktenderscore'),
            ],
            'default' => 'fixed',
        ]);

        $this->add_control('widget_position', [
            'label'     => __('Coin', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'bottom-left'  => ['title' => __('Bas gauche', 'blacktenderscore'),  'icon' => 'eicon-h-align-left'],
                'bottom-right' => ['title' => __('Bas droit', 'blacktenderscore'),   'icon' => 'eicon-h-align-right'],
                'top-left'     => ['title' => __('Haut gauche', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'top-right'    => ['title' => __('Haut droit', 'blacktenderscore'),  'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'bottom-right',
            'toggle'    => false,
            'condition' => ['position_type!' => 'relative'],
        ]);

        $this->add_control('avatar_side', [
            'label'   => __('Côté de l\'avatar', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'  => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'right' => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default' => 'left',
            'toggle'  => false,
        ]);

        $this->add_responsive_control('offset_x', [
            'label'      => __('Décalage horizontal', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 120]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'condition'  => ['position_type!' => 'relative'],
        ]);

        $this->add_responsive_control('offset_y', [
            'label'      => __('Décalage vertical', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 120]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'condition'  => ['position_type!' => 'relative'],
        ]);

        $this->add_control('z_index', [
            'label'     => __('Z-index', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'default'   => 9999,
            'min'       => 1,
            'max'       => 99999,
            'condition' => ['position_type!' => 'relative'],
        ]);

        $this->end_controls_section();

        // ── Style — Avatar ──────────────────────────────────────────────────
        $this->start_controls_section('style_avatar', [
            'label' => __('Style — Avatar', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('avatar_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 24, 'max' => 80]],
            'default'    => ['size' => 48, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-avatar-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('avatar_border_radius', [
            'label'      => __('Arrondi', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => ['%' => ['min' => 0, 'max' => 50], 'px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 50, 'unit' => '%'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-avatar-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('avatar_bg', [
            'label'     => __('Couleur de fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#e8e4ff',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-avatar-bg: {{VALUE}}'],
        ]);

        $this->add_control('avatar_initials_color', [
            'label'     => __('Couleur initiales', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#5a4fcf',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-avatar-initials-color: {{VALUE}}'],
        ]);

        $this->add_control('avatar_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'transparent',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-avatar-border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('avatar_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 6]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-avatar-border-width: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('sep_dot', ['type' => Controls_Manager::DIVIDER]);

        $this->add_control('dot_color_online', [
            'label'     => __('Dot — en ligne', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#22C55E',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-dot-color-online: {{VALUE}}'],
        ]);

        $this->add_control('dot_color_offline', [
            'label'     => __('Dot — hors ligne', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#94A3B8',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-dot-color-offline: {{VALUE}}'],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille du dot', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 6, 'max' => 20]],
            'default'    => ['size' => 11, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-dot-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Textes ──────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Textes', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('name_color', [
            'label'     => __('Couleur nom', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#111111',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-name-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('name_size', [
            'label'      => __('Taille nom', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 24]],
            'default'    => ['size' => 13, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-name-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('job_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#888888',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-job-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('job_size', [
            'label'      => __('Taille titre', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 20]],
            'default'    => ['size' => 11, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-job-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Bouton CTA ──────────────────────────────────────────────
        $this->start_controls_section('style_btn', [
            'label' => __('Style — Bouton CTA', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->start_controls_tabs('btn_state_tabs');

        $this->start_controls_tab('btn_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('btn_text_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#f2f2f2',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-color: {{VALUE}}'],
        ]);
        $this->add_control('btn_bg_color', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#1F1F22',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-bg: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('btn_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('btn_hover_text_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-hover-color: {{VALUE}}'],
        ]);
        $this->add_control('btn_hover_bg_color', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#3a3a42',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-hover-bg: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('btn_size', [
            'label'      => __('Taille police', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 24]],
            'default'    => ['size' => 13, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-size: {{SIZE}}{{UNIT}}'],
            'separator'  => 'before',
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'default'    => ['top' => '9', 'right' => '18', 'bottom' => '9', 'left' => '18', 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btn_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'default'    => ['size' => 100, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('btn_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'transparent',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('btn_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 4]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-border-width: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('sep_halo', ['type' => Controls_Manager::DIVIDER]);

        $this->add_control('enable_halo_effect', [
            'label'        => __('Effet halo rainbow', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_responsive_control('halo_thickness', [
            'label'       => __('Épaisseur bordure rainbow', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 1, 'max' => 8]],
            'default'     => ['size' => 2, 'unit' => 'px'],
            'selectors'   => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-halo-thickness: {{SIZE}}{{UNIT}}'],
            'condition'   => ['enable_halo_effect' => 'yes'],
        ]);

        $this->add_control('halo_speed', [
            'label'     => __('Vitesse rotation (secondes)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 20,
            'step'      => 0.5,
            'default'   => 3,
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-halo-speed: {{VALUE}}s'],
            'condition' => ['enable_halo_effect' => 'yes'],
        ]);

        $this->add_responsive_control('halo_opacity', [
            'label'      => __('Opacité', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'default'    => ['size' => 1],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-halo-opacity: {{SIZE}}'],
            'condition'  => ['enable_halo_effect' => 'yes'],
        ]);

        $this->add_responsive_control('halo_hover_opacity', [
            'label'      => __('Opacité au survol', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 0, 'max' => 1, 'step' => 0.05]],
            'default'    => ['size' => 1],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-halo-hover-opacity: {{SIZE}}'],
            'condition'  => ['enable_halo_effect' => 'yes'],
        ]);

        $this->add_responsive_control('halo_blur', [
            'label'       => __('Flou (effet glow)', 'blacktenderscore'),
            'description' => __('0 = bordure nette, > 0 = effet glow autour.', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px'],
            'range'       => ['px' => ['min' => 0, 'max' => 20]],
            'default'     => ['size' => 0, 'unit' => 'px'],
            'selectors'   => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-halo-blur: {{SIZE}}{{UNIT}}'],
            'condition'   => ['enable_halo_effect' => 'yes'],
        ]);

        $this->add_responsive_control('halo_hover_blur', [
            'label'      => __('Flou au survol', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-halo-hover-blur: {{SIZE}}{{UNIT}}'],
            'condition'  => ['enable_halo_effect' => 'yes'],
        ]);

        $this->add_control('sep_rey_scroll', ['type' => Controls_Manager::DIVIDER]);

        $this->add_control('btn_scroll_text_color', [
            'label'       => __('Rey — Couleur texte protégée', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'default'     => '',
            'description' => __('Force la couleur du texte du bouton via CSS !important (priorité haute). Protège contre les overrides du thème Rey (header scrollé, etc.). Laissez vide pour désactiver.', 'blacktenderscore'),
            'selectors'   => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-btn-rey-color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Conteneur ───────────────────────────────────────────────
        $this->start_controls_section('style_container', [
            'label' => __('Style — Conteneur', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('container_gap', [
            'label'      => __('Espace interne (avatar ↔ meta)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 10, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btn_gap', [
            'label'      => __('Espace avant le bouton CTA', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-cta__btn-wrap' => 'margin-inline-start: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('container_bg_color', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-container-bg: {{VALUE}}'],
        ]);

        $this->add_responsive_control('container_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 100]],
            'default'    => ['size' => 100, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-container-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('container_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px'],
            'default'    => ['top' => '7', 'right' => '14', 'bottom' => '7', 'left' => '8', 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-container-padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('container_border_color', [
            'label'     => __('Couleur bordure', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,0.15)',
            'selectors' => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-container-border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('container_border_width', [
            'label'      => __('Épaisseur bordure', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 4]],
            'default'    => ['size' => 1, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .btc-fixed-cta' => '--btc-container-border-width: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        $avatar   = $s['avatar_image']['url'] ?? '';
        $initials = esc_html($s['avatar_initials'] ?? 'YT');
        $cta_text = esc_html($s['cta_text'] ?? '');

        // Ne rien rendre si aucun contenu
        if ($avatar === '' && $cta_text === '') return;

        $position      = esc_attr($s['widget_position'] ?? 'bottom-right');
        $position_type = esc_attr($s['position_type'] ?? 'fixed');
        $avatar_side   = esc_attr($s['avatar_side'] ?? 'left');
        $store_id      = absint($s['store_id'] ?? 0);
        $show_dot    = ($s['show_status_dot'] ?? 'yes') === 'yes' ? 'true' : 'false';
        $halo        = ($s['enable_halo_effect'] ?? 'yes') === 'yes' ? 'true' : 'false';
        $closable    = ($s['show_close_btn'] ?? 'yes') === 'yes' ? 'true' : 'false';

        // Inline CSS vars pour offset/z-index (seuls styles inline autorisés)
        $ox = ($s['offset_x']['size'] ?? 20) . ($s['offset_x']['unit'] ?? 'px');
        $oy = ($s['offset_y']['size'] ?? 20) . ($s['offset_y']['unit'] ?? 'px');
        $z  = absint($s['z_index'] ?? 9999);

        $link     = $s['cta_link'] ?? [];
        $url      = esc_url($link['url'] ?? '#');
        $target   = !empty($link['is_external']) ? ' target="_blank"' : '';
        $nofollow = !empty($link['nofollow']) ? ' rel="noopener nofollow"' : (!empty($link['is_external']) ? ' rel="noopener"' : '');

        echo '<div class="btc-fixed-cta"'
           . ' data-pos="' . $position . '"'
           . ' data-pos-type="' . $position_type . '"'
           . ' data-avatar-side="' . $avatar_side . '"'
           . ' data-store-id="' . $store_id . '"'
           . ' data-text-online="' . esc_attr($s['text_online'] ?? 'Nous appeler') . '"'
           . ' data-text-offline="' . esc_attr($s['text_offline'] ?? 'Nous contacter') . '"'
           . ' data-show-dot="' . $show_dot . '"'
           . ' data-closable="' . $closable . '"'
           . ' data-scroll-color="' . esc_attr($s['btn_scroll_text_color'] ?? '') . '"'
           . ' style="--btc-x:' . esc_attr($ox) . ';--btc-y:' . esc_attr($oy) . ';--btc-z:' . $z . '"'
           . '>';

        // Avatar — le wrap positionne le dot, l'inner clippe l'image
        echo '<div class="btc-cta__avatar-wrap">';
        echo '<div class="btc-cta__avatar-inner">';
        if ($avatar !== '') {
            echo '<img class="btc-cta__avatar-img" src="' . esc_url($avatar) . '" alt="' . esc_attr($s['author_name'] ?? '') . '" loading="lazy" decoding="async">';
        } else {
            echo '<div class="btc-cta__avatar-placeholder">' . $initials . '</div>';
        }
        echo '</div>';
        echo '<span class="btc-cta__dot" data-status="offline" data-hidden="' . ($show_dot === 'false' ? 'true' : 'false') . '"></span>';
        echo '</div>';

        // Meta
        $name = esc_html($s['author_name'] ?? '');
        $job  = esc_html($s['author_job'] ?? '');
        if ($name !== '' || $job !== '') {
            echo '<div class="btc-cta__meta">';
            if ($name !== '') echo '<span class="btc-cta__name">' . $name . '</span>';
            if ($job !== '')  echo '<span class="btc-cta__job">' . $job . '</span>';
            echo '</div>';
        }

        // CTA Button
        if ($cta_text !== '') {
            echo '<div class="btc-cta__btn-wrap" data-halo="' . $halo . '">';
            echo '<div class="btc-cta__halo"></div>';
            echo '<a class="btc-cta__btn" href="' . $url . '"' . $target . $nofollow . '>' . $cta_text . '</a>';
            echo '</div>';
        }

        // Close button
        if ($closable === 'true') {
            echo '<button class="btc-cta__close" aria-label="' . esc_attr__('Fermer', 'blacktenderscore') . '" type="button">&times;</button>';
        }

        echo '</div>';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Génère les options SELECT pour les stores du CPT studiojaecore.
     */
    private function get_store_options(): array {
        $options = ['' => __('— Aucun (pas de statut)', 'blacktenderscore')];

        if (!post_type_exists('store')) return $options;

        $stores = get_posts([
            'post_type'      => 'store',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        foreach ($stores as $store) {
            $options[$store->ID] = esc_html($store->post_title);
        }

        return $options;
    }
}
