<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Excursions utilisant ce bateau.
 *
 * Effectue une WP_Query inverse sur le champ ACF `exp_boats` (relationship)
 * pour trouver toutes les excursions associées au bateau courant.
 */
class RelatedExcursions extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-related-excursions'; }
    public function get_title():      string { return 'BT — Excursions de ce bateau'; }
    public function get_icon():       string { return 'eicon-post-list'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['excursion', 'bateau', 'related', 'relation', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_relation_field', [
            'label'       => __('Champ ACF relation (sur excursion)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'exp_boats',
            'description' => __('Nom du champ ACF relationship utilisé sur les excursions pour pointer vers les bateaux.', 'blacktenderscore'),
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Excursions avec ce bateau', 'blacktenderscore'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->add_control('max_results', [
            'label'   => __('Nombre max d\'excursions', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 24,
            'default' => 6,
        ]);

        $this->end_controls_section();

        // ── Affichage cartes ──────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage des cartes', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 4,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-relexp__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_control('image_size', [
            'label'   => __('Taille d\'image', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['thumbnail' => 'Miniature', 'medium' => 'Moyenne', 'large' => 'Grande', 'full' => 'Originale'],
            'default' => 'medium',
        ]);

        $this->add_responsive_control('image_ratio', [
            'label'     => __('Ratio image (%)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 30, 'max' => 120]],
            'default'   => ['size' => 56],
            'selectors' => ['{{WRAPPER}} .bt-relexp__img-wrap' => 'padding-bottom: {{SIZE}}%'],
        ]);

        $this->add_control('show_image', [
            'label'        => __('Afficher l\'image', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_tagline', [
            'label'        => __('Afficher l\'accroche', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_price', [
            'label'        => __('Afficher le prix de départ', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('price_prefix', [
            'label'     => __('Préfixe prix', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Dès', 'blacktenderscore'),
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('currency', [
            'label'     => __('Symbole monnaie', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '€',
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('show_link', [
            'label'        => __('Afficher le bouton', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('link_label', [
            'label'     => __('Texte du bouton', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Voir l\'excursion', 'blacktenderscore'),
            'condition' => ['show_link' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Cartes ────────────────────────────────────────────────
        $this->start_controls_section('style_cards', [
            'label' => __('Style — Cartes', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('card_bg', [
            'label'     => __('Fond des cartes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relexp__card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .bt-relexp__card',
        ]);

        $this->add_responsive_control('card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__card' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .bt-relexp__card',
        ]);

        $this->end_controls_section();

        // ── Style — Texte ─────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('body_padding', [
            'label'      => __('Padding contenu', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'label'    => __('Typographie titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-relexp__card-title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relexp__card-title a, {{WRAPPER}} .bt-relexp__card-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'tagline_typo',
            'label'    => __('Typographie accroche', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-relexp__tagline',
        ]);

        $this->add_control('tagline_color', [
            'label'     => __('Couleur accroche', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relexp__tagline' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'price_typo',
            'label'    => __('Typographie prix', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-relexp__price',
        ]);

        $this->add_control('price_color', [
            'label'     => __('Couleur prix', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relexp__price' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'btn_typo',
            'label'    => __('Typographie bouton', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-relexp__btn',
        ]);

        $this->add_control('btn_color', [
            'label'     => __('Couleur bouton', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relexp__btn' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('btn_bg', [
            'label'     => __('Fond bouton', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-relexp__btn' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'btn_border',
            'selector' => '{{WRAPPER}} .bt-relexp__btn',
        ]);

        $this->add_responsive_control('btn_radius', [
            'label'      => __('Border radius bouton', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__btn' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('btn_padding', [
            'label'      => __('Padding bouton', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
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

        $meta_key = sanitize_key($s['acf_relation_field'] ?: 'exp_boats');
        $max      = max(1, (int) ($s['max_results'] ?: 6));

        // Reverse query avec cache transient
        $cache_key = 'bt_relexp_' . $post_id . '_' . $meta_key;
        $ids       = get_transient($cache_key);

        if ($ids === false) {
            $id_query = new \WP_Query([
                'post_type'      => 'excursion',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => [[
                    'key'     => $meta_key,
                    'value'   => '"' . $post_id . '"',
                    'compare' => 'LIKE',
                ]],
            ]);
            $ids = $id_query->posts ?: [];
            set_transient($cache_key, $ids, HOUR_IN_SECONDS * 6);
        }

        $query = new \WP_Query([
            'post_type'      => 'excursion',
            'posts_per_page' => $max,
            'post_status'    => 'publish',
            'post__in'       => $ids ?: [0],
            'orderby'        => 'post__in',
        ]);

        if (!$query->have_posts()) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucune excursion ne référence ce bateau via le champ <code>' . esc_html($meta_key) . '</code>.</p>';
            }
            return;
        }

        $currency = esc_html($s['currency'] ?: '€');
        $tag      = esc_attr($s['title_tag'] ?: 'h3');

        echo '<div class="bt-relexp">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-relexp__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        echo '<div class="bt-relexp__grid">';

        while ($query->have_posts()) {
            $query->the_post();
            $pid      = get_the_ID();
            $url      = get_permalink();
            $title    = get_the_title();
            $tagline  = (string) get_field('exp_tagline', $pid);
            $cover    = get_field('exp_cover', $pid);

            // Min price from repeater
            $min_price = null;
            if ($s['show_price'] === 'yes') {
                $rows = get_field('tarification_par_forfait', $pid);
                if (!empty($rows)) {
                    foreach ($rows as $row) {
                        $p = (float) ($row['exp_price'] ?? 0);
                        if ($p > 0 && ($min_price === null || $p < $min_price)) {
                            $min_price = $p;
                        }
                    }
                }
            }

            // Image
            $img_url = '';
            $img_alt = $title;
            if ($s['show_image'] === 'yes') {
                if (is_array($cover)) {
                    $sz      = $s['image_size'] ?: 'medium';
                    $img_url = $cover['sizes'][$sz] ?? $cover['url'] ?? '';
                    $img_alt = $cover['alt'] ?: $title;
                } else {
                    $thumb = get_post_thumbnail_id($pid);
                    if ($thumb) {
                        $src     = wp_get_attachment_image_src($thumb, $s['image_size'] ?: 'medium');
                        $img_url = $src ? $src[0] : '';
                    }
                }
            }

            echo '<div class="bt-relexp__card">';

            if ($s['show_image'] === 'yes' && $img_url) {
                echo '<a href="' . esc_url($url) . '" class="bt-relexp__img-wrap" tabindex="-1" aria-hidden="true">';
                echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" loading="lazy" class="bt-relexp__img" />';
                echo '</a>';
            }

            echo '<div class="bt-relexp__body">';
            echo '<h4 class="bt-relexp__card-title"><a href="' . esc_url($url) . '">' . esc_html($title) . '</a></h4>';

            if ($s['show_tagline'] === 'yes' && $tagline) {
                echo '<p class="bt-relexp__tagline">' . esc_html($tagline) . '</p>';
            }

            if ($s['show_price'] === 'yes' && $min_price !== null) {
                $prefix = esc_html($s['price_prefix'] ?: __('Dès', 'blacktenderscore'));
                echo '<p class="bt-relexp__price">' . $prefix . ' <strong>' . esc_html(number_format($min_price, 0, ',', ' ') . ' ' . $currency) . '</strong></p>';
            }

            if ($s['show_link'] === 'yes') {
                $lbl = $s['link_label'] ?: __('Voir l\'excursion', 'blacktenderscore');
                echo '<a href="' . esc_url($url) . '" class="bt-relexp__btn">' . esc_html($lbl) . '</a>';
            }

            echo '</div>'; // .bt-relexp__body
            echo '</div>'; // .bt-relexp__card
        }

        wp_reset_postdata();

        echo '</div>'; // .bt-relexp__grid
        echo '</div>'; // .bt-relexp
    }
}
