<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Excursions utilisant ce bateau.
 *
 * Effectue une WP_Query inverse sur le champ ACF `exp_boats` (relationship)
 * pour trouver toutes les excursions associées au bateau courant.
 */
class RelatedExcursions extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-related-excursions',
            'title'    => 'BT — Excursions de ce bateau',
            'icon'     => 'eicon-post-list',
            'keywords' => ['excursion', 'bateau', 'related', 'relation', 'bt'],
            'css'      => ['bt-related-excursions'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_relation_field', [
            'label'       => __('Champ ACF relation (sur excursion)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exp_boats',
            'description' => __('Champ ACF relationship sur les excursions pointant vers les bateaux.', 'blacktenderscore'),
        ]);

        $this->register_section_title_controls(['title' => __('Excursions avec ce bateau', 'blacktenderscore')]);

        $this->add_control('max_results', [
            'label'   => __('Nombre max d\'excursions', 'blacktenderscore'),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 24,
            'default' => 6,
        ]);

        $this->add_control('orderby', [
            'label'   => __('Trier par', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'post__in' => __('Ordre de relation', 'blacktenderscore'),
                'date'     => __('Date (récent → ancien)', 'blacktenderscore'),
                'title'    => __('Titre (A → Z)', 'blacktenderscore'),
                'rand'     => __('Aléatoire', 'blacktenderscore'),
            ],
            'default' => 'post__in',
        ]);

        $this->end_controls_section();

        // ── Affichage cartes ──────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage des cartes', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 4,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-relexp__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_control('image_size', [
            'label'   => __('Taille d\'image', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['thumbnail' => 'Miniature', 'medium' => 'Moyenne', 'large' => 'Grande', 'full' => 'Originale'],
            'default' => 'medium',
        ]);

        $this->add_control('card_direction', [
            'label'   => __('Direction carte', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'vertical'   => ['title' => __('Verticale', 'blacktenderscore'), 'icon' => 'eicon-arrow-down'],
                'horizontal' => ['title' => __('Horizontale', 'blacktenderscore'), 'icon' => 'eicon-arrow-right'],
            ],
            'default' => 'vertical',
            'toggle'  => false,
        ]);

        $this->add_responsive_control('image_ratio', [
            'label'     => __('Ratio image', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'auto'  => __('Auto (ratio naturel)', 'blacktenderscore'),
                '16/9'  => '16:9',
                '3/2'   => '3:2',
                '4/3'   => '4:3',
                '1'     => '1:1',
                '3/4'   => '3:4 (portrait)',
            ],
            'default'   => 'auto',
            'selectors' => ['{{WRAPPER}} .bt-relexp__card--vertical .bt-relexp__img-wrap' => 'aspect-ratio: {{VALUE}}'],
            'condition' => ['card_direction' => 'vertical'],
        ]);

        $this->add_responsive_control('image_width', [
            'label'      => __('Largeur image (%)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 15, 'max' => 60]],
            'default'    => ['size' => 30],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__card--horizontal .bt-relexp__img-wrap' => 'width: {{SIZE}}%; min-width: {{SIZE}}%'],
            'condition'  => ['card_direction' => 'horizontal'],
        ]);

        $this->add_control('show_image', [
            'label'        => __('Afficher l\'image', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('card_title_tag', [
            'label'   => __('Balise titre carte', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'h6' => 'H6', 'p' => 'p', 'span' => 'span'],
            'default' => 'h4',
        ]);

        $this->add_control('show_tagline', [
            'label'        => __('Afficher l\'accroche', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_duration', [
            'label'        => __('Afficher la durée', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('duration_subfield', [
            'label'     => __('Champ ACF durée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'exp_duration',
            'condition' => ['show_duration' => 'yes'],
        ]);

        $this->add_control('duration_label', [
            'label'     => __('Icône / préfixe durée', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '⏱',
            'condition' => ['show_duration' => 'yes'],
        ]);

        $this->add_control('show_price', [
            'label'        => __('Afficher le prix de départ', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('price_prefix', [
            'label'     => __('Préfixe prix', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Dès', 'blacktenderscore'),
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('currency', [
            'label'     => __('Symbole monnaie', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '€',
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('show_link', [
            'label'        => __('Afficher le bouton', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('link_label', [
            'label'     => __('Texte du bouton', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Voir l\'excursion', 'blacktenderscore'),
            'condition' => ['show_link' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── STYLE ─────────────────────────────────────────────────────────

        $this->register_section_title_style('{{WRAPPER}} .bt-relexp__title');

        $this->start_controls_section('style_grid', [
            'label' => __('Style — Grille', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('body_padding', [
            'label'      => __('Padding contenu carte', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-relexp__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->end_controls_section();

        $this->register_box_style('card', 'Style — Cartes', '{{WRAPPER}} .bt-relexp__card');

        $this->register_typography_section(
            'card_title',
            'Style — Titre excursion',
            '{{WRAPPER}} .bt-relexp__card-title a, {{WRAPPER}} .bt-relexp__card-title',
            ['with_hover' => true]
        );

        $this->register_typography_section(
            'tagline',
            'Style — Accroche',
            '{{WRAPPER}} .bt-relexp__tagline',
            [],
            [],
            ['show_tagline' => 'yes']
        );

        $this->register_typography_section(
            'duration',
            'Style — Durée',
            '{{WRAPPER}} .bt-relexp__duration',
            [],
            [],
            ['show_duration' => 'yes']
        );

        $this->register_typography_section(
            'price',
            'Style — Prix',
            '{{WRAPPER}} .bt-relexp__price',
            [],
            [],
            ['show_price' => 'yes']
        );

        $this->register_button_style(
            'btn',
            'Style — Bouton',
            '{{WRAPPER}} .bt-relexp__btn',
            [],
            ['show_link' => 'yes']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $meta_key = sanitize_key($s['acf_relation_field'] ?: 'exp_boats');
        $max      = max(1, (int) ($s['max_results'] ?: 6));
        $orderby  = $s['orderby'] ?: 'post__in';

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

        $query_args = [
            'post_type'      => 'excursion',
            'posts_per_page' => $max,
            'post_status'    => 'publish',
            'post__in'       => $ids ?: [0],
            'orderby'        => $orderby,
        ];
        if ($orderby === 'title') {
            $query_args['order'] = 'ASC';
        }

        $query = new \WP_Query($query_args);

        if (!$query->have_posts()) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(sprintf(__('Aucune excursion ne référence ce bateau via le champ « %s ».', 'blacktenderscore'), $meta_key));
            }
            return;
        }

        $currency    = esc_html($s['currency'] ?: '€');
        $duration_sf = sanitize_text_field($s['duration_subfield'] ?: 'exp_duration');

        echo '<div class="bt-relexp">';

        $this->render_section_title($s, 'bt-relexp__title');

        echo '<div class="bt-relexp__grid">';

        while ($query->have_posts()) {
            $query->the_post();
            $pid     = get_the_ID();
            $url     = get_permalink();
            $title   = get_the_title();
            // ACF fields — batch load to avoid N × get_field() queries per excursion
            $acf     = function_exists('get_fields') ? (get_fields($pid) ?: []) : [];
            $tagline = (string) ($acf['exp_tagline'] ?? '');
            $cover   = $acf['exp_cover'] ?? null;
            $duration_val = $s['show_duration'] === 'yes' ? (string) ($acf[$duration_sf] ?? '') : '';

            // Min price from repeater
            $min_price = null;
            if ($s['show_price'] === 'yes') {
                $rows = $acf['tarification_par_forfait'] ?? null;
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

            $dir = esc_attr($s['card_direction'] ?: 'vertical');
            echo '<div class="bt-relexp__card bt-relexp__card--' . $dir . '">';

            if ($s['show_image'] === 'yes' && $img_url) {
                echo '<a href="' . esc_url($url) . '" class="bt-relexp__img-wrap" tabindex="-1" aria-hidden="true">';
                echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" loading="lazy" class="bt-relexp__img" />';
                echo '</a>';
            }

            echo '<div class="bt-relexp__body">';
            $title_tag = esc_attr($s['card_title_tag'] ?: 'h4');
            echo "<{$title_tag} class=\"bt-relexp__card-title\"><a href=\"" . esc_url($url) . '">' . esc_html($title) . "</a></{$title_tag}>";

            if ($s['show_tagline'] === 'yes' && $tagline) {
                echo '<p class="bt-relexp__tagline">' . esc_html($tagline) . '</p>';
            }

            if ($s['show_duration'] === 'yes' && $duration_val) {
                $dur_prefix = esc_html($s['duration_label'] ?: '⏱');
                echo '<p class="bt-relexp__duration">' . $dur_prefix . ' ' . esc_html($duration_val) . '</p>';
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
