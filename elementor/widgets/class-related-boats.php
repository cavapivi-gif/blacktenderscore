<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Bateaux liés à une excursion.
 *
 * Lit le champ ACF `exp_boats` (relationship → boat) du post courant (excursion)
 * et affiche les cartes bateau correspondantes.
 */
class RelatedBoats extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-related-boats',
            'title'    => 'BT — Bateaux de l\'excursion',
            'icon'     => 'eicon-posts-grid',
            'keywords' => ['bateau', 'related', 'excursion', 'relation', 'bt'],
            'css'      => ['bt-related-boats'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'       => __('Champ ACF relationship', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exp_boats',
            'description' => __('Nom du champ ACF de type relationship pointant vers boat.', 'blacktenderscore'),
        ]);

        $this->register_section_title_controls(['title' => __('Bateaux utilisés', 'blacktenderscore')]);

        $this->end_controls_section();

        // ── Affichage ─────────────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage des cartes', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 4,
            'default'        => 2,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-relboats__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
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
            'selectors' => ['{{WRAPPER}} .bt-relboats__card--vertical .bt-relboats__img-wrap' => 'aspect-ratio: {{VALUE}}'],
            'condition' => ['card_direction' => 'vertical'],
        ]);

        $this->add_responsive_control('image_width', [
            'label'      => __('Largeur image (%)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 15, 'max' => 60]],
            'default'    => ['size' => 30],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__card--horizontal .bt-relboats__img-wrap' => 'width: {{SIZE}}%; min-width: {{SIZE}}%'],
            'condition'  => ['card_direction' => 'horizontal'],
        ]);

        $this->add_control('show_image', [
            'label'        => __('Afficher l\'image', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_tagline', [
            'label'        => __('Afficher l\'accroche (boat_tagline)', 'blacktenderscore'),
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

        $this->end_controls_section();

        // ── Specs affichées sur la carte ──────────────────────────────────
        $this->start_controls_section('section_specs', [
            'label' => __('Specs bateau', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_specs', [
            'label'        => __('Afficher les specs bateau', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('spec_pax_label', [
            'label'     => __('Icône passagers max', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '👥',
            'condition' => ['show_specs' => 'yes'],
        ]);

        $this->add_control('show_pax_comfort', [
            'label'        => __('Afficher passagers confort', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_specs' => 'yes'],
        ]);

        $this->add_control('spec_comfort_label', [
            'label'     => __('Icône passagers confort', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '🪑',
            'condition' => ['show_specs' => 'yes', 'show_pax_comfort' => 'yes'],
        ]);

        $this->add_control('show_cabins', [
            'label'        => __('Afficher cabines', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_specs' => 'yes'],
        ]);

        $this->add_control('spec_cabins_label', [
            'label'     => __('Icône cabines', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '🛏',
            'condition' => ['show_specs' => 'yes', 'show_cabins' => 'yes'],
        ]);

        $this->add_control('spec_engine_label', [
            'label'     => __('Icône motorisation', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '⚡',
            'condition' => ['show_specs' => 'yes'],
        ]);

        $this->add_control('show_year', [
            'label'        => __('Afficher l\'année', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_specs' => 'yes'],
        ]);

        $this->add_control('spec_year_label', [
            'label'     => __('Icône année', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => '📅',
            'condition' => ['show_specs' => 'yes', 'show_year' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Bouton lien ───────────────────────────────────────────────────
        $this->start_controls_section('section_link', [
            'label' => __('Bouton lien', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_link', [
            'label'        => __('Afficher le bouton lien', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('link_label', [
            'label'     => __('Texte du bouton', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Voir le bateau', 'blacktenderscore'),
            'condition' => ['show_link' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── STYLE ─────────────────────────────────────────────────────────

        $this->register_section_title_style('{{WRAPPER}} .bt-relboats__title');

        $this->start_controls_section('style_grid', [
            'label' => __('Style — Grille', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);
        $this->add_responsive_control('body_padding', [
            'label'      => __('Padding contenu carte', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-relboats__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);
        $this->end_controls_section();

        $this->register_box_style('card', 'Style — Cartes', '{{WRAPPER}} .bt-relboats__card');

        $this->register_typography_section(
            'card_title',
            'Style — Titre bateau',
            '{{WRAPPER}} .bt-relboats__card-title, {{WRAPPER}} .bt-relboats__card-title a',
            ['with_hover' => true]
        );

        $this->register_typography_section(
            'tagline',
            'Style — Accroche',
            '{{WRAPPER}} .bt-relboats__tagline',
            [],
            [],
            ['show_tagline' => 'yes']
        );

        $this->register_typography_section(
            'specs',
            'Style — Specs',
            '{{WRAPPER}} .bt-relboats__specs',
            [],
            [],
            ['show_specs' => 'yes']
        );

        $this->register_button_style(
            'btn',
            'Style — Bouton',
            '{{WRAPPER}} .bt-relboats__btn',
            [],
            ['show_link' => 'yes']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $field_name = sanitize_key($s['acf_field'] ?: 'exp_boats');
        $boats      = get_field($field_name, $post_id);

        if (empty($boats)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(sprintf(__('Aucun bateau lié trouvé via le champ « %s ». Vérifiez que des bateaux sont associés à cette excursion.', 'blacktenderscore'), $field_name));
            }
            return;
        }

        $boats = is_array($boats) ? $boats : [$boats];

        echo '<div class="bt-relboats">';

        $this->render_section_title($s, 'bt-relboats__title');

        echo '<div class="bt-relboats__grid">';

        foreach ($boats as $boat) {
            if (!($boat instanceof \WP_Post)) {
                $boat = get_post($boat);
            }
            if (!$boat) continue;

            $boat_url   = get_permalink($boat->ID);
            $boat_title = get_the_title($boat->ID);

            // Cover image
            $cover   = get_field('boat_cover', $boat->ID);
            $img_url = '';
            $img_alt = $boat_title;
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

            // ACF fields — batch load to avoid 6 × get_field() queries per boat
            $acf       = function_exists('get_fields') ? (get_fields($boat->ID) ?: []) : [];
            $tagline   = (string) ($acf['boat_tagline']     ?? '');
            $pax_max   = (string) ($acf['boat_pax_max']     ?? '');
            $pax_comf  = (string) ($acf['boat_pax_comfort'] ?? '');
            $cabins    = (string) ($acf['boat_cabins']      ?? '');
            $enginepow = (string) ($acf['boat_enginepower'] ?? '');
            $year      = (string) ($acf['boat_year']        ?? '');

            $dir = esc_attr($s['card_direction'] ?: 'vertical');
            echo '<div class="bt-relboats__card bt-relboats__card--' . $dir . '">';

            // Image
            if ($s['show_image'] === 'yes' && $img_url) {
                echo '<a href="' . esc_url($boat_url) . '" class="bt-relboats__img-wrap" tabindex="-1" aria-hidden="true">';
                echo '<img src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" loading="lazy" class="bt-relboats__img" />';
                echo '</a>';
            }

            echo '<div class="bt-relboats__body">';

            $title_tag = esc_attr($s['card_title_tag'] ?: 'h4');
            echo "<{$title_tag} class=\"bt-relboats__card-title\"><a href=\"" . esc_url($boat_url) . '">' . esc_html($boat_title) . "</a></{$title_tag}>";

            if ($s['show_tagline'] === 'yes' && $tagline) {
                echo '<p class="bt-relboats__tagline">' . esc_html($tagline) . '</p>';
            }

            if ($s['show_specs'] === 'yes') {
                $specs_html = '';
                if ($pax_max > 0) {
                    $specs_html .= '<li>' . esc_html($s['spec_pax_label'] ?: '👥') . ' ' . esc_html($pax_max) . ' pax</li>';
                }
                if ($s['show_pax_comfort'] === 'yes' && $pax_comf > 0) {
                    $specs_html .= '<li>' . esc_html($s['spec_comfort_label'] ?: '🪑') . ' ' . esc_html($pax_comf) . ' confort</li>';
                }
                if ($s['show_cabins'] === 'yes' && $cabins > 0) {
                    $specs_html .= '<li>' . esc_html($s['spec_cabins_label'] ?: '🛏') . ' ' . esc_html($cabins) . ' cab.</li>';
                }
                if ($enginepow > 0) {
                    $specs_html .= '<li>' . esc_html($s['spec_engine_label'] ?: '⚡') . ' ' . esc_html($enginepow) . ' CV</li>';
                }
                if ($s['show_year'] === 'yes' && $year) {
                    $specs_html .= '<li>' . esc_html($s['spec_year_label'] ?: '📅') . ' ' . esc_html($year) . '</li>';
                }
                if ($specs_html) {
                    echo '<ul class="bt-relboats__specs">' . $specs_html . '</ul>';
                }
            }

            if ($s['show_link'] === 'yes') {
                $lbl = $s['link_label'] ?: __('Voir le bateau', 'blacktenderscore');
                echo '<a href="' . esc_url($boat_url) . '" class="bt-relboats__btn">' . esc_html($lbl) . '</a>';
            }

            echo '</div>'; // .bt-relboats__body
            echo '</div>'; // .bt-relboats__card
        }

        echo '</div>'; // .bt-relboats__grid
        echo '</div>'; // .bt-relboats
    }
}
