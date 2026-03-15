<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Horaires de départ (et optionnellement prix).
 *
 * Lit le schéma ACF tout seul : on choisit 1 repeater, le tag détecte s'il contient
 * un sous-repeater (plusieurs départs/horaires) et un champ prix, puis affiche.
 *
 * Ex. tarification_par_forfait → détecte exp_departure_time (repeater) + departure_time_child (texte)
 *     et exp_price → "10:00 à 55€ ou 18:00 à 55€".
 *
 * Contrôles : uniquement le repeater (liste ACF) + séparateur, afficher prix, fallback.
 */
class Tag_Exp_Duration extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-duration'; }
    public function get_title():      string { return 'BT: Horaires & départ'; }
    public function get_categories(): array  { return ['text']; }

    protected function register_controls(): void {

        $repeater_opts = $this->acf_repeater_field_options();

        $this->add_control('repeater_field', [
            'label'   => __('Champ repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $repeater_opts,
            'default' => array_key_first($repeater_opts) ?: '',
            'description' => __('Le tag détecte seul le sous-repeater des horaires et le champ prix.', 'blacktenderscore'),
        ]);

        $this->add_control('separator', [
            'label'   => __('Séparateur entre horaires', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ' · ' => '·  (point médian)',
                ' ou ' => __('ou  (texte)', 'blacktenderscore'),
                ' / ' => '/  (slash)',
                ', '  => ',  (virgule)',
                ' — ' => '—  (tiret long)',
                ' '   => __('Espace', 'blacktenderscore'),
            ],
            'default' => ' ou ',
        ]);

        $this->add_control('show_price', [
            'label'        => __('Afficher le prix pour chaque horaire', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('currency', [
            'label'     => __('Symbole devise', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '€',
            'condition' => ['show_price' => 'yes'],
        ]);

        $this->add_control('price_template', [
            'label'       => __('Format prix', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => ' à {price}',
            'placeholder' => ' à {price}',
            'condition'   => ['show_price' => 'yes'],
        ]);

        $this->add_control('fallback', [
            'label'   => __('Texte si aucun horaire', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
    }

    public function render(): void {

        $post_id = (int) get_the_ID();
        $repeater_name = trim((string) ($this->get_settings('repeater_field') ?? ''));

        if ($repeater_name === '') {
            $fallback = (string) ($this->get_settings('fallback') ?? '');
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        $structure = $this->acf_detect_repeater_departure_structure($repeater_name);
        if ($structure === null) {
            $structure = [
                'times_subfield'      => 'exp_departure_time',
                'time_value_subfield' => 'departure_time_child',
                'price_subfield'      => 'exp_price',
            ];
        }

        $rows = $this->get_repeater_rows($post_id, $repeater_name);
        if (empty($rows)) {
            $fallback = (string) ($this->get_settings('fallback') ?? '');
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        $show_price = ($this->get_settings('show_price') ?? 'yes') === 'yes';
        $sep        = (string) ($this->get_settings('separator') ?: ' ou ');
        $currency   = (string) ($this->get_settings('currency') ?: '€');
        $price_tpl  = (string) ($this->get_settings('price_template') ?: ' à {price}');

        $parts = [];

        foreach ($rows as $row) {
            $times = $this->extract_times_from_row($row, $structure['times_subfield'], $structure['time_value_subfield']);
            $price = $show_price ? $this->format_price($row[$structure['price_subfield']] ?? '', $currency) : '';

            foreach ($times as $time_str) {
                $time_str = trim($time_str);
                if ($time_str === '') continue;
                if ($price !== '') {
                    $parts[] = $time_str . str_replace('{price}', $price, $price_tpl);
                } else {
                    $parts[] = $time_str;
                }
            }
        }

        $parts = array_values(array_unique($parts));

        if (empty($parts)) {
            $fallback = (string) ($this->get_settings('fallback') ?? '');
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        echo esc_html(implode($sep, $parts));
    }

    private function get_repeater_rows(int $post_id, string $repeater_name): array {

        if (!function_exists('get_field')) return [];

        $raw = get_field($repeater_name, $post_id);
        return is_array($raw) ? $raw : [];
    }

    /**
     * @return array<int, string>
     */
    private function extract_times_from_row(array $row, string $times_subfield, string $time_value_subfield): array {

        $sub = $row[$times_subfield] ?? null;
        if (!is_array($sub)) return [];

        $out = [];
        foreach ($sub as $item) {
            if (!is_array($item)) continue;
            $v = $item[$time_value_subfield] ?? '';
            if ($v !== '' && $v !== null) $out[] = (string) $v;
        }
        return $out;
    }

    private function format_price($price, string $currency): string {

        if ($price === null || $price === '') return '';
        return number_format((float) $price, 0, ',', ' ') . $currency;
    }
}
