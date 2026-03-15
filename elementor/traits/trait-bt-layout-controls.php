<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

defined('ABSPATH') || exit;

/**
 * BtLayoutControls — Grille responsive et box/container style.
 *
 * Méthodes :
 *   register_grid_layout_controls($container_sel, $defaults, $label)
 *   register_box_style($prefix, $label, $selector, $defaults, $condition)
 */
trait BtLayoutControls {

    /**
     * Section Mise en page : colonnes responsives + gap.
     *
     * Controls générés :
     *   columns  (RESPONSIVE SELECT 1-6, défaut 3 / 2 / 1)
     *   gap      (RESPONSIVE SLIDER px)
     *
     * @param string $container_sel  Sélecteur CSS de la grille
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

    /**
     * Section Style complète pour un bloc/container.
     * bg + border + radius + padding + shadow.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_bg        COLOR → background-color
     *   {prefix}_border    GROUP Border
     *   {prefix}_radius    DIMENSIONS px/% → border-radius
     *   {prefix}_padding   DIMENSIONS px/em → padding
     *   {prefix}_shadow    GROUP Box Shadow
     *
     * @param string $prefix    Préfixe IDs contrôles
     * @param string $label     Label section
     * @param string $selector  Sélecteur CSS
     * @param array  $defaults  'padding' (int px), 'radius' (int px)
     * @param array  $condition Condition Elementor optionnelle
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

        $this->add_responsive_control("{$prefix}_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $pad = $defaults['padding'] ?? null;
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

    /**
     * Section Style pour les étoiles de notation.
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_filled_color   COLOR → couleur étoiles pleines (.bt-star--filled)
     *   {prefix}_empty_color    COLOR → couleur étoiles vides (.bt-star--empty)
     *   {prefix}_size           SLIDER px/em → font-size du conteneur
     *   {prefix}_gap            SLIDER px → letter-spacing entre étoiles
     *
     * ⚠ Le render doit entourer chaque étoile de :
     *     <span class="bt-star bt-star--filled">★</span>
     *     <span class="bt-star bt-star--empty">☆</span>
     *
     * @param string $prefix    ex: 'stars'
     * @param string $label     ex: 'Style — Étoiles'
     * @param string $selector  ex: '{{WRAPPER}} .bt-reviews__stars'
     * @param array  $defaults  'filled_color', 'empty_color', 'size' (int px, défaut 18), 'gap' (int px)
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_stars_style(
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

        $this->add_control("{$prefix}_filled_color", [
            'label'     => __('Couleur étoiles pleines', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['filled_color'] ?? '',
            'selectors' => ["{$selector} .bt-star--filled" => 'color: {{VALUE}}'],
        ]);

        $this->add_control("{$prefix}_empty_color", [
            'label'     => __('Couleur étoiles vides', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['empty_color'] ?? '',
            'selectors' => ["{$selector} .bt-star--empty" => 'color: {{VALUE}}'],
        ]);

        $size = $defaults['size'] ?? 18;
        $this->add_responsive_control("{$prefix}_size", [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 10, 'max' => 48]],
            'default'    => ['size' => $size, 'unit' => 'px'],
            'selectors'  => [$selector => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control("{$prefix}_gap", [
            'label'      => __('Espacement entre étoiles', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 12]],
            'selectors'  => [$selector => 'letter-spacing: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    /**
     * Section Style pour un séparateur / diviseur visuel.
     *
     * Controls générés :
     *   {prefix}_color     COLOR → background-color (or border-color)
     *   {prefix}_width     SLIDER px → height (épaisseur)
     *   {prefix}_length    SLIDER % → width
     *   {prefix}_spacing   SLIDER px → margin (top+bottom)
     *
     * @param string $prefix   ex: 'separator'
     * @param string $label    ex: 'Séparateur'
     * @param string $selector ex: '{{WRAPPER}} .bt-widget__separator'
     * @param array  $defaults 'color' (hex), 'width' (px int), 'length' (% int)
     * @param array  $condition Elementor condition optionnelle
     */
    /**
     * Ajoute un contrôle gap responsive dans la section courante (inline).
     * À appeler ENTRE start_controls_section() et end_controls_section().
     *
     * Control généré :
     *   $control_id  RESPONSIVE SLIDER px → gap: {{SIZE}}{{UNIT}}
     *
     * @param string   $control_id  ID du contrôle (ex: 'items_gap', 'badges_gap')
     * @param string   $label       Label affiché
     * @param array    $selectors   Sélecteurs CSS (ex: ['{{WRAPPER}} .bt-foo__list'])
     * @param int      $default     Valeur par défaut en px (défaut 16)
     */
    protected function register_gap_control(
        string $control_id,
        string $label,
        array  $selectors,
        int    $default = 16
    ): void {
        $map = [];
        foreach ($selectors as $sel) {
            $map[$sel] = 'gap: {{SIZE}}{{UNIT}}';
        }

        $this->add_responsive_control($control_id, [
            'label'      => __($label, 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => $default, 'unit' => 'px'],
            'selectors'  => $map,
        ]);
    }

    /**
     * Ajoute une paire de contrôles couleur fond + texte pour un badge (inline).
     * À appeler ENTRE start_controls_section() et end_controls_section().
     *
     * Controls générés :
     *   {prefix}_bg      COLOR → background-color
     *   {prefix}_color   COLOR → color
     *
     * @param string $prefix    Préfixe IDs (ex: 'season_badge' → season_badge_bg, season_badge_color)
     * @param string $label     Titre HEADING visuel dans l'éditeur
     * @param string $selector  Sélecteur CSS
     * @param array  $defaults  'bg' (hex), 'color' (hex)
     * @param array  $condition Condition Elementor optionnelle (appliquée aux 3 controls)
     */
    protected function register_badge_colors(
        string $prefix,
        string $label,
        string $selector,
        array  $defaults  = [],
        array  $condition = []
    ): void {
        $heading_args = [
            'label'     => $label,
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ];
        if (!empty($condition)) $heading_args['condition'] = $condition;
        $this->add_control("{$prefix}_heading", $heading_args);

        $bg_args = [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['bg'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ];
        if (!empty($condition)) $bg_args['condition'] = $condition;
        $this->add_control("{$prefix}_bg", $bg_args);

        $color_args = [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ];
        if (!empty($condition)) $color_args['condition'] = $condition;
        $this->add_control("{$prefix}_color", $color_args);
    }

    /**
     * Section Style complète pour une icône (Elementor Icons, emoji, SVG).
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_size     RESPONSIVE SLIDER px/em → font-size + width
     *   {prefix}_color    COLOR → color (+ i, svg fill)
     *   {prefix}_bg       COLOR → background-color
     *   {prefix}_padding  DIMENSIONS px/em → padding
     *   {prefix}_radius   RESPONSIVE SLIDER px/% → border-radius
     *
     * @param string $prefix    Préfixe IDs (ex: 'icon')
     * @param string $label     Label section (ex: 'Style — Icône')
     * @param string $selector  Sélecteur CSS du wrapper icône
     * @param array  $defaults  'size' (int px, défaut 24), 'color', 'bg', 'radius' (int px)
     * @param array  $condition Condition Elementor optionnelle
     */
    protected function register_icon_style_section(
        string $prefix,
        string $label,
        string $selector,
        array  $defaults  = [],
        array  $condition = []
    ): void {
        $section_args = ['label' => $label, 'tab' => Controls_Manager::TAB_STYLE];
        if (!empty($condition)) $section_args['condition'] = $condition;
        $this->start_controls_section("style_{$prefix}", $section_args);

        $size = $defaults['size'] ?? 24;
        $this->add_responsive_control("{$prefix}_size", [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 12, 'max' => 80]],
            'default'    => ['size' => $size, 'unit' => 'px'],
            'selectors'  => [$selector => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [
                $selector           => 'color: {{VALUE}}',
                "{$selector} i"     => 'color: {{VALUE}}',
                "{$selector} svg"   => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
        ]);

        $this->add_control("{$prefix}_bg", [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['bg'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control("{$prefix}_padding", [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        if (isset($defaults['radius'])) {
            $r_default = ['size' => (int) $defaults['radius'], 'unit' => 'px'];
        } else {
            $r_default = [];
        }
        $this->add_responsive_control("{$prefix}_radius", [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => $r_default,
            'selectors'  => [$selector => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    /**
     * Section Style pour un séparateur / diviseur visuel.
     *
     * Contrôles : {prefix}_as_break (Saut de ligne), {prefix}_color, {prefix}_width,
     * {prefix}_length, {prefix}_spacing.
     * Si "Saut de ligne" est activé, le widget doit ajouter la classe .bt-sep--line-break
     * sur l’élément séparateur pour masquer la ligne (seul l’espacement reste).
     */
    protected function register_separator_controls(
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

        $this->add_control("{$prefix}_as_break", [
            'label'        => __('Saut de ligne', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Masque la ligne et ne garde que l’espace. Pensez à ajouter la classe .bt-sep--line-break sur l’élément séparateur quand activé.', 'blacktenderscore'),
        ]);

        $this->add_control("{$prefix}_color", [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => $defaults['color'] ?? '',
            'selectors' => [$selector => 'background-color: {{VALUE}}'],
        ]);

        $w = $defaults['width'] ?? 1;
        $this->add_responsive_control("{$prefix}_width", [
            'label'      => __('Épaisseur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 20]],
            'default'    => ['size' => $w, 'unit' => 'px'],
            'selectors'  => [$selector => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $l = $defaults['length'] ?? 100;
        $this->add_responsive_control("{$prefix}_length", [
            'label'      => __('Longueur (%)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => ['%' => ['min' => 10, 'max' => 100], 'px' => ['min' => 10, 'max' => 800]],
            'default'    => ['size' => $l, 'unit' => '%'],
            'selectors'  => [$selector => 'width: {{SIZE}}{{UNIT}}; display: block'],
        ]);

        $this->add_responsive_control("{$prefix}_spacing", [
            'label'      => __('Espacement (haut/bas)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [$selector => 'margin-block: {{SIZE}}{{UNIT}}'],
        ]);

        // Quand "Saut de ligne" est activé, le widget doit ajouter la classe .bt-sep--line-break
        // sur l’élément séparateur → la ligne est masquée, l’espacement (margin) reste.
        $break_selector = $selector . '.bt-sep--line-break';
        $this->add_control("{$prefix}_line_break_style", [
            'type'      => Controls_Manager::HIDDEN,
            'default'   => '',
            'selectors' => [
                $break_selector => 'height: 0 !important; min-height: 0 !important; background: transparent !important; border: none !important;',
            ],
        ]);

        $this->end_controls_section();
    }
}
