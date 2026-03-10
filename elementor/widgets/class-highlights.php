<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Points forts.
 *
 * Affiche un repeater ACF (highlight_icon, highlight_title, highlight_desc)
 * sous forme de grille ou de liste avec icône + titre + description.
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
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'exp_highlights',
        ]);

        $this->register_section_title_controls(['title' => __('Points forts', 'blacktenderscore')]);

        $this->add_control('max_items', [
            'label'   => __('Nombre max d\'éléments', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'min'     => 1,
            'max'     => 50,
            'default' => 12,
        ]);

        $this->add_control('sf_icon', [
            'label'   => __('Sous-champ icône (emoji/texte)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'highlight_icon',
        ]);

        $this->add_control('sf_title', [
            'label'   => __('Sous-champ titre', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'highlight_title',
        ]);

        $this->add_control('sf_desc', [
            'label'   => __('Sous-champ description', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'highlight_desc',
        ]);

        $this->add_control('default_icon', [
            'label'   => __('Icône par défaut', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '✓',
        ]);

        $this->add_control('show_icon', [
            'label'        => __('Afficher l\'icône', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_title', [
            'label'        => __('Afficher le titre', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_desc', [
            'label'        => __('Afficher la description', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();

        // ── Mise en page ──────────────────────────────────────────────────
        $this->start_controls_section('section_layout', [
            'label' => __('Mise en page', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'grid' => __('Grille', 'blacktenderscore'),
                'list' => __('Liste', 'blacktenderscore'),
            ],
            'default' => 'grid',
        ]);

        $this->add_responsive_control('columns', [
            'label'          => __('Colonnes', 'blacktenderscore'),
            'type'           => \Elementor\Controls_Manager::NUMBER,
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
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-highlights__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-highlights__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-highlights__section-title');

        $this->register_box_style('item', 'Style — Item', '{{WRAPPER}} .bt-highlights__item', ['padding' => 16]);

        // Style — Icône
        $this->start_controls_section('style_icon', [
            'label' => __('Style — Icône', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 12, 'max' => 80]],
            'default'    => ['size' => 32, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__icon' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('icon_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__icon' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // Style — Titre item
        $this->start_controls_section('style_item_title', [
            'label' => __('Style — Titre item', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'item_title_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-highlights__title',
        ]);

        $this->add_control('item_title_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__title' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // Style — Description item
        $this->start_controls_section('style_item_desc', [
            'label'     => __('Style — Description item', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['show_desc' => 'yes'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'item_desc_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-highlights__desc',
        ]);

        $this->add_control('item_desc_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__desc' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        if (!$this->acf_required()) return;

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_highlights');
        $rows = $this->get_acf_rows($field_name, __('Aucun point fort trouvé.', 'blacktenderscore'));
        if (!$rows) return;

        $max_items    = max(1, (int) ($s['max_items'] ?: 12));
        $rows         = array_slice($rows, 0, $max_items);
        $sf_icon      = sanitize_text_field($s['sf_icon']  ?: 'highlight_icon');
        $sf_title     = sanitize_text_field($s['sf_title'] ?: 'highlight_title');
        $sf_desc      = sanitize_text_field($s['sf_desc']  ?: 'highlight_desc');
        $default_icon = $s['default_icon'] ?: '✓';
        $layout       = $s['layout'] ?: 'grid';
        $wrap_cls     = $layout === 'list' ? 'bt-highlights__list' : 'bt-highlights__grid';

        echo '<div class="bt-highlights">';

        $this->render_section_title($s, 'bt-highlights__section-title');

        echo "<div class=\"{$wrap_cls}\">";

        foreach ($rows as $row) {
            $icon  = $row[$sf_icon]  ?? '';
            $title = $row[$sf_title] ?? '';
            $desc  = $row[$sf_desc]  ?? '';

            if (!$icon) $icon = $default_icon;

            echo '<div class="bt-highlights__item">';

            if ($s['show_icon'] === 'yes') {
                echo '<span class="bt-highlights__icon" aria-hidden="true">' . esc_html($icon) . '</span>';
            }

            echo '<div class="bt-highlights__content">';

            if ($s['show_title'] === 'yes' && $title) {
                echo '<span class="bt-highlights__title">' . esc_html($title) . '</span>';
            }

            if ($s['show_desc'] === 'yes' && $desc) {
                echo '<p class="bt-highlights__desc">' . esc_html($desc) . '</p>';
            }

            echo '</div>'; // .bt-highlights__content
            echo '</div>'; // .bt-highlights__item
        }

        echo '</div>'; // grid/list
        echo '</div>'; // .bt-highlights
    }
}
