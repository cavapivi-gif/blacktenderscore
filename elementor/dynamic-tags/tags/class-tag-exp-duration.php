<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Horaires de départ, prix et/ou durée.
 *
 * Modes d'affichage (display_mode) :
 *  - schedule  : Horaires & Prix (comportement d'origine)
 *  - duration  : Durée uniquement — collecte le sous-champ durée de chaque row,
 *                déduplique et affiche avec le séparateur choisi.
 *  - both      : Horaires + Durée (horaires avec durée accolée par row)
 *
 * Les contrôles prix/note sont cachés en mode "duration",
 * les contrôles durée sont cachés en mode "schedule".
 */
class Tag_Exp_Duration extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-duration'; }
    public function get_title():      string { return 'BT: Horaires & départ'; }
    public function get_categories(): array  { return ['text']; }

    protected function register_controls(): void {

        $repeater_opts = $this->acf_repeater_field_options();

        // ── Mode d'affichage ─────────────────────────────────────────────
        $this->add_control('display_mode', [
            'label'   => __('Mode d\'affichage', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'schedule' => __('Horaires & Prix', 'blacktenderscore'),
                'duration' => __('Durée uniquement', 'blacktenderscore'),
                'both'     => __('Horaires + Durée', 'blacktenderscore'),
            ],
            'default'     => 'schedule',
            'description' => __('Choisissez ce que ce tag affiche.', 'blacktenderscore'),
        ]);

        $this->add_control('repeater_field', [
            'label'   => __('Champ repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $repeater_opts,
            'default' => array_key_first($repeater_opts) ?: '',
            'description' => __('Le tag détecte seul le sous-repeater des horaires et le champ prix.', 'blacktenderscore'),
        ]);

        $this->add_control('separator', [
            'label'   => __('Séparateur', 'blacktenderscore'),
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

        // ── Contrôles Prix (cachés en mode "duration") ───────────────────
        $this->add_control('show_price', [
            'label'        => __('Afficher le prix pour chaque horaire', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['display_mode!' => 'duration'],
        ]);

        $this->add_control('currency', [
            'label'     => __('Symbole devise', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '€',
            'condition' => ['show_price' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('price_template', [
            'label'       => __('Format prix', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => ' à {price}',
            'placeholder' => ' à {price}',
            'condition'   => ['show_price' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('show_pricing_note', [
            'label'        => __('Afficher la note tarifaire', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Affiche exp_pricing_note de chaque row du repeater.', 'blacktenderscore'),
            'condition'    => ['display_mode!' => 'duration'],
        ]);

        $this->add_control('pricing_note_separator', [
            'label'     => __('Séparateur entre notes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => ' | ',
            'condition' => ['show_pricing_note' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('pricing_note_color', [
            'label'     => __('Couleur de la note', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '',
            'condition' => ['show_pricing_note' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('pricing_note_size', [
            'label'      => __('Taille de la note', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', 'em', 'rem'],
            'range'      => [
                'px'  => ['min' => 8, 'max' => 40],
                'em'  => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
                'rem' => ['min' => 0.5, 'max' => 3, 'step' => 0.1],
            ],
            'condition' => ['show_pricing_note' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('pricing_note_weight', [
            'label'   => __('Graisse de la note', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''    => __('Par défaut', 'blacktenderscore'),
                '300' => 'Light (300)',
                '400' => 'Normal (400)',
                '500' => 'Medium (500)',
                '600' => 'Semi-Bold (600)',
                '700' => 'Bold (700)',
            ],
            'default'   => '',
            'condition' => ['show_pricing_note' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('pricing_note_style', [
            'label'   => __('Style de la note', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''       => __('Par défaut', 'blacktenderscore'),
                'normal' => 'Normal',
                'italic' => 'Italique',
            ],
            'default'   => 'italic',
            'condition' => ['show_pricing_note' => 'yes', 'display_mode!' => 'duration'],
        ]);

        $this->add_control('fallback', [
            'label'   => __('Texte si vide', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        // ── Contrôles Durée (cachés en mode "schedule") ──────────────────
        $this->add_control('duration_subfield', [
            'label'       => __('Sous-champ durée', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'exc_timeinbot',
            'description' => __('Nom du sous-champ texte dans le repeater (ex: exc_timeinbot → "1h30").', 'blacktenderscore'),
            'separator'   => 'before',
            'condition'   => ['display_mode!' => 'schedule'],
        ]);

        $this->add_control('duration_label_before', [
            'label'     => __('Texte avant la durée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => ['display_mode!' => 'schedule'],
        ]);

        $this->add_control('duration_label_after', [
            'label'     => __('Texte après la durée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => ['display_mode!' => 'schedule'],
        ]);
    }

    public function render(): void {

        $post_id       = (int) get_the_ID();
        $repeater_name = trim((string) ($this->get_settings('repeater_field') ?? ''));
        $display_mode  = (string) ($this->get_settings('display_mode') ?: 'schedule');
        $fallback      = (string) ($this->get_settings('fallback') ?? '');
        $sep           = (string) ($this->get_settings('separator') ?: ' ou ');

        if ($repeater_name === '') {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        $rows = $this->get_repeater_rows($post_id, $repeater_name);
        if (empty($rows)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        // ── Mode "Durée uniquement" ──────────────────────────────────────
        if ($display_mode === 'duration') {
            $dur_subfield = sanitize_key((string) ($this->get_settings('duration_subfield') ?: 'exc_timeinbot'));
            $dur_before   = trim((string) ($this->get_settings('duration_label_before') ?? ''));
            $dur_after    = trim((string) ($this->get_settings('duration_label_after')  ?? ''));

            $durations = [];
            foreach ($rows as $row) {
                $val = trim((string) ($row[$dur_subfield] ?? ''));
                if ($val !== '') $durations[] = $val;
            }

            $durations = array_values(array_unique($durations));

            if (empty($durations)) {
                if ($fallback !== '') echo esc_html($fallback);
                return;
            }

            $parts = [];
            foreach ($durations as $d) {
                $parts[] = ($dur_before !== '' ? $dur_before . ' ' : '') . $d . ($dur_after !== '' ? ' ' . $dur_after : '');
            }

            echo esc_html(implode($sep, $parts));
            return;
        }

        // ── Modes "Horaires & Prix" et "Horaires + Durée" ────────────────
        $structure = $this->acf_detect_repeater_departure_structure($repeater_name);
        if ($structure === null) {
            $structure = [
                'times_subfield'      => 'exp_departure_time',
                'time_value_subfield' => 'departure_time_child',
                'price_subfield'      => 'exp_price',
            ];
        }

        $show_price        = ($this->get_settings('show_price') ?? 'yes') === 'yes';
        $show_pricing_note = ($this->get_settings('show_pricing_note') ?? '') === 'yes';
        $show_duration     = ($display_mode === 'both');
        $currency          = (string) ($this->get_settings('currency') ?: '€');
        $price_tpl         = (string) ($this->get_settings('price_template') ?: ' à {price}');
        $note_sep          = (string) ($this->get_settings('pricing_note_separator') ?: ' · ');
        $dur_subfield      = $show_duration ? sanitize_key((string) ($this->get_settings('duration_subfield') ?: 'exc_timeinbot')) : '';
        $dur_before        = $show_duration ? trim((string) ($this->get_settings('duration_label_before') ?? '')) : '';
        $dur_after         = $show_duration ? trim((string) ($this->get_settings('duration_label_after')  ?? '')) : '';

        $row_lines = [];
        $notes     = [];

        foreach ($rows as $row) {
            $times = $this->extract_times_from_row($row, $structure['times_subfield'], $structure['time_value_subfield']);
            $price = $show_price ? $this->format_price($row[$structure['price_subfield']] ?? '', $currency) : '';

            $row_parts = [];
            foreach ($times as $time_str) {
                $time_str = trim($time_str);
                if ($time_str === '') continue;
                $row_parts[] = $price !== '' ? $time_str . str_replace('{price}', $price, $price_tpl) : $time_str;
            }

            if (!empty($row_parts)) {
                $line = implode($sep, array_unique($row_parts));

                // Durée accolée à chaque row (mode "both")
                if ($show_duration && $dur_subfield !== '') {
                    $dur_val = trim((string) ($row[$dur_subfield] ?? ''));
                    if ($dur_val !== '') {
                        $line .= ' ' . ($dur_before !== '' ? $dur_before . ' ' : '') . $dur_val . ($dur_after !== '' ? ' ' . $dur_after : '');
                    }
                }

                $row_lines[] = $line;
            }

            if ($show_pricing_note) {
                $note = trim((string) ($row['exp_pricing_note'] ?? ''));
                if ($note !== '') $notes[] = $note;
            }
        }

        if (empty($row_lines) && empty($notes)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        /* Horaires : chaque row sur sa propre ligne */
        foreach ($row_lines as $i => $line) {
            if ($i > 0) echo '<br>';
            echo esc_html($line);
        }

        /* Notes tarifaires : saut de ligne + span stylé */
        if (!empty($notes)) {
            $unique_notes = array_values(array_unique($notes));
            $notes_str    = implode(esc_html($note_sep), array_map('esc_html', $unique_notes));

            $styles     = $this->build_note_inline_styles();
            $style_attr = $styles !== '' ? ' style="' . esc_attr($styles) . '"' : '';

            echo '<br><span class="bt-pricing-note"' . $style_attr . '>' . $notes_str . '</span>';
        }
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

    /**
     * Construit le style inline pour le span de la note tarifaire.
     */
    private function build_note_inline_styles(): string {

        $parts = [];

        $color = (string) ($this->get_settings('pricing_note_color') ?? '');
        if ($color !== '') {
            $parts[] = 'color:' . $color;
        }

        $size = $this->get_settings('pricing_note_size');
        if (!empty($size['size'])) {
            $unit = $size['unit'] ?? 'px';
            $parts[] = 'font-size:' . (float) $size['size'] . $unit;
        }

        $weight = (string) ($this->get_settings('pricing_note_weight') ?? '');
        if ($weight !== '') {
            $parts[] = 'font-weight:' . $weight;
        }

        $style = (string) ($this->get_settings('pricing_note_style') ?? '');
        if ($style !== '') {
            $parts[] = 'font-style:' . $style;
        }

        return implode(';', $parts);
    }
}
