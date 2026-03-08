<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Langues parlées de l'excursion (exp_languages).
 *
 * Options :
 *  - format    : code (fr), nom complet (Français), drapeau (🇫🇷), code+drapeau
 *  - separator : ·, /, espace, virgule, ou custom
 */
class Tag_Exp_Languages extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-languages'; }
    public function get_title():      string { return 'BT: Langues parlées'; }
    public function get_categories(): array  { return ['text']; }

    // ── Dictionnaire ──────────────────────────────────────────────────────────

    private static function dict(): array {
        return [
            'fr' => ['name' => 'Français',  'flag' => '🇫🇷'],
            'en' => ['name' => 'English',   'flag' => '🇬🇧'],
            'it' => ['name' => 'Italiano',  'flag' => '🇮🇹'],
            'de' => ['name' => 'Deutsch',   'flag' => '🇩🇪'],
            'es' => ['name' => 'Español',   'flag' => '🇪🇸'],
            'nl' => ['name' => 'Nederlands','flag' => '🇳🇱'],
            'ru' => ['name' => 'Русский',   'flag' => '🇷🇺'],
            'zh' => ['name' => '中文',       'flag' => '🇨🇳'],
            'ar' => ['name' => 'العربية',   'flag' => '🇸🇦'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        $this->add_control('format', [
            'label'   => __('Format', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'code'        => __('Code (fr, en…)', 'blacktenderscore'),
                'name'        => __('Nom complet (Français…)', 'blacktenderscore'),
                'flag'        => __('Drapeau seul (🇫🇷…)', 'blacktenderscore'),
                'flag_code'   => __('Drapeau + code (🇫🇷 fr)', 'blacktenderscore'),
                'flag_name'   => __('Drapeau + nom (🇫🇷 Français)', 'blacktenderscore'),
            ],
            'default' => 'flag_name',
        ]);

        $this->add_control('separator', [
            'label'   => __('Séparateur', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ' · ' => '·  (point médian)',
                ' / ' => '/  (slash)',
                ', '  => ',  (virgule)',
                ' '   => 'espace',
                ' — ' => '—  (tiret)',
            ],
            'default' => ' · ',
        ]);

        $this->add_control('uppercase', [
            'label'        => __('Code en majuscules', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['format' => ['code', 'flag_code']],
        ]);
    }

    // ── Render ───────────────────────────────────────────────────────────────

    public function render(): void {
        $raw = $this->acf('exp_languages');
        if (empty($raw)) return;

        $langs     = (array) $raw;
        $format    = $this->get_settings('format')    ?: 'flag_name';
        $separator = $this->get_settings('separator') ?: ' · ';
        $uppercase = $this->get_settings('uppercase') === 'yes';
        $dict      = self::dict();

        $parts = [];
        foreach ($langs as $code) {
            $code = strtolower(trim((string) $code));
            $info = $dict[$code] ?? ['name' => strtoupper($code), 'flag' => ''];

            $display_code = $uppercase ? strtoupper($code) : $code;

            $part = match ($format) {
                'code'      => $display_code,
                'name'      => $info['name'],
                'flag'      => $info['flag'],
                'flag_code' => $info['flag'] . ' ' . $display_code,
                'flag_name' => $info['flag'] . ' ' . $info['name'],
                default     => $code,
            };

            $parts[] = trim($part);
        }

        // Le flag est un emoji — pas besoin de esc_html qui le corrompt
        echo implode(esc_html($separator), array_map('esc_html', $parts));
    }
}
