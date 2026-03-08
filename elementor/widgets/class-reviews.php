<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Avis clients.
 *
 * Lit le repeater ACF `exp_reviews_highlight` (rev_name, rev_rating, rev_text)
 * et affiche les cartes avis. Injecte optionnellement le schema.org
 * AggregateRating + Review en JSON-LD.
 */
class Reviews extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-reviews'; }
    public function get_title():      string { return 'BT — Avis clients'; }
    public function get_icon():       string { return 'eicon-rating'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['avis', 'reviews', 'rating', 'étoiles', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'exp_reviews_highlight',
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Avis clients', 'bt-regiondo'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->add_control('max_reviews', [
            'label'   => __('Nombre max d\'avis', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 20,
            'default' => 5,
        ]);

        $this->end_controls_section();

        // ── Affichage ─────────────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'grid' => __('Grille', 'bt-regiondo'),
                'list' => __('Liste', 'bt-regiondo'),
            ],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'bt-regiondo'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 4,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-reviews__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition'      => ['layout' => 'grid'],
        ]);

        $this->add_control('show_stars', [
            'label'        => __('Afficher les étoiles', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('star_filled', [
            'label'     => __('Étoile pleine', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '★',
            'condition' => ['show_stars' => 'yes'],
        ]);

        $this->add_control('star_empty', [
            'label'     => __('Étoile vide', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '☆',
            'condition' => ['show_stars' => 'yes'],
        ]);

        $this->add_control('max_stars', [
            'label'     => __('Note max', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 3,
            'max'       => 10,
            'default'   => 5,
            'condition' => ['show_stars' => 'yes'],
        ]);

        $this->add_control('show_name', [
            'label'        => __('Afficher le prénom', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_text', [
            'label'        => __('Afficher le texte de l\'avis', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('quote_char', [
            'label'     => __('Caractère de citation', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '"',
            'condition' => ['show_text' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Schema.org ────────────────────────────────────────────────────
        $this->start_controls_section('section_schema', [
            'label' => __('Schema.org', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('schema_reviews', [
            'label'        => __('Injecter le schema AggregateRating (SEO)', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'description'  => __('Injecte un JSON-LD AggregateRating + Review calculé depuis les avis ACF.', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('schema_item_type', [
            'label'     => __('@type de l\'entité', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'Product'     => 'Product',
                'LocalBusiness' => 'LocalBusiness',
                'TouristTrip' => 'TouristTrip',
                'Service'     => 'Service',
            ],
            'default'   => 'TouristTrip',
            'condition' => ['schema_reviews' => 'yes'],
        ]);

        $this->add_control('schema_item_name', [
            'label'       => __('Nom de l\'entité (vide = titre du post)', 'bt-regiondo'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Laissez vide pour utiliser automatiquement le titre du post.', 'bt-regiondo'),
            'condition'   => ['schema_reviews' => 'yes'],
            'dynamic'     => ['active' => true],
        ]);

        $this->end_controls_section();

        // ── Style — Cartes ────────────────────────────────────────────────
        $this->start_controls_section('style_cards', [
            'label' => __('Style — Cartes', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'section_title_typo',
            'label'    => __('Typographie titre section', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-reviews__section-title',
        ]);

        $this->add_control('section_title_color', [
            'label'     => __('Couleur titre section', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-reviews__section-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-reviews__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-reviews__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('card_bg', [
            'label'     => __('Fond de la carte', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-reviews__card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .bt-reviews__card',
        ]);

        $this->add_responsive_control('card_radius', [
            'label'      => __('Border radius', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-reviews__card' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('card_padding', [
            'label'      => __('Padding', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '24', 'right' => '24', 'bottom' => '24', 'left' => '24', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-reviews__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .bt-reviews__card',
        ]);

        $this->end_controls_section();

        // ── Style — Texte ─────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('star_color', [
            'label'     => __('Couleur étoiles', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-reviews__stars' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('star_size', [
            'label'      => __('Taille étoiles', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 18, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-reviews__stars' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'review_text_typo',
            'label'    => __('Typographie avis', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-reviews__text',
        ]);

        $this->add_control('review_text_color', [
            'label'     => __('Couleur avis', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-reviews__text' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'name_typo',
            'label'    => __('Typographie prénom', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-reviews__name',
        ]);

        $this->add_control('name_color', [
            'label'     => __('Couleur prénom', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-reviews__name' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('quote_color', [
            'label'     => __('Couleur guillemet', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-reviews__quote' => 'color: {{VALUE}}'],
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

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_reviews_highlight');
        $rows       = get_field($field_name, $post_id);

        if (empty($rows)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun avis dans le champ <code>' . esc_html($field_name) . '</code>.</p>';
            }
            return;
        }

        $max_show  = max(1, (int) ($s['max_reviews'] ?: 5));
        $rows      = array_slice($rows, 0, $max_show);
        $max_stars = max(1, (int) ($s['max_stars'] ?: 5));
        $star_on   = esc_html($s['star_filled'] ?: '★');
        $star_off  = esc_html($s['star_empty']  ?: '☆');
        $quote     = esc_html($s['quote_char']  ?: '"');
        $layout    = $s['layout'] ?: 'grid';
        $tag       = esc_attr($s['title_tag'] ?: 'h3');
        $wrap_cls  = $layout === 'list' ? 'bt-reviews__list' : 'bt-reviews__grid';

        echo '<div class="bt-reviews">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-reviews__section-title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        echo "<div class=\"{$wrap_cls}\">";

        foreach ($rows as $row) {
            $name   = $row['rev_name']   ?? '';
            $rating = (int) ($row['rev_rating'] ?? 0);
            $text   = $row['rev_text']   ?? '';

            echo '<article class="bt-reviews__card">';

            if ($s['show_stars'] === 'yes' && $rating > 0) {
                echo '<div class="bt-reviews__stars" aria-label="' . esc_attr($rating . '/' . $max_stars) . '">';
                for ($i = 1; $i <= $max_stars; $i++) {
                    echo $i <= $rating ? $star_on : $star_off;
                }
                echo '</div>';
            }

            if ($s['show_text'] === 'yes' && $text) {
                echo '<blockquote class="bt-reviews__text">';
                echo '<span class="bt-reviews__quote" aria-hidden="true">' . $quote . '</span>';
                echo esc_html($text);
                echo '</blockquote>';
            }

            if ($s['show_name'] === 'yes' && $name) {
                echo '<footer class="bt-reviews__name">— ' . esc_html($name) . '</footer>';
            }

            echo '</article>';
        }

        echo '</div>'; // grid/list
        echo '</div>'; // .bt-reviews

        // Schema.org
        if ($s['schema_reviews'] === 'yes' && !is_admin()) {
            $this->render_schema($rows, $s, $post_id, $max_stars);
        }
    }

    // ── Schema ────────────────────────────────────────────────────────────────

    private function render_schema(array $rows, array $s, int $post_id, int $max_stars): void {
        if (empty($rows)) return;

        $ratings = array_filter(array_map(fn($r) => (float) ($r['rev_rating'] ?? 0), $rows));
        if (empty($ratings)) return;

        $avg   = round(array_sum($ratings) / count($ratings), 1);
        $count = count($ratings);
        $name  = !empty($s['schema_item_name']) ? $s['schema_item_name'] : get_the_title($post_id);
        $type  = $s['schema_item_type'] ?: 'TouristTrip';

        $schema = [
            '@context'        => 'https://schema.org',
            '@type'           => $type,
            'name'            => $name,
            'aggregateRating' => [
                '@type'       => 'AggregateRating',
                'ratingValue' => $avg,
                'reviewCount' => $count,
                'bestRating'  => $max_stars,
                'worstRating' => 1,
            ],
            'review' => [],
        ];

        foreach ($rows as $row) {
            $r = (int) ($row['rev_rating'] ?? 0);
            $t = $row['rev_text']   ?? '';
            $n = $row['rev_name']   ?? '';
            if (!$r || !$t) continue;
            $schema['review'][] = [
                '@type'        => 'Review',
                'author'       => ['@type' => 'Person', 'name' => $n],
                'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $r, 'bestRating' => $max_stars],
                'reviewBody'   => $t,
            ];
        }

        if (empty($schema['review'])) unset($schema['review']);

        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
