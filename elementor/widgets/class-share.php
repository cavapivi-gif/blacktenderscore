<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Partager.
 *
 * Bouton de partage natif (Web Share API / clipboard) + liens vers les IA
 * (Claude, ChatGPT, Gemini) pour poser une question sur la page courante.
 */
class Share extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-share',
            'title'    => 'BT — Partager',
            'icon'     => 'eicon-share',
            'keywords' => ['partager', 'share', 'ia', 'claude', 'chatgpt', 'lien', 'bt'],
            'js'       => ['bt-elementor'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Partage ───────────────────────────────────────────────────────
        $this->start_controls_section('section_share', [
            'label' => __('Partage', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('share_label', [
            'label'   => __('Label bouton partage', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Partager cette page',
        ]);

        $this->add_control('copied_label', [
            'label'   => __('Message après copie (fallback)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Lien copié !',
        ]);

        $this->end_controls_section();

        // ── Demander à l'IA ───────────────────────────────────────────────
        $this->start_controls_section('section_ai', [
            'label' => __('Demander à l\'IA', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_ai', [
            'label'        => __('Afficher les liens IA', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('ai_section_title', [
            'label'     => __('Titre section IA', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Demander à l\'IA',
            'condition' => ['show_ai' => 'yes'],
        ]);

        $this->add_control('ai_prompt_prefix', [
            'label'       => __('Début du message envoyé à l\'IA', 'blacktenderscore'),
            'description' => __('Le titre + URL de la page seront ajoutés.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'label_block' => true,
            'default'     => 'Résume et présente-moi cette page :',
            'condition'   => ['show_ai' => 'yes'],
        ]);

        $this->add_control('show_claude', [
            'label'        => __('Afficher Claude', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['show_ai' => 'yes'],
        ]);

        $this->add_control('claude_label', [
            'label'     => __('Label Claude', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Demander à Claude',
            'condition' => ['show_ai' => 'yes', 'show_claude' => 'yes'],
        ]);

        $this->add_control('show_chatgpt', [
            'label'        => __('Afficher ChatGPT', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['show_ai' => 'yes'],
        ]);

        $this->add_control('chatgpt_label', [
            'label'     => __('Label ChatGPT', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Demander à ChatGPT',
            'condition' => ['show_ai' => 'yes', 'show_chatgpt' => 'yes'],
        ]);

        $this->add_control('show_gemini', [
            'label'        => __('Afficher Gemini', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_ai' => 'yes'],
        ]);

        $this->add_control('gemini_label', [
            'label'     => __('Label Gemini', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'Demander à Gemini',
            'condition' => ['show_ai' => 'yes', 'show_gemini' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Bouton partage ─────────────────────────────────────────
        $this->start_controls_section('style_share_btn', [
            'label' => __('Style — Bouton partage', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'share_btn_typography',
            'selector' => '{{WRAPPER}} .bt-share__btn',
        ]);

        $this->add_control('share_btn_color', [
            'label'     => __('Couleur texte', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-share__btn' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('share_btn_bg', [
            'label'     => __('Couleur fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-share__btn' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'share_btn_border',
            'selector' => '{{WRAPPER}} .bt-share__btn',
        ]);

        $this->add_responsive_control('share_btn_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-share__btn' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('share_btn_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '10', 'right' => '20', 'bottom' => '10', 'left' => '20', 'unit' => 'px', 'isLinked' => false],
            'selectors'  => ['{{WRAPPER}} .bt-share__btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Liens IA ──────────────────────────────────────────────
        $this->start_controls_section('style_ai', [
            'label'     => __('Style — Liens IA', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['show_ai' => 'yes'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'ai_title_typography',
            'selector' => '{{WRAPPER}} .bt-share__ai-title',
        ]);

        $this->add_control('ai_title_color', [
            'label'     => __('Couleur titre IA', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-share__ai-title' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'ai_link_typography',
            'selector' => '{{WRAPPER}} .bt-share__ai-link',
        ]);

        $this->add_control('ai_link_color', [
            'label'     => __('Couleur liens IA', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-share__ai-link' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('ai_link_bg', [
            'label'     => __('Fond liens IA', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-share__ai-link' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('ai_links_gap', [
            'label'      => __('Espacement entre liens', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-share__ai-links' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        $page_title = get_the_title();
        $page_url   = get_permalink();
        $prompt     = ($s['ai_prompt_prefix'] ?: 'Résume et présente-moi cette page :') . ' ' . $page_title . ' — ' . $page_url;
        $prompt_enc = rawurlencode($prompt);

        $share_label  = esc_html($s['share_label'] ?: 'Partager cette page');
        $copied_label = esc_attr($s['copied_label'] ?: 'Lien copié !');

        echo '<div class="bt-share">';

        echo '<button type="button" class="bt-share__btn" data-bt-share data-bt-url="' . esc_attr($page_url) . '" data-bt-title="' . esc_attr($page_title) . '" data-bt-copied="' . $copied_label . '">';
        echo $share_label;
        echo '</button>';

        if ($s['show_ai'] === 'yes') {
            echo '<div class="bt-share__ai">';

            if ($s['ai_section_title']) {
                echo '<p class="bt-share__ai-title">' . esc_html($s['ai_section_title']) . '</p>';
            }

            echo '<div class="bt-share__ai-links">';

            if ($s['show_claude'] === 'yes') {
                $claude_url = 'https://claude.ai/new?q=' . $prompt_enc;
                echo '<a href="' . esc_url($claude_url) . '" class="bt-share__ai-link bt-share__ai-link--claude" target="_blank" rel="noopener">' . esc_html($s['claude_label'] ?: 'Demander à Claude') . '</a>';
            }

            if ($s['show_chatgpt'] === 'yes') {
                $chatgpt_url = 'https://chatgpt.com/?q=' . $prompt_enc;
                echo '<a href="' . esc_url($chatgpt_url) . '" class="bt-share__ai-link bt-share__ai-link--chatgpt" target="_blank" rel="noopener">' . esc_html($s['chatgpt_label'] ?: 'Demander à ChatGPT') . '</a>';
            }

            if ($s['show_gemini'] === 'yes') {
                $gemini_url = 'https://gemini.google.com/app?text=' . $prompt_enc;
                echo '<a href="' . esc_url($gemini_url) . '" class="bt-share__ai-link bt-share__ai-link--gemini" target="_blank" rel="noopener">' . esc_html($s['gemini_label'] ?: 'Demander à Gemini') . '</a>';
            }

            echo '</div>'; // .bt-share__ai-links
            echo '</div>'; // .bt-share__ai
        }

        echo '</div>'; // .bt-share
    }
}
