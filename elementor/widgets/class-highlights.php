<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Points forts.
 *
 * Repeater ACF (highlight_icon, highlight_title, highlight_desc)
 * en grille ou liste, avec Elementor Icons comme icône de fallback
 * et styles via les méthodes partagées du trait.
 */
class Highlights extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-highlights',
            'title'    => 'BT — Points forts',
            'icon'     => 'eicon-check-circle',
            'keywords' => ['highlights', 'points', 'forts', 'inclus', 'avantages', 'bt'],
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
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'exp_highlights',
        ]);

        $this->register_section_title_controls(['title' => __('Points forts', 'blacktenderscore')]);

        $this->add_control('max_items', [
            'label'   => __('Nombre max d\'éléments', 'blacktenderscore'),
            'type'    => Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 50,
            'default' => 12,
        ]);

        $this->add_control('sf_icon', [
            'label'   => __('Sous-champ icône (emoji/texte)', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'highlight_icon',
        ]);

        $this->add_control('sf_title', [
            'label'   => __('Sous-champ titre', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'highlight_title',
        ]);

        $this->add_control('sf_desc', [
            'label'   => __('Sous-champ description', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'highlight_desc',
        ]);

        $this->add_control('separator_icon_fallback', [
            'label'     => __('─────── Icône de fallback ───────', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('default_icon', [
            'label'       => __('Icône par défaut (si champ ACF vide)', 'blacktenderscore'),
            'description' => __('Utilisée quand le champ emoji/icône ACF est vide.', 'blacktenderscore'),
            'type'        => Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-check', 'library' => 'fa-solid'],
            'skin'        => 'inline',
        ]);

        $this->add_control('separator_visibility', [
            'label'     => __('─────── Visibilité ───────', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_icon', [
            'label'        => __('Afficher l\'icône', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_title', [
            'label'        => __('Afficher le titre', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_desc', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();

        // ── Mise en page ──────────────────────────────────────────────────
        $this->start_controls_section('section_layout', [
            'label' => __('Mise en page', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'grid' => __('Grille', 'blacktenderscore'),
                'list' => __('Liste', 'blacktenderscore'),
            ],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 6,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'selectors'      => ['{{WRAPPER}} .bt-highlights__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)'],
            'condition'      => ['layout' => 'grid'],
        ]);

        $this->add_responsive_control('gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-highlights__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-highlights__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('icon_position', [
            'label'   => __('Position de l\'icône', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'row'    => ['title' => __('Gauche du texte', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'column' => ['title' => __('Au-dessus du texte', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'row',
            'condition' => ['show_icon' => 'yes'],
            'selectors' => ['{{WRAPPER}} .bt-highlights__item' => 'flex-direction: {{VALUE}}'],
        ]);

        $this->add_control('icon_valign', [
            'label'   => __('Alignement vertical icône', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'flex-start' => ['title' => __('Haut', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'flex-end'   => ['title' => __('Bas', 'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'   => 'flex-start',
            'condition' => ['show_icon' => 'yes', 'icon_position' => 'row'],
            'selectors' => ['{{WRAPPER}} .bt-highlights__item' => 'align-items: {{VALUE}}'],
        ]);

        $this->add_control('content_align', [
            'label'   => __('Alignement du texte', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'default'   => '',
            'condition' => ['icon_position' => 'column'],
            'selectors' => [
                '{{WRAPPER}} .bt-highlights__item' => 'align-items: {{VALUE}}; text-align: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('icon_spacing', [
            'label'      => __('Espacement icône ↔ texte', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'condition'  => ['show_icon' => 'yes'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__item' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Styles via traits ─────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-highlights__section-title');

        $this->register_item_3state_style(
            'item',
            __('Style — Item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__item'
        );

        $this->register_icon_style_section(
            'icon',
            __('Style — Icône', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__icon',
            ['size' => 28],
            ['show_icon' => 'yes']
        );

        // Typographies via trait
        $this->register_typography_section(
            'item_title',
            __('Style — Titre item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__title',
            [],
            [],
            ['show_title' => 'yes']
        );

        $this->register_typography_section(
            'item_desc',
            __('Style — Description item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__desc',
            [],
            [],
            ['show_desc' => 'yes']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        if (!$this->acf_required()) return;

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_highlights');
        $rows = $this->get_acf_rows($field_name, __('Aucun point fort trouvé.', 'blacktenderscore'));
        if (!$rows) return;

        $max_items = max(1, (int) ($s['max_items'] ?: 12));
        $rows      = array_slice($rows, 0, $max_items);
        $sf_icon   = sanitize_text_field($s['sf_icon']  ?: 'highlight_icon');
        $sf_title  = sanitize_text_field($s['sf_title'] ?: 'highlight_title');
        $sf_desc   = sanitize_text_field($s['sf_desc']  ?: 'highlight_desc');
        $layout    = $s['layout'] ?: 'grid';
        $wrap_cls  = $layout === 'list' ? 'bt-highlights__list' : 'bt-highlights__grid';

        echo '<div class="bt-highlights">';

        $this->render_section_title($s, 'bt-highlights__section-title');

        echo "<div class=\"{$wrap_cls}\">";

        foreach ($rows as $row) {
            $icon  = $row[$sf_icon]  ?? '';
            $title = $row[$sf_title] ?? '';
            $desc  = $row[$sf_desc]  ?? '';

            echo '<div class="bt-highlights__item">';

            if ($s['show_icon'] === 'yes') {
                echo '<span class="bt-highlights__icon" aria-hidden="true">';
                if ($icon) {
                    // Emoji ou texte depuis ACF
                    echo esc_html($icon);
                } elseif (!empty($s['default_icon']['value'])) {
                    // Icône Elementor (fa, eicon...)
                    Icons_Manager::render_icon($s['default_icon'], ['aria-hidden' => 'true']);
                }
                echo '</span>';
            }

            echo '<div class="bt-highlights__content">';

            if ($s['show_title'] === 'yes' && $title) {
                echo '<span class="bt-highlights__title">' . esc_html($title) . '</span>';
            }

            if ($s['show_desc'] === 'yes' && $desc) {
                echo '<div class="bt-highlights__desc">' . wp_kses_post($desc) . '</div>';
            }

            echo '</div>'; // .bt-highlights__content
            echo '</div>'; // .bt-highlights__item
        }

        echo '</div>'; // grid/list
        echo '</div>'; // .bt-highlights
    }
}
