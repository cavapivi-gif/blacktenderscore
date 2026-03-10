<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Galerie photos.
 *
 * Lit un champ ACF de type gallery (`boat_gallery` ou `exp_gallery`)
 * et affiche les images en grille avec lightbox Elementor natif.
 */
class Gallery extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-gallery',
            'title'    => 'BT — Galerie photos',
            'icon'     => 'eicon-gallery-grid',
            'keywords' => ['galerie', 'photos', 'images', 'lightbox', 'bt'],
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
            'label'   => __('Champ ACF gallery', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'boat_gallery' => __('Galerie bateau (boat_gallery)', 'blacktenderscore'),
                'exp_gallery'  => __('Galerie excursion (exp_gallery)', 'blacktenderscore'),
            ],
            'default' => 'boat_gallery',
        ]);

        $this->register_section_title_controls();

        $this->add_control('max_images', [
            'label'       => __('Nombre max d\'images (0 = toutes)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'min'         => 0,
            'max'         => 100,
            'default'     => 0,
        ]);

        $this->add_control('thumb_size', [
            'label'   => __('Taille miniature', 'blacktenderscore'),
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
            'label'        => __('Lightbox Elementor', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Grille ────────────────────────────────────────────────────────
        $this->start_controls_section('section_grid', [
            'label' => __('Grille', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 8,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 2,
            'selectors'      => ['{{WRAPPER}} .bt-gallery__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_control('aspect_ratio', [
            'label'   => __('Ratio des images', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'square'   => __('Carré (1:1)', 'blacktenderscore'),
                'landscape' => __('Paysage (4:3)', 'blacktenderscore'),
                'wide'     => __('Cinémascope (16:9)', 'blacktenderscore'),
                'portrait' => __('Portrait (3:4)', 'blacktenderscore'),
                'auto'     => __('Auto (original)', 'blacktenderscore'),
            ],
            'default' => 'landscape',
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->start_controls_section('style_gallery', [
            'label' => __('Style', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typo',
            'label'    => __('Typographie titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-gallery__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-gallery__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('border_radius', [
            'label'      => __('Border radius images', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__item img' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('overlay_color', [
            'label'     => __('Couleur overlay au survol', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-gallery__item::after' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('overlay_icon', [
            'label'   => __('Icône overlay survol', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '🔍',
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $field_name = $s['acf_field'] ?: 'boat_gallery';
        $images = $this->get_acf_rows($field_name, __('Aucune image dans la galerie indiquée.', 'blacktenderscore'));
        if (!$images) return;

        $max = (int) ($s['max_images'] ?: 0);
        if ($max > 0) {
            $images = array_slice($images, 0, $max);
        }

        $thumb_size   = $s['thumb_size'] ?: 'medium';
        $lightbox     = $s['enable_lightbox'] === 'yes';
        $ratio        = $s['aspect_ratio'] ?: 'landscape';
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

        $this->render_section_title($s, 'bt-gallery__title');

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
