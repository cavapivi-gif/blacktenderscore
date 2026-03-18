<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Icons_Manager;
use Elementor\Repeater;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Points forts.
 *
 * Source : ACF repeater (noms de sous-champs configurables)
 *       OU Repeater natif Elementor (mode manuel, dynamic tags supportés).
 *
 * Layouts : grille | liste.
 * Icône   : emoji, texte, Elementor ICONS, image ACF (array ou URL) ou SVG.
 * Steps   : liste enfant en dot, depuis un sous-champ textarea ACF (1 ligne = 1 step).
 */
class Highlights extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-highlights',
            'title'    => 'BT — Points forts',
            'icon'     => 'eicon-check-circle',
            'keywords' => ['highlights', 'points', 'forts', 'inclus', 'avantages', 'bt'],
            'css'      => ['bt-highlights'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls(['title' => __('Points forts', 'blacktenderscore')]);
        $this->register_collapsible_section_control();

        $this->add_control('separator_data', [
            'type' => Controls_Manager::DIVIDER,
        ]);

        $this->add_control('data_source', [
            'label'   => __('Source des données', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'acf'    => ['title' => __('ACF', 'blacktenderscore'),    'icon' => 'eicon-database'],
                'static' => ['title' => __('Manuel', 'blacktenderscore'), 'icon' => 'eicon-editor-list-ul'],
            ],
            'default' => 'acf',
            'toggle'  => false,
        ]);

        // ── Mode ACF ──────────────────────────────────────────────────────
        $this->add_control('acf_field', [
            'label'     => __('Champ ACF repeater', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'exp_highlights',
            'dynamic'   => ['active' => true],
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('max_items', [
            'label'     => __('Nombre max d\'éléments', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 1,
            'max'       => 50,
            'default'   => 12,
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('sf_icon', [
            'label'     => __('Sous-champ icône', 'blacktenderscore'),
            'description' => __('Emoji, texte, URL image ou champ ACF Image.', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'highlight_icon',
            'dynamic'   => ['active' => true],
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('sf_title', [
            'label'     => __('Sous-champ titre', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'highlight_title',
            'dynamic'   => ['active' => true],
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('sf_desc', [
            'label'     => __('Sous-champ description', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'highlight_desc',
            'dynamic'   => ['active' => true],
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('sf_steps', [
            'label'       => __('Sous-champ étapes enfant', 'blacktenderscore'),
            'description' => __('Repeater ACF imbriqué ou Textarea (1 ligne = 1 étape). Laisser vide pour désactiver.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'highlight_steps',
            'dynamic'     => ['active' => true],
            'condition'   => ['data_source' => 'acf'],
        ]);

        $this->add_control('sf_step_label', [
            'label'     => __('Sous-champ label de l\'étape', 'blacktenderscore'),
            'description' => __('Nom du sous-champ dans le repeater d\'étapes (ex: step_label).', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'step_label',
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('icon_fallback_mode', [
            'label'     => __('Quand l\'icône ACF est vide', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'icon' => __('Afficher une icône par défaut', 'blacktenderscore'),
                'none' => __('Ne rien afficher', 'blacktenderscore'),
            ],
            'default'   => 'icon',
            'separator' => 'before',
            'condition' => ['data_source' => 'acf'],
        ]);

        $this->add_control('default_icon', [
            'label'       => __('Icône de fallback', 'blacktenderscore'),
            'description' => __('Affiché si le sous-champ icône ACF est vide.', 'blacktenderscore'),
            'type'        => Controls_Manager::ICONS,
            'default'     => ['value' => 'fas fa-check', 'library' => 'fa-solid'],
            'skin'        => 'inline',
            'condition'   => ['data_source' => 'acf', 'icon_fallback_mode' => 'icon'],
        ]);

        // ── Mode manuel (Repeater natif) ──────────────────────────────────
        $repeater = new Repeater();

        $repeater->add_control('item_icon', [
            'label'   => __('Icône', 'blacktenderscore'),
            'type'    => Controls_Manager::ICONS,
            'default' => ['value' => 'fas fa-check', 'library' => 'fa-solid'],
            'skin'    => 'inline',
        ]);

        $repeater->add_control('item_title', [
            'label'       => __('Titre', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'label_block' => true,
            'dynamic'     => ['active' => true],
        ]);

        $repeater->add_control('item_desc', [
            'label'       => __('Description', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXTAREA,
            'default'     => '',
            'label_block' => true,
            'dynamic'     => ['active' => true],
        ]);

        $repeater->add_control('item_steps', [
            'label'       => __('Étapes enfant', 'blacktenderscore'),
            'description' => __('1 ligne = 1 étape (dot list).', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXTAREA,
            'default'     => '',
            'label_block' => true,
        ]);

        $this->add_control('static_items', [
            'label'       => __('Éléments', 'blacktenderscore'),
            'type'        => Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => [
                ['item_icon' => ['value' => 'fas fa-check', 'library' => 'fa-solid'], 'item_title' => __('Point fort 1', 'blacktenderscore'), 'item_desc' => ''],
                ['item_icon' => ['value' => 'fas fa-check', 'library' => 'fa-solid'], 'item_title' => __('Point fort 2', 'blacktenderscore'), 'item_desc' => ''],
                ['item_icon' => ['value' => 'fas fa-check', 'library' => 'fa-solid'], 'item_title' => __('Point fort 3', 'blacktenderscore'), 'item_desc' => ''],
            ],
            'title_field' => '{{{ item_title || "Item" }}}',
            'condition'   => ['data_source' => 'static'],
        ]);

        $this->add_control('show_icon', [
            'label'        => __('Afficher l\'icône', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'separator'    => 'before',
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

        $this->add_control('show_steps', [
            'label'        => __('Afficher les étapes enfant', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Mise en page ──────────────────────────────────────────────────
        $this->start_controls_section('section_layout', [
            'label' => __('Mise en page', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'grid' => ['title' => __('Grille', 'blacktenderscore'), 'icon' => 'eicon-gallery-grid'],
                'list' => ['title' => __('Liste',  'blacktenderscore'), 'icon' => 'eicon-post-list'],
            ],
            'default' => 'grid',
            'toggle'  => false,
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
            'label'      => __('Gap entre les cartes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 80]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-highlights__grid' => 'gap: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-highlights__list' => 'gap: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('widget_margin_bottom', [
            'label'      => __('Espacement bas du widget', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => ['px' => ['min' => 0, 'max' => 120]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights' => 'margin-bottom: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Position & alignement icône (responsive — mobile passe en colonne) ───
        $this->add_responsive_control('icon_position', [
            'label'          => __('Position icône', 'blacktenderscore'),
            'type'           => Controls_Manager::CHOOSE,
            'options'        => [
                'row'         => ['title' => __('Gauche (inline)',  'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'row-reverse' => ['title' => __('Droite (inline)',  'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
                'column'      => ['title' => __('Au-dessus (bloc)', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
            ],
            'default'        => 'row',
            'mobile_default' => 'column',
            'condition'      => ['show_icon' => 'yes'],
            'selectors'      => ['{{WRAPPER}} .bt-highlights__item' => 'flex-direction: {{VALUE}}'],
            'separator'      => 'before',
        ]);

        $this->add_control('icon_valign', [
            'label'     => __('Alignement vertical', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('Haut',   'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'flex-end'   => ['title' => __('Bas',    'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'   => 'flex-start',
            'condition' => ['show_icon' => 'yes', 'icon_position' => ['row', 'row-reverse']],
            'selectors' => ['{{WRAPPER}} .bt-highlights__item' => 'align-items: {{VALUE}}'],
        ]);

        $this->add_control('icon_halign', [
            'label'     => __('Alignement horizontal', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'flex-start' => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center'     => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'flex-end'   => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'default'   => 'flex-start',
            'condition' => ['show_icon' => 'yes', 'icon_position' => 'column'],
            'selectors' => ['{{WRAPPER}} .bt-highlights__item' => 'align-items: {{VALUE}}'],
        ]);

        $this->add_control('content_text_align', [
            'label'     => __('Alignement texte', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'condition' => ['icon_position' => 'column'],
            'selectors' => ['{{WRAPPER}} .bt-highlights__content' => 'text-align: {{VALUE}}'],
        ]);

        $this->add_responsive_control('icon_spacing', [
            'label'      => __('Espace icône ↔ texte', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 48]],
            'default'    => ['size' => 12, 'unit' => 'px'],
            'condition'  => ['show_icon' => 'yes'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__item' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('content_spacing', [
            'label'      => __('Espace titre ↔ description', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 4, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__desc' => 'margin-top: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_desc' => 'yes'],
        ]);

        $this->add_responsive_control('steps_spacing', [
            'label'      => __('Espace avant les étapes', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__steps' => 'margin-top: {{SIZE}}{{UNIT}}'],
            'condition'  => ['show_steps' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Styles via traits ─────────────────────────────────────────────
        $this->register_section_title_style(
            '{{WRAPPER}} .bt-highlights__section-title',
            [
                '{{WRAPPER}} .bt-highlights__section-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-collapsible-block .bt-highlights__section-title' => 'margin-bottom: 0',
                '{{WRAPPER}} .bt-collapsible-block' => 'margin-bottom: {{SIZE}}{{UNIT}}',
            ]
        );

        $this->register_item_3state_style(
            'item',
            __('Style — Item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__item'
        );

        $this->register_icon_style_section(
            'icon',
            __('Style — Icône / Image', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__icon',
            ['size' => 28],
            ['show_icon' => 'yes']
        );

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

        $this->register_typography_section(
            'item_steps',
            __('Style — Étapes enfant', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__steps li',
            [],
            [],
            ['show_steps' => 'yes']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s      = $this->get_settings_for_display();
        $source = $s['data_source'] ?? 'acf';

        $rows = $source === 'static'
            ? $this->resolve_static_rows($s)
            : $this->resolve_acf_rows($s);

        if (!$rows) return;

        $layout      = $s['layout'] ?: 'grid';
        $wrap_cls    = $layout === 'list' ? 'bt-highlights__list' : 'bt-highlights__grid';
        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';

        echo '<div class="bt-highlights">';
        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-highlights__section-title');
        } else {
            $this->render_section_title($s, 'bt-highlights__section-title');
        }
        echo "<div class=\"{$wrap_cls}\">";

        foreach ($rows as $row) {
            echo '<div class="bt-highlights__item">';

            if ($s['show_icon'] === 'yes') {
                $this->render_icon_slot($row['icon']);
            }

            echo '<div class="bt-highlights__content">';

            if ($s['show_title'] === 'yes' && $row['title']) {
                echo '<span class="bt-highlights__title">' . esc_html($row['title']) . '</span>';
            }

            if ($s['show_desc'] === 'yes' && $row['desc']) {
                echo '<div class="bt-highlights__desc">' . wp_kses_post($row['desc']) . '</div>';
            }

            if ($s['show_steps'] === 'yes' && !empty($row['steps'])) {
                echo '<ul class="bt-highlights__steps">';
                foreach ($row['steps'] as $step) {
                    echo '<li>' . esc_html($step) . '</li>';
                }
                echo '</ul>';
            }

            echo '</div>'; // .bt-highlights__content
            echo '</div>'; // .bt-highlights__item
        }

        echo '</div>'; // grid / list
        if ($collapsible) {
            $this->render_collapsible_section_close();
        }
        echo '</div>'; // .bt-highlights
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Affiche le slot icône : supporte emoji, Elementor ICONS, image ACF (array ou URL).
     *
     * @param mixed $icon  String emoji, array ICONS Elementor, array image ACF, ou URL string.
     */
    private function render_icon_slot($icon): void {
        if ($icon === null) return;

        // ACF Image field — return format "Array" : ['url'=>..., 'alt'=>..., ...]
        if (is_array($icon) && !empty($icon['url'])) {
            echo '<span class="bt-highlights__icon bt-highlights__icon--img" aria-hidden="true">';
            echo '<img src="' . esc_url($icon['url']) . '" alt="' . esc_attr($icon['alt'] ?? '') . '" loading="lazy">';
            echo '</span>';
            return;
        }

        // Elementor ICONS control — ['value'=>'fas fa-check', 'library'=>'fa-solid']
        if (is_array($icon) && !empty($icon['value'])) {
            echo '<span class="bt-highlights__icon" aria-hidden="true">';
            Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
            echo '</span>';
            return;
        }

        // String URL (ACF Image return format "URL", ou SVG/img direct)
        if (is_string($icon) && filter_var($icon, FILTER_VALIDATE_URL)) {
            echo '<span class="bt-highlights__icon bt-highlights__icon--img" aria-hidden="true">';
            echo '<img src="' . esc_url($icon) . '" alt="" loading="lazy">';
            echo '</span>';
            return;
        }

        // String courte = emoji ou texte
        if (is_string($icon) && $icon !== '') {
            echo '<span class="bt-highlights__icon" aria-hidden="true">' . esc_html($icon) . '</span>';
        }
    }

    /**
     * Résout les rows depuis un repeater ACF.
     *
     * Icône : string emoji | array image ACF | URL image | fallback Elementor ICONS.
     * Steps : textarea ACF (1 ligne = 1 step), splitté par \n.
     */
    private function resolve_acf_rows(array $s): ?array {
        if (!$this->acf_required()) return null;

        $field_name = sanitize_text_field($s['acf_field'] ?: 'exp_highlights');
        $raw = $this->get_acf_rows($field_name, __('Aucun point fort trouvé.', 'blacktenderscore'));
        if (!$raw) return null;

        $max         = max(1, (int) ($s['max_items'] ?: 12));
        $sf_icon     = sanitize_text_field($s['sf_icon']       ?: 'highlight_icon');
        $sf_title    = sanitize_text_field($s['sf_title']      ?: 'highlight_title');
        $sf_desc     = sanitize_text_field($s['sf_desc']       ?: 'highlight_desc');
        $sf_steps    = sanitize_text_field($s['sf_steps']      ?? 'highlight_steps');
        $sf_step_lbl = sanitize_text_field($s['sf_step_label'] ?? 'step_label');

        // Fallback icône si champ vide
        $fallback_mode = $s['icon_fallback_mode'] ?? 'icon';
        if ($fallback_mode === 'none') {
            $fallback = null;
        } else {
            $fallback_raw = $s['default_icon'] ?? [];
            if (is_string($fallback_raw) && $fallback_raw !== '') {
                $fallback = $fallback_raw;
            } elseif (is_array($fallback_raw) && !empty($fallback_raw['value'])) {
                $fallback = $fallback_raw;
            } else {
                $fallback = ['value' => 'fas fa-check', 'library' => 'fa-solid'];
            }
        }

        $rows = [];
        foreach (array_slice($raw, 0, $max) as $row) {
            $icon_raw = $row[$sf_icon] ?? '';

            // Détermine l'icône : image ACF array > URL string > emoji string > fallback
            if (is_array($icon_raw) && !empty($icon_raw['url'])) {
                $icon = $icon_raw; // image ACF (return format Array)
            } elseif (is_string($icon_raw) && trim($icon_raw) !== '') {
                $icon = trim($icon_raw); // emoji, texte, ou URL (détecté à l'affichage)
            } else {
                $icon = $fallback;
            }

            // Parse steps : repeater ACF imbriqué (array of arrays) OU textarea (string)
            $steps = [];
            if ($sf_steps !== '') {
                $raw_steps = $row[$sf_steps] ?? '';
                if (is_array($raw_steps) && !empty($raw_steps)) {
                    // Nested repeater ACF — chaque sub-row a un sous-champ $sf_step_lbl
                    foreach ($raw_steps as $sub) {
                        $lbl = trim($sub[$sf_step_lbl] ?? '');
                        if ($lbl !== '') $steps[] = $lbl;
                    }
                } elseif (is_string($raw_steps) && trim($raw_steps) !== '') {
                    // Textarea — 1 ligne = 1 step
                    $steps = array_values(array_filter(
                        array_map('trim', explode("\n", $raw_steps))
                    ));
                }
            }

            $rows[] = [
                'icon'  => $icon,
                'title' => $row[$sf_title] ?? '',
                'desc'  => $row[$sf_desc]  ?? '',
                'steps' => $steps,
            ];
        }

        return $rows ?: null;
    }

    /**
     * Résout les rows depuis le Repeater natif Elementor.
     * Icône : array ICONS. Steps : textarea item_steps (1 ligne = 1 step).
     */
    private function resolve_static_rows(array $s): ?array {
        $items = $s['static_items'] ?? [];

        if (empty($items)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Ajoutez des éléments dans l\'onglet Contenu.', 'blacktenderscore'));
            }
            return null;
        }

        $rows = [];
        foreach ($items as $item) {
            $raw_steps = trim($item['item_steps'] ?? '');
            $steps = $raw_steps !== ''
                ? array_values(array_filter(array_map('trim', explode("\n", $raw_steps))))
                : [];

            $rows[] = [
                'icon'  => $item['item_icon']  ?? [],
                'title' => $item['item_title'] ?? '',
                'desc'  => $item['item_desc']  ?? '',
                'steps' => $steps,
            ];
        }

        return $rows;
    }
}
