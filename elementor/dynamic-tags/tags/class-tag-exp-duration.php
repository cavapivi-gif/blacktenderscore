<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Durée(s) de l'excursion.
 *
 * Lit exp_time (taxonomy) depuis chaque ligne de tarification_par_forfait.
 * Peut afficher la durée minimale, maximale, ou toutes en liste.
 *
 * Options :
 *  - mode      : min / max / all (toutes, séparées)
 *  - separator : si mode = all
 *  - fallback  : texte si aucune durée
 */
class Tag_Exp_Duration extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-duration'; }
    public function get_title():      string { return 'BT: Durée excursion'; }
    public function get_categories(): array  { return ['text']; }

    protected function register_controls(): void {

        $this->add_control('mode', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'min' => __('La plus courte', 'blacktenderscore'),
                'max' => __('La plus longue', 'blacktenderscore'),
                'all' => __('Toutes les durées', 'blacktenderscore'),
            ],
            'default' => 'min',
        ]);

        $this->add_control('separator', [
            'label'     => __('Séparateur (mode "toutes")', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => ' · ',
            'condition' => ['mode' => 'all'],
        ]);

        $this->add_control('fallback', [
            'label'   => __('Texte si aucune durée', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
    }

    public function render(): void {
        $rows = $this->acf('tarification_par_forfait');
        if (empty($rows)) {
            echo esc_html($this->get_settings('fallback') ?? '');
            return;
        }

        // Collecter les noms de termes exp_time (taxonomy) de chaque ligne
        $durations = [];
        foreach ((array) $rows as $row) {
            $term_val = $row['exp_time'] ?? null;
            if (!$term_val) continue;

            $name = $this->resolve_term_name($term_val);
            if ($name && !in_array($name, $durations, true)) {
                $durations[] = $name;
            }
        }

        if (empty($durations)) {
            echo esc_html($this->get_settings('fallback') ?? '');
            return;
        }

        $mode = $this->get_settings('mode') ?: 'min';

        $output = match ($mode) {
            'max'   => end($durations),
            'all'   => implode($this->get_settings('separator') ?: ' · ', $durations),
            default => $durations[0],
        };

        echo esc_html($output);
    }

    private function resolve_term_name(mixed $val): string {
        if ($val instanceof \WP_Term) return $val->name;
        if (is_array($val)) {
            $first = reset($val);
            return $this->resolve_term_name($first);
        }
        if (is_numeric($val)) {
            $t = get_term((int) $val);
            return ($t && !is_wp_error($t)) ? $t->name : '';
        }
        return (string) $val;
    }
}
