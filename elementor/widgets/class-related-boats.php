<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Bateaux liés à une excursion.
 *
 * Lit le champ ACF `exp_boats` (relationship → boat) du post courant (excursion)
 * et affiche les cartes bateau correspondantes.
 */
class RelatedBoats extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-related-boats'; }
    public function get_title():      string { return 'BT — Bateaux de l\'excursion'; }
    public function get_icon():       string { return 'eicon-posts-grid'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['bateau', 'related', 'excursion', 'relation', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'       => __('Champ ACF relationship', 'bt-regiondo'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'exp_boats',
            'description' => __('Nom du champ ACF de type relationship pointant vers boat.', 'bt-regiondo'),
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Bateaux utilisés', 'bt-regiondo'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->end_controls_section();

        // ── Affichage ─────────────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage des cartes', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'bt-regiondo'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 4,
            'default'        => 2,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-relboats__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_control('image_size', [
            'label'   => __('Taille d\'image', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'thumbnail' => 'Miniature',
                'medium'    => 'Moyenne',
                'large'     => 'Grande',
                'full'      => 'Originale',
            ],
            'default' => 'medium',
        ]);

        $this->add_responsive_control('image_ratio', [
            'label'      => __('Ratio image (%)', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 30, 'max' => 120]],
            'default'    => ['size' => 60],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__img-wrap' => 'padding-bottom: {{SIZE}}%'],
        ]);

        $this->add_control('show_image', [
            'label'        => __('Afficher l\'image', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_tagline', [
            'label'        => __('Afficher l\'accroche (boat_tagline)', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_specs', [
            'label'        => __('Afficher passagers + motorisation', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('spec_pax_label', [
            'label'     => __('Icône / Label passagers', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '👥',
            'condition' => ['show_specs' => 'yes'],
        ]);

        $this->add_control('spec_engine_label', [
            'label'     => __('Icône / Label motorisation', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '⚡',
            'condition' => ['show_specs' => 'yes'],
        ]);

        $this->add_control('show_link', [
            'label'        => __('Afficher le bouton lien', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('link_label', [
            'label'     => __('Texte du bouton', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Voir le bateau', 'bt-regiondo'),
            'condition' => ['show_link' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Cartes ────────────────────────────────────────────────
        $this->start_controls_section('style_cards', [
            'label' => __('Style — Cartes', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('card_bg', [
            'label'     => __('Fond des cartes', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relboats__card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .bt-relboats__card',
        ]);

        $this->add_responsive_control('card_radius', [
            'label'      => __('Border radius', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__card' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .bt-relboats__card',
        ]);

        $this->end_controls_section();

        // ── Style — Texte ─────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('body_padding', [
            'label'      => __('Padding contenu', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'label'    => __('Typographie titre bateau', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-relboats__card-title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relboats__card-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'tagline_typo',
            'label'    => __('Typographie accroche', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-relboats__tagline',
        ]);

        $this->add_control('tagline_color', [
            'label'     => __('Couleur accroche', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relboats__tagline' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'specs_typo',
            'label'    => __('Typographie specs', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-relboats__specs',
        ]);

        $this->add_control('specs_color', [
            'label'     => __('Couleur specs', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relboats__specs' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'btn_typo',
            'label'    => __('Typographie bouton', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-relboats__btn',
        ]);

        $this->add_control('btn_color', [
            'label'     => __('Couleur bouton', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relboats__btn' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('btn_bg', [
            'label'     => __('Fond bouton', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relboats__btn' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .bt-relboats__btn',
        ]);

        $this->add_responsive_control('btn_radius', [
            'label'      => __('Border radius bouton', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__btn' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('Padding bouton', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!function_exists('get_field')) {
            echo '<p class="bt-widget-placeholder">ACF Pro requis.</p>';
            return;
        }

        $field_name = sanitize_key($s['acf_field'] ?: 'exp_boats');
        $boats      = get_field($field_name, $post_id);

        if (empty($boats)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun bateau lié trouvé via le champ <code>' . esc_html($field_name) . '</code>. Vérifiez que des bateaux sont associés à cette excursion.</p>';
            }
            return;
        }

        $boats = is_array($boats) ? $boats : [$boats];
        $tag   = esc_attr($s['title_tag'] ?: 'h3');

        echo '<div class="bt-relboats">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-relboats__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        echo '<div class="bt-relboats__grid">';

        foreach ($boats as $boat) {
            if (!($boat instanceof \WP_Post)) {
                $boat = get_post($boat);
            }
            if (!$boat) continue;

            $boat_url   = get_permalink($boat->ID);
            $boat_title = get_the_title($boat->ID);

            // Cover image
            $cover = get_field('boat_cover', $boat->ID);
            $img_url = '';
            if (is_array($cover)) {
                $size    = $s['image_size'] ?: 'medium';
                $img_url = $cover['sizes'][$size] ?? $cover['url'] ?? '';
                $img_alt = $cover['alt'] ?: $boat_title;
            } elseif (!$cover) {
                $thumb = get_post_thumbnail_id($boat->ID);
                if ($thumb) {
                    $src     = wp_get_attachment_image_src($thumb, $s['image_size'] ?: 'medium');
                    $img_url = $src ? $src[0] : '';
                    $img_alt = get_post_meta($thumb, '_wp_attachment_image_alt', true) ?: $boat_title;
                }
            }

            // ACF fields
            $tagline    = (string) get_field('boat_tagline',    $boat->ID);
            $pax_max    = (string) get_field('boat_pax_max',    $boat->ID);
            $enginepow  = (string) get_field('boat_enginepower', $boat->ID);

            echo '<div class="bt-relboats__card">';

            // Image
            if ($s['show_image'] === 'yes' && $img_url) {
                echo '<a href="' . esc_url($boat_url) . '" class="bt-relboats__img-wrap" tabindex="-1" aria-hidden="true">';
                echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt ?? $boat_title) . '" loading="lazy" class="bt-relboats__img" />';
                echo '</a>';
            }

            echo '<div class="bt-relboats__body">';

            echo '<h4 class="bt-relboats__card-title"><a href="' . esc_url($boat_url) . '">' . esc_html($boat_title) . '</a></h4>';

            if ($s['show_tagline'] === 'yes' && $tagline) {
                echo '<p class="bt-relboats__tagline">' . esc_html($tagline) . '</p>';
            }

            if ($s['show_specs'] === 'yes' && ($pax_max || $enginepow)) {
                echo '<ul class="bt-relboats__specs">';
                if ($pax_max) {
                    echo '<li>' . esc_html($s['spec_pax_label'] ?: '👥') . ' ' . esc_html($pax_max) . ' pax</li>';
                }
                if ($enginepow) {
                    echo '<li>' . esc_html($s['spec_engine_label'] ?: '⚡') . ' ' . esc_html($enginepow) . ' CV</li>';
                }
                echo '</ul>';
            }

            if ($s['show_link'] === 'yes') {
                $lbl = $s['link_label'] ?: __('Voir le bateau', 'bt-regiondo');
                echo '<a href="' . esc_url($boat_url) . '" class="bt-relboats__btn">' . esc_html($lbl) . '</a>';
            }

            echo '</div>'; // .bt-relboats__body
            echo '</div>'; // .bt-relboats__card
        }

        echo '</div>'; // .bt-relboats__grid
        echo '</div>'; // .bt-relboats
    }
}
