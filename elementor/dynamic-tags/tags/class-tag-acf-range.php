<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Plage de deux champs ACF.
 *
 * Permet d'afficher deux valeurs ACF combinées avec un séparateur,
 * chacune avec un préfixe et un suffixe optionnels.
 *
 * Exemples de rendu :
 *  "8 - 12 (pers.)"       → field_1=boat_pax_min, sep=" - ", suffix_2=" (pers.)"
 *  "De 45€ à 120€"        → prefix_1="De ", suffix_1="€", sep=" à ", suffix_2="€"
 *  "minimum 2 personnes"  → prefix_1="minimum ", suffix_1=" personnes", field_2 vide
 *
 * Les valeurs ACF sont résolues de façon intelligente :
 *  - Terme WP (taxonomy field)  → nom du terme
 *  - Scalaire (number, text)    → valeur brute
 *  - Champ non renseigné        → la partie est omise (pas de séparateur orphelin)
 */
class Tag_Acf_Range extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-acf-range'; }
    public function get_title():      string { return 'BT: Plage de champs ACF'; }
    public function get_categories(): array  { return ['text', 'number']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        $field_opts = $this->acf_field_options();

        // ── Champ 1 ───────────────────────────────────────────────────────────

        $this->add_control('heading_1', [
            'label' => __('Valeur 1', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ]);

        $this->add_control('field_1', [
            'label'   => __('Champ ACF', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $field_opts,
            'default' => array_key_first($field_opts),
        ]);

        $this->add_control('prefix_1', [
            'label'       => __('Préfixe', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'De ',
            'description' => __('Collé avant la valeur 1. Incluez les espaces si besoin.', 'blacktenderscore'),
        ]);

        $this->add_control('suffix_1', [
            'label'       => __('Suffixe', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '€',
            'description' => __('Collé après la valeur 1.', 'blacktenderscore'),
        ]);

        // ── Séparateur ────────────────────────────────────────────────────────

        $this->add_control('separator', [
            'label'   => __('Séparateur', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ' - '       => '-  (trait d\'union)',
                ' · '       => '·  (point médian)',
                ' / '       => '/  (slash)',
                ', '        => ',  (virgule)',
                ' — '       => '—  (tiret long)',
                ' à '       => 'à  (plage française)',
                ' → '       => '→  (flèche)',
                ' jusqu\'à ' => 'jusqu\'à',
                ' '         => 'espace',
            ],
            'default' => ' - ',
        ]);

        // ── Champ 2 ───────────────────────────────────────────────────────────

        $this->add_control('heading_2', [
            'label' => __('Valeur 2', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ]);

        $this->add_control('field_2', [
            'label'   => __('Champ ACF', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['' => __('— Aucun —', 'blacktenderscore')] + $field_opts,
            'default' => '',
        ]);

        $this->add_control('prefix_2', [
            'label'       => __('Préfixe', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => 'à ',
            'description' => __('Collé avant la valeur 2. Incluez les espaces si besoin.', 'blacktenderscore'),
        ]);

        $this->add_control('suffix_2', [
            'label'       => __('Suffixe', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'placeholder' => '€',
            'description' => __('Collé après la valeur 2.', 'blacktenderscore'),
        ]);

        // ── Fallback ──────────────────────────────────────────────────────────

        $this->add_control('fallback', [
            'label'       => __('Texte si aucune valeur', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Laissez vide pour n\'afficher rien.', 'blacktenderscore'),
        ]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): void {
        $post_id  = (int) get_the_ID();
        $sep      = (string) ($this->get_settings('separator') ?: ' - ');
        $fallback = (string) ($this->get_settings('fallback')  ?? '');

        $parts = [];

        foreach ([1, 2] as $n) {
            $key = trim((string) ($this->get_settings("field_{$n}") ?? ''));
            if (!$key) continue;

            $raw = function_exists('get_field') ? get_field($key, $post_id) : null;

            // Fallback taxonomie WP native
            if (empty($raw) && $raw !== '0' && $raw !== 0) {
                $native = get_the_terms($post_id, $key);
                if (is_array($native) && !empty($native)) $raw = $native;
            }

            $str = $this->acf_scalar($raw);
            if ($str === '') continue;

            $prefix = (string) ($this->get_settings("prefix_{$n}") ?? '');
            $suffix = (string) ($this->get_settings("suffix_{$n}") ?? '');

            $parts[] = $prefix . $str . $suffix;
        }

        if (empty($parts)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        echo esc_html(implode($sep, $parts));
    }
}
