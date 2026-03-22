<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Galerie photos.
 *
 * Deux dispositions :
 *   airbnb — 1 grande image (colonne principale) + vignettes en grille 2×N
 *   grid   — grille libre N colonnes
 *
 * Toutes les images (y compris au-delà de max_visible) sont injectées dans
 * le slideshow Elementor : la lightbox affiche la galerie complète.
 *
 * Overlay "+N photos" sur la dernière vignette visible, avec option flou.
 * Bouton "Voir toutes les photos" positionnable sur l'image principale.
 * Zoom au survol sans emoji — design épuré, pas d'icône kitch.
 *
 * ── Layout Airbnb (5 images, col_ratio = 2fr 1fr 1fr) ──────────────────
 *   ┌─────────────┬──────┬──────┐
 *   │             │  2   │  3   │
 *   │      1      ├──────┼──────┤
 *   │   [btn]     │  4   │ 5+x  │
 *   └─────────────┴──────┴──────┘
 */
class Gallery extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-gallery',
            'title'    => 'BT — Galerie photos',
            'icon'     => 'eicon-gallery-grid',
            'keywords' => ['galerie', 'photos', 'images', 'lightbox', 'airbnb', 'bt'],
            'css'      => ['bt-gallery', 'bt-lightbox'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

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

        $this->register_section_title_controls();

        $this->add_control('layout', [
            'label'     => __('Disposition', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'airbnb' => ['title' => __('Airbnb (hero + vignettes)', 'blacktenderscore'), 'icon' => 'eicon-image'],
                'grid'   => ['title' => __('Grille libre', 'blacktenderscore'),               'icon' => 'eicon-gallery-grid'],
            ],
            'default'   => 'airbnb',
            'toggle'    => false,
            'separator' => 'before',
        ]);

        $this->add_control('max_visible', [
            'label'       => __('Images affichées dans la grille', 'blacktenderscore'),
            'description' => __('Les images supplémentaires restent accessibles via la lightbox.', 'blacktenderscore'),
            'type'        => Controls_Manager::NUMBER,
            'min'         => 1,
            'max'         => 20,
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

        $this->add_control('show_caption', [
            'label'        => __('Afficher les légendes', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();

        // ── Grille libre [condition: grid] ─────────────────────────────────
        $this->start_controls_section('section_grid_layout', [
            'label'     => __('Mise en page — Grille', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['layout' => 'grid'],
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SELECT,
            'options'        => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
            'default'        => '3',
            'tablet_default' => '2',
            'mobile_default' => '2',
            'selectors'      => ['{{WRAPPER}} .bt-gallery__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
        ]);

        $this->add_control('aspect_ratio', [
            'label'   => __('Ratio des images', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'square'    => __('Carré (1:1)', 'blacktenderscore'),
                'landscape' => __('Paysage (4:3)', 'blacktenderscore'),
                'wide'      => __('Cinémascope (16:9)', 'blacktenderscore'),
                'portrait'  => __('Portrait (3:4)', 'blacktenderscore'),
                'auto'      => __('Auto (original)', 'blacktenderscore'),
            ],
            'default' => 'landscape',
        ]);

        $this->end_controls_section();

        // ── Overlay — Dernière image ───────────────────────────────────────
        $this->start_controls_section('section_overlay', [
            'label' => __('Overlay — Dernière image', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('overlay_mode', [
            'label'   => __('Affichage', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'photos' => __('+N photos', 'blacktenderscore'),
                'count'  => __('+N (chiffre seul)', 'blacktenderscore'),
                'custom' => __('Texte personnalisé', 'blacktenderscore'),
                'none'   => __('Aucun overlay', 'blacktenderscore'),
            ],
            'default' => 'photos',
        ]);

        $this->add_control('overlay_text', [
            'label'       => __('Texte personnalisé', 'blacktenderscore'),
            'description' => __('Utilisez {n} pour le nombre de photos cachées.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Voir les {n} photos', 'blacktenderscore'),
            'condition'   => ['overlay_mode' => 'custom'],
        ]);

        $this->add_control('overlay_show_always', [
            'label'        => __('Toujours afficher l\'overlay', 'blacktenderscore'),
            'description'  => __('Même si toutes les images sont visibles.', 'blacktenderscore'),
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
            'condition'    => ['overlay_mode!' => 'none'],
        ]);

        $this->add_responsive_control('overlay_blur_intensity', [
            'label'      => __('Intensité du flou', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20, 'step' => 1]],
            'default'    => ['size' => 4, 'unit' => 'px'],
            'condition'  => ['overlay_mode!' => 'none', 'overlay_blur' => 'yes'],
            'selectors'  => [
                '{{WRAPPER}} .bt-gallery__item--last .bt-gallery__img' => 'filter: blur({{SIZE}}{{UNIT}}); transform: scale(1.06)',
            ],
        ]);

        $this->end_controls_section();

        // ── Bouton "Voir toutes les photos" [airbnb] ───────────────────────
        $this->start_controls_section('section_allphotos_btn', [
            'label'     => __('Bouton — Voir toutes les photos', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['layout' => 'airbnb'],
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
            'label'   => __('Position', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'bottom-left'   => __('Bas gauche', 'blacktenderscore'),
                'bottom-right'  => __('Bas droite', 'blacktenderscore'),
                'bottom-center' => __('Bas centre', 'blacktenderscore'),
            ],
            'default'   => 'bottom-right',
            'condition' => ['show_allphotos_btn' => 'yes'],
        ]);

        $this->end_controls_section();

        // ══ STYLE TAB ═════════════════════════════════════════════════════════

        $this->register_section_title_style('{{WRAPPER}} .bt-gallery__title');

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
            'selectors'  => ['{{WRAPPER}} .bt-gallery__grid' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('grid_height', [
            'label'      => __('Hauteur de la grille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'vh'],
            'range'      => ['px' => ['min' => 100, 'max' => 800], 'vh' => ['min' => 10, 'max' => 100]],
            'default'    => ['size' => 420, 'unit' => 'px'],
            'condition'  => ['layout' => 'airbnb'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__grid' => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('col_ratio', [
            'label'     => __('Proportion colonne principale', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                '1fr 1fr 1fr' => __('Égal — 1/3 · 1/3 · 1/3', 'blacktenderscore'),
                '2fr 1fr 1fr' => __('Standard Airbnb — 50% · 25% · 25%', 'blacktenderscore'),
                '3fr 2fr 2fr' => __('Grande principale — 43% · 29% · 29%', 'blacktenderscore'),
                '3fr 1fr 1fr' => __('Très grande — 60% · 20% · 20%', 'blacktenderscore'),
            ],
            'default'   => '2fr 1fr 1fr',
            'condition' => ['layout' => 'airbnb'],
            'selectors' => ['{{WRAPPER}} .bt-gallery__grid' => 'grid-template-columns: {{VALUE}}'],
        ]);

        $this->add_responsive_control('grid_radius', [
            'label'      => __('Border radius global', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__grid' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_responsive_control('img_radius', [
            'label'      => __('Border radius images individuelles', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-gallery__item' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_control('img_fit', [
            'label'     => __('Remplissage des images', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'cover'   => __('Couverture — rogne pour remplir (cover)', 'blacktenderscore'),
                'contain' => __('Contenu — affiche tout (contain)', 'blacktenderscore'),
                'fill'    => __('Étirement (fill)', 'blacktenderscore'),
            ],
            'default'   => 'cover',
            'selectors' => ['{{WRAPPER}} .bt-gallery__img' => 'object-fit: {{VALUE}}'],
        ]);

        $this->add_control('img_position', [
            'label'     => __('Point focal des images', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'center center' => __('Centre', 'blacktenderscore'),
                'center top'    => __('Haut', 'blacktenderscore'),
                'center bottom' => __('Bas', 'blacktenderscore'),
                'left center'   => __('Gauche', 'blacktenderscore'),
                'right center'  => __('Droite', 'blacktenderscore'),
            ],
            'default'   => 'center center',
            'condition' => ['img_fit' => 'cover'],
            'selectors' => ['{{WRAPPER}} .bt-gallery__img' => 'object-position: {{VALUE}}'],
        ]);

        $this->add_control('hover_zoom', [
            'label'        => __('Zoom au survol', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
        ]);

        $this->add_responsive_control('hover_zoom_scale', [
            'label'     => __('Intensité du zoom (%)', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 100, 'max' => 130, 'step' => 1]],
            'default'   => ['size' => 104, 'unit' => 'px'],
            'condition' => ['hover_zoom' => 'yes'],
            'selectors' => [
                '{{WRAPPER}} .bt-gallery--zoom .bt-gallery__link:hover .bt-gallery__img' => 'transform: scale({{SIZE}}%)',
            ],
        ]);

        $this->end_controls_section();

        // ── Style — Overlay (+N photos) ────────────────────────────────────
        $this->start_controls_section('style_overlay', [
            'label'     => __('Style — Overlay (+N photos)', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['overlay_mode!' => 'none'],
        ]);

        $this->add_control('overlay_bg', [
            'label'     => __('Couleur de fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => 'rgba(0,0,0,0.45)',
            'selectors' => ['{{WRAPPER}} .bt-gallery__overlay' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'count_typography',
            'label'    => __('Typographie compteur', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-gallery__count',
        ]);

        $this->add_control('count_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .bt-gallery__count' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Légende ────────────────────────────────────────────────
        $this->register_typography_section(
            'caption',
            __('Style — Légende', 'blacktenderscore'),
            '{{WRAPPER}} .bt-gallery__caption',
            [],
            [],
            ['show_caption' => 'yes']
        );

        // ── Style — Bouton "Voir toutes" ───────────────────────────────────
        $this->register_box_style(
            'allphotos',
            __('Style — Bouton voir toutes les photos', 'blacktenderscore'),
            '{{WRAPPER}} .bt-gallery__allphotos-btn',
            ['padding' => 10, 'radius' => 8],
            ['show_allphotos_btn' => 'yes', 'layout' => 'airbnb']
        );

        $this->register_typography_section(
            'allphotos_label',
            __('Style — Label bouton', 'blacktenderscore'),
            '{{WRAPPER}} .bt-gallery__allphotos-btn',
            [],
            [],
            ['show_allphotos_btn' => 'yes', 'layout' => 'airbnb']
        );
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        if (!$this->acf_required()) return;

        $s          = $this->get_settings_for_display();
        $field_name = $s['acf_field'] ?: 'boat_gallery';
        $all_images = $this->get_acf_rows($field_name, __('Aucune image dans la galerie indiquée.', 'blacktenderscore'));

        if (!$all_images) return;

        $max_visible = max(1, (int) ($s['max_visible'] ?? 5));
        $visible     = array_slice($all_images, 0, $max_visible);
        $remaining   = count($all_images) - count($visible);

        $layout     = $s['layout'] ?: 'airbnb';
        $lightbox   = ($s['enable_lightbox'] ?? 'yes') === 'yes';
        $group_id   = 'bt-gallery-' . $this->get_id();
        $thumb_size = $s['thumb_size'] ?: 'large';
        $hover_zoom = ($s['hover_zoom'] ?? 'yes') === 'yes';

        $overlay_mode = $s['overlay_mode'] ?? 'photos';
        $show_always  = ($s['overlay_show_always'] ?? '') === 'yes';
        $show_overlay = $overlay_mode !== 'none' && ($remaining > 0 || $show_always);
        $count_vis    = count($visible);

        $show_btn  = $layout === 'airbnb' && ($s['show_allphotos_btn'] ?? 'yes') === 'yes';
        $btn_label = esc_html($s['allphotos_label'] ?: __('Voir toutes les photos', 'blacktenderscore'));
        $btn_pos   = $s['allphotos_position'] ?? 'bottom-right';

        // Wrapper classes
        $wrap_cls = 'bt-gallery bt-gallery--' . esc_attr($layout);
        if ($hover_zoom) $wrap_cls .= ' bt-gallery--zoom';
        if ($layout === 'grid') {
            $wrap_cls .= ' bt-gallery--' . esc_attr($s['aspect_ratio'] ?? 'landscape');
        }

        // Build JSON image list for custom lightbox (all images, including hidden)
        $lb_data = [];
        if ($lightbox) {
            foreach ($all_images as $img) {
                if (!is_array($img) || empty($img['url'])) continue;
                $lb_data[] = [
                    'src'     => $img['url'],
                    'thumb'   => $img['sizes']['medium'] ?? ($img['sizes']['thumbnail'] ?? $img['url']),
                    'alt'     => $img['alt']     ?? ($img['title'] ?? ''),
                    'caption' => $img['caption'] ?? '',
                ];
            }
        }

        $data_attr = $lightbox
            ? ' data-bt-gallery="' . esc_attr($group_id) . '" data-bt-gallery-images="' . esc_attr(wp_json_encode($lb_data)) . '"'
            : '';

        echo '<div class="' . esc_attr($wrap_cls) . '"' . $data_attr . '>';

        $this->render_section_title($s, 'bt-gallery__title');

        echo '<div class="bt-gallery__grid">';

        foreach ($visible as $i => $img) {
            if (!is_array($img)) continue;

            $is_main  = ($i === 0);
            $is_last  = ($i === $count_vis - 1);
            $has_over = $is_last && $show_overlay;

            $full_url  = $img['url'] ?? '';
            $thumb_url = $img['sizes'][$thumb_size] ?? ($img['sizes']['large'] ?? $full_url);
            $alt       = esc_attr($img['alt'] ?? ($img['title'] ?? ''));
            $caption   = $img['caption'] ?? '';

            $item_cls = 'bt-gallery__item';
            if ($is_main)  $item_cls .= ' bt-gallery__item--main';
            if ($has_over) $item_cls .= ' bt-gallery__item--last';

            echo '<figure class="' . esc_attr($item_cls) . '">';

            if ($lightbox && $full_url) {
                echo '<a class="bt-gallery__link"'
                    . ' href="' . esc_url($full_url) . '"'
                    . ' data-bt-lb-index="' . (int) $i . '"'
                    . '>';
            } else {
                echo '<span class="bt-gallery__link">';
            }

            // Responsive images: srcset WP + sizes contextuel (basé sur le layout réel).
            // WP génère un sizes générique "768px" qui ignore la vraie taille affichée.
            $img_id = (int) ($img['ID'] ?? 0);
            $srcset = $img_id ? wp_get_attachment_image_srcset($img_id, $thumb_size) : '';
            $sizes  = $this->compute_sizes($is_main, $layout, $s);
            $img_w  = (int) ($img['sizes'][$thumb_size . '-width']  ?? ($img['width']  ?? 0));
            $img_h  = (int) ($img['sizes'][$thumb_size . '-height'] ?? ($img['height'] ?? 0));

            echo '<img'
                . ' src="' . esc_url($thumb_url) . '"'
                . ($srcset ? ' srcset="' . esc_attr($srcset) . '"' : '')
                . ($sizes  ? ' sizes="'  . esc_attr($sizes)  . '"' : '')
                . ($img_w  ? ' width="'  . $img_w . '"' : '')
                . ($img_h  ? ' height="' . $img_h . '"' : '')
                . ' alt="' . $alt . '"'
                . ' loading="' . ($is_main ? 'eager' : 'lazy') . '"'
                . ($is_main ? ' fetchpriority="high"' : ' decoding="async"')
                . ' class="bt-gallery__img"'
                . ' />';

            // Overlay "+N photos" sur la dernière vignette
            if ($has_over) {
                $text = $this->build_overlay_text($overlay_mode, $s['overlay_text'] ?? '', $remaining);
                echo '<span class="bt-gallery__overlay" aria-hidden="true">';
                if ($text !== '') {
                    echo '<span class="bt-gallery__count">' . esc_html($text) . '</span>';
                }
                echo '</span>';
            }

            // Bouton "Voir toutes les photos" sur l'image principale (airbnb uniquement)
            if ($is_main && $show_btn) {
                $lb_open = $lightbox ? ' data-bt-lb-open' : '';
                echo '<a class="bt-gallery__allphotos-btn bt-gallery__allphotos-btn--' . esc_attr($btn_pos) . '" href="#"' . $lb_open . '>';
                echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg> ';
                echo $btn_label;
                echo '</a>';
            }

            echo $lightbox ? '</a>' : '</span>';

            if (($s['show_caption'] ?? '') === 'yes' && $caption) {
                echo '<figcaption class="bt-gallery__caption">' . esc_html($caption) . '</figcaption>';
            }

            echo '</figure>';
        }

        echo '</div>'; // .bt-gallery__grid
        echo '</div>'; // .bt-gallery
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Calcule l'attribut sizes en fonction du layout réel de la galerie.
     *
     * WP génère un sizes générique ("(max-width: 768px) 100vw, 768px") qui ne tient
     * pas compte de la disposition : les vignettes airbnb (~25vw) téléchargent
     * inutilement l'image 768px. Cette méthode corrige ça.
     *
     * @param bool   $is_main  Vrai pour l'image principale (airbnb).
     * @param string $layout   'airbnb' | 'grid'.
     * @param array  $s        Settings Elementor du widget.
     */
    private function compute_sizes(bool $is_main, string $layout, array $s): string {
        if ($layout === 'airbnb') {
            // Extrait les valeurs fr de la chaîne "2fr 1fr 1fr"
            preg_match_all('/(\d+)fr/', $s['col_ratio'] ?? '2fr 1fr 1fr', $m);
            $fractions = array_map('intval', $m[1] ?? [2, 1, 1]);
            $total     = max(1, array_sum($fractions));

            $pct = $is_main
                ? (int) round($fractions[0] / $total * 100)                    // ex: 2/4 = 50vw
                : (int) round(($fractions[1] ?? 1) / $total * 100);            // ex: 1/4 = 25vw

            // Mobile : toute la largeur ; desktop : fraction réelle
            return "(max-width: 767px) 100vw, {$pct}vw";
        }

        // Layout grid : on tient compte des colonnes responsive
        $cols_d = max(1, (int) ($s['columns']        ?? 3));
        $cols_t = max(1, (int) ($s['columns_tablet']  ?? 2));
        $cols_m = max(1, (int) ($s['columns_mobile']  ?? 1));

        $pct_d = (int) round(100 / $cols_d);
        $pct_t = (int) round(100 / $cols_t);
        $pct_m = (int) round(100 / $cols_m);

        return "(max-width: 480px) {$pct_m}vw, (max-width: 1024px) {$pct_t}vw, {$pct_d}vw";
    }

    private function build_overlay_text(string $mode, string $custom, int $remaining): string {
        return match ($mode) {
            'count'  => '+' . $remaining,
            'photos' => '+' . $remaining . ' ' . _n('photo', 'photos', $remaining, 'blacktenderscore'),
            'custom' => str_replace('{n}', (string) $remaining, $custom),
            default  => '',
        };
    }
}
