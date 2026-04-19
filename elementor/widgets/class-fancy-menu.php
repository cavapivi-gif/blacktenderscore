<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Fancy Menu (inspiré de Rey Theme).
 *
 * Menu avec navigation multi-niveaux animée (slide horizontal),
 * support des icônes et descriptions ACF.
 */
class FancyMenu extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-fancy-menu',
            'title'    => 'BT — Fancy Menu',
            'icon'     => 'eicon-text-align-left',
            'keywords' => ['menu', 'fancy', 'navigation', 'nav', 'bt'],
            'css'      => ['bt-fancy-menu'],
            'js'       => ['bt-fancy-menu'],
        ];
    }

    public function on_export($element) {
        unset($element['settings']['menu_id']);
        return $element;
    }

    protected function register_controls() {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Content Controls
    // ─────────────────────────────────────────────────────────────────────

    private function register_content_controls(): void {

        // ── Layout ───────────────────────────────────────────────────────
        $this->start_controls_section('section_layout', [
            'label' => __('Layout', 'blacktenderscore'),
        ]);

        $menus = wp_get_nav_menus();
        $menu_options = ['' => __('-- Sélectionner un menu --', 'blacktenderscore')];
        foreach ($menus as $menu) {
            $menu_options[$menu->term_id] = $menu->name;
        }

        $this->add_control('menu_id', [
            'label'   => __('Menu', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => $menu_options,
            'default' => '',
        ]);

        $this->add_control('menu_depth', [
            'label'   => __('Profondeur du menu', 'blacktenderscore'),
            'type'    => Controls_Manager::NUMBER,
            'default' => 3,
            'min'     => 1,
            'step'    => 1,
        ]);

        $this->add_responsive_control('align', [
            'label'   => __('Alignement', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'prefix_class' => 'elementor%s-align-',
        ]);

        $this->end_controls_section();

        // ── Icône ────────────────────────────────────────────────────────
        $this->start_controls_section('section_icon', [
            'label' => __('Icône', 'blacktenderscore'),
        ]);

        $this->add_control('show_icon', [
            'label'        => __('Afficher les icônes', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => '',
            'description'  => __('Affiche l\'icône ACF (bt_menu_icon) de chaque item.', 'blacktenderscore'),
        ]);

        $this->add_control('icon_position', [
            'label'   => __('Position', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'left',
            'options' => [
                'left'  => __('Gauche', 'blacktenderscore'),
                'right' => __('Droite', 'blacktenderscore'),
                'top'   => __('Haut', 'blacktenderscore'),
            ],
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Description ──────────────────────────────────────────────────
        $this->start_controls_section('section_description', [
            'label' => __('Description', 'blacktenderscore'),
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher les descriptions', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => '',
            'description'  => __('Affiche la description ACF (bt_menu_description) sous chaque item.', 'blacktenderscore'),
        ]);

        $this->add_control('desc_align', [
            'label'   => __('Alignement description', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'default'   => 'left',
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Count (sous-items) ───────────────────────────────────────
        $this->start_controls_section('section_count', [
            'label' => __('Compteur sous-items', 'blacktenderscore'),
        ]);

        $this->add_control('show_count', [
            'label'        => __('Afficher le compteur', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => '',
            'description'  => __('Affiche le nombre de sous-items à côté de chaque item parent.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Dividers ─────────────────────────────────────────────────
        $this->start_controls_section('section_dividers', [
            'label' => __('Séparateurs', 'blacktenderscore'),
        ]);

        $this->add_control('show_dividers', [
            'label'   => __('Afficher les séparateurs', 'blacktenderscore'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ]);

        $this->end_controls_section();

        // ── Indicateurs sous-menus ───────────────────────────────────
        $this->start_controls_section('section_indicators', [
            'label' => __('Indicateurs sous-menus', 'blacktenderscore'),
        ]);

        $this->add_control('show_indicators', [
            'label'   => __('Afficher les indicateurs', 'blacktenderscore'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ]);

        $this->add_control('indicator_icon', [
            'label'   => __('Icône indicateur', 'blacktenderscore'),
            'type'    => Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-chevron-right',
                'library' => 'fa-solid',
            ],
            'recommended' => [
                'fa-solid' => ['chevron-right', 'arrow-right', 'angle-right', 'caret-right', 'play'],
                'fa-regular' => ['arrow-alt-circle-right'],
            ],
            'condition' => ['show_indicators' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Bouton Retour ────────────────────────────────────────────
        $this->start_controls_section('section_back_button', [
            'label' => __('Bouton Retour', 'blacktenderscore'),
        ]);

        $this->add_control('back_icon', [
            'label'   => __('Icône retour', 'blacktenderscore'),
            'type'    => Controls_Manager::ICONS,
            'default' => [
                'value'   => 'fas fa-chevron-left',
                'library' => 'fa-solid',
            ],
            'recommended' => [
                'fa-solid' => ['chevron-left', 'arrow-left', 'angle-left', 'caret-left', 'long-arrow-alt-left'],
                'fa-regular' => ['arrow-alt-circle-left'],
            ],
        ]);

        $this->add_control('back_show_label', [
            'label'   => __('Afficher un texte', 'blacktenderscore'),
            'type'    => Controls_Manager::SWITCHER,
            'default' => '',
        ]);

        $this->add_control('back_label_type', [
            'label'   => __('Type de texte', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => 'dynamic',
            'options' => [
                'dynamic' => __('Nom du menu parent', 'blacktenderscore'),
                'static'  => __('Texte personnalisé', 'blacktenderscore'),
            ],
            'condition' => ['back_show_label' => 'yes'],
        ]);

        $this->add_control('back_label_static', [
            'label'       => __('Texte', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Retour', 'blacktenderscore'),
            'placeholder' => __('Retour', 'blacktenderscore'),
            'condition'   => [
                'back_show_label' => 'yes',
                'back_label_type' => 'static',
            ],
        ]);

        $this->add_control('back_show_breadcrumb', [
            'label'       => __('Afficher le breadcrumb', 'blacktenderscore'),
            'type'        => Controls_Manager::SWITCHER,
            'default'     => '',
            'description' => __('Affiche le chemin des menus parents sous le bouton retour.', 'blacktenderscore'),
        ]);

        $this->add_control('back_breadcrumb_prefix', [
            'label'       => __('Préfixe', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'Menu',
            'placeholder' => 'Menu',
            'condition'   => ['back_show_breadcrumb' => 'yes'],
        ]);

        $this->add_control('back_breadcrumb_separator', [
            'label'     => __('Séparateur', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => ' › ',
            'condition' => ['back_show_breadcrumb' => 'yes'],
        ]);

        $this->add_control('back_icon_position', [
            'label'   => __('Position icône', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'left' => [
                    'title' => __('Gauche', 'blacktenderscore'),
                    'icon'  => 'eicon-h-align-left',
                ],
                'right' => [
                    'title' => __('Droite', 'blacktenderscore'),
                    'icon'  => 'eicon-h-align-right',
                ],
            ],
            'default' => 'left',
            'toggle'  => false,
        ]);

        $this->end_controls_section();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Style Controls
    // ─────────────────────────────────────────────────────────────────────

    private function register_style_controls(): void {

        // ── Items ────────────────────────────────────────────────────────
        $this->start_controls_section('section_style_items', [
            'label' => __('Items', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'typo',
            'label'    => __('Typographie titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .menu-item > a .bt-fancyMenu-title',
        ]);

        $this->add_control('color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .menu-item > a, {{WRAPPER}} .bt-fancyMenu-back' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('hover_color', [
            'label'     => __('Couleur hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .menu-item > a:hover, {{WRAPPER}} .menu-item.current-menu-item > a, {{WRAPPER}} .bt-fancyMenu-back:hover' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('distance', [
            'label'     => __('Espacement items', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 60]],
            'selectors' => [
                '{{WRAPPER}} .menu-item:not(:first-child)' => 'margin-top: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Icône Style ──────────────────────────────────────────────────
        $this->start_controls_section('section_style_icon', [
            'label'     => __('Icône', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_icon' => 'yes'],
        ]);

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['em', 'px'],
            'range'      => [
                'em' => ['min' => 0.5, 'max' => 4, 'step' => 0.1],
                'px' => ['min' => 8, 'max' => 80],
            ],
            'default'    => ['size' => 1.2, 'unit' => 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .bt-fancyMenu-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('icon_color', [
            'label'       => __('Couleur', 'blacktenderscore'),
            'type'        => Controls_Manager::COLOR,
            'description' => __('Fonctionne sur les SVG inline uniquement.', 'blacktenderscore'),
            'selectors'   => [
                '{{WRAPPER}} .bt-fancyMenu-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
                '{{WRAPPER}} .bt-fancyMenu-icon svg' => 'fill: {{VALUE}};',
                '{{WRAPPER}} .bt-fancyMenu-icon svg path' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('icon_hover_color', [
            'label'     => __('Couleur hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .menu-item > a:hover .bt-fancyMenu-icon' => 'color: {{VALUE}}; fill: {{VALUE}};',
                '{{WRAPPER}} .menu-item > a:hover .bt-fancyMenu-icon svg' => 'fill: {{VALUE}};',
                '{{WRAPPER}} .menu-item > a:hover .bt-fancyMenu-icon svg path' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('icon_spacing', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['em', 'px'],
            'range'      => [
                'em' => ['min' => 0, 'max' => 3, 'step' => 0.1],
                'px' => ['min' => 0, 'max' => 40],
            ],
            'default'    => ['size' => 0.5, 'unit' => 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-link' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('icon_border_radius', [
            'label'      => __('Coins', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-icon img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        // ── Description Style ────────────────────────────────────────────
        $this->start_controls_section('section_style_desc', [
            'label'     => __('Description', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_description' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'desc_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-fancyMenu-desc',
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-desc' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('desc_hover_color', [
            'label'     => __('Couleur hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .menu-item > a:hover .bt-fancyMenu-desc' => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('desc_spacing', [
            'label'      => __('Marge haut', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 4, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-desc' => 'margin-top: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('desc_max_width', [
            'label'      => __('Largeur max', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 100, 'max' => 600], '%' => ['min' => 50, 'max' => 100]],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-desc' => 'max-width: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        // ── Count Style ──────────────────────────────────────────────────
        $this->start_controls_section('section_style_count', [
            'label'     => __('Compteur', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_count' => 'yes'],
        ]);

        // Taille du badge (min-width/height) - en px pour éviter conflit avec font-size
        $this->add_responsive_control('count_badge_size', [
            'label'      => __('Taille badge', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 16, 'max' => 48]],
            'default'    => ['size' => 22, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'min-width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        // Typographie du texte - indépendante de la taille du badge
        $this->add_responsive_control('count_font_size', [
            'label'      => __('Taille texte', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 24]],
            'default'    => ['size' => 11, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'font-size: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('count_font_weight', [
            'label'   => __('Graisse', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'default' => '600',
            'options' => [
                '400' => __('Normal', 'blacktenderscore'),
                '500' => __('Medium', 'blacktenderscore'),
                '600' => __('Semi-bold', 'blacktenderscore'),
                '700' => __('Bold', 'blacktenderscore'),
            ],
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'font-weight: {{VALUE}};',
            ],
        ]);

        $this->add_control('count_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('count_bg', [
            'label'     => __('Couleur fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('count_spacing', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'margin-left: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('count_border_radius', [
            'label'      => __('Coins', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default'    => ['top' => 50, 'right' => 50, 'bottom' => 50, 'left' => 50, 'unit' => '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-count' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        // ── Dividers Style ──────────────────────────────────────────────
        $this->start_controls_section('section_style_dividers', [
            'label'     => __('Séparateurs', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_dividers' => 'yes'],
        ]);

        $this->add_control('divider_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#e5e5e5',
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-nav .menu-item:not(:last-child)::after' => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_responsive_control('divider_width', [
            'label'      => __('Largeur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['%', 'px'],
            'range'      => [
                '%'  => ['min' => 10, 'max' => 100],
                'px' => ['min' => 20, 'max' => 500],
            ],
            'default'    => ['size' => 100, 'unit' => '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-nav .menu-item:not(:last-child)::after' => 'width: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('divider_height', [
            'label'     => __('Épaisseur', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 1, 'max' => 10]],
            'default'   => ['size' => 1, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-nav .menu-item:not(:last-child)::after' => 'height: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('divider_spacing', [
            'label'     => __('Espacement', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 40]],
            'default'   => ['size' => 10, 'unit' => 'px'],
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-nav .menu-item:not(:last-child)::after' => 'margin-top: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Indicateurs Style ───────────────────────────────────────────
        $this->start_controls_section('section_style_indicators', [
            'label'     => __('Indicateurs sous-menus', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_indicators' => 'yes'],
        ]);

        $this->add_responsive_control('indicator_size', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => [
                'px' => ['min' => 6, 'max' => 32],
                'em' => ['min' => 0.5, 'max' => 2, 'step' => 0.1],
            ],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .--submenu-indicator' => 'font-size: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .--submenu-indicator i' => 'font-size: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .--submenu-indicator svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_control('indicator_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .--submenu-indicator' => 'color: {{VALUE}}',
                '{{WRAPPER}} .--submenu-indicator svg' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('indicator_hover_color', [
            'label'     => __('Couleur hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .menu-item > a:hover .--submenu-indicator' => 'color: {{VALUE}}',
                '{{WRAPPER}} .menu-item > a:hover .--submenu-indicator svg' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('indicator_spacing', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['em', 'px'],
            'range'      => [
                'em' => ['min' => 0, 'max' => 3, 'step' => 0.1],
                'px' => ['min' => 0, 'max' => 30],
            ],
            'default'    => ['size' => 0.5, 'unit' => 'em'],
            'selectors'  => [
                '{{WRAPPER}} .--submenu-indicator' => 'margin-left: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();

        // ── Bouton Retour Style ─────────────────────────────────────────
        $this->start_controls_section('section_style_back', [
            'label' => __('Bouton Retour', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('back_info', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => __('Le bouton retour apparaît uniquement lors de la navigation dans les sous-menus.', 'blacktenderscore'),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
        ]);

        $this->add_responsive_control('back_icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => [
                'px' => ['min' => 12, 'max' => 60],
                'em' => ['min' => 0.8, 'max' => 3, 'step' => 0.1],
            ],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-back__icon' => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .bt-fancyMenu-back__icon i' => 'font-size: {{SIZE}}{{UNIT}};',
                '{{WRAPPER}} .bt-fancyMenu-back__icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('back_icon_spacing', [
            'label'      => __('Espacement icône/label', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => [
                'px' => ['min' => 0, 'max' => 30],
                'em' => ['min' => 0, 'max' => 2, 'step' => 0.1],
            ],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-back__main' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        // ── Section Label ──
        $this->add_control('back_label_heading', [
            'label'     => __('Label', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
            'condition' => ['back_show_label' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'      => 'back_label_typo',
            'label'     => __('Typographie', 'blacktenderscore'),
            'selector'  => '{{WRAPPER}} .bt-fancyMenu-back__label',
            'condition' => ['back_show_label' => 'yes'],
        ]);

        $this->add_control('back_label_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back__label' => 'color: {{VALUE}};',
            ],
            'condition' => ['back_show_label' => 'yes'],
        ]);

        $this->add_control('back_label_hover_color', [
            'label'     => __('Couleur hover', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back:hover .bt-fancyMenu-back__label' => 'color: {{VALUE}};',
            ],
            'condition' => ['back_show_label' => 'yes'],
        ]);

        // ── Section Icône + Background ──
        $this->add_control('back_style_heading', [
            'label'     => __('Icône & Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        // ── Tabs Normal / Hover ──
        $this->start_controls_tabs('back_tabs');

        // Tab Normal
        $this->start_controls_tab('back_tab_normal', [
            'label' => __('Normal', 'blacktenderscore'),
        ]);

        $this->add_control('back_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back' => 'color: {{VALUE}};',
                '{{WRAPPER}} .bt-fancyMenu-back svg' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('back_bg', [
            'label'     => __('Arrière-plan', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('back_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-back' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('back_border_radius', [
            'label'      => __('Coins', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-back' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_tab();

        // Tab Hover
        $this->start_controls_tab('back_tab_hover', [
            'label' => __('Hover', 'blacktenderscore'),
        ]);

        $this->add_control('back_hover_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back:hover' => 'color: {{VALUE}};',
                '{{WRAPPER}} .bt-fancyMenu-back:hover svg' => 'fill: {{VALUE}};',
            ],
        ]);

        $this->add_control('back_bg_hover', [
            'label'     => __('Arrière-plan', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back:hover' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_control('back_hover_transition', [
            'label'   => __('Durée transition', 'blacktenderscore'),
            'type'    => Controls_Manager::SLIDER,
            'range'   => ['px' => ['min' => 0, 'max' => 1000, 'step' => 50]],
            'default' => ['size' => 300],
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-back' => 'transition-duration: {{SIZE}}ms;',
            ],
        ]);

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();

        // ── Breadcrumb Style (section séparée) ─────────────────────────────
        $this->start_controls_section('section_style_breadcrumb', [
            'label'     => __('Breadcrumb', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['back_show_breadcrumb' => 'yes'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'breadcrumb_typo',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-fancyMenu-breadcrumb',
        ]);

        $this->add_control('breadcrumb_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-breadcrumb' => 'color: {{VALUE}};',
            ],
        ]);

        $this->add_control('breadcrumb_bg', [
            'label'     => __('Arrière-plan', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-fancyMenu-breadcrumb' => 'background-color: {{VALUE}};',
            ],
        ]);

        $this->add_responsive_control('breadcrumb_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-breadcrumb' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->add_responsive_control('breadcrumb_margin', [
            'label'      => __('Marge', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-fancyMenu-breadcrumb' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    // ─────────────────────────────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────────────────────────────

    private function render_start(array $settings): void {
        $classes = ['bt-fancyMenu'];

        if ($settings['show_icon'] === 'yes') {
            $classes[] = '--has-icons';
            $classes[] = '--icon-' . ($settings['icon_position'] ?? 'left');
        }
        if ($settings['show_description'] === 'yes') {
            $classes[] = '--has-desc';
            $classes[] = '--desc-' . ($settings['desc_align'] ?? 'left');
        }
        if ($settings['show_dividers'] === 'yes') {
            $classes[] = '--has-dividers';
        }

        // Position icône bouton retour
        $back_icon_pos = $settings['back_icon_position'] ?? 'left';
        $classes[] = '--back-icon-' . $back_icon_pos;

        // Afficher le label
        $has_label = $settings['back_show_label'] === 'yes';
        $label_type = $settings['back_label_type'] ?? 'dynamic';
        $static_label = $settings['back_label_static'] ?? __('Retour', 'blacktenderscore');

        // Breadcrumb
        $has_breadcrumb = $settings['back_show_breadcrumb'] === 'yes';
        $breadcrumb_sep = $settings['back_breadcrumb_separator'] ?? ' › ';

        if ($has_label) {
            $classes[] = '--back-has-label';
        }
        if ($has_breadcrumb) {
            $classes[] = '--back-has-breadcrumb';
        }

        $this->add_render_attribute('wrapper', 'class', $classes);
        $this->add_render_attribute('wrapper', 'data-depth', $settings['menu_depth']);

        // Indicateurs activés
        if ($settings['show_indicators'] === 'yes') {
            $this->add_render_attribute('wrapper', 'data-indicators', 'yes');
        }

        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <?php if ($settings['show_indicators'] === 'yes'): ?>
            <template class="bt-fancyMenu-indicator-tpl">
                <?php $this->render_indicator_icon($settings); ?>
            </template>
            <?php endif; ?>
        <?php
    }

    /**
     * Rend l'icône de l'indicateur de sous-menu.
     */
    private function render_indicator_icon(array $settings): void {
        $icon = $settings['indicator_icon'] ?? [];

        if (!empty($icon['value'])) {
            \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
        } else {
            // Fallback SVG chevron
            echo '<svg viewBox="0 0 20 20" fill="currentColor"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>';
        }
    }

    private function render_end(): void {
        ?></div><?php
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();

        if (empty($settings['menu_id'])) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                $this->render_placeholder(__('Sélectionnez un menu dans les réglages.', 'blacktenderscore'));
            }
            return;
        }

        $this->render_start($settings);

        $walker = new FancyMenu_Walker([
            'show_icon'        => $settings['show_icon'] === 'yes',
            'show_description' => $settings['show_description'] === 'yes',
            'show_count'       => $settings['show_count'] === 'yes',
            'icon_position'    => $settings['icon_position'] ?? 'left',
            // Back button options
            'back_icon'        => $settings['back_icon'] ?? [],
            'back_show_label'  => $settings['back_show_label'] === 'yes',
            'back_label_type'  => $settings['back_label_type'] ?? 'dynamic',
            'back_label_static'=> $settings['back_label_static'] ?? __('Retour', 'blacktenderscore'),
            'back_show_breadcrumb' => $settings['back_show_breadcrumb'] === 'yes',
            'back_breadcrumb_prefix' => $settings['back_breadcrumb_prefix'] ?? 'Menu',
            'back_breadcrumb_sep'  => $settings['back_breadcrumb_separator'] ?? ' › ',
        ]);

        wp_nav_menu([
            'menu'       => $settings['menu_id'],
            'container'  => '',
            'menu_class' => 'bt-fancyMenu-nav --start',
            'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'depth'      => $settings['menu_depth'],
            'walker'     => $walker,
        ]);

        $this->render_end();
    }

    protected function render_placeholder(string $message): void {
        echo '<p class="bt-widget-placeholder">' . esc_html($message) . '</p>';
    }

    protected function content_template() {}
}

/**
 * Walker personnalisé pour le Fancy Menu.
 * Ajoute l'icône et la description ACF dans chaque item.
 * Génère le bouton retour avec breadcrumb dans chaque sous-menu.
 */
class FancyMenu_Walker extends \Walker_Nav_Menu {

    private array $options;
    private array $menu_items = [];

    /** @var array Stack des titres pour le breadcrumb */
    private array $breadcrumb_stack = [];

    /** @var string Titre du parent courant (pour le label dynamique) */
    private string $current_parent_title = '';

    public function __construct(array $options = []) {
        $this->options = wp_parse_args($options, [
            'show_icon'        => false,
            'show_description' => false,
            'show_count'       => false,
            'icon_position'    => 'left',
            // Back button
            'back_icon'        => [],
            'back_show_label'  => false,
            'back_label_type'  => 'dynamic',
            'back_label_static'=> 'Retour',
            'back_show_breadcrumb' => false,
            'back_breadcrumb_prefix' => 'Menu',
            'back_breadcrumb_sep'  => ' › ',
        ]);
    }

    /**
     * Avant de parcourir, on indexe les items par parent pour compter les enfants.
     */
    public function walk($elements, $max_depth, ...$args) {
        // Indexer les items par parent_id pour compter les enfants
        foreach ($elements as $e) {
            $parent_id = (int) $e->menu_item_parent;
            if (!isset($this->menu_items[$parent_id])) {
                $this->menu_items[$parent_id] = 0;
            }
            $this->menu_items[$parent_id]++;
        }
        return parent::walk($elements, $max_depth, ...$args);
    }

    /**
     * Compte les sous-items directs d'un item.
     */
    private function get_children_count(int $item_id): int {
        return $this->menu_items[$item_id] ?? 0;
    }

    /**
     * Début d'un sous-menu — injecte le bouton retour.
     */
    public function start_lvl(&$output, $depth = 0, $args = null) {
        $indent = str_repeat("\t", $depth);
        $output .= "\n{$indent}<ul class=\"sub-menu\">\n";

        // Générer le bouton retour avec breadcrumb
        $output .= $this->render_back_button();
    }

    /**
     * Génère le HTML du bouton retour + breadcrumb (séparés).
     */
    private function render_back_button(): string {
        $html = '<li class="bt-fancyMenu-back-wrapper">';

        // Button (icon + label uniquement)
        $html .= '<button class="bt-fancyMenu-back" aria-label="' . esc_attr__('Retour', 'blacktenderscore') . '">';
        $html .= '<span class="bt-fancyMenu-back__icon">' . $this->render_back_icon() . '</span>';

        if ($this->options['back_show_label']) {
            $label = ($this->options['back_label_type'] === 'static')
                ? esc_html($this->options['back_label_static'])
                : esc_html($this->current_parent_title);
            $html .= '<span class="bt-fancyMenu-back__label">' . $label . '</span>';
        }

        $html .= '</button>';

        // Breadcrumb (HORS du button) — seulement si depth > 1 (pas au premier sous-menu)
        if ($this->options['back_show_breadcrumb'] && count($this->breadcrumb_stack) > 1) {
            $prefix = $this->options['back_breadcrumb_prefix'];
            $sep = $this->options['back_breadcrumb_sep'];
            $items = array_map('esc_html', $this->breadcrumb_stack);

            // Préfixe + items
            if (!empty($prefix)) {
                array_unshift($items, esc_html($prefix));
            }

            $breadcrumb = implode($sep, $items);
            $html .= '<div class="bt-fancyMenu-breadcrumb">' . $breadcrumb . '</div>';
        }

        $html .= '</li>';

        return $html;
    }

    /**
     * Rend l'icône du bouton retour.
     */
    private function render_back_icon(): string {
        $icon = $this->options['back_icon'];

        if (!empty($icon['value'])) {
            ob_start();
            \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
            return ob_get_clean();
        }

        // Fallback SVG
        return '<svg class="bt-arrowSvg" viewBox="0 0 20 20" fill="currentColor"><path d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"/></svg>';
    }

    /**
     * Starts the element output.
     */
    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        // Track breadcrumb - push parent title before processing children
        $title = apply_filters('the_title', $item->title, $item->ID);
        $this->current_parent_title = $title;

        // Si cet item a des enfants, on l'ajoute au breadcrumb stack
        if (in_array('menu-item-has-children', (array) $item->classes)) {
            $this->breadcrumb_stack[] = $title;
        }
        $indent = ($depth) ? str_repeat("\t", $depth) : '';

        $classes   = empty($item->classes) ? [] : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;

        $class_names = implode(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
        $class_names = $class_names ? ' class="' . esc_attr($class_names) . '"' : '';

        $id_attr = apply_filters('nav_menu_item_id', 'menu-item-' . $item->ID, $item, $args, $depth);
        $id_attr = $id_attr ? ' id="' . esc_attr($id_attr) . '"' : '';

        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        // Attributs du lien
        $atts = [
            'title'  => !empty($item->attr_title) ? $item->attr_title : '',
            'target' => !empty($item->target) ? $item->target : '',
            'rel'    => !empty($item->xfn) ? $item->xfn : '',
            'href'   => !empty($item->url) ? $item->url : '',
        ];

        $atts = apply_filters('nav_menu_link_attributes', $atts, $item, $args, $depth);

        $attributes = '';
        foreach ($atts as $attr => $value) {
            if (!empty($value)) {
                $value = ('href' === $attr) ? esc_url($value) : esc_attr($value);
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }

        // Récupérer icône et description ACF
        $icon        = $this->options['show_icon'] ? get_field('bt_menu_icon', $item->ID) : null;
        $description = $this->options['show_description'] ? get_field('bt_menu_description', $item->ID) : '';

        $item_output  = $args->before ?? '';
        $item_output .= '<a' . $attributes . ' class="bt-fancyMenu-link">';

        // Icône (toujours avant le contenu, la position est gérée par CSS via le wrapper)
        if ($icon) {
            $item_output .= $this->render_icon($icon);
        }

        // Count badge (nombre de sous-items) — affiché si activé globalement ET sur l'item ACF
        $count_html = '';
        if ($this->options['show_count']) {
            $show_count_on_item = get_field('bt_menu_show_count', $item->ID);
            if ($show_count_on_item) {
                $count = $this->get_children_count($item->ID);
                if ($count > 0) {
                    $count_html = '<span class="bt-fancyMenu-count">' . $count . '</span>';
                }
            }
        }

        // Contenu texte
        $item_output .= '<span class="bt-fancyMenu-content">';
        $item_output .= '<span class="bt-fancyMenu-title">';
        $item_output .= ($args->link_before ?? '') . apply_filters('the_title', $item->title, $item->ID) . ($args->link_after ?? '');
        $item_output .= $count_html; // Count juste après le titre
        $item_output .= '</span>';

        if ($description) {
            $item_output .= '<span class="bt-fancyMenu-desc">' . wp_kses_post($description) . '</span>';
        }
        $item_output .= '</span>';

        $item_output .= '</a>';
        $item_output .= $args->after ?? '';

        $output .= apply_filters('walker_nav_menu_start_el', $item_output, $item, $depth, $args);
    }

    /**
     * Fin d'un élément — pop le breadcrumb si c'était un parent.
     */
    public function end_el(&$output, $item, $depth = 0, $args = null) {
        // Pop du breadcrumb après avoir traité tous les enfants
        if (in_array('menu-item-has-children', (array) $item->classes)) {
            array_pop($this->breadcrumb_stack);
        }
        $output .= "</li>\n";
    }

    /**
     * Rend l'icône (SVG inline ou img).
     */
    private function render_icon(?array $icon): string {
        if (!$icon || empty($icon['url'])) {
            return '';
        }

        $url      = $icon['url'];
        $alt      = $icon['alt'] ?? '';
        $mime     = $icon['mime_type'] ?? '';
        $is_svg   = ($mime === 'image/svg+xml') || (substr($url, -4) === '.svg');

        // SVG inline pour permettre le style CSS (couleur)
        if ($is_svg && !empty($icon['id'])) {
            $svg_path = get_attached_file($icon['id']);
            if ($svg_path && file_exists($svg_path)) {
                $svg_content = file_get_contents($svg_path);
                // Nettoyer le XML declaration et doctype
                $svg_content = preg_replace('/<\?xml[^>]*\?>/i', '', $svg_content);
                $svg_content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg_content);
                return '<span class="bt-fancyMenu-icon bt-fancyMenu-icon--svg">' . $svg_content . '</span>';
            }
        }

        // Image normale
        return '<span class="bt-fancyMenu-icon"><img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy" /></span>';
    }
}
