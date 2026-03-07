<?php
namespace BT_Regiondo\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — FAQ accordéon / tabs.
 *
 * Lit le repeater ACF `exp_faq` (faq_question + faq_answer) du post courant
 * et affiche soit un accordéon, soit des tabs.
 * Injecte également le JSON-LD FAQPage pour le SEO.
 */
class FaqAccordion extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-faq-accordion'; }
    public function get_title():      string { return 'BT — FAQ'; }
    public function get_icon():       string { return 'eicon-toggle'; }
    public function get_categories(): array  { return ['bt-regiondo']; }
    public function get_keywords():   array  { return ['faq', 'accordéon', 'accordion', 'tabs', 'question', 'bt']; }
    public function get_script_depends(): array { return ['bt-elementor']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Content ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'       => __('Champ ACF repeater', 'bt-regiondo'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'exp_faq',
            'description' => __("Nom du champ repeater ACF. Par défaut : exp_faq (faq_question + faq_answer)", 'bt-regiondo'),
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'accordion' => __('Accordéon', 'bt-regiondo'),
                'tabs'      => __('Tabs', 'bt-regiondo'),
            ],
            'default' => 'accordion',
        ]);

        $this->add_control('open_first', [
            'label'        => __('Ouvrir le premier élément', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'bt-regiondo'),
            'label_off'    => __('Non', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['layout' => 'accordion'],
        ]);

        $this->add_control('schema_faq', [
            'label'        => __('Injecter le Schema FAQPage (SEO)', 'bt-regiondo'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'bt-regiondo'),
            'label_off'    => __('Non', 'bt-regiondo'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Style — Questions ─────────────────────────────────────────────
        $this->start_controls_section('style_question', [
            'label' => __('Style — Questions', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'question_typography',
            'selector' => '{{WRAPPER}} .bt-faq__question',
        ]);

        $this->add_control('question_color', [
            'label'     => __('Couleur', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__question' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('question_bg', [
            'label'     => __('Fond', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__question' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('question_padding', [
            'label'      => __('Padding', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__question' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_control('question_active_color', [
            'label'     => __('Couleur (actif)', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--open .bt-faq__question' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__tab--active'                  => 'color: {{VALUE}}',
            ],
        ]);

        $this->add_control('question_active_bg', [
            'label'     => __('Fond (actif)', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--open .bt-faq__question' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__tab--active'                  => 'background-color: {{VALUE}}',
            ],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-faq__item',
        ]);

        $this->add_responsive_control('border_radius', [
            'label'      => __('Border radius', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__item' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden'],
        ]);

        $this->add_responsive_control('items_gap', [
            'label'      => __('Espacement entre éléments', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__list' => 'display: flex; flex-direction: column; gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Réponses ──────────────────────────────────────────────
        $this->start_controls_section('style_answer', [
            'label' => __('Style — Réponses', 'bt-regiondo'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'answer_typography',
            'selector' => '{{WRAPPER}} .bt-faq__answer',
        ]);

        $this->add_control('answer_color', [
            'label'     => __('Couleur', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__answer' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('answer_bg', [
            'label'     => __('Fond', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__answer-inner' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('answer_padding', [
            'label'      => __('Padding', 'bt-regiondo'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__answer-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
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

        $rows = get_field($s['acf_field'], $post_id);

        if (empty($rows)) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucune FAQ trouvée pour le champ « ' . esc_html($s['acf_field']) . ' ».</p>';
            }
            return;
        }

        $layout     = $s['layout'] ?: 'accordion';
        $open_first = $s['open_first'] === 'yes';
        $uid        = 'bt-faq-' . $this->get_id();

        if ($layout === 'tabs') {
            $this->render_tabs($rows, $uid);
        } else {
            $this->render_accordion($rows, $uid, $open_first);
        }

        // Schema.org FAQPage
        if ($s['schema_faq'] === 'yes' && !is_admin()) {
            $this->render_schema($rows);
        }
    }

    private function render_accordion(array $rows, string $uid, bool $open_first): void {
        echo "<div class=\"bt-faq bt-faq--accordion\" id=\"{$uid}\" data-bt-accordion>";
        echo '<ul class="bt-faq__list" role="list">';

        foreach ($rows as $i => $row) {
            $q        = $row['faq_question'] ?? ($row['question'] ?? '');
            $a        = $row['faq_answer']   ?? ($row['answer']   ?? '');
            $item_id  = "{$uid}-item-{$i}";
            $panel_id = "{$uid}-panel-{$i}";
            $is_open  = ($open_first && $i === 0);
            $open_cls = $is_open ? ' bt-faq__item--open' : '';

            echo "<li class=\"bt-faq__item{$open_cls}\">";
            echo "<button class=\"bt-faq__question\" id=\"{$item_id}\" aria-expanded=\"" . ($is_open ? 'true' : 'false') . "\" aria-controls=\"{$panel_id}\">";
            echo '<span>' . esc_html($q) . '</span>';
            echo '<span class="bt-faq__icon" aria-hidden="true"></span>';
            echo '</button>';
            echo "<div class=\"bt-faq__answer\" id=\"{$panel_id}\" role=\"region\" aria-labelledby=\"{$item_id}\"" . ($is_open ? '' : ' hidden') . '>';
            echo '<div class="bt-faq__answer-inner">' . wp_kses_post(nl2br(esc_html($a))) . '</div>';
            echo '</div>';
            echo '</li>';
        }

        echo '</ul></div>';
    }

    private function render_tabs(array $rows, string $uid): void {
        echo "<div class=\"bt-faq bt-faq--tabs\" id=\"{$uid}\" data-bt-tabs>";

        // Tab bar
        echo '<div class="bt-faq__tablist" role="tablist">';
        foreach ($rows as $i => $row) {
            $q      = $row['faq_question'] ?? ($row['question'] ?? "Question " . ($i + 1));
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-tabpanel-{$i}";
            $active = $i === 0 ? ' bt-faq__tab--active' : '';
            echo "<button class=\"bt-faq__tab{$active}\" id=\"{$tab_id}\" role=\"tab\" aria-selected=\"" . ($i === 0 ? 'true' : 'false') . "\" aria-controls=\"{$pan_id}\" tabindex=\"" . ($i === 0 ? '0' : '-1') . "\">";
            echo esc_html($q);
            echo '</button>';
        }
        echo '</div>';

        // Panels
        foreach ($rows as $i => $row) {
            $a      = $row['faq_answer'] ?? ($row['answer'] ?? '');
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-tabpanel-{$i}";
            $hidden = $i === 0 ? '' : ' hidden';
            echo "<div class=\"bt-faq__tabpanel\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\"{$hidden}>";
            echo '<div class="bt-faq__answer-inner">' . wp_kses_post(nl2br(esc_html($a))) . '</div>';
            echo '</div>';
        }

        echo '</div>';
    }

    private function render_schema(array $rows): void {
        $items = [];
        foreach ($rows as $row) {
            $q = $row['faq_question'] ?? ($row['question'] ?? '');
            $a = $row['faq_answer']   ?? ($row['answer']   ?? '');
            if ($q && $a) {
                $items[] = [
                    '@type'          => 'Question',
                    'name'           => $q,
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
                ];
            }
        }

        if (empty($items)) return;

        $schema = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items];
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
