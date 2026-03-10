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
 * Affiche N images en grille asymétrique (1 grande + petites).
 * La dernière image visible affiche un overlay "+X photos".
 * Un clic sur n'importe quelle image ouvre la lightbox Elementor native
 * avec TOUTES les images du champ ACF (y compris celles non affichées).
 *
 * Champ source : ACF gallery (boat_gallery ou exp_gallery).
 *
 * Layout :
 *   ┌──────────┬─────┬─────┐
 *   │          │  2  │  3  │
 *   │    1     ├─────┼─────┤
 *   │          │  4  │  5  │
 *   └──────────┴─────┴─────┘
 *   (sur mobile : 1 grande + 2 petites max)
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

    public function __construct($data = [], $args = null) {
        parent::__construct($data, $args);
        $this->selectors = [
            'grid'    => '{{WRAPPER}} .bt-gprev__grid',
            'item'    => '{{WRAPPER}} .bt-gprev__item',
            'img'     => '{{WRAPPER}} .bt-gprev__img',
            'overlay' => '{{WRAPPER}} .bt-gprev__overlay',
            'count'   => '{{WRAPPER}} .bt-gprev__count',
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
            'label'       => __('Images visibles', 'blacktenderscore'),
            'description' => __('Nombre d\'images affichées dans la grille (les autres restent accessibles via la lightbox).', 'blacktenderscore'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 1,
            'max'         => 9,
            'default'     => 5,
        ]);

        $this->add_control('thumb_size', [
            'label'   => __('Taille miniature', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'medium'      => 'Moyenne (300px)',
                'large'       => 'Grande (1024px)',
                'medium_large' => 'Moyen-grand (768px)',
                'full'        => 'Originale',
            ],
            'default' => 'large',
        ]);

        $this->add_control('enable_lightbox', [
            'label'        => __('Lightbox', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Overlay ───────────────────────────────────────────────────────
        $this->start_controls_section('section_overlay', [
            'label' => __('Overlay — Dernière image', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('overlay_mode', [
            'label'   => __('Contenu overlay', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'count'  => __('Nombre seulement  (+12)', 'blacktenderscore'),
                'photos' => __('Nombre + "photos"  (+12 photos)', 'blacktenderscore'),
                'custom' => __('Texte personnalisé', 'blacktenderscore'),
                'none'   => __('Aucun texte', 'blacktenderscore'),
            ],
            'default' => 'photos',
        ]);

        $this->add_control('overlay_text', [
            'label'       => __('Texte personnalisé', 'blacktenderscore'),
            'description' => __('Utilisez {n} pour le nombre de photos restantes. Ex: «Voir les {n} photos»', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Voir les {n} photos', 'blacktenderscore'),
            'condition'   => ['overlay_mode' => 'custom'],
            'dynamic'     => ['active' => false],
        ]);

        $this->add_control('overlay_show_always', [
            'label'        => __('Toujours afficher l\'overlay', 'blacktenderscore'),
            'description'  => __('Afficher même si toutes les images sont visibles.', 'blacktenderscore'),
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
            'selectors'  => [$this->sel('grid') => 'gap: {{SIZE}}{{UNIT}}'],
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
            'selectors'  => [$this->sel('grid') => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('grid_radius', [
            'label'      => __('Border radius (grille)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => [$this->sel('grid') => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_responsive_control('img_radius', [
            'label'      => __('Border radius (images individuelles)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => [$this->sel('item') => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->end_controls_section();

        // ── Style — Overlay ───────────────────────────────────────────────
        $this->start_controls_section('style_overlay', [
            'label'     => __('Style — Overlay', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['overlay_mode!' => 'none'],
        ]);

        $this->add_control('overlay_bg', [
            'label'     => __('Couleur de fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,0.45)',
            'selectors' => [$this->sel('overlay') => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'count_typography',
            'label'    => __('Typographie compteur', 'blacktenderscore'),
            'selector' => $this->sel('count'),
        ]);

        $this->add_control('count_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => [$this->sel('count') => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
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
        $remaining   = count($hidden); // images hors grille

        $lightbox   = ($s['enable_lightbox'] ?? '') === 'yes';
        $group_id   = 'bt-gprev-' . $this->get_id();
        $thumb_size = $s['thumb_size'] ?: 'large';

        $overlay_mode  = $s['overlay_mode'] ?? 'photos';
        $show_always   = ($s['overlay_show_always'] ?? '') === 'yes';
        $blur          = ($s['overlay_blur'] ?? '') === 'yes';
        $count_visible = count($visible);

        // Faut-il afficher l'overlay sur la dernière image ?
        $show_overlay = ($overlay_mode !== 'none') && ($remaining > 0 || $show_always);

        echo '<div class="bt-gprev">';
        echo '<div class="bt-gprev__grid bt-gprev__grid--' . (int) $count_visible . '">';

        foreach ($visible as $i => $img) {
            if (!is_array($img)) continue;

            $is_last   = ($i === $count_visible - 1);
            $has_over  = $is_last && $show_overlay;

            $full_url  = $img['url'] ?? '';
            $thumb_url = $img['sizes'][$thumb_size] ?? ($img['sizes']['large'] ?? $full_url);
            $alt       = esc_attr($img['alt'] ?? ($img['title'] ?? ''));

            $item_cls = 'bt-gprev__item';
            if ($i === 0)    $item_cls .= ' bt-gprev__item--main';
            if ($has_over)   $item_cls .= ' bt-gprev__item--last';
            if ($has_over && $blur) $item_cls .= ' bt-gprev__item--blur';

            echo '<figure class="' . esc_attr($item_cls) . '">';

            // Lien lightbox
            if ($lightbox && $full_url) {
                // Dernière image avec overlay → ouvre la lightbox depuis le début (voir tout)
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

            echo '<img src="' . esc_url($thumb_url) . '" alt="' . $alt . '" loading="' . ($i === 0 ? 'eager' : 'lazy') . '" class="bt-gprev__img" />';

            if ($has_over) {
                echo '<span class="bt-gprev__overlay" aria-hidden="true">';
                $text = $this->build_overlay_text($overlay_mode, $s['overlay_text'] ?? '', $remaining);
                if ($text !== '') {
                    echo '<span class="bt-gprev__count">' . esc_html($text) . '</span>';
                }
                echo '</span>';
            }

            echo $lightbox ? '</a>' : '</span>';
            echo '</figure>';
        }

        echo '</div>'; // .bt-gprev__grid

        // ── Éléments cachés pour la lightbox (images hors grille) ─────────
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
