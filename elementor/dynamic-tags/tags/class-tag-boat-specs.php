<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tags — Specs techniques du bateau.
 *
 * Deux tags dans ce fichier (pattern identique) :
 *  - bt-boat-pax    : passagers excursion (exp_pax_min → exp_pax_max, plage configurable)
 *  - bt-boat-engine : motorisation (boat_enginepower en CV)
 *
 * Options communes :
 *  - show_suffix : afficher l'unité (pax / CV)
 *  - fallback    : texte si champ vide
 */

abstract class Abstract_Boat_Spec_Tag extends Abstract_BT_Tag {

    public function get_categories(): array { return ['text', 'number']; }

    abstract protected function field_name(): string;
    abstract protected function default_suffix(): string;

    protected function register_controls(): void {

        $this->add_control('show_suffix', [
            'label'        => __('Afficher l\'unité', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('suffix_text', [
            'label'     => __('Texte unité', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => $this->default_suffix(),
            'condition' => ['show_suffix' => 'yes'],
        ]);

        $this->add_control('fallback', [
            'label'   => __('Texte si vide', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
    }

    public function render(): void {
        $val = $this->acf($this->field_name());

        if ($val === null || $val === '' || $val === false) {
            echo esc_html($this->get_settings('fallback') ?? '');
            return;
        }

        $output = (string) $val;

        if ($this->get_settings('show_suffix') === 'yes') {
            $suffix = $this->get_settings('suffix_text') ?: $this->default_suffix();
            $output .= ' ' . $suffix;
        }

        echo esc_html($output);
    }
}

// ── Tag : Passagers ───────────────────────────────────────────────────────────

/**
 * Affiche la plage de passagers d'une excursion (exp_pax_min → exp_pax_max).
 *
 * Chaque borne est configurable (champ ACF, préfixe, suffixe) et le
 * séparateur est librement choisi — identique à Tag_Acf_Range.
 *
 * Rendu par défaut : "8 - 12 pax"
 */
class Tag_Boat_Pax extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-boat-pax'; }
    public function get_title():      string { return 'BT: Passagers (bateau)'; }
    public function get_categories(): array  { return ['text', 'number']; }

    protected function register_controls(): void {

        $field_opts = $this->acf_field_options();

        // ── Borne min ─────────────────────────────────────────────────────────

        $this->add_control('heading_min', [
            'label' => __('Passagers min', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ]);

        $this->add_control('field_min', [
            'label'   => __('Champ ACF (min)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['' => __('— Aucun —', 'blacktenderscore')] + $field_opts,
            'default' => 'exp_pax_min',
        ]);

        $this->add_control('prefix_min', [
            'label'       => __('Préfixe min', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '',
            'description' => __('Collé avant la valeur min. Incluez les espaces si besoin.', 'blacktenderscore'),
        ]);

        $this->add_control('suffix_min', [
            'label'       => __('Suffixe min', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '',
            'description' => __('Collé après la valeur min.', 'blacktenderscore'),
        ]);

        // ── Séparateur ────────────────────────────────────────────────────────

        $this->add_control('separator', [
            'label'   => __('Séparateur', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ' - '        => '-  (trait d\'union)',
                ' · '        => '·  (point médian)',
                ' / '        => '/  (slash)',
                ', '         => ',  (virgule)',
                ' — '        => '—  (tiret long)',
                ' à '        => 'à  (plage française)',
                ' → '        => '→  (flèche)',
                ' jusqu\'à ' => 'jusqu\'à',
                ' '          => 'espace',
            ],
            'default' => ' - ',
        ]);

        // ── Borne max ─────────────────────────────────────────────────────────

        $this->add_control('heading_max', [
            'label' => __('Passagers max', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ]);

        $this->add_control('field_max', [
            'label'   => __('Champ ACF (max)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['' => __('— Aucun —', 'blacktenderscore')] + $field_opts,
            'default' => 'exp_pax_max',
        ]);

        $this->add_control('prefix_max', [
            'label'       => __('Préfixe max', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '',
            'description' => __('Collé avant la valeur max. Incluez les espaces si besoin.', 'blacktenderscore'),
        ]);

        $this->add_control('suffix_max', [
            'label'       => __('Suffixe max', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'pax',
            'description' => __('Collé après la valeur max.', 'blacktenderscore'),
        ]);

        // ── Fallback ──────────────────────────────────────────────────────────

        $this->add_control('fallback', [
            'label'       => __('Texte si aucune valeur', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Laissez vide pour n\'afficher rien.', 'blacktenderscore'),
        ]);
    }

    public function render(): void {
        $post_id  = (int) get_the_ID();
        $sep      = (string) ($this->get_settings('separator') ?: ' - ');
        $fallback = (string) ($this->get_settings('fallback')  ?? '');

        $parts = [];

        foreach (['min', 'max'] as $side) {
            $key = trim((string) ($this->get_settings("field_{$side}") ?? ''));
            if (!$key) continue;

            $raw = function_exists('get_field') ? get_field($key, $post_id) : null;
            $str = $this->acf_scalar($raw);
            if ($str === '') continue;

            $prefix  = (string) ($this->get_settings("prefix_{$side}") ?? '');
            $suffix  = (string) ($this->get_settings("suffix_{$side}") ?? '');
            $parts[] = $prefix . $str . $suffix;
        }

        if (empty($parts)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        echo esc_html(implode($sep, $parts));
    }
}

// ── Tag : Motorisation ────────────────────────────────────────────────────────

class Tag_Boat_Engine extends Abstract_Boat_Spec_Tag {

    public function get_name():  string { return 'bt-boat-engine'; }
    public function get_title(): string { return 'BT: Motorisation (CV)'; }

    protected function field_name():     string { return 'boat_enginepower'; }
    protected function default_suffix(): string { return 'CV'; }
}
