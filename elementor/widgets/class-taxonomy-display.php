<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Affichage Taxonomie (texte + icône + description + layout).
 *
 * Choix du champ ACF, affichage titre / description / icône taxonomie,
 * séparateur (texte ou saut de ligne), disposition inline / liste / grille responsive.
 */
class TaxonomyDisplay extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-taxonomy-display',
            'title'    => 'BT — Affichage Taxonomie',
            'icon'     => 'eicon-tags',
            'keywords' => ['taxonomie', 'terme', 'acf', 'champ', 'bt'],
            'css'      => ['bt-taxonomy-display'],
        ];
    }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $opts = static::acf_field_options('', ['exp_included' => 'exp_included', 'exp_to_excluded' => 'exp_to_excluded']);
        $this->add_control('acf_field', [
            'label'   => __('Champ ACF', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => $opts,
            'default' => array_key_first($opts) ?: '',
        ]);

        $this->register_section_title_controls();

        $this->add_control('section_description', [
            'label'   => __('Description de section', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'rows'    => 3,
            'default' => '',
            'dynamic' => ['active' => true],
        ]);

        $this->register_collapsible_section_control();

        // ── Affichage ──────────────────────────────────────────────────────────
        $this->add_control('format_heading', [
            'label'     => __('Affichage', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_title', [
            'label'        => __('Titre du terme', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Description du terme', 'blacktenderscore'),
            'description'  => __('S\'affiche sous le titre de chaque terme.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('show_taxonomy_icons', [
            'label'        => __('Icône de la taxonomie', 'blacktenderscore'),
            'description'  => __('Affiche l\'icône du terme (taxomonies_icons / term_icon_class) si disponible.', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('fallback_icon', [
            'label'     => __('Icône de remplacement', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => '', 'library' => ''],
            'condition' => ['show_taxonomy_icons' => 'yes'],
        ]);

        // ── Disposition ────────────────────────────────────────────────────────
        $this->add_control('layout_heading', [
            'label'     => __('Disposition', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('layout', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'inline' => __('Inline (séparateur)', 'blacktenderscore'),
                'list'   => __('Liste verticale', 'blacktenderscore'),
                'grid'   => __('Grille', 'blacktenderscore'),
            ],
            'default' => 'inline',
        ]);

        $this->add_control('separator', [
            'label'     => __('Séparateur entre termes', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                ' · '    => '·  (point médian)',
                ' / '    => '/  (slash)',
                ', '     => ',  (virgule)',
                ' '      => __('Espace', 'blacktenderscore'),
                ' — '    => '—  (tiret long)',
                ' - '    => '-  (trait d\'union)',
                '__br__' => __('Saut de ligne', 'blacktenderscore'),
            ],
            'default'   => ' · ',
            'condition' => ['layout' => 'inline'],
        ]);

        $this->add_responsive_control('grid_columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px'],
            'range'          => ['px' => ['min' => 1, 'max' => 10, 'step' => 1]],
            'default'        => ['size' => 3, 'unit' => 'px'],
            'tablet_default' => ['size' => 2, 'unit' => 'px'],
            'mobile_default' => ['size' => 1, 'unit' => 'px'],
            'selectors'      => ['{{WRAPPER}} .bt-taxdisp__text--grid' => 'grid-template-columns: repeat({{SIZE}}, 1fr)'],
            'condition'      => ['layout' => 'grid'],
        ]);

        $this->end_controls_section();

        // ── Style — Section title ─────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-taxdisp__section-title');

        // ── Style — Description de section ───────────────────────────────────
        $this->start_controls_section('style_section_desc', [
            'label'     => __('Style — Description de section', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['section_description!' => ''],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'section_desc_typography',
            'selector' => '{{WRAPPER}} .bt-taxdisp__section-desc',
        ]);
        $this->add_control('section_desc_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxdisp__section-desc' => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('section_desc_spacing', [
            'label'      => __('Espacement bas', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__section-desc' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);
        $this->end_controls_section();

        // ── Style — Disposition ───────────────────────────────────────────────
        $this->start_controls_section('style_layout', [
            'label'     => __('Style — Disposition', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout!' => 'inline'],
        ]);

        $this->add_responsive_control('items_gap_row', [
            'label'      => __('Espacement vertical (lignes)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__text--list, {{WRAPPER}} .bt-taxdisp__text--grid' => 'row-gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('items_gap_col', [
            'label'      => __('Espacement horizontal (colonnes)', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__text--list, {{WRAPPER}} .bt-taxdisp__text--grid' => 'column-gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Titre du terme ────────────────────────────────────────────
        $this->start_controls_section('style_label', [
            'label' => __('Style — Titre du terme', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'label_typography',
            'selector' => '{{WRAPPER}} .bt-taxdisp__label, {{WRAPPER}} .bt-taxdisp__text',
        ]);
        $this->add_control('label_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-taxdisp__label' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxdisp__text'  => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_section();

        // ── Style — Description ───────────────────────────────────────────────
        $this->start_controls_section('style_desc', [
            'label'     => __('Style — Description', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_description' => 'yes'],
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'desc_typography',
            'selector' => '{{WRAPPER}} .bt-taxdisp__desc',
        ]);
        $this->add_control('desc_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxdisp__desc' => 'color: {{VALUE}}'],
        ]);
        $this->add_responsive_control('desc_margin_top', [
            'label'      => __('Marge haute', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-taxdisp__desc' => 'margin-top: {{SIZE}}{{UNIT}}'],
        ]);
        $this->end_controls_section();

        // ── Style — Icônes ────────────────────────────────────────────────────
        $this->start_controls_section('style_icon', [
            'label'     => __('Style — Icônes', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_taxonomy_icons' => 'yes'],
        ]);
        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-taxdisp__icon'     => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-taxdisp__icon img' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; object-fit: contain',
            ],
        ]);
        $this->add_control('icon_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-taxdisp__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxdisp__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-taxdisp__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_section();

        // ── Style — Item ──────────────────────────────────────────────────────
        $this->register_button_style('item', __('Style — Item', 'blacktenderscore'), '{{WRAPPER}} .bt-taxdisp__item', [], [], ['with_gap' => true]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    protected function render(): void {
        $s           = $this->get_settings_for_display();
        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';
        $key         = (string) ($s['acf_field'] ?? '');
        $layout      = (string) ($s['layout'] ?? 'inline');
        $sep         = (string) ($s['separator'] ?? ' · ');
        $show_title  = ($s['show_title'] ?? 'yes') === 'yes';
        $show_desc   = ($s['show_description'] ?? '') === 'yes';
        $show_icons  = ($s['show_taxonomy_icons'] ?? '') === 'yes';
        $fallback    = $s['fallback_icon'] ?? [];
        $is_inline   = $layout === 'inline';

        if ($key === '') {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Choisissez un champ ACF.', 'blacktenderscore'));
            }
            return;
        }

        $post_id = (int) get_the_ID();
        $raw     = function_exists('get_field') ? get_field($key, $post_id) : null;

        if (empty($raw)) {
            $native = get_the_terms($post_id, $key);
            if (is_array($native) && !empty($native)) {
                $raw = $native;
            }
        }

        if (empty($raw) && $raw !== '0' && $raw !== 0) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(sprintf(__('Champ « %s » vide ou introuvable.', 'blacktenderscore'), $key));
            }
            return;
        }

        if (!is_array($raw))        $raw = [$raw];
        if (isset($raw['term_id'])) $raw = [$raw];

        $items = [];
        foreach ($raw as $raw_item) {
            $term = $this->resolve_term($raw_item);
            [$name, $desc] = $this->extract_term_data($raw_item, $term, $show_title);
            if ($name === '' && $desc === '') continue;
            $items[] = ['term' => $term, 'name' => $name, 'desc' => $desc];
        }

        if (empty($items)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucun terme à afficher.', 'blacktenderscore'));
            }
            return;
        }

        // Mode HTML : nécessaire si icônes, description, saut de ligne, ou layout list/grid
        $html_mode = !$is_inline || $show_icons || $show_desc || $sep === '__br__';

        // Classe du wrapper texte selon le layout
        $text_class = 'bt-taxdisp__text';
        if ($layout === 'list') $text_class .= ' bt-taxdisp__text--list';
        if ($layout === 'grid') $text_class .= ' bt-taxdisp__text--grid';

        echo '<div class="bt-taxdisp">';

        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-taxdisp__section-title');
        } else {
            $this->render_section_title($s, 'bt-taxdisp__section-title');
            $this->render_section_description($s);
        }

        echo '<div class="' . esc_attr($text_class) . '">';

        if ($html_mode) {
            foreach ($items as $i => $item) {
                // Séparateur uniquement en mode inline
                if ($i > 0 && $is_inline) {
                    echo $sep === '__br__'
                        ? '<br>'
                        : '<span class="bt-taxdisp__sep" aria-hidden="true">' . esc_html($sep) . '</span>';
                }

                echo '<span class="bt-taxdisp__item">';

                if ($show_icons) {
                    $this->render_taxdisp_icon($item['term'], $fallback);
                }

                echo '<span class="bt-taxdisp__body">';
                if ($item['name'] !== '') {
                    echo '<span class="bt-taxdisp__label">' . esc_html($item['name']) . '</span>';
                }
                if ($show_desc && $item['desc'] !== '') {
                    echo '<span class="bt-taxdisp__desc">' . esc_html($item['desc']) . '</span>';
                }
                echo '</span>'; // .bt-taxdisp__body

                echo '</span>'; // .bt-taxdisp__item
            }
        } else {
            // Mode texte simple : noms séparés par le séparateur
            echo implode(esc_html($sep), array_map('esc_html', array_column($items, 'name')));
        }

        echo '</div>'; // .bt-taxdisp__text

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }
        echo '</div>'; // .bt-taxdisp
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Affiche la description de section si renseignée (indépendante des termes).
     */
    private function render_section_description(array $s): void {
        $desc = trim((string) ($s['section_description'] ?? ''));
        if ($desc === '') return;
        echo '<p class="bt-taxdisp__section-desc">' . esc_html($desc) . '</p>';
    }

    /**
     * Extrait [name, description] depuis un item brut + son WP_Term résolu.
     *
     * @return array{0: string, 1: string}  [name, description]
     */
    private function extract_term_data(mixed $item, ?\WP_Term $term, bool $show_title): array {
        if ($term instanceof \WP_Term) {
            return [
                $show_title ? $term->name : '',
                wp_strip_all_tags($term->description),
            ];
        }
        if (is_scalar($item)) return [(string) $item, ''];
        if (is_array($item))  return [$item['name'] ?? $item['label'] ?? '', $item['description'] ?? ''];
        return ['', ''];
    }

    private function resolve_term(mixed $item): ?\WP_Term {
        if ($item instanceof \WP_Term) return $item;
        if (is_array($item) && isset($item['term_id'])) {
            $t = get_term((int) $item['term_id']);
            return $t instanceof \WP_Term ? $t : null;
        }
        if (is_numeric($item)) {
            $t = get_term((int) $item);
            return $t instanceof \WP_Term ? $t : null;
        }
        return null;
    }

    /**
     * Affiche l'icône d'un terme : SVG inline > <img> > FA class > fallback Elementor.
     *
     * @uses get_field('taxomonies_icons', $term)  ACF — URL ou array d'image
     * @uses get_field('term_icon_class', $term)   ACF — classe FA (ex: "fas fa-anchor")
     */
    private function render_taxdisp_icon(?\WP_Term $term, array $fallback_icon): void {
        $icon_url = '';
        $icon_fa  = '';

        if ($term && function_exists('get_field')) {
            $icon_data = get_field('taxomonies_icons', $term);
            if (is_array($icon_data))      $icon_url = $icon_data['url'] ?? '';
            elseif (is_string($icon_data)) $icon_url = $icon_data;

            if (!$icon_url) {
                $fa_raw = get_field('term_icon_class', $term);
                if ($fa_raw && is_string($fa_raw)) $icon_fa = trim($fa_raw);
            }
        }

        if (!$icon_url && !$icon_fa && empty($fallback_icon['value'])) return;

        echo '<span class="bt-taxdisp__icon" aria-hidden="true">';

        if ($icon_url) {
            $ext = strtolower((string) pathinfo(wp_parse_url($icon_url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
            if ($ext === 'svg') {
                $svg = $this->fetch_svg_content($icon_url);
                echo $svg !== ''
                    ? $this->kses_svg($svg)
                    : '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            } else {
                echo '<img src="' . esc_url($icon_url) . '" alt="" loading="lazy" />';
            }
        } elseif ($icon_fa) {
            echo '<i class="' . esc_attr($icon_fa) . '"></i>';
        } elseif (!empty($fallback_icon['value'])) {
            \Elementor\Icons_Manager::render_icon($fallback_icon, ['aria-hidden' => 'true']);
        }

        echo '</span>';
    }

    /** Lit le contenu d'un SVG depuis le filesystem local ou via HTTP. */
    private function fetch_svg_content(string $url): string {
        $parsed_path = wp_parse_url($url, PHP_URL_PATH) ?: '';
        $path = ABSPATH . ltrim($parsed_path, '/');
        if (is_readable($path)) {
            $c = file_get_contents($path);
            return is_string($c) ? $c : '';
        }
        $r = wp_safe_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) return '';
        $body = wp_remote_retrieve_body($r);
        return is_string($body) ? $body : '';
    }

    /** Sanitize SVG via wp_kses avec les balises autorisées. */
    private function kses_svg(string $html): string {
        return wp_kses($html, [
            'svg'      => ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true, 'aria-hidden' => true],
            'path'     => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true, 'stroke-linejoin' => true, 'class' => true],
            'g'        => ['fill' => true, 'stroke' => true, 'class' => true, 'transform' => true],
            'circle'   => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'rect'     => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'line'     => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'class' => true],
            'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'polygon'  => ['points' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'ellipse'  => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'defs'     => [],
            'use'      => ['href' => true, 'xlink:href' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true],
        ]);
    }
}
