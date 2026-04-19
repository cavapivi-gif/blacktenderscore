<?php
namespace BlackTenders\Elementor;

defined('ABSPATH') || exit;

use Elementor\Plugin;

/**
 * Ajoute l'option "Template Mobile" aux widgets Loop d'Elementor Pro.
 *
 * Rend une copie du Loop avec le template mobile, masquée via CSS media queries.
 */
class LoopMobileTemplate {

    /** @var int|null Template ID pour le rendu mobile en cours */
    private static ?int $forcing_template = null;

    /** @var bool Flag pour éviter la récursion */
    private static bool $rendering_mobile = false;

    public function init(): void {
        // Injecter les contrôles dans les widgets Loop
        add_action('elementor/element/loop-grid/section_layout/after_section_end', [$this, 'add_mobile_template_controls'], 10, 2);
        add_action('elementor/element/loop-carousel/section_layout/after_section_end', [$this, 'add_mobile_template_controls'], 10, 2);

        // Hook principal pour ajouter la version mobile après le desktop
        add_filter('elementor/widget/render_content', [$this, 'append_mobile_version'], 10, 2);
    }

    /**
     * Ajoute les contrôles "Template Mobile".
     */
    public function add_mobile_template_controls(\Elementor\Element_Base $element, array $args): void {
        $element->start_controls_section('section_bt_mobile_template', [
            'label' => __('BT - Template Mobile', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $element->add_control('bt_enable_mobile_template', [
            'label'        => __('Activer template mobile', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'default'      => '',
            'return_value' => 'yes',
        ]);

        $templates = $this->get_loop_templates();

        $element->add_control('bt_mobile_template_id', [
            'label'     => __('Template Mobile', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => $templates,
            'default'   => '',
            'condition' => ['bt_enable_mobile_template' => 'yes'],
        ]);

        $element->add_control('bt_mobile_breakpoint', [
            'label'     => __('Breakpoint (px)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'default'   => 767,
            'min'       => 320,
            'max'       => 1200,
            'condition' => ['bt_enable_mobile_template' => 'yes'],
        ]);

        $element->end_controls_section();
    }

    /**
     * Liste des templates Loop.
     */
    private function get_loop_templates(): array {
        $templates = ['' => __('-- Selectionner --', 'blacktenderscore')];

        $query = new \WP_Query([
            'post_type'      => 'elementor_library',
            'posts_per_page' => 100,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'   => '_elementor_template_type',
                    'value' => 'loop-item',
                ],
            ],
            'orderby' => 'title',
            'order'   => 'ASC',
        ]);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $templates[get_the_ID()] = get_the_title();
            }
            wp_reset_postdata();
        }

        return $templates;
    }

    /**
     * Ajoute la version mobile après le contenu desktop.
     */
    public function append_mobile_version(string $content, \Elementor\Element_Base $widget): string {
        $name = $widget->get_name();
        if (!in_array($name, ['loop-grid', 'loop-carousel'], true)) {
            return $content;
        }

        // Éviter la récursion
        if (self::$rendering_mobile) {
            return $content;
        }

        $settings = $widget->get_settings_for_display();

        if (($settings['bt_enable_mobile_template'] ?? '') !== 'yes') {
            return $content;
        }

        $mobile_tpl = (int) ($settings['bt_mobile_template_id'] ?? 0);
        if (!$mobile_tpl) {
            return $content;
        }

        $breakpoint = (int) ($settings['bt_mobile_breakpoint'] ?? 767);
        $widget_id  = $widget->get_id();

        // Rendre la version mobile
        $mobile_content = $this->render_mobile_loop($widget, $settings, $mobile_tpl);

        // CSS + wrappers
        $css = sprintf(
            '<style>
                .bt-loop-mobile-%1$s { display: none; }
                @media (max-width: %2$dpx) {
                    .bt-loop-desktop-%1$s { display: none !important; }
                    .bt-loop-mobile-%1$s { display: block !important; }
                }
            </style>',
            esc_attr($widget_id),
            $breakpoint
        );

        $desktop = sprintf('<div class="bt-loop-desktop-%s">%s</div>', esc_attr($widget_id), $content);
        $mobile  = sprintf('<div class="bt-loop-mobile-%s">%s</div>', esc_attr($widget_id), $mobile_content);

        return $css . $desktop . $mobile;
    }

    /**
     * Rend le loop mobile en utilisant un shortcode Elementor.
     * Le shortcode crée une nouvelle instance propre du widget.
     */
    private function render_mobile_loop(\Elementor\Element_Base $widget, array $settings, int $mobile_template_id): string {
        self::$rendering_mobile = true;

        // Récupérer la config du widget
        $widget_name = $widget->get_name();
        $widget_data = $widget->get_raw_data();

        // Modifier les settings pour le mobile
        $mobile_settings = $settings;
        $mobile_settings['template_id'] = $mobile_template_id;
        $mobile_settings['bt_enable_mobile_template'] = ''; // Désactiver pour éviter récursion
        $mobile_settings['alternate_template'] = ''; // Simplifier
        $mobile_settings['alternate_templates'] = [];

        // Créer le widget mobile via l'API Elementor
        $widget_type = Plugin::instance()->widgets_manager->get_widget_types($widget_name);
        if (!$widget_type) {
            self::$rendering_mobile = false;
            return '';
        }

        // Construire les données du widget mobile
        $mobile_data = [
            'id' => $widget->get_id() . '_mobile',
            'elType' => 'widget',
            'widgetType' => $widget_name,
            'settings' => $mobile_settings,
        ];

        // Créer et rendre le widget
        ob_start();

        try {
            /** @var \Elementor\Widget_Base $mobile_widget */
            $mobile_widget = Plugin::instance()->elements_manager->create_element_instance($mobile_data);

            if ($mobile_widget) {
                $mobile_widget->print_element();
            }
        } catch (\Exception $e) {
            // Log error silently
            error_log('BT Mobile Template Error: ' . $e->getMessage());
        }

        $output = ob_get_clean();

        self::$rendering_mobile = false;

        return $output;
    }
}
