<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Galerie preview style Airbnb.
 *
 * Grille asymétrique : 1 grande image + vignettes.
 * Overlay "+X photos" sur la dernière vignette.
 * Bouton "Voir toutes les photos" en bas de l'image principale.
 * Lightbox Elementor native sur toutes les images (y compris cachées).
 *
 * Layout :
 *   ┌──────────┬─────┬─────┐
 *   │          │  2  │  3  │
 *   │    1     ├─────┼─────┤
 *   │  [btn]   │  4  │ 5+x │
 *   └──────────┴─────┴─────┘
 */
class GalleryPreview extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-gallery-preview',
            'title'    => 'BT — Galerie Preview',
            'icon'     => 'eicon-media-carousel',
            'keywords' => ['galerie', 'preview', 'photos', 'airbnb', 'images', 'lightbox', 'bt'],
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
            'label'   => __('Champ ACF galerie', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'boat_gallery' => __('Galerie bateau (boat_gallery)', 'blacktenderscore'),
                'exp_gallery'  => __('Galerie excursion (exp_gallery)', 'blacktenderscore'),
            ],
            'default' => 'boat_gallery',
        ]);

        $this->add_control('max_visible', [
            'label'       => __('Images visibles dans la grille', 'blacktenderscore'),
            'description' => __('Les autres sont accessibles via la lightbox.', 'blacktenderscore'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 1,
            'max'         => 9,
            'default'     => 5,
        ]);

        $this->add_control('thumb_size', [
            'label'   => __('Qualité des miniatures', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'medium'       => __('Moyenne (300px)', 'blacktenderscore'),
                'medium_large' => __('Moyen-grand (768px)', 'blacktenderscore'),
                'large'        => __('Grande (1024px)', 'blacktenderscore'),
                'full'         => __('Originale', 'blacktenderscore'),
            ],
            'default' => 'large',
        ]);

        $this->add_control('enable_lightbox', [
            'label'        => __('Activer la lightbox', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Overlay — Dernière vignette ────────────────────────────────────
        $this->start_controls_section('section_overlay', [
            'label' => __('Overlay — Dernière vignette', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('overlay_mode', [
            'label'   => __('Contenu', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'count'  => __('+12', 'blacktenderscore'),
                'photos' => __('+12 photos', 'blacktenderscore'),
                'custom' => __('Texte personnalisé', 'blacktenderscore'),
                'none'   => __('Aucun texte', 'blacktenderscore'),
            ],
            'default' => 'photos',
        ]);

        $this->add_control('overlay_text', [
            'label'       => __('Texte personnalisé', 'blacktenderscore'),
            'description' => __('Utilisez {n} pour le nombre de photos restantes.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Voir les {n} photos', 'blacktenderscore'),
            'condition'   => ['overlay_mode' => 'custom'],
            'dynamic'     => ['active' => false],
        ]);

        $this->add_control('overlay_show_always', [
            'label'        => __('Toujours afficher l\'overlay', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('overlay_blur', [
            'label'        => __('Flou sur la dernière image', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'separator'    => 'before',
        ]);

        $this->add_responsive_control('overlay_blur_intensity', [
            'label'      => __('Intensité du flou', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20, 'step' => 1]],
            'default'    => ['size' => 4, 'unit' => 'px'],
            'condition'  => ['overlay_blur' => 'yes'],
            'selectors'  => [
                '{{WRAPPER}} .bt-gprev__item--last .bt-gprev__img' => 'filter: blur({{SIZE}}{{UNIT}}); transform: scale(1.06)',
            ],
        ]);

        $this->end_controls_section();

        // ── Bouton "Voir toutes les photos" ────────────────────────────────
        $this->start_controls_section('section_allphotos_btn', [
            'label' => __('Bouton — Voir toutes les photos', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_allphotos_btn', [
            'label'        => __('Afficher le bouton', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('allphotos_label', [
            'label'     => __('Label', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Voir toutes les photos', 'blacktenderscore'),
            'condition' => ['show_allphotos_btn' => 'yes'],
        ]);

        $this->add_control('allphotos_position', [
            'label'     => __('Position', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'bottom-left'  => __('Bas gauche', 'blacktenderscore'),
                'bottom-right' => __('Bas droite', 'blacktenderscore'),
                'bottom-center'=> __('Bas centre', 'blacktenderscore'),
            ],
            'default'   => 'bottom-right',
            'condition' => ['show_allphotos_btn' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Grille ────────────────────────────────────────────────
        $this->start_controls_section('style_grid', [
            'label' => __('Style — Grille', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('grid_gap', [
            'label'      => __('Espacement entre images', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gprev__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('grid_height', [
            'label'      => __('Hauteur de la grille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range'      => [
                'px' => ['min' => 100, 'max' => 800],
                'vh' => ['min' => 10,  'max' => 100],
            ],
            'default'    => ['size' => 420, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gprev__grid' => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('main_col_ratio', [
            'label'     => __('Ratio colonne principale', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                '2fr 1fr 1fr' => __('2/3 – 1/3', 'blacktenderscore'),
                '3fr 2fr 2fr' => __('3/7 – 2/7', 'blacktenderscore'),
                '1fr 1fr 1fr' => __('Égal (1/3)', 'blacktenderscore'),
                '3fr 1fr 1fr' => __('3/5 – 1/5', 'blacktenderscore'),
            ],
            'default'   => '2fr 1fr 1fr',
            'selectors' => [
                '{{WRAPPER}} .bt-gprev__grid--3' => 'grid-template-columns: {{VALUE}} !important',
                '{{WRAPPER}} .bt-gprev__grid--4' => 'grid-template-columns: {{VALUE}} !important',
                '{{WRAPPER}} .bt-gprev__grid--5' => 'grid-template-columns: {{VALUE}} !important',
                '{{WRAPPER}} .bt-gprev__grid--6' => 'grid-template-columns: {{VALUE}} !important',
                '{{WRAPPER}} .bt-gprev__grid--7' => 'grid-template-columns: {{VALUE}} !important',
                '{{WRAPPER}} .bt-gprev__grid--8' => 'grid-template-columns: {{VALUE}} !important',
                '{{WRAPPER}} .bt-gprev__grid--9' => 'grid-template-columns: {{VALUE}} !important',
            ],
        ]);

        $this->add_responsive_control('grid_radius', [
            'label'      => __('Border radius global', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gprev__grid' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_responsive_control('img_radius', [
            'label'      => __('Border radius images individuelles', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gprev__item' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_control('img_position', [
            'label'     => __('Point focal des images', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'center center' => __('Centre', 'blacktenderscore'),
                'center top'    => __('Haut centre', 'blacktenderscore'),
                'center bottom' => __('Bas centre', 'blacktenderscore'),
                'left center'   => __('Gauche', 'blacktenderscore'),
                'right center'  => __('Droite', 'blacktenderscore'),
            ],
            'default'   => 'center center',
            'selectors' => ['{{WRAPPER}} .bt-gprev__img' => 'object-position: {{VALUE}}'],
        ]);

        $this->add_control('hover_zoom', [
            'label'        => __('Zoom au survol', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
        ]);

        $this->add_responsive_control('hover_zoom_scale', [
            'label'     => __('Intensité du zoom', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 100, 'max' => 130, 'step' => 1]],
            'default'   => ['size' => 104, 'unit' => 'px'],
            'condition' => ['hover_zoom' => 'yes'],
            'selectors' => [
                '{{WRAPPER}} .bt-gprev__link:hover .bt-gprev__img' => 'transform: scale({{SIZE}}%)',
            ],
        ]);

        $this->end_controls_section();

        // ── Style — Overlay ───────────────────────────────────────────────
        $this->start_controls_section('style_overlay', [
            'label'     => __('Style — Overlay dernière vignette', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['overlay_mode!' => 'none'],
        ]);

        $this->add_control('overlay_bg', [
            'label'     => __('Couleur de fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,0.45)',
            'selectors' => ['{{WRAPPER}} .bt-gprev__overlay' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'count_typography',
            'label'    => __('Typographie compteur', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-gprev__count',
        ]);

        $this->add_control('count_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .bt-gprev__count' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Bouton "Voir toutes" ──────────────────────────────────
        $this->register_box_style('allphotos', __('Style — Bouton voir toutes les photos', 'blacktenderscore'), '{{WRAPPER}} .bt-gprev__allphotos-btn', ['padding' => 10, 'radius' => 8], ['show_allphotos_btn' => 'yes']);
        $this->register_typography_section('allphotos_label', __('Style — Label bouton', 'blacktenderscore'), '{{WRAPPER}} .bt-gprev__allphotos-btn', [], [], ['show_allphotos_btn' => 'yes']);
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        if (!$this->acf_required()) return;

        $s          = $this->get_settings_for_display();
        $field_name = $s['acf_field'] ?: 'boat_gallery';
        $all_images = $this->get_acf_rows($field_name, __('Aucune image dans la galerie indiquée.', 'blacktenderscore'));

        if (!$all_images) return;

        $total       = count($all_images);
        $max_visible = max(1, (int) ($s['max_visible'] ?? 5));
        $visible     = array_slice($all_images, 0, $max_visible);
        $hidden      = array_slice($all_images, $max_visible);
        $remaining   = count($hidden);

        $lightbox   = ($s['enable_lightbox'] ?? '') === 'yes';
        $group_id   = 'bt-gprev-' . $this->get_id();
        $thumb_size = $s['thumb_size'] ?: 'large';

        $overlay_mode  = $s['overlay_mode'] ?? 'photos';
        $show_always   = ($s['overlay_show_always'] ?? '') === 'yes';
        $blur          = ($s['overlay_blur'] ?? '') === 'yes';
        $count_visible = count($visible);
        $show_overlay  = ($overlay_mode !== 'none') && ($remaining > 0 || $show_always);

        $show_btn    = ($s['show_allphotos_btn'] ?? '') === 'yes';
        $btn_label   = esc_html($s['allphotos_label'] ?: __('Voir toutes les photos', 'blacktenderscore'));
        $btn_pos     = $s['allphotos_position'] ?? 'bottom-right';
        $hover_zoom  = ($s['hover_zoom'] ?? 'yes') === 'yes';

        echo '<div class="bt-gprev">';
        echo '<div class="bt-gprev__grid bt-gprev__grid--' . (int) $count_visible . ($hover_zoom ? ' bt-gprev__grid--zoom' : '') . '">';

        foreach ($visible as $i => $img) {
            if (!is_array($img)) continue;

            $is_main  = ($i === 0);
            $is_last  = ($i === $count_visible - 1);
            $has_over = $is_last && $show_overlay;

            $full_url  = $img['url'] ?? '';
            $thumb_url = $img['sizes'][$thumb_size] ?? ($img['sizes']['large'] ?? $full_url);
            $alt       = esc_attr($img['alt'] ?? ($img['title'] ?? ''));

            $item_cls = 'bt-gprev__item';
            if ($is_main)  $item_cls .= ' bt-gprev__item--main';
            if ($has_over) $item_cls .= ' bt-gprev__item--last';
            if ($has_over && $blur) $item_cls .= ' bt-gprev__item--blur';

            echo '<figure class="' . esc_attr($item_cls) . '">';

            if ($lightbox && $full_url) {
                $lb_index = $has_over ? 0 : $i;
                echo '<a class="bt-gprev__link"'
                    . ' href="' . esc_url($full_url) . '"'
                    . ' data-elementor-open-lightbox="yes"'
                    . ' data-elementor-lightbox-slideshow="' . esc_attr($group_id) . '"'
                    . ' data-elementor-lightbox-index="' . (int) $lb_index . '"'
                    . '>';
            } else {
                echo '<span class="bt-gprev__link">';
            }

            echo '<img src="' . esc_url($thumb_url) . '" alt="' . $alt . '" loading="' . ($is_main ? 'eager' : 'lazy') . '" class="bt-gprev__img" />';

            if ($has_over) {
                $text = $this->build_overlay_text($overlay_mode, $s['overlay_text'] ?? '', $remaining + ($show_btn ? 0 : 0));
                echo '<span class="bt-gprev__overlay" aria-hidden="true">';
                if ($text !== '') {
                    echo '<span class="bt-gprev__count">' . esc_html($text) . '</span>';
                }
                echo '</span>';
            }

            // Bouton "Voir toutes les photos" sur l'image principale
            if ($is_main && $show_btn) {
                $lb_href = $lightbox && $full_url
                    ? ' href="' . esc_url($full_url) . '" data-elementor-open-lightbox="yes" data-elementor-lightbox-slideshow="' . esc_attr($group_id) . '" data-elementor-lightbox-index="0"'
                    : '';
                echo '<a class="bt-gprev__allphotos-btn bt-gprev__allphotos-btn--' . esc_attr($btn_pos) . '"' . $lb_href . '>';
                echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
                echo ' ' . $btn_label;
                echo '</a>';
            }

            echo $lightbox ? '</a>' : '</span>';
            echo '</figure>';
        }

        echo '</div>'; // .bt-gprev__grid

        // Éléments cachés pour la lightbox (hors grille)
        if ($lightbox && !empty($hidden)) {
            echo '<div class="bt-gprev__lightbox-hidden" aria-hidden="true" style="display:none">';
            foreach ($hidden as $j => $img) {
                if (!is_array($img) || empty($img['url'])) continue;
                echo '<a'
                    . ' href="' . esc_url($img['url']) . '"'
                    . ' data-elementor-open-lightbox="yes"'
                    . ' data-elementor-lightbox-slideshow="' . esc_attr($group_id) . '"'
                    . ' data-elementor-lightbox-index="' . (int) ($max_visible + $j) . '"'
                    . '></a>';
            }
            echo '</div>';
        }

        echo '</div>'; // .bt-gprev
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function build_overlay_text(string $mode, string $custom, int $remaining): string {
        return match ($mode) {
            'count'  => '+' . $remaining,
            'photos' => '+' . $remaining . ' ' . _n('photo', 'photos', $remaining, 'blacktenderscore'),
            'custom' => str_replace('{n}', $remaining, $custom),
            default  => '',
        };
    }
}
