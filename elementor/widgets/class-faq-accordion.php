<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — FAQ accordéon / tabs.
 *
 * Reprend 100% les paramètres du widget natif Elementor "Accordion"
 * + nos besoins : ACF repeater, tabs layout, Schema.org FAQPage.
 *
 * HTML classes :
 *   .bt-faq__header       — bouton question (était .bt-faq__question)
 *   .bt-faq__item--active — item ouvert (était --open)
 *   .bt-faq__body         — wrapper collapsible (était .bt-faq__answer)
 *   .bt-faq__body-inner   — contenu interne (était .bt-faq__answer-inner)
 *
 * Animation accordion : CSS grid-template-rows 0fr→1fr (pas de [hidden]).
 */
class FaqAccordion extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-faq-accordion'; }
    public function get_title():      string { return 'BT — FAQ'; }
    public function get_icon():       string { return 'eicon-toggle'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['faq', 'accordéon', 'accordion', 'tabs', 'question', 'bt']; }
    public function get_script_depends(): array { return ['bt-elementor']; }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('section_title_tag', [
            'label'   => __('Balise titre section', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p', 'span' => 'span'],
            'default' => 'h3',
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF (FAQ)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => self::get_faq_field_options(),
            'default' => 'exp_faq',
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'accordion' => __('Accordéon', 'blacktenderscore'),
                'tabs'      => __('Tabs', 'blacktenderscore'),
            ],
            'default' => 'accordion',
        ]);

        $this->add_control('selected_icon', [
            'label'     => __('Icône (fermé)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-plus', 'library' => 'fa-solid'],
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->add_control('selected_active_icon', [
            'label'     => __('Icône (ouvert)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-minus', 'library' => 'fa-solid'],
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->add_control('icon_align', [
            'label'     => __('Position icône', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'options'   => [
                'left'  => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'right' => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default'   => 'right',
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->add_control('faq_mode', [
            'label'        => __('Un seul ouvert à la fois', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['layout' => 'accordion'],
        ]);

        $this->add_control('open_first', [
            'label'        => __('Ouvrir le premier élément', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['layout' => 'accordion'],
        ]);

        $this->add_control('schema_faq', [
            'label'        => __('Injecter Schema FAQPage (SEO)', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Style — Titre section ──────────────────────────────────────────────
        $this->start_controls_section('style_heading', [
            'label'     => __('Style — Titre section', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['section_title!' => ''],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'heading_typography',
            'selector' => '{{WRAPPER}} .bt-faq__section-title',
        ]);

        $this->add_control('heading_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__section-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('heading_align', [
            'label'     => __('Alignement', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'),  'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'),  'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'),  'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => ['{{WRAPPER}} .bt-faq__section-title' => 'text-align: {{VALUE}}'],
        ]);

        $this->add_responsive_control('heading_spacing', [
            'label'      => __('Espacement sous le titre', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__section-title' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Accordéon ─────────────────────────────────────────────────
        $this->start_controls_section('style_accordion', [
            'label' => __('Style — Accordéon', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('items_gap', [
            'label'      => __('Espacement entre éléments', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__list' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        // Item : Normal / Survol / Actif
        $this->start_controls_tabs('item_style_tabs');

        $this->start_controls_tab('item_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('item_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__item' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-faq__item',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('item_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('item_bg_hover', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__item:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border_hover',
            'selector' => '{{WRAPPER}} .bt-faq__item:hover',
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('item_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('item_bg_active', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__item.bt-faq__item--active' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border_active',
            'selector' => '{{WRAPPER}} .bt-faq__item.bt-faq__item--active',
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('item_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%', 'em'],
            'selectors'  => [
                '{{WRAPPER}} .bt-faq__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden',
            ],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'item_shadow',
            'selector' => '{{WRAPPER}} .bt-faq__item',
        ]);

        $this->end_controls_section();

        // ── Style — Questions ─────────────────────────────────────────────────
        $this->start_controls_section('style_question', [
            'label' => __('Style — Questions', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'question_typography',
            'selector' => '{{WRAPPER}} .bt-faq__header',
        ]);

        $this->start_controls_tabs('question_style_tabs');

        $this->start_controls_tab('question_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('question_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('question_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('question_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('question_color_hover', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header:hover' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('question_bg_hover', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('question_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('question_color_active', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__header' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__tab--active'                  => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('question_bg_active', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__header' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__tab--active'                  => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('question_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('question_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Icône ─────────────────────────────────────────────────────
        $this->start_controls_section('style_icon', [
            'label'     => __('Style — Icône', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->start_controls_tabs('icon_style_tabs');

        $this->start_controls_tab('icon_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('icon_color', [
            'label'     => __('Couleur icône', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__icon'         => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__icon--closed' => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('icon_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('icon_color_active', [
            'label'     => __('Couleur icône (actif)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__icon'       => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__icon--open' => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 8, 'max' => 48]],
            'selectors'  => [
                '{{WRAPPER}} .bt-faq__icon'         => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-faq__icon--closed' => 'font-size: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-faq__icon--open'   => 'font-size: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('icon_spacing', [
            'label'      => __('Espacement icône / texte', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__header' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Réponses ──────────────────────────────────────────────────
        $this->start_controls_section('style_answer', [
            'label' => __('Style — Réponses', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'answer_typography',
            'selector' => '{{WRAPPER}} .bt-faq__body-inner',
        ]);

        $this->add_control('answer_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__body-inner' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('answer_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__body-inner' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('answer_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__body-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'answer_border',
            'selector' => '{{WRAPPER}} .bt-faq__body-inner',
        ]);

        $this->add_responsive_control('answer_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__body-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Tabs ──────────────────────────────────────────────────────
        $this->start_controls_section('style_tabs', [
            'label'     => __('Style — Tabs', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'tabs'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'tab_typography',
            'selector' => '{{WRAPPER}} .bt-faq__tab',
        ]);

        $this->start_controls_tabs('tab_style_tabs');

        $this->start_controls_tab('tab_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('tab_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('tab_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('tab_color_active', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab--active' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('tab_bg_active', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab--active' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('tab_active_border_color', [
            'label'     => __('Couleur bordure active', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab--active' => 'border-bottom-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('tab_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('tab_gap', [
            'label'      => __('Espacement entre tabs', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__tablist' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ══ Render ════════════════════════════════════════════════════════════════

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

        $layout = $s['layout'] ?: 'accordion';
        $uid    = 'bt-faq-' . $this->get_id();

        // Titre de section optionnel
        if (!empty($s['section_title'])) {
            $tag = esc_attr($s['section_title_tag'] ?: 'h3');
            echo "<{$tag} class=\"bt-faq__section-title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        if ($layout === 'tabs') {
            $this->render_tabs($rows, $uid);
        } else {
            $this->render_accordion($rows, $uid, ($s['open_first'] ?? '') === 'yes', $s);
        }

        // Schema.org FAQPage
        if (($s['schema_faq'] ?? '') === 'yes' && !is_admin()) {
            $this->render_schema($rows);
        }
    }

    // ── Accordion ─────────────────────────────────────────────────────────────

    private function render_accordion(array $rows, string $uid, bool $open_first, array $s): void {
        $faq_mode   = ($s['faq_mode'] ?? '') === 'yes';
        $icon_align = $s['icon_align'] ?? 'right';

        $icon_closed_html = $this->capture_icon($s['selected_icon']        ?? []);
        $icon_open_html   = $this->capture_icon($s['selected_active_icon'] ?? []);
        $has_fa_icons     = $icon_closed_html !== '' || $icon_open_html !== '';

        $data_faq_mode = $faq_mode ? ' data-bt-faq-mode' : '';

        echo "<div class=\"bt-faq bt-faq--accordion bt-faq--icon-{$icon_align}\" id=\"{$uid}\" data-bt-accordion{$data_faq_mode}>";
        echo '<ul class="bt-faq__list" role="list">';

        foreach ($rows as $i => $row) {
            [$q, $a]   = $this->resolve_qa($row);
            $item_id   = "{$uid}-item-{$i}";
            $panel_id  = "{$uid}-panel-{$i}";
            $is_active = ($open_first && $i === 0);
            $cls       = $is_active ? ' bt-faq__item--active' : '';

            echo "<li class=\"bt-faq__item{$cls}\">";
            echo "<button class=\"bt-faq__header\" id=\"{$item_id}\" aria-expanded=\"" . ($is_active ? 'true' : 'false') . "\" aria-controls=\"{$panel_id}\">";

            if ($has_fa_icons) {
                $icon_html = '<span class="bt-faq__icon-wrap" aria-hidden="true">'
                           . '<span class="bt-faq__icon--closed">' . $icon_closed_html . '</span>'
                           . '<span class="bt-faq__icon--open">'   . $icon_open_html   . '</span>'
                           . '</span>';
            } else {
                $icon_html = '<span class="bt-faq__icon" aria-hidden="true"></span>';
            }

            if ($icon_align === 'left') {
                echo $icon_html;
                echo '<span class="bt-faq__title-text">' . esc_html($q) . '</span>';
            } else {
                echo '<span class="bt-faq__title-text">' . esc_html($q) . '</span>';
                echo $icon_html;
            }

            echo '</button>';
            echo "<div class=\"bt-faq__body\" id=\"{$panel_id}\" role=\"region\" aria-labelledby=\"{$item_id}\">";
            echo '<div class="bt-faq__body-inner">' . wp_kses_post(nl2br($a)) . '</div>';
            echo '</div>';
            echo '</li>';
        }

        echo '</ul></div>';
    }

    // ── Tabs ──────────────────────────────────────────────────────────────────

    private function render_tabs(array $rows, string $uid): void {
        echo "<div class=\"bt-faq bt-faq--tabs\" id=\"{$uid}\" data-bt-tabs>";

        echo '<div class="bt-faq__tablist" role="tablist">';
        foreach ($rows as $i => $row) {
            [$q]    = $this->resolve_qa($row);
            $q      = $q ?: 'Question ' . ($i + 1);
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-tabpanel-{$i}";
            $active = $i === 0 ? ' bt-faq__tab--active' : '';
            $sel    = $i === 0 ? 'true' : 'false';
            $tabi   = $i === 0 ? '0' : '-1';
            echo "<button class=\"bt-faq__tab{$active}\" id=\"{$tab_id}\" role=\"tab\" aria-selected=\"{$sel}\" aria-controls=\"{$pan_id}\" tabindex=\"{$tabi}\">";
            echo esc_html($q);
            echo '</button>';
        }
        echo '</div>';

        foreach ($rows as $i => $row) {
            [, $a]      = $this->resolve_qa($row);
            $tab_id     = "{$uid}-tab-{$i}";
            $pan_id     = "{$uid}-tabpanel-{$i}";
            $active_cls = $i === 0 ? ' bt-faq__tabpanel--active' : '';
            echo "<div class=\"bt-faq__tabpanel{$active_cls}\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\">";
            echo '<div class="bt-faq__body-inner">' . wp_kses_post(nl2br($a)) . '</div>';
            echo '</div>';
        }

        echo '</div>';
    }

    // ── ACF field discovery ───────────────────────────────────────────────────

    /**
     * Retourne tous les champs ACF dont le nom contient "faq" (insensible à la casse).
     * Inclut repeaters et relationships — resolve_qa() gère les deux types.
     */
    private static function get_faq_field_options(): array {
        $options = [];

        if (!function_exists('acf_get_field_groups')) {
            return ['exp_faq' => 'FAQ (exp_faq)'];
        }

        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group['key']);
            if (empty($fields)) continue;
            foreach ($fields as $field) {
                if (stripos($field['name'], 'faq') === false) continue;
                $type_label = $field['type']; // repeater, relationship, post_object…
                $options[$field['name']] = sprintf('%s (%s) [%s]',
                    $field['label'],
                    $field['name'],
                    $type_label
                );
            }
        }

        return $options ?: ['exp_faq' => 'FAQ (exp_faq)'];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolve_qa($row): array {
        if ($row instanceof \WP_Post) {
            $q = $row->post_title;
            $a = function_exists('get_field')
                ? (string) get_field('faq_description', $row->ID)
                : wp_strip_all_tags($row->post_content);
            return [$q, $a];
        }
        if (is_array($row)) {
            $q = $row['faq_question'] ?? ($row['question'] ?? '');
            $a = $row['faq_answer']   ?? ($row['answer']   ?? '');
            return [$q, $a];
        }
        return ['', ''];
    }

    private function capture_icon(array $icon_settings): string {
        if (empty($icon_settings['value'])) return '';
        ob_start();
        \Elementor\Icons_Manager::render_icon($icon_settings, ['aria-hidden' => 'true']);
        return (string) ob_get_clean();
    }

    private function render_schema(array $rows): void {
        $items = [];
        foreach ($rows as $row) {
            [$q, $a] = $this->resolve_qa($row);
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
