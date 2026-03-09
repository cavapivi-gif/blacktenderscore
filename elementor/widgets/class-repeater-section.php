<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Section Repeater générique.
 *
 * Affiche n'importe quel champ ACF Repeater avec :
 *  - un en-tête optionnel (titre + description, dynamic-tag compatibles)
 *  - un mapping de sous-champs manuel ou automatique
 *  - une logique de découpe flexible (premiers N / offset+N / indexes précis)
 *  - un layout list ou grid responsive
 *
 * Deux instances du widget sur la même page avec des offsets différents
 * permettent d'afficher la même liste en plusieurs blocs séparés.
 */
class RepeaterSection extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-repeater-section'; }
    public function get_title():      string { return 'BT — Section Repeater'; }
    public function get_icon():       string { return 'eicon-post-list'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['repeater', 'section', 'liste', 'grille', 'acf', 'bt']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->section_header();
        $this->section_data();
        $this->section_slice();
        $this->section_layout();
        $this->section_style_header();
        $this->section_style_items();
    }

    // ── TAB CONTENT ───────────────────────────────────────────────────────────

    private function section_header(): void {

        $this->start_controls_section('sec_header', [
            'label' => __('En-tête', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_header', [
            'label'        => __('Afficher l\'en-tête', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('header_title', [
            'label'       => __('Titre', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'label_block' => true,
            'dynamic'     => ['active' => true],
            'condition'   => ['show_header' => 'yes'],
        ]);

        $this->add_control('header_title_tag', [
            'label'     => __('Balise', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'p' => 'p'],
            'default'   => 'h3',
            'condition' => ['show_header' => 'yes', 'header_title!' => ''],
        ]);

        $this->add_control('header_desc', [
            'label'     => __('Description', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXTAREA,
            'rows'      => 3,
            'dynamic'   => ['active' => true],
            'condition' => ['show_header' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    private function section_data(): void {

        $this->start_controls_section('sec_data', [
            'label' => __('Données', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        // ── Champ ACF ─────────────────────────────────────────────────────────

        $repeater_opts             = $this->acf_repeater_options();
        $repeater_opts['_custom']  = __('→ Saisie manuelle…', 'blacktenderscore');
        $repeater_default          = array_key_first($repeater_opts);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF Repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $repeater_opts,
            'default' => $repeater_default,
        ]);

        $this->add_control('acf_field_custom', [
            'label'       => __('Clé du champ (manuelle)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'mon_repeater',
            'condition'   => ['acf_field' => '_custom'],
        ]);

        $this->add_control('divider_map', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // ── Mapping sous-champs ───────────────────────────────────────────────

        $this->add_control('auto_map', [
            'label'        => __('Affichage automatique', 'blacktenderscore'),
            'description'  => __('Affiche tous les sous-champs du repeater tels quels.', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('sf_title', [
            'label'       => __('Sous-champ — Titre', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'item_title',
            'condition'   => ['auto_map!' => 'yes'],
        ]);

        $this->add_control('sf_body', [
            'label'       => __('Sous-champ — Description', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'item_desc',
            'condition'   => ['auto_map!' => 'yes'],
        ]);

        $this->add_control('sf_icon', [
            'label'       => __('Sous-champ — Icône', 'blacktenderscore'),
            'description' => __('Classe CSS (fas fa-anchor), emoji, ou champ image ACF.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'item_icon',
            'condition'   => ['auto_map!' => 'yes'],
        ]);

        $this->add_control('sf_link', [
            'label'       => __('Sous-champ — Lien', 'blacktenderscore'),
            'description' => __('Champ link ACF ou URL directe. Rend l\'item cliquable.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'item_url',
            'condition'   => ['auto_map!' => 'yes'],
        ]);

        $this->end_controls_section();
    }

    private function section_slice(): void {

        $this->start_controls_section('sec_slice', [
            'label' => __('Découpe', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('slice_mode', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'all'      => __('Tout afficher', 'blacktenderscore'),
                'first_n'  => __('Les N premiers', 'blacktenderscore'),
                'offset_n' => __('Offset + N', 'blacktenderscore'),
                'specific' => __('Indexes précis', 'blacktenderscore'),
            ],
            'default' => 'all',
        ]);

        $this->add_control('items_count', [
            'label'     => __('Nombre d\'items', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 100,
            'default'   => 3,
            'condition' => ['slice_mode' => ['first_n', 'offset_n']],
        ]);

        $this->add_control('items_offset', [
            'label'       => __('Départ depuis l\'index', 'blacktenderscore'),
            'description' => __('0 = premier item. Combiné avec « Nombre », permet de diviser le repeater en plusieurs blocs.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'min'         => 0,
            'default'     => 0,
            'condition'   => ['slice_mode' => 'offset_n'],
        ]);

        $this->add_control('items_specific', [
            'label'       => __('Indexes (1-basé)', 'blacktenderscore'),
            'description' => __('Ex : 1,3,5 — affiche les items 1, 3 et 5. Les indexes manquants sont ignorés.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '1,3,5',
            'condition'   => ['slice_mode' => 'specific'],
        ]);

        $this->add_control('empty_text', [
            'label'       => __('Texte si vide', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => __('Aucun contenu disponible.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();
    }

    private function section_layout(): void {

        $this->start_controls_section('sec_layout', [
            'label' => __('Mise en page', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'list' => __('Liste', 'blacktenderscore'),
                'grid' => __('Grille', 'blacktenderscore'),
            ],
            'default' => 'list',
        ]);

        $this->add_responsive_control('columns', [
            'label'     => __('Colonnes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6'],
            'default'        => '3',
            'tablet_default' => '2',
            'mobile_default' => '1',
            'condition' => ['layout' => 'grid'],
            'selectors' => [
                '{{WRAPPER}} .bt-rsection__items--grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
            ],
        ]);

        $this->add_responsive_control('items_gap', [
            'label'      => __('Espacement', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-rsection__items' => 'gap: {{SIZE}}{{UNIT}};',
            ],
        ]);

        $this->end_controls_section();
    }

    // ── TAB STYLE ─────────────────────────────────────────────────────────────

    private function section_style_header(): void {

        $this->start_controls_section('style_header', [
            'label'     => __('En-tête', 'blacktenderscore'),
            'tab'       => \Elementor\Controls_Manager::TAB_STYLE,
            'condition' => ['show_header' => 'yes'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'label'    => __('Typographie — Titre', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-rsection__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur — Titre', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-rsection__title' => 'color: {{VALUE}};'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'desc_typography',
            'label'    => __('Typographie — Description', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-rsection__desc',
        ]);

        $this->add_control('desc_color', [
            'label'     => __('Couleur — Description', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-rsection__desc' => 'color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('header_spacing', [
            'label'      => __('Espace sous l\'en-tête', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-rsection__header' => 'margin-bottom: {{SIZE}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    private function section_style_items(): void {

        $this->start_controls_section('style_items', [
            'label' => __('Items', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        // Icône
        $this->add_control('item_icon_color', [
            'label'     => __('Couleur icône', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-rsection__item-icon' => 'color: {{VALUE}};'],
        ]);

        $this->add_responsive_control('item_icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-rsection__item-icon' => 'font-size: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_control('divider_item_title', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // Titre item
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'item_title_typography',
            'label'    => __('Typographie — Titre item', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-rsection__item-title',
        ]);

        $this->add_control('item_title_color', [
            'label'     => __('Couleur — Titre item', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-rsection__item-title' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('divider_item_body', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // Body item
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'item_body_typography',
            'label'    => __('Typographie — Description item', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-rsection__item-body',
        ]);

        $this->add_control('item_body_color', [
            'label'     => __('Couleur — Description item', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-rsection__item-body' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('divider_item_card', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // Carte / conteneur item
        $this->add_control('item_bg', [
            'label'     => __('Fond de l\'item', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-rsection__item' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'item_border',
            'selector' => '{{WRAPPER}} .bt-rsection__item',
        ]);

        $this->add_responsive_control('item_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-rsection__item' => 'border-radius: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('item_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em', 'rem'],
            'selectors'  => ['{{WRAPPER}} .bt-rsection__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        if (!function_exists('get_field')) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">ACF Pro requis.</p>';
            }
            return;
        }

        // Résoudre la clé du champ
        $key = $s['acf_field'] === '_custom'
            ? trim((string) ($s['acf_field_custom'] ?? ''))
            : (string) ($s['acf_field'] ?? '');

        if (!$key || $key === '_none') {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun champ ACF sélectionné.</p>';
            }
            return;
        }

        $rows = get_field($key, get_the_ID());
        if (!is_array($rows)) $rows = [];

        // Découpe
        $rows = $this->apply_slice($rows, $s);

        if (empty($rows)) {
            $empty = trim((string) ($s['empty_text'] ?? ''));
            if ($empty) {
                echo '<p class="bt-rsection__empty">' . esc_html($empty) . '</p>';
            } elseif (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun item à afficher pour « ' . esc_html($key) . ' ».</p>';
            }
            return;
        }

        $layout = $s['layout'] ?: 'list';
        $auto   = ($s['auto_map'] ?? '') === 'yes';

        echo '<div class="bt-rsection">';

        // En-tête
        if (($s['show_header'] ?? '') === 'yes') {
            $title = trim((string) ($s['header_title'] ?? ''));
            $desc  = trim((string) ($s['header_desc']  ?? ''));
            if ($title || $desc) {
                $htag = in_array($s['header_title_tag'] ?? '', ['h2','h3','h4','h5','p'], true)
                    ? $s['header_title_tag']
                    : 'h3';
                echo '<header class="bt-rsection__header">';
                if ($title) {
                    echo "<{$htag} class=\"bt-rsection__title\">" . esc_html($title) . "</{$htag}>";
                }
                if ($desc) {
                    echo '<p class="bt-rsection__desc">' . wp_kses_post($desc) . '</p>';
                }
                echo '</header>';
            }
        }

        // Items
        if ($layout === 'grid') {
            echo '<div class="bt-rsection__items bt-rsection__items--grid" role="list">';
            $itag = 'div';
        } else {
            echo '<ul class="bt-rsection__items bt-rsection__items--list">';
            $itag = 'li';
        }

        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            echo "<{$itag} class=\"bt-rsection__item\"" . ($layout === 'grid' ? ' role="listitem"' : '') . '>';
            $auto ? $this->render_item_auto($row) : $this->render_item_mapped($row, $s);
            echo "</{$itag}>";
        }

        echo $layout === 'grid' ? '</div>' : '</ul>';
        echo '</div>';
    }

    // ── Rendu item — mode auto ────────────────────────────────────────────────

    private function render_item_auto(array $row): void {
        foreach ($row as $key => $value) {
            if ($value === null || $value === '' || $value === false) continue;

            $key_class = 'bt-rsection__item-field--' . esc_attr(sanitize_html_class($key));

            // Image ACF (array avec 'url')
            if (is_array($value) && isset($value['url'])) {
                echo '<span class="bt-rsection__item-field ' . $key_class . '">';
                echo '<img src="' . esc_url($value['url']) . '" alt="' . esc_attr($value['alt'] ?? '') . '" loading="lazy">';
                echo '</span>';
                continue;
            }

            // Lien ACF (array avec 'url' + 'title')
            if (is_array($value) && isset($value['url'], $value['title'])) {
                echo '<span class="bt-rsection__item-field ' . $key_class . '">';
                echo '<a href="' . esc_url($value['url']) . '"' . (!empty($value['target']) ? ' target="' . esc_attr($value['target']) . '"' : '') . '>' . esc_html($value['title']) . '</a>';
                echo '</span>';
                continue;
            }

            // Scalaire
            if (is_scalar($value)) {
                echo '<span class="bt-rsection__item-field ' . $key_class . '">' . wp_kses_post((string) $value) . '</span>';
            }
        }
    }

    // ── Rendu item — mode mapping ─────────────────────────────────────────────

    private function render_item_mapped(array $row, array $s): void {
        $sf_icon  = trim((string) ($s['sf_icon']  ?? ''));
        $sf_title = trim((string) ($s['sf_title'] ?? ''));
        $sf_body  = trim((string) ($s['sf_body']  ?? ''));
        $sf_link  = trim((string) ($s['sf_link']  ?? ''));

        $link_data = $sf_link ? $this->resolve_link($row[$sf_link] ?? null) : null;

        // Ouvrir le lien (wrapper)
        if ($link_data) {
            echo '<a href="' . esc_url($link_data['url']) . '"'
                . (!empty($link_data['target']) ? ' target="' . esc_attr($link_data['target']) . '"' : '')
                . ' class="bt-rsection__item-link">';
        }

        // Icône
        if ($sf_icon && !empty($row[$sf_icon])) {
            $icon_val = $row[$sf_icon];
            echo '<span class="bt-rsection__item-icon" aria-hidden="true">';
            echo $this->render_icon_value($icon_val);
            echo '</span>';
        }

        // Contenu texte
        $title_val = $sf_title ? ($row[$sf_title] ?? '') : '';
        $body_val  = $sf_body  ? ($row[$sf_body]  ?? '') : '';

        if ($title_val !== '' || $body_val !== '') {
            echo '<div class="bt-rsection__item-content">';
            if ($title_val !== '') {
                echo '<span class="bt-rsection__item-title">' . esc_html((string) $title_val) . '</span>';
            }
            if ($body_val !== '') {
                echo '<p class="bt-rsection__item-body">' . wp_kses_post((string) $body_val) . '</p>';
            }
            echo '</div>';
        }

        if ($link_data) {
            echo '</a>';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Rend une valeur de champ icône :
     *  - Classe CSS (fas fa-anchor, eicon-check…) → <i>
     *  - Array image ACF                          → <img>
     *  - Autre texte / emoji                      → texte brut
     */
    private function render_icon_value(mixed $val): string {
        if (is_array($val) && isset($val['url'])) {
            return '<img src="' . esc_url($val['url']) . '" alt="' . esc_attr($val['alt'] ?? '') . '" loading="lazy">';
        }

        if (is_string($val) && $val !== '') {
            // Ressemble à des classes CSS (lettres, tirets, espaces, sans HTML)
            if (preg_match('/^[a-z0-9\-_ ]+$/i', $val)) {
                return '<i class="' . esc_attr($val) . '" aria-hidden="true"></i>';
            }
            return esc_html($val);
        }

        return '';
    }

    /**
     * Normalise une valeur de champ link ACF :
     *  - array ACF ['url', 'title', 'target']
     *  - string URL directe
     *
     * @return array{url: string, target: string}|null
     */
    private function resolve_link(mixed $val): ?array {
        if (empty($val)) return null;

        if (is_array($val) && !empty($val['url'])) {
            return ['url' => $val['url'], 'target' => $val['target'] ?? ''];
        }

        if (is_string($val) && filter_var($val, FILTER_VALIDATE_URL)) {
            return ['url' => $val, 'target' => ''];
        }

        return null;
    }

    /**
     * Applique la logique de découpe selon le mode choisi.
     *
     * @param  array $rows Toutes les lignes du repeater
     * @param  array $s    Settings Elementor
     * @return array
     */
    private function apply_slice(array $rows, array $s): array {
        $mode = $s['slice_mode'] ?? 'all';

        switch ($mode) {
            case 'first_n':
                $count = max(1, (int) ($s['items_count'] ?? 3));
                return array_slice($rows, 0, $count);

            case 'offset_n':
                $offset = max(0, (int) ($s['items_offset'] ?? 0));
                $count  = max(0, (int) ($s['items_count']  ?? 0));
                return $count > 0
                    ? array_slice($rows, $offset, $count)
                    : array_slice($rows, $offset);

            case 'specific':
                $raw = (string) ($s['items_specific'] ?? '');
                // Parse "1, 3, 5" → [0, 2, 4] (0-basé)
                $indexes = array_filter(
                    array_map(fn($v) => (int) trim($v) - 1, explode(',', $raw)),
                    fn($i) => $i >= 0
                );
                $out = [];
                foreach (array_unique($indexes) as $i) {
                    if (isset($rows[$i])) $out[] = $rows[$i];
                }
                return $out;

            default: // 'all'
                return $rows;
        }
    }

    /**
     * Retourne tous les champs ACF de type "repeater" enregistrés,
     * formatés pour un SELECT Elementor.
     *
     * @return array<string, string>
     */
    private function acf_repeater_options(): array {
        if (!function_exists('acf_get_field_groups')) {
            return ['_none' => __('ACF requis', 'blacktenderscore')];
        }

        $opts = [];
        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group['key']);
            if (!is_array($fields)) continue;
            foreach ($fields as $field) {
                if (($field['type'] ?? '') !== 'repeater') continue;
                $label          = $group['title'] . ' → ' . $field['label'];
                $opts[$field['name']] = $label . '  (' . $field['name'] . ')';
            }
        }

        return $opts ?: ['_none' => __('Aucun repeater ACF trouvé', 'blacktenderscore')];
    }
}
