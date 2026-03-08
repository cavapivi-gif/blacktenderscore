<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Galerie photos.
 *
 * Lit un champ ACF de type gallery (`boat_gallery` ou `exp_gallery`)
 * et affiche les images en grille avec lightbox Elementor natif.
 */
class Gallery extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-gallery'; }
    public function get_title():      string { return 'BT — Galerie photos'; }
    public function get_icon():       string { return 'eicon-gallery-grid'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['galerie', 'photos', 'images', 'lightbox', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF gallery', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'boat_gallery' => __('Galerie bateau (boat_gallery)', 'bt-regiondo'),
                'exp_gallery'  => __('Galerie excursion (exp_gallery)', 'bt-regiondo'),
            ],
            'default' => 'boat_gallery',
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->add_control('max_images', [
            'label'       => __('Nombre max d\'images (0 = toutes)', 'bt-regiondo'),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'min'         => 0,
            'max'         => 100,
            'default'     => 0,
        ]);

        $this->add_control('thumb_size', [
            'label'   => __('Taille miniature', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'thumbnail' => 'Miniature (150px)',
                'medium'    => 'Moyenne (300px)',
                'large'     => 'Grande (1024px)',
                'full'      => 'Originale',
            ],
            'default' => 'medium',
        ]);

        $this->add_control('enable_lightbox', [
            'label'        => __('Lightbox Elementor', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Grille ────────────────────────────────────────────────────────
        $this->start_controls_section('section_grid', [
            'label' => __('Grille', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'bt-regiondo'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 8,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 2,
            'selectors'      => ['{{WRAPPER}} .bt-gallery__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_control('aspect_ratio', [
            'label'   => __('Ratio des images', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'square'   => __('Carré (1:1)', 'bt-regiondo'),
                'landscape' => __('Paysage (4:3)', 'bt-regiondo'),
                'wide'     => __('Cinémascope (16:9)', 'bt-regiondo'),
                'portrait' => __('Portrait (3:4)', 'bt-regiondo'),
                'auto'     => __('Auto (original)', 'bt-regiondo'),
            ],
            'default' => 'landscape',
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->start_controls_section('style_gallery', [
            'label' => __('Style', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'label'    => __('Typographie titre', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-gallery__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-gallery__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('gap', [
            'label'      => __('Espacement', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('border_radius', [
            'label'      => __('Border radius images', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__item img' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('overlay_color', [
            'label'     => __('Couleur overlay au survol', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-gallery__item::after' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('overlay_icon', [
            'label'   => __('Icône overlay survol', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '🔍',
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

        $field_name = $s['acf_field'] ?: 'boat_gallery';
        $images     = get_field($field_name, $post_id);

        if (empty($images)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucune image dans la galerie <code>' . esc_html($field_name) . '</code>.</p>';
            }
            return;
        }

        $max = (int) ($s['max_images'] ?: 0);
        if ($max > 0) {
            $images = array_slice($images, 0, $max);
        }

        $thumb_size   = $s['thumb_size'] ?: 'medium';
        $lightbox     = $s['enable_lightbox'] === 'yes';
        $ratio        = $s['aspect_ratio'] ?: 'landscape';
        $tag          = esc_attr($s['title_tag'] ?: 'h3');
        $group_id     = 'bt-gallery-' . $this->get_id();
        $overlay_icon = esc_html($s['overlay_icon'] ?: '🔍');

        $ratio_cls = match ($ratio) {
            'square'   => ' bt-gallery--square',
            'wide'     => ' bt-gallery--wide',
            'portrait' => ' bt-gallery--portrait',
            'auto'     => ' bt-gallery--auto',
            default    => ' bt-gallery--landscape',
        };

        echo '<div class="bt-gallery' . $ratio_cls . '">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-gallery__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        echo '<div class="bt-gallery__grid">';

        foreach ($images as $i => $img) {
            if (!is_array($img)) continue;

            $full_url  = $img['url']   ?? '';
            $thumb_url = $img['sizes'][$thumb_size] ?? $full_url;
            $alt       = $img['alt']   ?? ($img['title'] ?? '');
            $caption   = $img['caption'] ?? '';

            echo '<figure class="bt-gallery__item">';

            if ($lightbox && $full_url) {
                echo '<a href="' . esc_url($full_url) . '"'
                    . ' data-elementor-open-lightbox="yes"'
                    . ' data-elementor-lightbox-slideshow="' . esc_attr($group_id) . '"'
                    . ' data-elementor-lightbox-index="' . (int) $i . '"'
                    . ($caption ? ' data-elementor-lightbox-title="' . esc_attr($caption) . '"' : '')
                    . ' class="bt-gallery__link"'
                    . '>';
                echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt) . '" loading="lazy" class="bt-gallery__img" />';
                echo '<span class="bt-gallery__overlay" aria-hidden="true"><span class="bt-gallery__overlay-icon">' . $overlay_icon . '</span></span>';
                echo '</a>';
            } else {
                echo '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($alt) . '" loading="lazy" class="bt-gallery__img" />';
            }

            if ($caption) {
                echo '<figcaption class="bt-gallery__caption">' . esc_html($caption) . '</figcaption>';
            }

            echo '</figure>';
        }

        echo '</div>'; // .bt-gallery__grid
        echo '</div>'; // .bt-gallery
    }
}
