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
            'keywords' => ['highlights', 'points', 'forts', 'inclus', 'avantages', 'bt', 'slider'],
            'css'      => ['bt-highlights'],
            'js'       => ['bt-elementor'],
        ];
    }

    /**
     * Swiper JS/CSS toujours déclarés — chargés par Elementor uniquement
     * si le widget est sur la page.
     */
    public function get_script_depends(): array {
        return array_merge(parent::get_script_depends(), ['swiper']);
    }

    public function get_style_depends(): array {
        return array_merge(parent::get_style_depends(), ['swiper']);
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

        $this->add_control('item_title_tag', [
            'label'     => __('Balise des titres', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'h1'   => 'H1',
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'h5'   => 'H5',
                'h6'   => 'H6',
                'p'    => 'p',
                'span' => 'span',
                'div'  => 'div',
            ],
            'default'   => 'span',
            'condition' => ['show_title' => 'yes'],
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

        $this->add_control('steps_numbered', [
            'label'        => __('Numéroter les étapes', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_steps' => 'yes'],
            'separator'    => 'before',
        ]);

        $this->add_control('steps_number_prefix', [
            'label'       => __('Texte avant le chiffre', 'blacktenderscore'),
            'description' => __('Ex: "Étape " → Étape 1, ou vide → 1', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'condition'   => ['show_steps' => 'yes', 'steps_numbered' => 'yes'],
        ]);

        $this->add_control('steps_number_suffix', [
            'label'       => __('Texte après le chiffre', 'blacktenderscore'),
            'description' => __('Ex: "." → 1. ou ")" → 1)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'condition'   => ['show_steps' => 'yes', 'steps_numbered' => 'yes'],
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

        $this->add_control('slider_sep', ['type' => Controls_Manager::DIVIDER]);

        // ── Mode Slider ─────────────────────────────────────────────────────
        $this->add_control('slider_enabled', [
            'label'        => __('Afficher en Slider', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'render_type'  => 'template',
        ]);

        $this->add_control('slider_devices', [
            'label'       => __('Devices en mode Slider', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT2,
            'multiple'    => true,
            'options'     => [
                'desktop' => __('Desktop', 'blacktenderscore'),
                'tablet'  => __('Tablette', 'blacktenderscore'),
                'mobile'  => __('Mobile', 'blacktenderscore'),
            ],
            'default'     => ['desktop', 'tablet', 'mobile'],
            'condition'   => ['slider_enabled' => 'yes'],
            'render_type' => 'template',
        ]);

        $this->add_responsive_control('slides_per_view', [
            'label'          => __('Slides visibles', 'blacktenderscore'),
            'type'           => Controls_Manager::NUMBER,
            'min'            => 1,
            'max'            => 10,
            'step'           => 0.5,
            'default'        => 3,
            'tablet_default' => 2,
            'mobile_default' => 1,
            'condition'      => ['slider_enabled' => 'yes'],
        ]);

        $this->add_responsive_control('slides_gap', [
            'label'          => __('Espace entre slides', 'blacktenderscore'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px'],
            'range'          => ['px' => ['min' => 0, 'max' => 80]],
            'default'        => ['size' => 16, 'unit' => 'px'],
            'tablet_default' => ['size' => 12, 'unit' => 'px'],
            'mobile_default' => ['size' => 8,  'unit' => 'px'],
            'condition'      => ['slider_enabled' => 'yes'],
        ]);

        $this->add_control('slider_autoplay', [
            'label'        => __('Autoplay', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['slider_enabled' => 'yes'],
        ]);

        $this->add_control('slider_autoplay_speed', [
            'label'     => __('Intervalle autoplay (ms)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 1000,
            'max'       => 15000,
            'step'      => 500,
            'default'   => 4000,
            'condition' => ['slider_enabled' => 'yes', 'slider_autoplay' => 'yes'],
        ]);

        $this->add_control('slider_loop', [
            'label'        => __('Boucle infinie', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['slider_enabled' => 'yes'],
        ]);

        $this->add_control('slider_speed', [
            'label'     => __('Vitesse transition (ms)', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 100,
            'max'       => 2000,
            'step'      => 50,
            'default'   => 400,
            'condition' => ['slider_enabled' => 'yes'],
        ]);

        $this->add_control('slider_arrows', [
            'label'        => __('Flèches navigation', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['slider_enabled' => 'yes'],
        ]);

        $this->add_control('slider_dots', [
            'label'        => __('Points de pagination', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['slider_enabled' => 'yes'],
        ]);

        $this->add_control('slider_offset_side', [
            'label'     => __('Direction de l\'offset', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'both'  => ['title' => __('Les deux côtés', 'blacktenderscore'), 'icon' => 'eicon-h-align-stretch'],
                'left'  => ['title' => __('Gauche', 'blacktenderscore'),         'icon' => 'eicon-h-align-left'],
                'right' => ['title' => __('Droite', 'blacktenderscore'),         'icon' => 'eicon-h-align-right'],
            ],
            'default'   => 'both',
            'toggle'    => false,
            'condition' => ['slider_enabled' => 'yes'],
        ]);

        $this->add_responsive_control('slider_offset', [
            'label'          => __('Offset (aperçu slide suivant)', 'blacktenderscore'),
            'type'           => Controls_Manager::SLIDER,
            'size_units'     => ['px', '%'],
            'range'          => ['px' => ['min' => 0, 'max' => 200], '%' => ['min' => 0, 'max' => 30]],
            'default'        => ['size' => 0, 'unit' => 'px'],
            'tablet_default' => ['size' => 0, 'unit' => 'px'],
            'mobile_default' => ['size' => 0, 'unit' => 'px'],
            'condition'      => ['slider_enabled' => 'yes'],
            'selectors'      => [
                '{{WRAPPER}} .bt-highlights__swiper[data-offset-side="both"]  ' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-highlights__swiper[data-offset-side="left"]  ' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: 0',
                '{{WRAPPER}} .bt-highlights__swiper[data-offset-side="right"] ' => 'padding-left: 0; padding-right: {{SIZE}}{{UNIT}}',
            ],
        ]);

        // ── Position & alignement icône (responsive — mobile passe en colonne) ───
        // Comme Icon Box / Image Box d'Elementor : quand l'icône est à droite,
        // le texte s'aligne à droite automatiquement.
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
            'selectors_dictionary' => [
                'row'         => 'flex-direction: row; --bt-hl-text-align: left;',
                'row-reverse' => 'flex-direction: row-reverse; --bt-hl-text-align: right;',
                'column'      => 'flex-direction: column; --bt-hl-text-align: inherit;',
            ],
            'selectors'      => ['{{WRAPPER}} .bt-highlights__item' => '{{VALUE}}'],
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
            'label'       => __('Alignement texte', 'blacktenderscore'),
            'type'        => Controls_Manager::CHOOSE,
            'options'     => [
                'left'   => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'description' => __('Par défaut : gauche si icône à gauche, droite si icône à droite', 'blacktenderscore'),
            'selectors'   => ['{{WRAPPER}} .bt-highlights__content' => 'text-align: {{VALUE}}'],
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
                // Cas non repliable : margin-bottom directement sur le titre
                '{{WRAPPER}} .bt-highlights__section-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                // Cas repliable : le titre dans le trigger reste collé à l'icône,
                // l'espacement est appliqué en haut de la grille/liste (contenu)
                '{{WRAPPER}} .bt-collapsible-block .bt-highlights__section-title' => 'margin-bottom: 0',
                '{{WRAPPER}} .bt-collapsible-block .bt-highlights__grid' => 'margin-top: {{SIZE}}{{UNIT}}',
                '{{WRAPPER}} .bt-collapsible-block .bt-highlights__list' => 'margin-top: {{SIZE}}{{UNIT}}',
            ]
        );

        $this->register_item_3state_style(
            'item',
            __('Style — Item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__item'
        );

        // ── Style — Icône / Image ────────────────────────────────────────────
        $this->start_controls_section('style_icon_img', [
            'label'     => __('Style — Icône / Image', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_icon' => 'yes'],
        ]);

        // Taille (font-size) — emoji, icônes font, SVG Elementor
        $this->add_responsive_control('icon_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'range'      => ['px' => ['min' => 12, 'max' => 80]],
            'default'    => ['size' => 28, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__icon' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('icon_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [
                '{{WRAPPER}} .bt-highlights__icon'     => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-highlights__icon i'   => 'color: {{VALUE}}',
                '{{WRAPPER}} .bt-highlights__icon svg' => 'fill: {{VALUE}}; color: {{VALUE}}',
            ],
        ]);

        $this->add_control('icon_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__icon' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('icon_padding', [
            'label'      => __('Padding', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__icon' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('icon_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__icon' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Image uniquement ─────────────────────────────────────────────────
        $this->add_control('icon_img_heading', [
            'label'     => __('Image (dimensions / recadrage)', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_responsive_control('icon_img_width', [
            'label'      => __('Largeur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%', 'em'],
            'range'      => ['px' => ['min' => 20, 'max' => 800], '%' => ['min' => 1, 'max' => 100]],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__icon--img' => 'width: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('icon_img_height', [
            'label'       => __('Hauteur', 'blacktenderscore'),
            'description' => __('Fixe une hauteur uniforme (évite l\'effet escalier en slider).', 'blacktenderscore'),
            'type'        => Controls_Manager::SLIDER,
            'size_units'  => ['px', 'em', 'vh'],
            'range'       => ['px' => ['min' => 20, 'max' => 800]],
            'selectors'   => ['{{WRAPPER}} .bt-highlights__icon--img' => 'height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('icon_img_fit', [
            'label'     => __('Object-fit', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'contain'    => 'contain — cadre sans recadrage',
                'cover'      => 'cover — remplit (recadré)',
                'fill'       => 'fill — étire',
                'none'       => 'none — taille naturelle',
                'scale-down' => 'scale-down — contain ou none (le plus petit)',
            ],
            'default'   => 'contain',
            'selectors' => ['{{WRAPPER}} .bt-highlights__icon--img img' => 'object-fit: {{VALUE}}'],
        ]);

        $this->add_control('icon_img_position', [
            'label'     => __('Object-position', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'center'       => 'center',
                'top'          => 'top',
                'bottom'       => 'bottom',
                'left center'  => 'left center',
                'right center' => 'right center',
                'top left'     => 'top left',
                'top right'    => 'top right',
                'bottom left'  => 'bottom left',
                'bottom right' => 'bottom right',
            ],
            'default'   => 'center',
            'selectors' => ['{{WRAPPER}} .bt-highlights__icon--img img' => 'object-position: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        $this->register_typography_section(
            'item_title',
            __('Style — Titre item', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__title',
            ['with_align' => true, 'with_width' => true],
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

        // ── Style — Numéro d'étape ──────────────────────────────────────────
        $this->register_button_style(
            'step_number',
            __('Style — Numéro étape', 'blacktenderscore'),
            '{{WRAPPER}} .bt-highlights__step-num',
            [],
            ['show_steps' => 'yes', 'steps_numbered' => 'yes'],
            ['with_gap' => false]
        );

        // ── Style — Slider Navigation ──────────────────────────────────────
        $this->register_slider_style_controls();
    }

    /**
     * Enregistre les contrôles de style pour le mode slider (flèches + dots).
     */
    private function register_slider_style_controls(): void {

        // ── Flèches ─────────────────────────────────────────────────────────
        $this->start_controls_section('style_slider_arrows', [
            'label'     => __('Style — Slider Flèches', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['slider_enabled' => 'yes', 'slider_arrows' => 'yes'],
        ]);

        $this->add_responsive_control('arrow_size', [
            'label'      => __('Taille icône', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 10, 'max' => 60]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__arrow' => 'font-size: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('arrow_box_size', [
            'label'      => __('Taille du bouton', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 20, 'max' => 80]],
            'default'    => ['size' => 40, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-highlights__arrow' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->start_controls_tabs('arrow_state_tabs');

        $this->start_controls_tab('arrow_tab_normal', ['label' => __('Normal', 'blacktenderscore')]);
        $this->add_control('arrow_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__arrow' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('arrow_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__arrow' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->start_controls_tab('arrow_tab_hover', ['label' => __('Survol', 'blacktenderscore')]);
        $this->add_control('arrow_color_hover', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__arrow:hover' => 'color: {{VALUE}}'],
        ]);
        $this->add_control('arrow_bg_hover', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__arrow:hover' => 'background-color: {{VALUE}}'],
        ]);
        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control('arrow_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default'    => ['top' => '50', 'right' => '50', 'bottom' => '50', 'left' => '50', 'unit' => '%'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__arrow' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
            'separator'  => 'before',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'arrow_border',
            'selector' => '{{WRAPPER}} .bt-highlights__arrow',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'arrow_shadow',
            'selector' => '{{WRAPPER}} .bt-highlights__arrow',
        ]);

        // ── Flèche Précédent — Icône & Position ─────────────────────────────
        $this->add_control('arrow_prev_heading', [
            'label'     => __('Flèche Précédent', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('arrow_prev_icon_mode', [
            'label'   => __('Icône', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'default' => __('Flèche par défaut', 'blacktenderscore'),
                'icon'    => __('Icône personnalisée', 'blacktenderscore'),
                'none'    => __('Aucune', 'blacktenderscore'),
            ],
            'default' => 'default',
        ]);

        $this->add_control('arrow_prev_icon', [
            'label'     => __('Icône personnalisée', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'skin'      => 'inline',
            'condition' => ['arrow_prev_icon_mode' => 'icon'],
        ]);

        $this->add_control('arrow_prev_h_orient', [
            'label'   => __('Horizontal — Ancrage', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'center' => ['title' => __('Centre',  'blacktenderscore'), 'icon' => 'eicon-h-align-center'],
                'end'    => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default'              => 'start',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-prev-l: 0px; --bt-prev-r: auto; --bt-prev-trx: 0px',
                'center' => '--bt-prev-l: 50%; --bt-prev-r: auto; --bt-prev-trx: -50%',
                'end'    => '--bt-prev-l: auto; --bt-prev-r: 0px; --bt-prev-trx: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-highlights__arrow--prev' => '{{VALUE}}'],
        ]);

        $this->add_responsive_control('arrow_prev_h_pos', [
            'label'      => __('Horizontal — Décalage', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => -20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__arrow--prev' => '--bt-prev-h-off: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('arrow_prev_v_orient', [
            'label'   => __('Vertical — Ancrage', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => __('Haut',   'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'end'    => ['title' => __('Bas',    'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'              => 'center',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-prev-t: 0px; --bt-prev-b: auto; --bt-prev-try: 0px',
                'center' => '--bt-prev-t: 50%; --bt-prev-b: auto; --bt-prev-try: -50%',
                'end'    => '--bt-prev-t: auto; --bt-prev-b: 0px; --bt-prev-try: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-highlights__arrow--prev' => '{{VALUE}}'],
        ]);

        $this->add_responsive_control('arrow_prev_v_pos', [
            'label'      => __('Vertical — Décalage', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__arrow--prev' => '--bt-prev-v-off: {{SIZE}}{{UNIT}}'],
        ]);

        // ── Flèche Suivant — Icône & Position ──────────────────────────────
        $this->add_control('arrow_next_heading', [
            'label'     => __('Flèche Suivant', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('arrow_next_icon_mode', [
            'label'   => __('Icône', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'default' => __('Flèche par défaut', 'blacktenderscore'),
                'icon'    => __('Icône personnalisée', 'blacktenderscore'),
                'none'    => __('Aucune', 'blacktenderscore'),
            ],
            'default' => 'default',
        ]);

        $this->add_control('arrow_next_icon', [
            'label'     => __('Icône personnalisée', 'blacktenderscore'),
            'type'      => Controls_Manager::ICONS,
            'skin'      => 'inline',
            'condition' => ['arrow_next_icon_mode' => 'icon'],
        ]);

        $this->add_control('arrow_next_h_orient', [
            'label'   => __('Horizontal — Ancrage', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'center' => ['title' => __('Centre',  'blacktenderscore'), 'icon' => 'eicon-h-align-center'],
                'end'    => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
            ],
            'default'              => 'end',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-next-l: 0px; --bt-next-r: auto; --bt-next-trx: 0px',
                'center' => '--bt-next-l: 50%; --bt-next-r: auto; --bt-next-trx: -50%',
                'end'    => '--bt-next-l: auto; --bt-next-r: 0px; --bt-next-trx: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-highlights__arrow--next' => '{{VALUE}}'],
        ]);

        $this->add_responsive_control('arrow_next_h_pos', [
            'label'      => __('Horizontal — Décalage', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => -20, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__arrow--next' => '--bt-next-h-off: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('arrow_next_v_orient', [
            'label'   => __('Vertical — Ancrage', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'start'  => ['title' => __('Haut',   'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-v-align-middle'],
                'end'    => ['title' => __('Bas',    'blacktenderscore'), 'icon' => 'eicon-v-align-bottom'],
            ],
            'default'              => 'center',
            'toggle'               => false,
            'selectors_dictionary' => [
                'start'  => '--bt-next-t: 0px; --bt-next-b: auto; --bt-next-try: 0px',
                'center' => '--bt-next-t: 50%; --bt-next-b: auto; --bt-next-try: -50%',
                'end'    => '--bt-next-t: auto; --bt-next-b: 0px; --bt-next-try: 0px',
            ],
            'selectors'            => ['{{WRAPPER}} .bt-highlights__arrow--next' => '{{VALUE}}'],
        ]);

        $this->add_responsive_control('arrow_next_v_pos', [
            'label'      => __('Vertical — Décalage', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => -200, 'max' => 200], '%' => ['min' => -50, 'max' => 50]],
            'default'    => ['size' => 0, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__arrow--next' => '--bt-next-v-off: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Dots / Pagination ───────────────────────────────────────────────
        $this->start_controls_section('style_slider_dots', [
            'label'     => __('Style — Slider Pagination', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['slider_enabled' => 'yes', 'slider_dots' => 'yes'],
        ]);

        $this->add_responsive_control('dot_size', [
            'label'      => __('Taille du point', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 4, 'max' => 24]],
            'default'    => ['size' => 8, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-highlights__dot' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('dot_active_width', [
            'label'      => __('Largeur point actif', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 4, 'max' => 40]],
            'default'    => ['size' => 20, 'unit' => 'px'],
            'selectors'  => [
                '{{WRAPPER}} .bt-highlights__dot--active' => 'width: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_responsive_control('dot_gap', [
            'label'      => __('Espace entre points', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 20]],
            'default'    => ['size' => 6, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__dots' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('dots_spacing', [
            'label'      => __('Espace au-dessus des points', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__dots' => 'margin-top: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('dot_color', [
            'label'     => __('Couleur point', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__dot' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('dot_active_color', [
            'label'     => __('Couleur point actif', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-highlights__dot--active' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('dot_border_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::DIMENSIONS,
            'size_units' => ['px', '%'],
            'default'    => ['top' => '50', 'right' => '50', 'bottom' => '50', 'left' => '50', 'unit' => '%'],
            'selectors'  => ['{{WRAPPER}} .bt-highlights__dot' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
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
        $is_slider   = ($s['slider_enabled'] ?? '') === 'yes';
        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';

        echo '<div class="bt-highlights">';
        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-highlights__section-title');
        } else {
            $this->render_section_title($s, 'bt-highlights__section-title');
        }

        if ($is_slider) {
            $this->render_slider($s, $rows);
        } else {
            $this->render_grid_list($s, $rows, $layout);
        }

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }
        echo '</div>'; // .bt-highlights
    }

    /**
     * Rendu mode grille / liste (comportement original).
     */
    private function render_grid_list(array $s, array $rows, string $layout): void {
        $wrap_cls = $layout === 'list' ? 'bt-highlights__list' : 'bt-highlights__grid';
        echo "<div class=\"{$wrap_cls}\">";
        foreach ($rows as $row) {
            $this->render_single_item($s, $row);
        }
        echo '</div>';
    }

    /**
     * Rendu mode slider (Swiper).
     * Les data-attributes sont lus par le JS pour configurer Swiper.
     */
    private function render_slider(array $s, array $rows): void {
        $devices = $s['slider_devices'] ?? ['desktop', 'tablet', 'mobile'];
        if (!is_array($devices)) $devices = ['desktop', 'tablet', 'mobile'];

        $config = [
            'devices'       => $devices,
            'slidesPerView' => [
                'desktop' => (float) ($s['slides_per_view']        ?? 3),
                'tablet'  => (float) ($s['slides_per_view_tablet'] ?? 2),
                'mobile'  => (float) ($s['slides_per_view_mobile'] ?? 1),
            ],
            'spaceBetween'  => [
                'desktop' => (int) ($s['slides_gap']['size']        ?? 16),
                'tablet'  => (int) (($s['slides_gap_tablet']['size'] ?? null) ?: ($s['slides_gap']['size'] ?? 16)),
                'mobile'  => (int) (($s['slides_gap_mobile']['size'] ?? null) ?: ($s['slides_gap']['size'] ?? 16)),
            ],
            'autoplay'      => ($s['slider_autoplay'] ?? '') === 'yes',
            'autoplaySpeed' => (int) ($s['slider_autoplay_speed'] ?? 4000),
            'loop'          => ($s['slider_loop'] ?? '') === 'yes',
            'speed'         => (int) ($s['slider_speed'] ?? 400),
            'arrows'        => ($s['slider_arrows'] ?? '') === 'yes',
            'dots'          => ($s['slider_dots'] ?? '') === 'yes',
            'layout'        => $s['layout'] ?: 'grid',
            'columns'       => [
                'desktop' => (int) ($s['columns']        ?? 3),
                'tablet'  => (int) ($s['columns_tablet'] ?? 2),
                'mobile'  => (int) ($s['columns_mobile'] ?? 1),
            ],
        ];

        $uid = 'bt-hl-slider-' . $this->get_id();

        echo '<div class="bt-highlights__slider-wrap" id="' . esc_attr($uid) . '" data-bt-highlights-slider=\'' . wp_json_encode($config) . '\'>';

        // Swiper container — data-offset-side pour le sélecteur CSS conditionnel
        $offset_side = esc_attr($s['slider_offset_side'] ?? 'both');
        echo '<div class="swiper bt-highlights__swiper" data-offset-side="' . $offset_side . '">';
        echo '<div class="swiper-wrapper">';
        foreach ($rows as $row) {
            echo '<div class="swiper-slide">';
            $this->render_single_item($s, $row);
            echo '</div>';
        }
        echo '</div>'; // .swiper-wrapper
        echo '</div>'; // .swiper

        // Arrows
        if ($config['arrows']) {
            $prev_mode = $s['arrow_prev_icon_mode'] ?? 'default';
            $next_mode = $s['arrow_next_icon_mode'] ?? 'default';

            echo '<button class="bt-highlights__arrow bt-highlights__arrow--prev" aria-label="' . esc_attr__('Précédent', 'blacktenderscore') . '">';
            if ($prev_mode === 'icon') {
                $prev_icon = $s['arrow_prev_icon'] ?? [];
                if (is_array($prev_icon) && !empty($prev_icon['value'])) {
                    Icons_Manager::render_icon($prev_icon, ['aria-hidden' => 'true']);
                }
            } elseif ($prev_mode !== 'none') {
                echo '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>';
            }
            echo '</button>';

            echo '<button class="bt-highlights__arrow bt-highlights__arrow--next" aria-label="' . esc_attr__('Suivant', 'blacktenderscore') . '">';
            if ($next_mode === 'icon') {
                $next_icon = $s['arrow_next_icon'] ?? [];
                if (is_array($next_icon) && !empty($next_icon['value'])) {
                    Icons_Manager::render_icon($next_icon, ['aria-hidden' => 'true']);
                }
            } elseif ($next_mode !== 'none') {
                echo '<svg viewBox="0 0 24 24" width="1em" height="1em" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>';
            }
            echo '</button>';
        }

        // Dots
        if ($config['dots']) {
            echo '<div class="bt-highlights__dots"></div>';
        }

        echo '</div>'; // .bt-highlights__slider-wrap
    }

    /**
     * Rendu d'un seul item (partagé entre grid/list et slider).
     */
    private function render_single_item(array $s, array $row): void {
        echo '<div class="bt-highlights__item">';

        if ($s['show_icon'] === 'yes') {
            $this->render_icon_slot($row['icon']);
        }

        echo '<div class="bt-highlights__content">';

        if ($s['show_title'] === 'yes' && $row['title']) {
            $title_tag = $s['item_title_tag'] ?? 'span';
            $allowed_tags = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div'];
            if (!in_array($title_tag, $allowed_tags, true)) {
                $title_tag = 'span';
            }
            echo '<' . $title_tag . ' class="bt-highlights__title">' . esc_html($row['title']) . '</' . $title_tag . '>';
        }

        if ($s['show_desc'] === 'yes' && $row['desc']) {
            echo '<div class="bt-highlights__desc">' . wp_kses_post($row['desc']) . '</div>';
        }

        if ($s['show_steps'] === 'yes' && !empty($row['steps'])) {
            $numbered = ($s['steps_numbered'] ?? '') === 'yes';
            $prefix   = $numbered ? esc_html($s['steps_number_prefix'] ?? '') : '';
            $suffix   = $numbered ? esc_html($s['steps_number_suffix'] ?? '') : '';
            $tag      = $numbered ? 'ol' : 'ul';
            $cls      = 'bt-highlights__steps' . ($numbered ? ' bt-highlights__steps--numbered' : '');

            echo '<' . $tag . ' class="' . esc_attr($cls) . '">';
            foreach ($row['steps'] as $i => $step) {
                if ($numbered) {
                    $num = $prefix . ($i + 1) . $suffix;
                    echo '<li><span class="bt-highlights__step-num">' . esc_html($num) . '</span>' . esc_html($step) . '</li>';
                } else {
                    echo '<li>' . esc_html($step) . '</li>';
                }
            }
            echo '</' . $tag . '>';
        }

        echo '</div>'; // .bt-highlights__content
        echo '</div>'; // .bt-highlights__item
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Affiche le slot icône : supporte emoji, Elementor ICONS, image ACF (array ou URL).
     *
     * @param mixed $icon  String emoji, array ICONS Elementor, array image ACF, ou URL string.
     */
    private function render_icon_slot($icon): void {
        if ($icon === null) return;

        // ACF Image field — return format "Array" : ['url'=>..., 'alt'=>..., 'width'=>..., 'height'=>...]
        if (is_array($icon) && !empty($icon['url'])) {
            $w   = (int) ($icon['width']  ?? 0);
            $h   = (int) ($icon['height'] ?? 0);
            echo '<span class="bt-highlights__icon bt-highlights__icon--img" aria-hidden="true">';
            echo '<img src="' . esc_url($icon['url']) . '"'
                . ' alt="' . esc_attr($icon['alt'] ?? '') . '"'
                . ($w ? ' width="' . $w . '"'  : '')
                . ($h ? ' height="' . $h . '"' : '')
                . ' loading="lazy" decoding="async">';
            echo '</span>';
            return;
        }

        // Elementor ICONS control — ['value'=>'fas fa-check', 'library'=>'fa-solid'] (font icon, pas d'img)
        if (is_array($icon) && !empty($icon['value'])) {
            echo '<span class="bt-highlights__icon" aria-hidden="true">';
            Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
            echo '</span>';
            return;
        }

        // String URL (ACF Image return format "URL", ou SVG/img direct)
        if (is_string($icon) && filter_var($icon, FILTER_VALIDATE_URL)) {
            echo '<span class="bt-highlights__icon bt-highlights__icon--img" aria-hidden="true">';
            echo '<img src="' . esc_url($icon) . '" alt="" loading="lazy" decoding="async">';
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
