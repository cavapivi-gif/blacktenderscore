<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Avis clients.
 *
 * Lit le repeater ACF `exp_reviews_highlight` (rev_name, rev_rating, rev_text,
 * optionnellement rev_date et rev_source) et affiche les cartes avis.
 * Injecte optionnellement le schema.org AggregateRating + Review en JSON-LD.
 */
class Reviews extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-reviews',
            'title'    => 'BT — Avis clients',
            'icon'     => 'eicon-rating',
            'keywords' => ['avis', 'reviews', 'rating', 'étoiles', 'bt'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'exp_reviews_highlight',
        ]);

        $this->register_section_title_controls(['title' => __('Avis clients', 'blacktenderscore')]);

        $this->add_control('max_reviews', [
            'label'   => __('Nombre max d\'avis', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 20,
            'default' => 5,
        ]);

        $this->end_controls_section();

        // ── Affichage ─────────────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['grid' => __('Grille', 'blacktenderscore'), 'list' => __('Liste', 'blacktenderscore')],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 4,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-reviews__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition'      => ['layout' => 'grid'],
        ]);

        $this->add_control('rating_display', [
            'label'   => __('Affichage de la note', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'stars'  => __('Étoiles', 'blacktenderscore'),
                'number' => __('Note numérique', 'blacktenderscore'),
                'both'   => __('Étoiles + note', 'blacktenderscore'),
                'none'   => __('Aucun', 'blacktenderscore'),
            ],
            'default' => 'stars',
        ]);

        $this->add_control('star_filled', [
            'label'     => __('Étoile pleine', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '★',
            'condition' => ['rating_display' => ['stars', 'both']],
        ]);

        $this->add_control('star_empty', [
            'label'     => __('Étoile vide', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '☆',
            'condition' => ['rating_display' => ['stars', 'both']],
        ]);

        $this->add_control('max_stars', [
            'label'     => __('Note max', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 3,
            'max'       => 10,
            'default'   => 5,
            'condition' => ['rating_display!' => 'none'],
        ]);

        $this->add_control('show_name', [
            'label'        => __('Afficher le prénom', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_text', [
            'label'        => __('Afficher le texte de l\'avis', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('quote_char', [
            'label'     => __('Caractère de citation', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '"',
            'condition' => ['show_text' => 'yes'],
        ]);

        $this->add_control('show_date', [
            'label'        => __('Afficher la date', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('date_subfield', [
            'label'     => __('Sous-champ date', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'rev_date',
            'condition' => ['show_date' => 'yes'],
        ]);

        $this->add_control('show_source', [
            'label'        => __('Afficher la source (Google, Tripadvisor…)', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('source_subfield', [
            'label'     => __('Sous-champ source', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'rev_source',
            'condition' => ['show_source' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Schema.org ────────────────────────────────────────────────────
        $this->start_controls_section('section_schema', [
            'label' => __('Schema.org', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('schema_reviews', [
            'label'        => __('Injecter le schema AggregateRating (SEO)', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'description'  => __('Injecte un JSON-LD AggregateRating + Review calculé depuis les avis ACF.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('schema_item_type', [
            'label'     => __('@type de l\'entité', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'Product'       => 'Product',
                'LocalBusiness' => 'LocalBusiness',
                'TouristTrip'   => 'TouristTrip',
                'Service'       => 'Service',
            ],
            'default'   => 'TouristTrip',
            'condition' => ['schema_reviews' => 'yes'],
        ]);

        $this->add_control('schema_item_name', [
            'label'       => __('Nom de l\'entité (vide = titre du post)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Laissez vide pour utiliser automatiquement le titre du post.', 'blacktenderscore'),
            'condition'   => ['schema_reviews' => 'yes'],
            'dynamic'     => ['active' => true],
        ]);

        $this->end_controls_section();

        // ── STYLE ─────────────────────────────────────────────────────────

        $this->register_section_title_style('{{WRAPPER}} .bt-reviews__section-title');

        $this->start_controls_section('style_layout', [
            'label' => __('Style — Espacement', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);
        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement entre cartes', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-reviews__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-reviews__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);
        $this->end_controls_section();

        $this->register_box_style('card', 'Style — Cartes', '{{WRAPPER}} .bt-reviews__card', ['padding' => 24]);

        $this->register_stars_style(
            'stars',
            'Style — Étoiles',
            '{{WRAPPER}} .bt-reviews__stars',
            ['size' => 18],
            ['rating_display' => ['stars', 'both']]
        );

        $this->register_typography_section(
            'review_text',
            'Style — Texte de l\'avis',
            '{{WRAPPER}} .bt-reviews__text',
            [],
            [],
            ['show_text' => 'yes']
        );

        $this->register_typography_section(
            'name',
            'Style — Prénom',
            '{{WRAPPER}} .bt-reviews__name',
            [],
            [],
            ['show_name' => 'yes']
        );

        $this->register_typography_section(
            'quote',
            'Style — Guillemet',
            '{{WRAPPER}} .bt-reviews__quote',
            [],
            [],
            ['show_text' => 'yes']
        );

        $this->register_typography_section(
            'meta',
            'Style — Date & source',
            '{{WRAPPER}} .bt-reviews__meta'
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $field_name  = sanitize_text_field($s['acf_field'] ?: 'exp_reviews_highlight');
        $rows        = $this->get_acf_rows($field_name, __('Aucun avis dans le champ indiqué.', 'blacktenderscore'));
        if (!$rows) return;

        $max_show    = max(1, (int) ($s['max_reviews'] ?: 5));
        $rows        = array_slice($rows, 0, $max_show);
        $max_stars   = max(1, (int) ($s['max_stars'] ?: 5));
        $star_on     = esc_html($s['star_filled'] ?: '★');
        $star_off    = esc_html($s['star_empty']  ?: '☆');
        $quote       = esc_html($s['quote_char']  ?: '"');
        $layout      = $s['layout'] ?: 'grid';
        $wrap_cls    = $layout === 'list' ? 'bt-reviews__list' : 'bt-reviews__grid';
        $rating_mode = $s['rating_display'] ?: 'stars';
        $date_sf     = sanitize_text_field($s['date_subfield']   ?: 'rev_date');
        $source_sf   = sanitize_text_field($s['source_subfield'] ?: 'rev_source');

        echo '<div class="bt-reviews">';

        $this->render_section_title($s, 'bt-reviews__section-title');

        echo "<div class=\"{$wrap_cls}\">";

        foreach ($rows as $row) {
            $name   = $row['rev_name']   ?? '';
            $rating = (int) ($row['rev_rating'] ?? 0);
            $text   = $row['rev_text']   ?? '';
            $date   = $row[$date_sf]     ?? '';
            $source = $row[$source_sf]   ?? '';

            echo '<article class="bt-reviews__card">';

            // Note / étoiles
            if ($rating_mode !== 'none' && $rating > 0) {
                echo '<div class="bt-reviews__stars" aria-label="' . esc_attr($rating . '/' . $max_stars) . '">';
                if ($rating_mode === 'stars' || $rating_mode === 'both') {
                    for ($i = 1; $i <= $max_stars; $i++) {
                        $cls = $i <= $rating ? 'bt-star bt-star--filled' : 'bt-star bt-star--empty';
                        echo '<span class="' . $cls . '">' . ($i <= $rating ? $star_on : $star_off) . '</span>';
                    }
                }
                if ($rating_mode === 'number' || $rating_mode === 'both') {
                    echo '<span class="bt-reviews__rating-number">' . esc_html($rating . '/' . $max_stars) . '</span>';
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

            $has_meta = ($s['show_date'] === 'yes' && $date) || ($s['show_source'] === 'yes' && $source);
            if ($has_meta) {
                echo '<div class="bt-reviews__meta">';
                if ($s['show_date'] === 'yes' && $date) {
                    echo '<span class="bt-reviews__date">' . esc_html($date) . '</span>';
                }
                if ($s['show_source'] === 'yes' && $source) {
                    echo '<span class="bt-reviews__source">' . esc_html($source) . '</span>';
                }
                echo '</div>';
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

        $ratings = array_filter(array_map(fn($r) => (float) ($r['rev_rating'] ?? 0), $rows), fn($v) => $v > 0);
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
