<?php
/**
 * Blog Article Widget
 *
 * Renders ACF-driven blog post content composed of:
 * - hero (group)
 * - blocks (flexible_content)
 * - conclusion (group)
 * - related_excursions (relationship)
 *
 * @package BlackTenders\Elementor\Widgets\BlogArticle
 */

namespace BlackTenders\Elementor\Widgets\BlogArticle;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;

defined('ABSPATH') || exit;

class Blog_Article extends \Elementor\Widget_Base {

    const WIDGET_NAME   = 'bt_blog_article';
    const WIDGET_TITLE  = 'Blog Article';
    const WIDGET_ICON   = 'eicon-post-content';
    const STYLE_HANDLE  = 'bt-blog-article';
    const TEMPLATE_PATH = __DIR__ . '/template-parts';

    public function get_name() {
        return self::WIDGET_NAME;
    }

    public function get_title() {
        return __( self::WIDGET_TITLE, 'blacktenderscore' );
    }

    public function get_icon() {
        return self::WIDGET_ICON;
    }

    public function get_categories() {
        return [ 'blacktenders' ];
    }

    public function get_style_depends() {
        return [ self::STYLE_HANDLE ];
    }

    public function get_keywords() {
        return [ 'blog', 'article', 'post', 'content', 'acf' ];
    }

    public function is_reload_preview_required() {
        return true;
    }

    protected function register_controls() {
        $this->register_display_controls();
        $this->register_style_typography();
        $this->register_style_colors();
        $this->register_style_layout();
        $this->register_style_hero();
        $this->register_style_blocks();
        $this->register_style_conclusion();
        $this->register_style_pullquote();
        $this->register_style_callout();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CONTENT TAB
    // ═══════════════════════════════════════════════════════════════════════

    private function register_display_controls() {
        $this->start_controls_section( 'section_display', [
            'label' => __( 'Display', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ] );

        $this->add_control( 'show_title', [
            'label'        => __( 'Titre (H1)', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_image', [
            'label'        => __( 'Image / vidéo héro', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_kicker', [
            'label'        => __( 'Kicker', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_reading_time', [
            'label'        => __( 'Temps de lecture', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_toc', [
            'label'        => __( 'Table des matières', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'no',
            'return_value' => 'yes',
            'description'  => __( 'Générée depuis les blocs texte avec ancres (H2 uniquement).', 'blacktenderscore' ),
        ] );

        $this->add_control( 'show_conclusion', [
            'label'        => __( 'Bloc conclusion', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->add_control( 'show_related', [
            'label'        => __( 'Excursions liées', 'blacktenderscore' ),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'return_value' => 'yes',
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Typographies
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_typography() {
        $this->start_controls_section( 'section_style_typo', [
            'label' => __( 'Typographies', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_h1',
            'label'    => __( 'H1 — titre article', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__title',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_h2',
            'label'    => __( 'H2', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__block-heading--h2',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_h3',
            'label'    => __( 'H3', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__block-heading--h3',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_h4',
            'label'    => __( 'H4', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__block-heading--h4',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_h5',
            'label'    => __( 'H5', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__prose h5',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_h6',
            'label'    => __( 'H6', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__prose h6',
        ] );

        $this->add_control( '_typo_div1', [ 'type' => Controls_Manager::DIVIDER ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_kicker',
            'label'    => __( 'Kicker', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__kicker',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_lead',
            'label'    => __( 'Lead / chapeau', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__lead',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_body',
            'label'    => __( 'Corps de texte', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__prose',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_meta',
            'label'    => __( 'Méta (temps de lecture)', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__meta',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_caption',
            'label'    => __( 'Légende image', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__hero-caption',
        ] );

        $this->add_control( '_typo_div2', [ 'type' => Controls_Manager::DIVIDER ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_pullquote',
            'label'    => __( 'Pullquote', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__pullquote-text',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_callout_title',
            'label'    => __( 'Callout — titre', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__callout-title',
        ] );

        $this->add_group_control( Group_Control_Typography::get_type(), [
            'name'     => 'typo_conclusion_cta',
            'label'    => __( 'Conclusion — CTA', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__conclusion-cta',
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Couleurs
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_colors() {
        $this->start_controls_section( 'section_style_colors', [
            'label' => __( 'Couleurs', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_control( 'color_h1', [
            'label'     => __( 'H1', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__title' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_h2', [
            'label'     => __( 'H2', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__block-heading--h2' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_h3', [
            'label'     => __( 'H3', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__block-heading--h3' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_h4', [
            'label'     => __( 'H4', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__block-heading--h4' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_h5h6', [
            'label'     => __( 'H5 / H6', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-blog__prose h5' => 'color: {{VALUE}};',
                '{{WRAPPER}} .bt-blog__prose h6' => 'color: {{VALUE}};',
            ],
        ] );

        $this->add_control( '_color_div1', [ 'type' => Controls_Manager::DIVIDER ] );

        $this->add_control( 'color_kicker', [
            'label'     => __( 'Kicker', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__kicker' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_lead', [
            'label'     => __( 'Lead', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__lead' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_body', [
            'label'     => __( 'Corps de texte', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__prose' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_link', [
            'label'     => __( 'Liens', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__prose a' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_meta', [
            'label'     => __( 'Méta (temps de lecture)', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__meta' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'color_caption', [
            'label'     => __( 'Légende image', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__hero-caption' => 'color: {{VALUE}};' ],
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Mise en page (layout global)
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_layout() {
        $this->start_controls_section( 'section_style_layout', [
            'label' => __( 'Mise en page', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'     => 'layout_bg',
            'label'    => __( 'Fond du layout', 'blacktenderscore' ),
            'types'    => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .bt-blog',
        ] );

        $this->add_responsive_control( 'layout_padding', [
            'label'      => __( 'Padding', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'layout_radius', [
            'label'      => __( 'Border-radius', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_control( '_layout_div', [ 'type' => Controls_Manager::DIVIDER ] );

        $this->add_responsive_control( 'narrow_width', [
            'label'      => __( 'Largeur narrow', 'blacktenderscore' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'ch' ],
            'range'      => [
                'px' => [ 'min' => 480, 'max' => 900 ],
                'ch' => [ 'min' => 40,  'max' => 90  ],
            ],
            'default'    => [ 'unit' => 'ch', 'size' => 68 ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog' => '--bt-blog-narrow: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'wide_width', [
            'label'      => __( 'Largeur wide', 'blacktenderscore' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px' ],
            'range'      => [ 'px' => [ 'min' => 800, 'max' => 1600 ] ],
            'default'    => [ 'unit' => 'px', 'size' => 1100 ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog' => '--bt-blog-wide: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'block_spacing', [
            'label'      => __( 'Espacement entre blocs', 'blacktenderscore' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'rem' ],
            'range'      => [
                'px'  => [ 'min' => 16, 'max' => 120 ],
                'rem' => [ 'min' => 1,  'max' => 8   ],
            ],
            'default'    => [ 'unit' => 'rem', 'size' => 2.5 ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog' => '--bt-blog-block-gap: {{SIZE}}{{UNIT}};' ],
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Héro
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_hero() {
        $this->start_controls_section( 'section_style_hero', [
            'label' => __( 'Héro', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'     => 'hero_bg',
            'label'    => __( 'Fond héro', 'blacktenderscore' ),
            'types'    => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .bt-blog__hero',
        ] );

        $this->add_responsive_control( 'hero_padding', [
            'label'      => __( 'Padding héro', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__hero' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'hero_radius', [
            'label'      => __( 'Border-radius héro', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__hero' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'hero_inner_padding', [
            'label'      => __( 'Padding contenu héro', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__hero-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_control( '_hero_div', [ 'type' => Controls_Manager::DIVIDER ] );

        $this->add_responsive_control( 'hero_image_radius', [
            'label'      => __( 'Border-radius image', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__hero-media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;' ],
        ] );

        $this->add_responsive_control( 'hero_image_max_height', [
            'label'      => __( 'Hauteur max image', 'blacktenderscore' ),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => [ 'px', 'vh' ],
            'range'      => [
                'px' => [ 'min' => 200, 'max' => 900 ],
                'vh' => [ 'min' => 20,  'max' => 90  ],
            ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__hero-image' => 'max-height: {{SIZE}}{{UNIT}}; width: 100%; object-fit: cover;' ],
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Blocs de contenu
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_blocks() {
        $this->start_controls_section( 'section_style_blocks', [
            'label' => __( 'Blocs de contenu', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'     => 'block_bg',
            'label'    => __( 'Fond des blocs', 'blacktenderscore' ),
            'types'    => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .bt-blog__block',
        ] );

        $this->add_responsive_control( 'block_padding', [
            'label'      => __( 'Padding des blocs', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__block' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'block_radius', [
            'label'      => __( 'Border-radius des blocs', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__block' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_group_control( Group_Control_Border::get_type(), [
            'name'     => 'block_border',
            'label'    => __( 'Bordure des blocs', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__block',
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Conclusion
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_conclusion() {
        $this->start_controls_section( 'section_style_conclusion', [
            'label' => __( 'Conclusion', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'     => 'conclusion_bg',
            'label'    => __( 'Fond conclusion', 'blacktenderscore' ),
            'types'    => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .bt-blog__conclusion',
        ] );

        $this->add_responsive_control( 'conclusion_padding', [
            'label'      => __( 'Padding', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__conclusion' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'conclusion_radius', [
            'label'      => __( 'Border-radius', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__conclusion' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_control( 'conclusion_cta_color', [
            'label'     => __( 'Couleur CTA', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__conclusion-cta' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'conclusion_cta_bg', [
            'label'     => __( 'Fond CTA', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__conclusion-cta' => 'background-color: {{VALUE}};' ],
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Pullquote
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_pullquote() {
        $this->start_controls_section( 'section_style_pullquote', [
            'label' => __( 'Pullquote', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'     => 'pullquote_bg',
            'label'    => __( 'Fond', 'blacktenderscore' ),
            'types'    => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .bt-blog__block--pullquote',
        ] );

        $this->add_responsive_control( 'pullquote_padding', [
            'label'      => __( 'Padding', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__block--pullquote' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'pullquote_radius', [
            'label'      => __( 'Border-radius', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__block--pullquote' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_control( 'pullquote_accent_color', [
            'label'     => __( 'Couleur accent (bordure)', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__pullquote--bordered' => 'border-left-color: {{VALUE}};' ],
        ] );

        $this->add_control( 'pullquote_text_color', [
            'label'     => __( 'Couleur texte', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__pullquote-text' => 'color: {{VALUE}};' ],
        ] );

        $this->add_control( 'pullquote_cite_color', [
            'label'     => __( 'Couleur auteur / source', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-blog__pullquote-author' => 'color: {{VALUE}};',
                '{{WRAPPER}} .bt-blog__pullquote-source' => 'color: {{VALUE}};',
            ],
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STYLE TAB — Callout
    // ═══════════════════════════════════════════════════════════════════════

    private function register_style_callout() {
        $this->start_controls_section( 'section_style_callout', [
            'label' => __( 'Callout', 'blacktenderscore' ),
            'tab'   => Controls_Manager::TAB_STYLE,
        ] );

        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'     => 'callout_bg',
            'label'    => __( 'Fond', 'blacktenderscore' ),
            'types'    => [ 'classic', 'gradient' ],
            'selector' => '{{WRAPPER}} .bt-blog__callout',
        ] );

        $this->add_responsive_control( 'callout_padding', [
            'label'      => __( 'Padding', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', 'rem', '%' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__callout' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_responsive_control( 'callout_radius', [
            'label'      => __( 'Border-radius', 'blacktenderscore' ),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => [ 'px', '%', 'rem' ],
            'selectors'  => [ '{{WRAPPER}} .bt-blog__callout' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ],
        ] );

        $this->add_group_control( Group_Control_Border::get_type(), [
            'name'     => 'callout_border',
            'label'    => __( 'Bordure', 'blacktenderscore' ),
            'selector' => '{{WRAPPER}} .bt-blog__callout',
        ] );

        $this->add_control( 'callout_title_color', [
            'label'     => __( 'Couleur titre', 'blacktenderscore' ),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [ '{{WRAPPER}} .bt-blog__callout-title' => 'color: {{VALUE}};' ],
        ] );

        $this->end_controls_section();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RENDER
    // ═══════════════════════════════════════════════════════════════════════

    protected function render() {
        $post_id = $this->resolve_post_id();
        if ( ! $post_id ) {
            $this->render_placeholder();
            return;
        }

        $settings = $this->get_settings_for_display();
        $context  = $this->build_context( $post_id, $settings );

        echo '<article class="bt-blog" data-post-id="' . esc_attr( $post_id ) . '">';

        $this->render_partial( 'hero.php', $context );

        if ( 'yes' === $settings['show_toc'] ) {
            $this->render_toc( $context['blocks'] );
        }

        $this->render_blocks( $context['blocks'] );

        if ( 'yes' === $settings['show_conclusion'] && ! empty( $context['conclusion']['text'] ) ) {
            $this->render_partial( 'conclusion.php', $context );
        }

        if ( 'yes' === $settings['show_related'] && ! empty( $context['related_excursions'] ) ) {
            $this->render_partial( 'related-excursions.php', $context );
        }

        echo '</article>';
    }

    private function resolve_post_id() {
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode()
            || \Elementor\Plugin::$instance->preview->is_preview_mode() ) {
            $preview_id = get_query_var( 'elementor_library_preview_id' );
            if ( $preview_id ) {
                return (int) $preview_id;
            }
        }
        return get_the_ID();
    }

    private function build_context( $post_id, $settings ) {
        $hero   = get_field( 'hero', $post_id ) ?: [];
        $blocks = get_field( 'blocks', $post_id ) ?: [];

        return [
            'post_id'            => $post_id,
            'settings'           => $settings,
            'post_title'         => get_the_title( $post_id ),
            'featured_image_id'  => get_post_thumbnail_id( $post_id ),
            'permalink'          => get_permalink( $post_id ),
            'hero'               => wp_parse_args( $hero, [
                'kicker'         => '',
                'title_override' => '',
                'lead'           => '',
                'media_type'     => 'featured',
                'image'          => null,
                'video'          => '',
                'caption'        => '',
                'reading_time'   => 0,
            ] ),
            'blocks'             => $blocks,
            'conclusion'         => get_field( 'conclusion', $post_id ) ?: [],
            'related_excursions' => get_field( 'related_excursions', $post_id ) ?: [],
            'reading_time'       => $this->calc_reading_time( $blocks, $hero ),
        ];
    }

    private function calc_reading_time( array $blocks, array $hero ) {
        if ( ! empty( $hero['reading_time'] ) ) {
            return (int) $hero['reading_time'];
        }
        $text = (string) ( $hero['lead'] ?? '' );
        foreach ( $blocks as $block ) {
            if ( isset( $block['content'] ) ) {
                $text .= ' ' . wp_strip_all_tags( $block['content'] );
            }
            if ( isset( $block['body'] ) ) {
                $text .= ' ' . wp_strip_all_tags( $block['body'] );
            }
            if ( isset( $block['quote'] ) ) {
                $text .= ' ' . $block['quote'];
            }
        }
        $words = max( 1, str_word_count( $text ) );
        return max( 1, (int) ceil( $words / 230 ) );
    }

    private function render_blocks( array $blocks ) {
        if ( empty( $blocks ) ) {
            return;
        }
        echo '<div class="bt-blog__blocks">';
        foreach ( $blocks as $index => $block ) {
            $layout = $block['acf_fc_layout'] ?? '';
            $file   = self::TEMPLATE_PATH . '/blocks/' . sanitize_file_name( $layout ) . '.php';
            if ( $layout && file_exists( $file ) ) {
                $this->render_partial( 'blocks/' . $layout . '.php', [
                    'block' => $block,
                    'index' => $index,
                ] );
            }
        }
        echo '</div>';
    }

    private function render_toc( array $blocks ) {
        $items = [];
        foreach ( $blocks as $block ) {
            if ( ( $block['acf_fc_layout'] ?? '' ) !== 'text' ) {
                continue;
            }
            if ( empty( $block['heading'] ) || empty( $block['anchor'] ) ) {
                continue;
            }
            if ( ( $block['heading_level'] ?? 'h2' ) !== 'h2' ) {
                continue;
            }
            $items[] = [
                'anchor' => sanitize_title( $block['anchor'] ),
                'label'  => $block['heading'],
            ];
        }
        if ( empty( $items ) ) {
            return;
        }
        echo '<nav class="bt-blog__toc" aria-label="' . esc_attr__( 'Dans cet article', 'blacktenderscore' ) . '">';
        echo '<p class="bt-blog__toc-label">' . esc_html__( 'Dans cet article', 'blacktenderscore' ) . '</p>';
        echo '<ol class="bt-blog__toc-list">';
        foreach ( $items as $item ) {
            printf(
                '<li><a href="#%1$s">%2$s</a></li>',
                esc_attr( $item['anchor'] ),
                esc_html( $item['label'] )
            );
        }
        echo '</ol></nav>';
    }

    private function render_partial( $relative_path, array $context ) {
        $file = self::TEMPLATE_PATH . '/' . ltrim( $relative_path, '/' );
        if ( ! file_exists( $file ) ) {
            return;
        }
        extract( $context, EXTR_SKIP );
        include $file;
    }

    private function render_placeholder() {
        if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            echo '<div class="bt-blog bt-blog--placeholder">';
            echo '<p>' . esc_html__( 'Ce widget affiche les données ACF d\'un article. Assignez-le à un template de single post.', 'blacktenderscore' ) . '</p>';
            echo '</div>';
        }
    }
}
