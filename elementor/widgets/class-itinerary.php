<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Programme / Itinéraire.
 *
 * Lit le repeater ACF `exp_itinerary` (step_time, step_title, step_desc)
 * et affiche une timeline verticale.
 */
class Itinerary extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-itinerary'; }
    public function get_title():      string { return 'BT — Programme / Itinéraire'; }
    public function get_icon():       string { return 'eicon-time-line'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['itinéraire', 'programme', 'timeline', 'étapes', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'exp_itinerary',
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Programme', 'bt-regiondo'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p'],
            'default' => 'h3',
        ]);

        $this->add_control('show_time', [
            'label'        => __('Afficher l\'heure / moment', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('connector', [
            'label'   => __('Connecteur timeline', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'line' => __('Ligne verticale', 'bt-regiondo'),
                'none' => __('Aucun', 'bt-regiondo'),
            ],
            'default' => 'line',
        ]);

        $this->end_controls_section();

        // ── Style — Timeline ──────────────────────────────────────────────
        $this->start_controls_section('style_timeline', [
            'label' => __('Style — Timeline', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'section_title_typo',
            'label'    => __('Typographie titre section', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-itin__title',
        ]);

        $this->add_control('section_title_color', [
            'label'     => __('Couleur titre section', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('steps_gap', [
            'label'      => __('Espacement entre étapes', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 8, 'max' => 80]],
            'default'    => ['size' => 32, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('line_color', [
            'label'     => __('Couleur de la ligne', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step::before' => 'background-color: {{VALUE}}'],
            'condition' => ['connector' => 'line'],
        ]);

        $this->add_responsive_control('line_width', [
            'label'      => __('Épaisseur de la ligne', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 1, 'max' => 8]],
            'default'    => ['size' => 2, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step::before' => 'width: {{SIZE}}{{UNIT}}'],
            'condition'  => ['connector' => 'line'],
        ]);

        $this->add_control('dot_color', [
            'label'     => __('Couleur du point', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__dot' => 'background-color: {{VALUE}}; border-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille du point', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 32]],
            'default'    => ['size' => 14, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-itin__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->end_controls_section();

        // ── Style — Texte ─────────────────────────────────────────────────
        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte des étapes', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'time_typo',
            'label'    => __('Typographie heure', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-itin__time',
        ]);

        $this->add_control('time_color', [
            'label'     => __('Couleur heure', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__time' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'step_title_typo',
            'label'    => __('Typographie titre étape', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-itin__step-title',
        ]);

        $this->add_control('step_title_color', [
            'label'     => __('Couleur titre étape', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'desc_typo',
            'label'    => __('Typographie description', 'bt-regiondo'),
            'selector' => '{{WRAPPER}} .bt-itin__step-desc',
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur description', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step-desc' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('content_padding', [
            'label'      => __('Padding contenu étape', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step-body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('step_bg', [
            'label'     => __('Fond de l\'étape', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-itin__step-body' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'step_border',
            'selector' => '{{WRAPPER}} .bt-itin__step-body',
        ]);

        $this->add_responsive_control('step_radius', [
            'label'      => __('Border radius étape', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-itin__step-body' => 'border-radius: {{SIZE}}{{UNIT}}'],
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

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_itinerary');
        $rows       = get_field($field_name, $post_id);

        if (empty($rows)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucune étape dans le champ <code>' . esc_html($field_name) . '</code>.</p>';
            }
            return;
        }

        $tag          = esc_attr($s['title_tag'] ?: 'h3');
        $show_time    = $s['show_time'] === 'yes';
        $show_desc    = $s['show_description'] === 'yes';
        $connector    = $s['connector'] ?: 'line';
        $connector_cls = $connector === 'none' ? ' bt-itin--no-connector' : '';

        echo '<div class="bt-itin' . $connector_cls . '">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-itin__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        echo '<ol class="bt-itin__list">';

        foreach ($rows as $i => $row) {
            $time  = $row['step_time']  ?? '';
            $title = $row['step_title'] ?? '';
            $desc  = $row['step_desc']  ?? '';

            echo '<li class="bt-itin__step">';
            echo '<span class="bt-itin__dot" aria-hidden="true"></span>';
            echo '<div class="bt-itin__step-body">';

            if ($show_time && $time) {
                echo '<span class="bt-itin__time">' . esc_html($time) . '</span>';
            }

            if ($title) {
                echo '<strong class="bt-itin__step-title">' . esc_html($title) . '</strong>';
            }

            if ($show_desc && $desc) {
                echo '<p class="bt-itin__step-desc">' . esc_html($desc) . '</p>';
            }

            echo '</div>';
            echo '</li>';
        }

        echo '</ol></div>';
    }
}
