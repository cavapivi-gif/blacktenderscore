<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

/**
 * Sections de contrôles Elementor réutilisables pour les widgets BlackTenders.
 * Inspiré de SharedControls (studiojaereview — cavapivi-gif).
 *
 * Chaque méthode enregistre UNE SECTION complète (ou un groupe de controls
 * à insérer dans une section déjà ouverte).
 *
 * Appel depuis register_controls() :
 *
 *   — Inline (dans une section déjà ouverte) :
 *   $this->register_section_title_controls(['title' => 'Mon titre']);
 *   $this->register_cta_button_controls('cta', 'Bouton CTA');
 *
 *   — Ouvrent leur propre section :
 *   $this->register_section_title_style('{{WRAPPER}} .bt-widget__title');
 *   $this->register_grid_layout_controls('{{WRAPPER}} .bt-widget__grid', ['columns' => 3, 'gap' => 24]);
 *   $this->register_box_style('card', 'Style — Cartes', '{{WRAPPER}} .bt-widget__card');
 *   $this->register_typography_section('title', 'Style — Titre', '{{WRAPPER}} .bt-widget__title', ['with_align' => true]);
 *   $this->register_tabs_nav_style('tab', 'Style — Onglets', '{{WRAPPER}} .bt-widget__tab', '{{WRAPPER}} .bt-widget__tab--active');
 *   $this->register_panel_style('panel', 'Style — Panneau', '{{WRAPPER}} .bt-widget__panel');
 *   $this->register_item_3state_style('item', 'Style — Items', '{{WRAPPER}} .bt-widget__item');
 */
trait BtSharedControls {

    // ══ Titre de section ══════════════════════════════════════════════════════

    /**
     * Ajoute les controls "Titre de section" + "Balise" dans la section courante.
     * À appeler ENTRE start_controls_section() et end_controls_section().
     *
     * Controls générés :
     *   section_title     (TEXT  — supporte les dynamic tags)
     *   section_title_tag (SELECT — h2/h3/h4/p/span)
     *
     * @param array $defaults  Valeurs par défaut optionnelles :
     *   'title' (string) — texte par défaut du titre (ex: 'Nos avis clients')
     *   'tag'   (string) — balise par défaut (ex: 'h2', défaut: 'h3')
     */
    protected function register_section_title_controls(array $defaults = []): void {
        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['title'] ?? '',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('section_title_tag', [
            'label'   => __('Balise', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'p'    => 'p',
                'span' => 'span',
            ],
            'default' => $defaults['tag'] ?? 'h3',
        ]);
    }

    /**
     * Enregistre une section Style complète pour le titre de section.
     * S'affiche uniquement si section_title n'est pas vide (condition Elementor).
     *
     * Controls générés (tous prefixés "heading_") :
     *   heading_typography, heading_color, heading_align, heading_spacing
     *
     * @param string $selector Sélecteur CSS — ex: '{{WRAPPER}} .bt-faq__section-title'
     */
    protected function register_section_title_style(string $selector): void {
        $this->start_controls_section('style_heading', [
            'label'     => __('Style — Titre section', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['section_title!' => ''],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'heading_typography',
            'selector' => $selector,
        ]);

        $this->add_control('heading_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('heading_align', [
            'label'     => __('Alignement', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => [$selector => 'text-align: {{VALUE}}'],
        ]);

        $this->add_responsive_control('heading_spacing', [
            'label'      => __('Espacement bas', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ══ Onglets (navigation) ══════════════════════════════════════════════════

    /**
     * Section de style pour une barre d'onglets — Normal et Actif.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography
     *   {prefix}_color / {prefix}_bg          (tab Normal)
     *   {prefix}_active_color / {prefix}_active_bg / {prefix}_active_border_color  (tab Actif)
     *   {prefix}_padding
     *   {prefix}_border
     *   {prefix}_gap  (si $tablist_sel fourni)
     *
     * @param string $prefix      Préfixe IDs contrôles — ex: 'tab'
     * @param string $label       Label section — ex: 'Style — Onglets'
     * @param string $tab_sel     Sélecteur onglet — ex: '{{WRAPPER}} .bt-pricing__tab'
     * @param string $active_sel  Sélecteur actif — ex: '{{WRAPPER}} .bt-pricing__tab--active'
     * @param string $tablist_sel Sélecteur liste (pour gap) — optionnel
     * @param array  $condition   Condition Elementor optionnelle sur la section
     */
    protected function register_tabs_nav_style(
        string $prefix,
        string $label,
        string $tab_sel,
        string $active_sel,
        string $tablist_sel = '',
        array  $condition   = []
    ): void {
        $section_args = [
            'label' => $label,
            'tab'   => Controls_Manager::TAB_STYLE,
        ];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => "{$prefix}_typography",
            'selector' => $tab_sel,
        ]);

        $this->start_controls_tabs("{$prefix}_color_tabs");

        $this->start_controls_tab("{$prefix}_tab_normal", ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$tab_sel => 'color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$tab_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab("{$prefix}_tab_active", ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control("{$prefix}_active_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_active_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control("{$prefix}_active_border_color", [
            'label'     => __('Bordure active', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'border-bottom-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding onglet', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$tab_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $tab_sel,
        ]);

        if ($tablist_sel) {
            $this->add_responsive_control("{$prefix}_gap", [
                'label'      => __('Espacement entre onglets', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'selectors'  => [$tablist_sel => 'gap: {{SIZE}}{{UNIT}}'],
            ]);
        }

        $this->end_controls_section();
    }

    // ══ Panneau de contenu ════════════════════════════════════════════════════

    /**
     * Section de style pour un panneau de contenu (corps d'onglet, réponse...).
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography, {prefix}_color, {prefix}_bg
     *   {prefix}_padding, {prefix}_border, {prefix}_border_radius, {prefix}_shadow
     *
     * @param string $prefix   Préfixe IDs contrôles — ex: 'panel'
     * @param string $label    Label section — ex: 'Style — Panneau'
     * @param string $selector Sélecteur CSS
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_panel_style(
        string $prefix,
        string $label,
        string $selector,
        array  $condition = []
    ): void {
        $section_args = [
            'label' => $label,
            'tab'   => Controls_Manager::TAB_STYLE,
        ];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => "{$prefix}_typography",
            'selector' => $selector,
        ]);

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);

        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $selector,
        ]);

        $this->add_responsive_control("{$prefix}_border_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $selector,
        ]);

        $this->end_controls_section();
    }

    // ══ Item 3 états (Normal / Survol / Actif) ════════════════════════════════

    /**
     * Section de style pour un élément à 3 états : Normal / Survol / Actif.
     * Utile pour les items de liste, cartes, accordéons, etc.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_style_tabs (conteneur tabs)
     *   {prefix}_bg / {prefix}_border             (Normal)
     *   {prefix}_bg_hover / {prefix}_border_hover (Survol)
     *   {prefix}_bg_active / {prefix}_border_active (Actif)
     *   {prefix}_border_radius, {prefix}_padding, {prefix}_shadow (hors tabs)
     *
     * ⚠ Les IDs de controls sont fixes et NE DOIVENT PAS changer une fois
     *   des templates Elementor enregistrés avec ce widget.
     *
     * @param string      $prefix      Préfixe — ex: 'item'
     * @param string      $label       Label section — ex: 'Style — Items'
     * @param string      $item_sel    Sélecteur item normal
     * @param string|null $hover_sel   Sélecteur survol (défaut: $item_sel + ':hover')
     * @param string|null $active_sel  Sélecteur actif  (défaut: $item_sel + '.{prefix}--active')
     * @param array       $condition   Condition Elementor optionnelle
     */
    protected function register_item_3state_style(
        string  $prefix,
        string  $label,
        string  $item_sel,
        ?string $hover_sel  = null,
        ?string $active_sel = null,
        array   $condition  = []
    ): void {
        $hover_sel  = $hover_sel  ?? "{$item_sel}:hover";
        $active_sel = $active_sel ?? "{$item_sel}.{$prefix}--active";

        $section_args = [
            'label' => $label,
            'tab'   => Controls_Manager::TAB_STYLE,
        ];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->start_controls_tabs("{$prefix}_style_tabs");

        // ── Normal
        $this->start_controls_tab("{$prefix}_tab_normal", ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$item_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $item_sel,
        ]);
        $this->end_controls_tab();

        // ── Survol
        $this->start_controls_tab("{$prefix}_tab_hover", ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control("{$prefix}_bg_hover", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$hover_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border_hover",
            'selector' => $hover_sel,
        ]);
        $this->end_controls_tab();

        // ── Actif
        $this->start_controls_tab("{$prefix}_tab_active", ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control("{$prefix}_bg_active", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$active_sel => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border_active",
            'selector' => $active_sel,
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control("{$prefix}_border_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [
                $item_sel => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden',
            ],
        ]);

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$item_sel => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $item_sel,
        ]);

        $this->end_controls_section();
    }

    // ══ Grille responsive (colonnes + espacement) ══════════════════════════════

    /**
     * Section Mise en page : colonnes responsives + gap.
     * Idéal pour les widgets de type grille (cards, reviews, specs...).
     *
     * Controls générés :
     *   columns  (RESPONSIVE SELECT 1-6, défaut 3 / 2 / 1)
     *   gap      (RESPONSIVE SLIDER px)
     *
     * @param string $container_sel  Sélecteur CSS de la grille (ex: '{{WRAPPER}} .bt-reviews__grid')
     * @param array  $defaults       'columns' (int, défaut 3), 'gap' (int px, défaut 24)
     * @param string $label          Label de la section (défaut: 'Mise en page')
     */
    protected function register_grid_layout_controls(
        string $container_sel,
        array  $defaults = [],
        string $label    = 'Mise en page'
    ): void {
        $this->start_controls_section('section_layout', [
            'label' => __($label, 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
            'default'        => (string) ($defaults['columns'] ?? 3),
            'tablet_default' => '2',
            'mobile_default' => '1',
            'selectors'      => [$container_sel => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $gap = $defaults['gap'] ?? 24;
        $this->add_responsive_control('gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => $gap, 'unit' => 'px'],
            'selectors'  => [$container_sel => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ══ Box / Container style ═════════════════════════════════════════════════

    /**
     * Section Style complète pour un bloc/container : bg + border + radius + padding + shadow.
     * Équivalent de register_box_controls() dans SjWidgetBase.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_bg        COLOR → background-color
     *   {prefix}_border    GROUP Border
     *   {prefix}_radius    DIMENSIONS px/% → border-radius
     *   {prefix}_padding   DIMENSIONS px/em → padding
     *   {prefix}_shadow    GROUP Box Shadow
     *
     * @param string $prefix    Préfixe IDs contrôles — ex: 'card', 'item', 'container'
     * @param string $label     Label section — ex: 'Style — Cartes'
     * @param string $selector  Sélecteur CSS
     * @param array  $defaults  'padding' (array TRBL in px), 'radius' (int px)
     * @param array  $condition Condition Elementor optionnelle sur la section
     */
    protected function register_box_style(
        string $prefix,
        string $label,
        string $selector,
        array  $defaults  = [],
        array  $condition = []
    ): void {
        $section_args = ['label' => $label, 'tab' => Controls_Manager::TAB_STYLE];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => "{$prefix}_border",
            'selector' => $selector,
        ]);

        $pad = $defaults['padding'] ?? null;
        $this->add_responsive_control("{$prefix}_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => $pad ? ['top' => $pad, 'right' => $pad, 'bottom' => $pad, 'left' => $pad, 'unit' => 'px', 'isLinked' => true] : [],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => "{$prefix}_shadow",
            'selector' => $selector,
        ]);

        $this->end_controls_section();
    }

    // ══ Bouton / CTA ══════════════════════════════════════════════════════════

    /**
     * Ajoute les controls bouton/CTA dans la section courante (sans ouvrir de section).
     * À appeler ENTRE start_controls_section() et end_controls_section().
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_text   TEXT
     *   {prefix}_link   URL (avec dynamic tags)
     *
     * @param string $prefix Préfixe IDs — ex: 'cta', 'btn'
     * @param string $label  Titre du séparateur visuel dans l'éditeur
     * @param array  $defaults 'text' (string), 'url' (string)
     */
    protected function register_cta_button_controls(string $prefix, string $label, array $defaults = []): void {
        $this->add_control("{$prefix}_heading", [
            'label'     => $label,
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control("{$prefix}_text", [
            'label'   => __('Texte du bouton', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['text'] ?? '',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control("{$prefix}_link", [
            'label'         => __('Lien', 'blacktenderscore'),
            'type'          => Controls_Manager::URL,
            'dynamic'       => ['active' => true],
            'placeholder'   => 'https://',
            'default'       => ['url' => $defaults['url'] ?? '', 'is_external' => false, 'nofollow' => false],
        ]);
    }

    // ══ Typographie (section complète) ════════════════════════════════════════

    /**
     * Section Style typographie pour un élément textuel.
     * Équivalent de register_typography_controls() dans SjWidgetBase.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_typography   GROUP Typography
     *   {prefix}_color        COLOR
     *   {prefix}_hover_color  COLOR :hover  (si $options['with_hover'])
     *   {prefix}_align        CHOOSE left/center/right (si $options['with_align'])
     *   {prefix}_spacing      SLIDER margin-bottom (si $options['with_spacing'])
     *
     * @param string $prefix    Préfixe IDs — ex: 'title', 'text', 'label'
     * @param string $label     Label de la section — ex: 'Style — Titre'
     * @param string $selector  Sélecteur CSS
     * @param array  $options   Clés: 'with_hover' (bool), 'with_align' (bool), 'with_spacing' (bool)
     * @param array  $defaults  Clés: 'color' (hex), 'hover_color' (hex), 'align' (left/center/right)
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_typography_section(
        string $prefix,
        string $label,
        string $selector,
        array  $options   = [],
        array  $defaults  = [],
        array  $condition = []
    ): void {
        $section_args = ['label' => $label, 'tab' => Controls_Manager::TAB_STYLE];
        if (!empty($condition)) {
            $section_args['condition'] = $condition;
        }
        $this->start_controls_section("style_{$prefix}", $section_args);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => "{$prefix}_typography",
            'selector' => $selector,
        ]);

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);

        if (!empty($options['with_hover'])) {
            $this->add_control("{$prefix}_hover_color", [
                'label'     => __('Couleur survol', 'blacktenderscore'),
                'type'      => Controls_Manager::COLOR,
                'default'   => $defaults['hover_color'] ?? '',
                'selectors' => ["{$selector}:hover" => 'color: {{VALUE}}'],
            ]);
        }

        if (!empty($options['with_align'])) {
            $this->add_responsive_control("{$prefix}_align", [
                'label'     => __('Alignement', 'blacktenderscore'),
                'type'      => Controls_Manager::CHOOSE,
                'default'   => $defaults['align'] ?? '',
                'options'   => [
                    'left'   => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                    'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                    'right'  => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
                ],
                'selectors' => [$selector => 'text-align: {{VALUE}}'],
            ]);
        }

        if (!empty($options['with_spacing'])) {
            $this->add_responsive_control("{$prefix}_spacing", [
                'label'      => __('Espacement bas', 'blacktenderscore'),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'selectors'  => [$selector => 'margin-bottom: {{SIZE}}{{UNIT}}'],
            ]);
        }

        $this->end_controls_section();
    }
}
