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

        // ── Type de contenu (excursion vs bateau) ────────────────────────
        $this->add_control('is_boat', [
            'label'        => __('Est un bateau', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'description'  => __('Active le mode bateau : affiche les forfaits (durées) et prix depuis boat_price.', 'blacktenderscore'),
        ]);

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
            'condition'   => ['is_boat' => ''],
        ]);

        $this->add_control('repeater_field', [
            'label'     => __('Champ repeater', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => $repeater_opts,
            'default'   => array_key_first($repeater_opts) ?: '',
            'condition' => ['is_boat' => ''],
        ]);

        $this->add_control('separator', [
            'label'   => __('Séparateur', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ' · '  => '·  (point médian)',
                ' ou ' => __('ou  (texte)', 'blacktenderscore'),
                ' / '  => '/  (slash)',
                ', '   => ',  (virgule)',
                ' — '  => '—  (tiret long)',
                ' '    => __('Espace', 'blacktenderscore'),
                '<br>' => __('Saut de ligne', 'blacktenderscore'),
            ],
            'default' => ' ou ',
        ]);

        // ── Contrôles Prix (cachés en mode "duration" et mode bateau) ────
        $this->add_control('show_price', [
            'label'        => __('Afficher le prix pour chaque horaire', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['display_mode!' => 'duration', 'is_boat' => ''],
        ]);

        $this->add_control('group_by_price', [
            'label'        => __('Grouper les dates par prix', 'blacktenderscore'),
            'description'  => __('Une ligne par prix avec toutes les dates correspondantes.', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_price' => 'yes', 'display_mode!' => 'duration', 'is_boat' => ''],
        ]);

        $this->add_control('table_template', [
            'label'   => __('Template tableau', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'v1' => __('V1 — Pills (badges dates)', 'blacktenderscore'),
                'v2' => __('V2 — Split colonne', 'blacktenderscore'),
                'v3' => __('V3 — Cards badge', 'blacktenderscore'),
                'v4' => __('V4 — Minimal lignes', 'blacktenderscore'),
            ],
            'default'   => 'v1',
            'condition' => ['show_price' => 'yes', 'group_by_price' => 'yes', 'display_mode!' => 'duration', 'is_boat' => ''],
        ]);

        $this->add_control('currency', [
            'label'     => __('Symbole devise', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '€',
        ]);

        $this->add_control('price_template', [
            'label'       => __('Format prix', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => ' à {price}',
            'placeholder' => ' à {price}',
            'condition'   => ['show_price' => 'yes', 'display_mode!' => 'duration', 'is_boat' => ''],
        ]);

        // ── Contrôles Bateau ─────────────────────────────────────────────
        $this->add_control('boat_show_price', [
            'label'        => __('Afficher le prix', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'condition'    => ['is_boat' => 'yes'],
        ]);

        $this->add_control('boat_price_tax_label', [
            'label'   => __('Mention HT/TTC', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ''    => __('Aucune', 'blacktenderscore'),
                'HT'  => __('HT (Hors Taxes)', 'blacktenderscore'),
                'TTC' => __('TTC (Toutes Taxes Comprises)', 'blacktenderscore'),
            ],
            'default'   => '',
            'condition' => ['is_boat' => 'yes', 'boat_show_price' => 'yes'],
        ]);

        $this->add_control('boat_price_template', [
            'label'       => __('Format prix', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '{label} : {price}',
            'placeholder' => '{label} : {price}',
            'description' => __('Placeholders: {label} = nom du forfait, {price} = prix formaté', 'blacktenderscore'),
            'condition'   => ['is_boat' => 'yes', 'boat_show_price' => 'yes', 'boat_use_template' => ''],
        ]);

        $this->add_control('boat_use_template', [
            'label'        => __('Afficher en template', 'blacktenderscore'),
            'description'  => __('Utilise un des 4 templates visuels (V1-V4).', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['is_boat' => 'yes'],
        ]);

        $this->add_control('boat_template', [
            'label'   => __('Template', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'v1' => __('V1 — Pills (badges)', 'blacktenderscore'),
                'v2' => __('V2 — Split colonne', 'blacktenderscore'),
                'v3' => __('V3 — Cards badge', 'blacktenderscore'),
                'v4' => __('V4 — Minimal lignes', 'blacktenderscore'),
            ],
            'default'   => 'v1',
            'condition' => ['is_boat' => 'yes', 'boat_use_template' => 'yes'],
        ]);

        $this->add_control('boat_show_duration', [
            'label'        => __('Afficher la durée', 'blacktenderscore'),
            'description'  => __('Ajoute la durée entre parenthèses (ex: "Journée complète (4h)").', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['is_boat' => 'yes'],
        ]);

        $this->add_control('boat_duration_suffix', [
            'label'       => __('Suffixe durée', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'h',
            'placeholder' => 'h, min, heures...',
            'description' => __('Suffixe après le chiffre (ex: h → "4h", min → "30min").', 'blacktenderscore'),
            'condition'   => ['is_boat' => 'yes', 'boat_show_duration' => 'yes'],
        ]);

        $this->add_control('boat_show_carburant', [
            'label'        => __('Afficher le champ carburant', 'blacktenderscore'),
            'description'  => __('Affiche "Carburant inclus" sous le prix si activé.', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['is_boat' => 'yes'],
        ]);

        $this->add_control('boat_carburant_text', [
            'label'       => __('Texte carburant', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'Carburant inclus',
            'placeholder' => 'Carburant inclus',
            'condition'   => ['is_boat' => 'yes', 'boat_show_carburant' => 'yes'],
        ]);

        $this->add_control('boat_show_per_person', [
            'label'        => __('Afficher prix par personne', 'blacktenderscore'),
            'description'  => __('Ajoute "soit X€/pers*" avec info pax minimum.', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['is_boat' => 'yes', 'boat_use_template' => 'yes'],
        ]);

        $this->add_control('fallback', [
            'label'   => __('Texte si vide', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        // ── Contrôles Durée (popover, cachés en mode "schedule" et mode bateau) ──
        $this->add_control('duration_popover', [
            'label'        => __('Options durée', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::POPOVER_TOGGLE,
            'return_value' => 'yes',
            'separator'    => 'before',
            'condition'    => ['display_mode!' => 'schedule', 'is_boat' => ''],
        ]);

        $this->start_popover();

        $this->add_control('duration_subfield', [
            'label'     => __('Sous-champ durée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'exc_timeinbot',
            'condition' => ['duration_popover' => 'yes'],
        ]);

        $this->add_control('duration_label_before', [
            'label'     => __('Texte avant', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => ['duration_popover' => 'yes'],
        ]);

        $this->add_control('duration_label_after', [
            'label'     => __('Texte après', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => ['duration_popover' => 'yes'],
        ]);

        $this->end_popover();
    }

    public function render(): void {

        $post_id   = (int) get_the_ID();
        $is_boat   = ($this->get_settings('is_boat') ?? '') === 'yes';
        $fallback  = (string) ($this->get_settings('fallback') ?? '');
        $sep       = (string) ($this->get_settings('separator') ?: ' ou ');

        // ── Mode Bateau ──────────────────────────────────────────────────
        if ($is_boat) {
            $this->render_boat_mode($post_id, $sep, $fallback);
            return;
        }

        // ── Mode Excursion (comportement original) ───────────────────────
        $repeater_name = trim((string) ($this->get_settings('repeater_field') ?? ''));
        $display_mode  = (string) ($this->get_settings('display_mode') ?: 'schedule');

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

        $show_price     = ($this->get_settings('show_price') ?? 'yes') === 'yes';
        $show_duration  = ($display_mode === 'both');
        $group_by_price = $show_price && ($this->get_settings('group_by_price') ?? '') === 'yes';
        $currency       = (string) ($this->get_settings('currency') ?: '€');
        $price_tpl      = (string) ($this->get_settings('price_template') ?: ' à {price}');
        $dur_subfield   = $show_duration ? sanitize_key((string) ($this->get_settings('duration_subfield') ?: 'exc_timeinbot')) : '';
        $dur_before     = $show_duration ? trim((string) ($this->get_settings('duration_label_before') ?? '')) : '';
        $dur_after      = $show_duration ? trim((string) ($this->get_settings('duration_label_after')  ?? '')) : '';

        $row_lines = [];

        // ── Mode groupé par prix → toujours afficher avec template ─────
        if ($group_by_price) {
            $price_groups = []; // [prix => [dates...]]

            foreach ($rows as $row) {
                $times = $this->extract_times_from_row($row, $structure['times_subfield'], $structure['time_value_subfield']);
                $price = $this->format_price($row[$structure['price_subfield']] ?? '', $currency);
                if ($price === '') $price = __('Prix non défini', 'blacktenderscore');

                foreach ($times as $time_str) {
                    $time_str = trim($time_str);
                    if ($time_str === '') continue;
                    if (!isset($price_groups[$price])) $price_groups[$price] = [];
                    $price_groups[$price][] = $time_str;
                }
            }

            // Rendu template
            if (!empty($price_groups)) {
                $template = (string) ($this->get_settings('table_template') ?: 'v1');
                $this->render_schedule_template($template, $price_groups);
            }
            return;
        }

        // ── Mode standard (une date = un prix) ───────────────────────────
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
        }

        if (empty($row_lines)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        /* Horaires : séparateur configurable (texte ou <br>) */
        $is_br_sep = ($sep === '<br>');
        foreach ($row_lines as $i => $line) {
            if ($i > 0) {
                echo $is_br_sep ? '<br>' : esc_html($sep);
            }
            echo esc_html($line);
        }
    }

    /**
     * Rendu mode bateau — affiche les forfaits depuis boat_price.
     *
     * Structure boat_price :
     *  - boat_price_boat       : float (prix)
     *  - boat_location_duration: int (term ID exp_duration)
     *  - boat_carburant        : bool
     *  - boat_price_note       : string
     *  - boat_deposit          : float
     */
    private function render_boat_mode(int $post_id, string $sep, string $fallback): void {

        if (!function_exists('get_field')) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        $rows = get_field('boat_price', $post_id);
        if (!is_array($rows) || empty($rows)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        $show_price       = ($this->get_settings('boat_show_price') ?? 'yes') === 'yes';
        $use_template     = ($this->get_settings('boat_use_template') ?? '') === 'yes';
        $price_tpl        = (string) ($this->get_settings('boat_price_template') ?: '{label} : {price}');
        $currency         = (string) ($this->get_settings('currency') ?: '€');
        $show_duration    = ($this->get_settings('boat_show_duration') ?? '') === 'yes';
        $duration_suffix  = (string) ($this->get_settings('boat_duration_suffix') ?: 'h');
        $show_carburant   = ($this->get_settings('boat_show_carburant') ?? '') === 'yes';
        $carburant_text   = (string) ($this->get_settings('boat_carburant_text') ?: 'Carburant inclus');

        // Collecter les forfaits [price_str => ['labels' => [...], 'carburant' => bool]]
        $price_groups = [];
        $forfaits     = []; // Pour le mode texte simple

        foreach ($rows as $row) {
            $price_val = (float) ($row['boat_price_boat'] ?? 0);
            if ($price_val <= 0) continue;

            // Résoudre le nom de la durée depuis la taxonomie
            $dur_id = $row['boat_location_duration'] ?? null;
            $label  = '';
            if ($dur_id) {
                $term = is_numeric($dur_id) ? get_term((int) $dur_id) : $dur_id;
                if ($term && !is_wp_error($term)) {
                    $label = $term->name;
                }
            }
            if ($label === '') {
                $label = __('Forfait', 'blacktenderscore');
            }

            // Récupérer la durée en heures/minutes si activé
            $duration_text = '';
            if ($show_duration) {
                $duration_val = $row['boat_location_duration_inhour'] ?? '';
                if ($duration_val !== '' && $duration_val !== null) {
                    $duration_text = ' (' . $duration_val . $duration_suffix . ')';
                }
            }

            // Label final avec durée optionnelle
            $label_with_duration = $label . $duration_text;

            // Carburant inclus (true/false)
            $has_carburant = !empty($row['boat_carburant']);

            $price_str = number_format($price_val, 0, ',', ' ') . $currency;

            // Pour template : grouper par prix avec structure enrichie
            if (!isset($price_groups[$price_str])) {
                $price_groups[$price_str] = [
                    'labels'    => [],
                    'carburant' => false,
                ];
            }
            $price_groups[$price_str]['labels'][] = $label_with_duration;
            // Si au moins un forfait à ce prix a carburant inclus
            if ($has_carburant) {
                $price_groups[$price_str]['carburant'] = true;
            }

            // Pour mode texte simple
            if ($show_price) {
                $forfaits[] = str_replace(
                    ['{label}', '{price}'],
                    [$label_with_duration, $price_str],
                    $price_tpl
                );
            } else {
                $forfaits[] = $label_with_duration;
            }
        }

        if (empty($forfaits)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        // ── Mode template ──────────────────────────────────────────────────
        if ($use_template && !empty($price_groups)) {
            $template         = (string) ($this->get_settings('boat_template') ?: 'v1');
            $show_per_person  = ($this->get_settings('boat_show_per_person') ?? '') === 'yes';

            // Contexte bateau pour calcul par personne
            $boat_context = null;
            if ($show_per_person) {
                $pax_min = (int) get_field('boat_pax_minimum', $post_id);
                $pax_max = (int) get_field('boat_pax_max', $post_id);
                if ($pax_max > 0) {
                    $boat_context = [
                        'pax_min'  => $pax_min > 0 ? $pax_min : $pax_max,
                        'pax_max'  => $pax_max,
                        'currency' => $currency,
                    ];
                }
            }

            // Options carburant
            $carburant_opts = null;
            if ($show_carburant) {
                $carburant_opts = [
                    'text' => $carburant_text,
                ];
            }

            // Label HT/TTC
            $tax_label = (string) ($this->get_settings('boat_price_tax_label') ?: '');

            $this->render_boat_schedule_template($template, $price_groups, $boat_context, $carburant_opts, $tax_label);
            return;
        }

        // ── Mode texte simple ──────────────────────────────────────────────
        $is_br_sep = ($sep === '<br>');
        foreach ($forfaits as $i => $line) {
            if ($i > 0) {
                echo $is_br_sep ? '<br>' : esc_html($sep);
            }
            echo esc_html($line);
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
     * Rend le tableau groupé par prix selon le template choisi.
     *
     * @param string $template v1|v2|v3|v4
     * @param array  $price_groups [prix => [dates...]]
     */
    private function render_schedule_template(string $template, array $price_groups): void {

        // Enqueue CSS
        wp_enqueue_style(
            'bt-schedule-templates',
            BT_URL . 'elementor/assets/bt-schedule-templates.css',
            [],
            BT_VERSION
        );

        switch ($template) {
            case 'v2':
                // V2 — Split colonne
                echo '<div class="bt-schedule bt-schedule--v2">';
                foreach ($price_groups as $price => $dates) {
                    $dates = array_unique($dates);
                    echo '<div class="bt-schedule__row">';
                    echo '<div class="bt-schedule__price">' . esc_html($price) . '</div>';
                    echo '<div class="bt-schedule__dates">';
                    foreach ($dates as $d) {
                        echo '<span class="bt-schedule__date">' . esc_html($d) . '</span>';
                    }
                    echo '</div></div>';
                }
                echo '</div>';
                break;

            case 'v3':
                // V3 — Cards badge
                echo '<div class="bt-schedule bt-schedule--v3">';
                foreach ($price_groups as $price => $dates) {
                    $dates = array_unique($dates);
                    echo '<div class="bt-schedule__row">';
                    echo '<div class="bt-schedule__price">' . esc_html($price) . '</div>';
                    echo '<div class="bt-schedule__dates">';
                    foreach ($dates as $d) {
                        echo '<span class="bt-schedule__date">' . esc_html($d) . '</span>';
                    }
                    echo '</div></div>';
                }
                echo '</div>';
                break;

            case 'v4':
                // V4 — Minimal lignes (table)
                echo '<table class="bt-schedule bt-schedule--v4">';
                foreach ($price_groups as $price => $dates) {
                    $dates = array_unique($dates);
                    echo '<tr class="bt-schedule__row">';
                    echo '<td class="bt-schedule__price">' . esc_html($price) . '</td>';
                    echo '<td class="bt-schedule__dates">';
                    foreach ($dates as $d) {
                        echo '<span class="bt-schedule__date">' . esc_html($d) . '</span>';
                    }
                    echo '</td></tr>';
                }
                echo '</table>';
                break;

            case 'v1':
            default:
                // V1 — Pills
                echo '<div class="bt-schedule bt-schedule--v1">';
                foreach ($price_groups as $price => $dates) {
                    $dates = array_unique($dates);
                    echo '<div class="bt-schedule__row">';
                    echo '<div class="bt-schedule__price">' . esc_html($price) . '</div>';
                    echo '<div class="bt-schedule__dates">';
                    foreach ($dates as $d) {
                        echo '<span class="bt-schedule__date">' . esc_html($d) . '</span>';
                    }
                    echo '</div></div>';
                }
                echo '</div>';
                break;
        }
    }

    /**
     * Rendu template bateau avec option prix par personne, carburant et HT/TTC.
     *
     * @param string     $template       v1|v2|v3|v4
     * @param array      $price_groups   [prix_str => ['labels' => [...], 'carburant' => bool]]
     * @param array|null $boat_context   ['pax_min' => int, 'pax_max' => int, 'currency' => string]
     * @param array|null $carburant_opts ['text' => string]
     * @param string     $tax_label      'HT', 'TTC' ou '' (vide)
     */
    private function render_boat_schedule_template(string $template, array $price_groups, ?array $boat_context, ?array $carburant_opts = null, string $tax_label = ''): void {

        // Enqueue CSS
        wp_enqueue_style(
            'bt-schedule-templates',
            BT_URL . 'elementor/assets/bt-schedule-templates.css',
            [],
            BT_VERSION
        );

        $show_per_person = $boat_context !== null && $boat_context['pax_max'] > 0;

        // Helper pour extraire le prix numérique depuis "850€" ou "1 250€"
        $extract_price = function (string $price_str): float {
            $clean = preg_replace('/[^\d,.]/', '', $price_str);
            $clean = str_replace(',', '.', $clean);
            return (float) $clean;
        };

        // Helper pour formater le prix par personne (avec espace insécable avant devise)
        $format_per_person = function (float $total, array $ctx): string {
            $per_person = $total / $ctx['pax_max'];
            // Format français : "71 €" avec espace insécable (\u00A0)
            return number_format($per_person, 0, ',', ' ') . "\u{00A0}" . $ctx['currency'];
        };

        // Tooltip texte
        $tooltip_text = '';
        if ($show_per_person) {
            $tooltip_text = sprintf(
                __('En partant avec %d personnes (capacité max)', 'blacktenderscore'),
                $boat_context['pax_max']
            );
        }

        // Icône info SVG
        $info_icon = '<svg class="bt-schedule__info-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>';

        $wrapper_class = 'bt-schedule bt-schedule--' . esc_attr($template);
        if ($show_per_person) {
            $wrapper_class .= ' bt-schedule--with-per-person';
        }

        // Helper pour afficher le bloc carburant
        $render_carburant = function (bool $has_carburant, string $tag = 'div') use ($carburant_opts): void {
            if ($carburant_opts === null || !$has_carburant) return;
            echo '<' . $tag . ' class="bt-schedule__carburant">' . esc_html($carburant_opts['text']) . '</' . $tag . '>';
        };

        // Helper pour afficher le prix avec label HT/TTC
        $render_price = function (string $price) use ($tax_label): void {
            echo '<div class="bt-schedule__price">' . esc_html($price);
            if ($tax_label !== '') {
                echo ' <span class="bt-schedule__tax-label">' . esc_html($tax_label) . '</span>';
            }
            echo '</div>';
        };

        // Rendu selon template
        switch ($template) {
            case 'v4':
                // V4 — Table
                echo '<table class="' . esc_attr($wrapper_class) . '">';
                foreach ($price_groups as $price => $data) {
                    $labels       = array_unique($data['labels'] ?? []);
                    $has_carburant = $data['carburant'] ?? false;

                    echo '<tr class="bt-schedule__row">';
                    echo '<td class="bt-schedule__price-cell">';
                    $render_price($price);
                    $render_carburant($has_carburant, 'div');
                    echo '</td>';
                    echo '<td class="bt-schedule__dates">';
                    foreach ($labels as $l) {
                        echo '<span class="bt-schedule__date">' . esc_html($l) . '</span>';
                    }
                    echo '</td>';

                    if ($show_per_person) {
                        $total = $extract_price($price);
                        $pp    = $format_per_person($total, $boat_context);
                        echo '<td class="bt-schedule__per-person">';
                        echo '<span class="bt-schedule__pp-text">' . esc_html(sprintf(__('soit %s /pers', 'blacktenderscore'), $pp)) . '</span>';
                        echo '<span class="bt-schedule__pp-tooltip" title="' . esc_attr($tooltip_text) . '">' . $info_icon . '</span>';
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                echo '</table>';
                break;

            case 'v2':
            case 'v3':
            case 'v1':
            default:
                // V1, V2, V3 — Div-based
                echo '<div class="' . esc_attr($wrapper_class) . '">';
                foreach ($price_groups as $price => $data) {
                    $labels       = array_unique($data['labels'] ?? []);
                    $has_carburant = $data['carburant'] ?? false;

                    echo '<div class="bt-schedule__row">';
                    echo '<div class="bt-schedule__price-cell">';
                    $render_price($price);
                    $render_carburant($has_carburant, 'div');
                    echo '</div>';
                    echo '<div class="bt-schedule__dates">';
                    foreach ($labels as $l) {
                        echo '<span class="bt-schedule__date">' . esc_html($l) . '</span>';
                    }
                    echo '</div>';

                    if ($show_per_person) {
                        $total = $extract_price($price);
                        $pp    = $format_per_person($total, $boat_context);
                        echo '<div class="bt-schedule__per-person">';
                        echo '<span class="bt-schedule__pp-text">' . esc_html(sprintf(__('soit %s /pers', 'blacktenderscore'), $pp)) . '</span>';
                        echo '<span class="bt-schedule__pp-tooltip" title="' . esc_attr($tooltip_text) . '">' . $info_icon . '</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                }
                echo '</div>';
                break;
        }
    }
}
