<?php
namespace BT_Regiondo\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Ville / port de départ de l'excursion.
 *
 * Lit exp_departure_point (taxonomie city) ou exp_departure_coords (GPS).
 *
 * Options :
 *  - source    : city_name / gps_coords / maps_url
 *  - separator : si plusieurs villes
 */
class Tag_Exp_Departure extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-departure'; }
    public function get_title():      string { return 'BT: Ville / port de départ'; }
    public function get_categories(): array  { return ['text', 'url']; }

    protected function register_controls(): void {

        $this->add_control('source', [
            'label'   => __('Source', 'bt-regiondo'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'city_name'  => __('Nom de la ville (taxonomie city)', 'bt-regiondo'),
                'gps_coords' => __('Coordonnées GPS (exp_departure_coords)', 'bt-regiondo'),
                'maps_url'   => __('URL Google Maps (depuis GPS)', 'bt-regiondo'),
            ],
            'default' => 'city_name',
        ]);

        $this->add_control('separator', [
            'label'     => __('Séparateur si plusieurs villes', 'bt-regiondo'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => ', ',
            'condition' => ['source' => 'city_name'],
        ]);
    }

    public function render(): void {
        $source = $this->get_settings('source') ?: 'city_name';

        if ($source === 'gps_coords') {
            $coords = (string) $this->acf('exp_departure_coords');
            echo esc_html($coords);
            return;
        }

        if ($source === 'maps_url') {
            $coords = (string) $this->acf('exp_departure_coords');
            if ($coords) {
                $clean = preg_replace('/\s+/', '', $coords);
                echo esc_url('https://www.google.com/maps/search/?api=1&query=' . urlencode($clean));
            }
            return;
        }

        // city_name
        $raw = $this->acf('exp_departure_point');
        if (empty($raw)) return;

        $terms = is_array($raw) ? $raw : [$raw];
        $names = [];
        foreach ($terms as $t) {
            if ($t instanceof \WP_Term) {
                $names[] = $t->name;
            } elseif (is_numeric($t)) {
                $term = get_term((int) $t, 'city');
                if ($term && !is_wp_error($term)) $names[] = $term->name;
            }
        }

        echo esc_html(implode($this->get_settings('separator') ?: ', ', $names));
    }
}
