<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tags — Specs techniques du bateau.
 *
 * Deux tags dans ce fichier (pattern identique) :
 *  - bt-boat-pax    : passagers (boat_pax_max ou boat_pax_comfort)
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

class Tag_Boat_Pax extends Abstract_Boat_Spec_Tag {

    public function get_name():  string { return 'bt-boat-pax'; }
    public function get_title(): string { return 'BT: Passagers (bateau)'; }

    protected function default_suffix(): string { return 'pax'; }

    // Bonus : choix entre max légal et confort
    protected function register_controls(): void {
        $this->add_control('pax_field', [
            'label'   => __('Champ', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'boat_pax_max'     => __('Max légal (boat_pax_max)', 'blacktenderscore'),
                'boat_pax_comfort' => __('Confort optimal (boat_pax_comfort)', 'blacktenderscore'),
            ],
            'default' => 'boat_pax_max',
        ]);
        parent::register_controls();
    }

    protected function field_name(): string {
        return $this->get_settings('pax_field') ?: 'boat_pax_max';
    }
}

// ── Tag : Motorisation ────────────────────────────────────────────────────────

class Tag_Boat_Engine extends Abstract_Boat_Spec_Tag {

    public function get_name():  string { return 'bt-boat-engine'; }
    public function get_title(): string { return 'BT: Motorisation (CV)'; }

    protected function field_name():     string { return 'boat_enginepower'; }
    protected function default_suffix(): string { return 'CV'; }
}
