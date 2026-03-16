<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

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
class FaqAccordion extends AbstractBtWidget {
    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-faq-accordion',
            'title'    => 'BT — FAQ',
            'icon'     => 'eicon-toggle',
            'keywords' => ['faq', 'accordéon', 'accordion', 'tabs', 'question', 'bt'],
            'css'      => ['bt-faq-accordion'],
            'js'       => ['bt-elementor'],
        ];
    }

    // ══ Controls ══════════════════════════════════════════════════════════════

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF (FAQ)', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => self::get_faq_field_options(),
            'default' => 'exp_faq',
        ]);

        $this->add_control('prioritize_page_questions', [
            'label'        => __('Mettre en avant les questions de la page', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('Place les questions de la page courante en premier quand on combine FAQ locale et page liée.', 'blacktenderscore'),
            'condition'    => ['acf_field' => '__combined_exp_faq'],
        ]);

        $this->add_control('combined_local_fields', [
            'label'       => __('Champs ACF locaux (FAQ)', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => self::get_faq_repeater_options(),
            'default'     => ['exp_faq_current', 'city_faq'],
            'label_block' => true,
            'description' => __('Repeaters ACF utilisés pour les FAQ sur cette page (ex: exp_faq_current, city_faq).', 'blacktenderscore'),
            'condition'   => ['acf_field' => '__combined_exp_faq'],
        ]);

        $this->add_control('combined_relationship_field', [
            'label'       => __('Champ relation vers pages FAQ', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => self::get_faq_relationship_options(),
            'default'     => 'exp_faq',
            'label_block' => true,
            'description' => __('Champ ACF de type Relationship qui pointe vers les pages FAQ (ex: exp_faq).', 'blacktenderscore'),
            'condition'   => ['acf_field' => '__combined_exp_faq'],
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'accordion' => ['title' => __('Accordéon', 'blacktenderscore'), 'icon' => 'eicon-toggle'],
                'tabs'      => ['title' => __('Tabs', 'blacktenderscore'),      'icon' => 'eicon-tabs'],
            ],
            'default' => 'accordion',
            'toggle'  => false,
        ]);

        $this->add_control('selected_icon', [
            'label'     => __('Icône (fermé)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-plus', 'library' => 'fa-solid'],
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->add_control('selected_active_icon', [
            'label'     => __('Icône (ouvert)', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'default'   => ['value' => 'fas fa-minus', 'library' => 'fa-solid'],
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->add_control('icon_align', [
            'label'     => __('Position icône', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'left'  => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'right' => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default'   => 'right',
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->add_control('faq_mode', [
            'label'        => __('Un seul ouvert à la fois', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['layout' => 'accordion'],
        ]);

        $this->add_control('open_first', [
            'label'        => __('Ouvrir le premier élément', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['layout' => 'accordion'],
        ]);

        $this->add_control('schema_faq', [
            'label'        => __('Injecter Schema FAQPage (SEO)', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        $this->register_section_title_style('{{WRAPPER}} .bt-faq__section-title');

        // ── Style — Espacement ────────────────────────────────────────────────
        $this->start_controls_section('style_accordion_layout', [
            'label' => __('Style — Espacement', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->register_gap_control(
            'items_gap',
            __('Espacement entre éléments', 'blacktenderscore'),
            ['{{WRAPPER}} .bt-faq__list'],
            8
        );
        $this->end_controls_section();

        // ── Style — Item (Normal / Survol / Actif) ────────────────────────────
        $this->register_item_3state_style(
            'item',
            __('Style — Item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-faq__item',
            '{{WRAPPER}} .bt-faq__item:hover',
            '{{WRAPPER}} .bt-faq__item.bt-faq__item--active'
        );

        // ── Style — Questions ─────────────────────────────────────────────────
        $this->start_controls_section('style_question', [
            'label' => __('Style — Questions', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'question_typography',
            'selector' => '{{WRAPPER}} .bt-faq__header',
        ]);

        $this->start_controls_tabs('question_style_tabs');

        $this->start_controls_tab('question_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('question_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('question_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('question_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('question_color_hover', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header:hover' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('question_bg_hover', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__header:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('question_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('question_color_active', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__header' => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__tab--active'                  => 'color: {{VALUE}}',
            ],
        ]);
        $this->add_control('question_bg_active', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__header' => 'background-color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__tab--active'                  => 'background-color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('question_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('question_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Icône ─────────────────────────────────────────────────────
        $this->start_controls_section('style_icon', [
            'label'     => __('Style — Icône', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'accordion'],
        ]);

        $this->start_controls_tabs('icon_style_tabs');

        $this->start_controls_tab('icon_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('icon_color', [
            'label'     => __('Couleur icône', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__icon'         => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__icon--closed' => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('icon_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('icon_color_active', [
            'label'     => __('Couleur icône (actif)', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__icon'       => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-faq__item--active .bt-faq__icon--open' => 'color: {{VALUE}}',
            ],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
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
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__header' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Réponses ──────────────────────────────────────────────────
        $this->start_controls_section('style_answer', [
            'label' => __('Style — Réponses', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'answer_typography',
            'selector' => '{{WRAPPER}} .bt-faq__body-inner',
        ]);

        $this->add_control('answer_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__body-inner' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('answer_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__body-inner' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('answer_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__body-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'answer_border',
            'selector' => '{{WRAPPER}} .bt-faq__body-inner',
        ]);

        $this->add_responsive_control('answer_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__body-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Style — Tabs ──────────────────────────────────────────────────────
        $this->start_controls_section('style_tabs', [
            'label'     => __('Style — Tabs', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['layout' => 'tabs'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'tab_typography',
            'selector' => '{{WRAPPER}} .bt-faq__tab',
        ]);

        $this->start_controls_tabs('tab_style_tabs');

        $this->start_controls_tab('tab_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('tab_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('tab_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('tab_tab_active', ['label' => __('Actif', 'blacktenderscore')]);
        $this->add_control('tab_color_active', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab--active' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('tab_bg_active', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab--active' => 'background-color: {{VALUE}}'],
        ]);
        $this->add_control('tab_active_border_color', [
            'label'     => __('Couleur bordure active', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-faq__tab--active' => 'border-bottom-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('tab_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('tab_gap', [
            'label'      => __('Espacement entre tabs', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'selectors'  => ['{{WRAPPER}} .bt-faq__tablist' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
    }

    // ══ Render ════════════════════════════════════════════════════════════════

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $field = $s['acf_field'] ?? '';

        if ($field === '__combined_exp_faq') {
            $rows = $this->get_combined_faq_rows($s);
        } else {
            $rows = $this->get_acf_rows(
                $field,
                sprintf(__('Aucune FAQ trouvée pour le champ « %s ».', 'blacktenderscore'), $field)
            );
        }

        if (!$rows) return;

        $layout = $s['layout'] ?: 'accordion';
        $uid    = 'bt-faq-' . $this->get_id();

        $this->render_section_title($s, 'bt-faq__section-title');

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
            if ($q === '' && $a === '') {
                continue;
            }
            $item_id   = "{$uid}-item-{$i}";
            $panel_id  = "{$uid}-panel-{$i}";
            $is_active = ($open_first && $i === 0);
            $cls       = $is_active ? ' bt-faq__item--active' : '';

            echo "<li class=\"bt-faq__item{$cls}\">";
            echo "<button class=\"bt-faq__header\" id=\"{$item_id}\" aria-expanded=\"" . ($is_active ? 'true' : 'false') . "\" aria-controls=\"{$panel_id}\">";

        // Wrapper .bt-faq__icon toujours présent :
        // - pseudo-éléments ::before/::after dessinent un +/− CSS (fallback robuste)
        // - si des icônes Elementor sont définies, on les injecte à l’intérieur
        //   et on désactive le +/− CSS via la classe --has-fa (voir CSS).
        $icon_cls  = $has_fa_icons ? 'bt-faq__icon bt-faq__icon--has-fa' : 'bt-faq__icon';
        $icon_html = '<span class="' . esc_attr($icon_cls) . '" aria-hidden="true">';
            if ($has_fa_icons) {
                $icon_html .= '<span class="bt-faq__icon--closed">' . $icon_closed_html . '</span>';
                $icon_html .= '<span class="bt-faq__icon--open">'   . $icon_open_html   . '</span>';
            }
            $icon_html .= '</span>';

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
            if ($q === '') {
                continue;
            }
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
            if ($a === '') {
                continue;
            }
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
        $options = static::acf_field_options('faq', ['exp_faq' => 'FAQ (exp_faq)']);

        // Option virtuelle : combine FAQ de la page courante (repeater) + FAQ d'une page liée (relationship).
        // La clé commence par "__" pour éviter toute collision avec un vrai nom de champ ACF.
        $virtual_key   = '__combined_exp_faq';
        $virtual_label = __('FAQ locale + page liée (combiné)', 'blacktenderscore');

        // On place l’option combinée en tête pour qu’elle soit facile à trouver.
        return [$virtual_key => $virtual_label] + $options;
    }

    /**
     * Champs ACF de type repeater contenant "faq" dans leur nom.
     *
     * @return array<string, string>
     */
    private static function get_faq_repeater_options(): array {
        if (!function_exists('acf_get_field_groups')) {
            return ['exp_faq_current' => 'FAQ pour cette sortie (exp_faq_current) [repeater]'];
        }

        $options = [];
        foreach (acf_get_field_groups() as $group) {
            foreach (acf_get_fields($group['key']) ?: [] as $field) {
                if (($field['type'] ?? '') !== 'repeater') {
                    continue;
                }
                if (stripos($field['name'] ?? '', 'faq') === false) {
                    continue;
                }
                $name             = $field['name'];
                $options[$name] = sprintf(
                    '%s (%s) [repeater]',
                    $field['label'] ?? $name,
                    $name
                );
            }
        }

        if (empty($options)) {
            $options['exp_faq_current'] = 'FAQ pour cette sortie (exp_faq_current) [repeater]';
            $options['city_faq']        = 'FAQ locale (city_faq) [repeater]';
        }

        return $options;
    }

    /**
     * Champs ACF de type relationship contenant "faq" dans leur nom.
     *
     * @return array<string, string>
     */
    private static function get_faq_relationship_options(): array {
        if (!function_exists('acf_get_field_groups')) {
            return ['exp_faq' => 'FAQ (Schema FAQPage) (exp_faq) [relationship]'];
        }

        $options = [];
        foreach (acf_get_field_groups() as $group) {
            foreach (acf_get_fields($group['key']) ?: [] as $field) {
                if (($field['type'] ?? '') !== 'relationship') {
                    continue;
                }
                if (stripos($field['name'] ?? '', 'faq') === false) {
                    continue;
                }
                $name             = $field['name'];
                $options[$name] = sprintf(
                    '%s (%s) [relationship]',
                    $field['label'] ?? $name,
                    $name
                );
            }
        }

        if (empty($options)) {
            $options['exp_faq'] = 'FAQ (Schema FAQPage) (exp_faq) [relationship]';
        }

        return $options;
    }

    /**
     * Combine les FAQ locales (page courante) et celles d’une page liée via un champ relationship.
     *
     * - Repeater locaux   : contrôlés par combined_local_fields (par défaut exp_faq_current, city_faq)
     * - Relationship FAQ  : contrôlé par combined_relationship_field (par défaut exp_faq)
     *
     * @param array $s  Settings complets du widget.
     * @return array|null
     */
    private function get_combined_faq_rows(array $s): ?array {
        $local_rows = [];

        // Champs repeaters locaux choisis dans le panel (fallback sur nos valeurs historiques).
        $local_fields = (array) ($s['combined_local_fields'] ?? ['exp_faq_current', 'city_faq']);

        foreach ($local_fields as $field_name) {
            if (!is_string($field_name) || $field_name === '') {
                continue;
            }
            $rows = $this->get_acf_rows($field_name) ?? [];
            foreach ((array) $rows as $row) {
                if (is_array($row)) {
                    $local_rows[] = $row;
                }
            }
        }

        // Pages FAQ liées via relationship, chacune pouvant contenir un ou plusieurs repeaters de FAQ.
        $related_rows = [];
        if (function_exists('get_field')) {
            $relationship_field = is_string($s['combined_relationship_field'] ?? '')
                ? $s['combined_relationship_field']
                : 'exp_faq';

            $related = get_field($relationship_field, get_the_ID());
            if ($related instanceof \WP_Post) {
                $related = [$related];
            }
            foreach ((array) $related as $rel) {
                // Relationship peut renvoyer : WP_Post, ID, ou tableau avec clé ID.
                if ($rel instanceof \WP_Post) {
                    $post_id = $rel->ID;
                } elseif (is_numeric($rel)) {
                    $post_id = (int) $rel;
                } elseif (is_array($rel) && isset($rel['ID'])) {
                    $post_id = (int) $rel['ID'];
                } else {
                    continue;
                }

                if ($post_id <= 0) {
                    continue;
                }

                $found_rows = false;

                // Réutilise la même liste de repeaters que pour la page courante,
                // mais appliquée à la page FAQ liée.
                foreach ($local_fields as $field_name) {
                    if (!is_string($field_name) || $field_name === '') {
                        continue;
                    }
                    $repeater = get_field($field_name, $post_id);
                    foreach ((array) $repeater as $row) {
                        if (is_array($row)) {
                            $related_rows[] = $row;
                            $found_rows      = true;
                        }
                    }
                }

                // Si aucun repeater n'a retourné de lignes, ajoute au moins la page FAQ elle-même
                // (resolve_qa() utilisera faq_description ou le contenu du post).
                if (!$found_rows) {
                    $post = get_post($post_id);
                    if ($post instanceof \WP_Post) {
                        $related_rows[] = $post;
                    }
                }
            }
        }

        $prioritize_page = ($s['prioritize_page_questions'] ?? '') === 'yes';

        $rows = $prioritize_page
            ? array_merge($local_rows, $related_rows)
            : array_merge($related_rows, $local_rows);

        if (empty($rows)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucune FAQ trouvée pour les champs combinés.', 'blacktenderscore'));
            }
            return null;
        }

        return $rows;
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
