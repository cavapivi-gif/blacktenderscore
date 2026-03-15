<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Affichage Taxonomie (texte).
 *
 * Même logique que le Dynamic Tag BT: Champ ACF / Taxonomie :
 * choix du champ ACF, affichage titre et/ou description (on/off), séparateur.
 * Réutilise les traits (section_title, collapsible, etc.).
 */
class TaxonomyDisplay extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-taxonomy-display',
            'title'    => 'BT — Affichage Taxonomie',
            'icon'     => 'eicon-tags',
            'keywords' => ['taxonomie', 'terme', 'acf', 'champ', 'bt'],
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $opts = static::acf_field_options('', ['exp_included' => 'exp_included', 'exp_to_excluded' => 'exp_to_excluded']);
        $this->add_control('acf_field', [
            'label'   => __('Champ ACF', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => $opts,
            'default' => array_key_first($opts) ?: '',
        ]);

        $this->register_section_title_controls();
        $this->register_collapsible_section_control();

        $this->add_control('format_heading', [
            'label'     => __('Format d\'affichage', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control('show_title', [
            'label'        => __('Afficher le titre du terme', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'        => __('Afficher la description du terme', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('separator', [
            'label'   => __('Séparateur entre termes', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                ' · ' => '·  (point médian)',
                ' / ' => '/  (slash)',
                ', '  => ',  (virgule)',
                ' '   => __('Espace', 'blacktenderscore'),
                ' — ' => '—  (tiret long)',
                ' - ' => '-  (trait d\'union)',
            ],
            'default' => ' · ',
        ]);

        $this->end_controls_section();

        $this->register_section_title_style('{{WRAPPER}} .bt-taxdisp__section-title');

        $this->start_controls_section('style_text', [
            'label' => __('Style — Texte', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'text_typography',
            'selector' => '{{WRAPPER}} .bt-taxdisp__text',
        ]);
        $this->add_control('text_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-taxdisp__text' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s           = $this->get_settings_for_display();
        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';
        $key         = (string) ($s['acf_field'] ?? '');
        $sep         = (string) ($s['separator'] ?? ' · ');
        $show_title  = ($s['show_title'] ?? 'yes') === 'yes';
        $show_desc   = ($s['show_description'] ?? '') === 'yes';

        if ($key === '') {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Choisissez un champ ACF.', 'blacktenderscore'));
            }
            return;
        }

        $post_id = (int) get_the_ID();
        $raw     = function_exists('get_field') ? get_field($key, $post_id) : null;

        if (empty($raw)) {
            $native = get_the_terms($post_id, $key);
            if (is_array($native) && !empty($native)) {
                $raw = $native;
            }
        }

        if (empty($raw) && $raw !== '0' && $raw !== 0) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(sprintf(__('Champ « %s » vide ou introuvable.', 'blacktenderscore'), $key));
            }
            return;
        }

        if (!is_array($raw)) {
            $raw = [$raw];
        }
        if (isset($raw['term_id'])) {
            $raw = [$raw];
        }

        $parts = [];
        foreach ($raw as $item) {
            $str = $this->format_term_item($item, $show_title, $show_desc);
            if ($str !== '') {
                $parts[] = $str;
            }
        }

        if (empty($parts)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucun terme à afficher.', 'blacktenderscore'));
            }
            return;
        }

        echo '<div class="bt-taxdisp">';
        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-taxdisp__section-title');
        } else {
            $this->render_section_title($s, 'bt-taxdisp__section-title');
        }

        echo '<div class="bt-taxdisp__text">';
        echo implode(esc_html($sep), array_map('esc_html', $parts));
        echo '</div>';

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }
        echo '</div>';
    }

    /**
     * Retourne une chaîne pour un item (WP_Term, array, ID, etc.).
     * Si show_title et show_description : "Nom — Description" (ou seulement description si pas de nom).
     */
    private function format_term_item(mixed $item, bool $show_title, bool $show_desc): string {
        $term = $this->resolve_term($item);
        if (!$term instanceof \WP_Term) {
            if (is_scalar($item)) {
                return (string) $item;
            }
            return '';
        }

        $name = $show_title ? $term->name : '';
        $desc = $show_desc ? wp_strip_all_tags($term->description) : '';

        if ($name !== '' && $desc !== '') {
            return $name . ' — ' . $desc;
        }
        if ($name !== '') {
            return $name;
        }
        if ($desc !== '') {
            return $desc;
        }
        return '';
    }

    private function resolve_term(mixed $item): ?\WP_Term {
        if ($item instanceof \WP_Term) {
            return $item;
        }
        if (is_array($item) && isset($item['term_id'])) {
            $t = get_term((int) $item['term_id']);
            return $t instanceof \WP_Term ? $t : null;
        }
        if (is_numeric($item)) {
            $t = get_term((int) $item);
            return $t instanceof \WP_Term ? $t : null;
        }
        return null;
    }
}
