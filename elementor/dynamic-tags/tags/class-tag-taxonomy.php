<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Champ ACF (texte, nombre, taxonomie…).
 *
 * Le SELECT liste les champs ACF scalaires enregistrés (pas les slugs WP).
 * La résolution est intelligente :
 *
 *  1. get_field($key, $post_id)
 *      → WP_Term | WP_Term[]      : affiche le nom/slug/description
 *      → array assoc ['term_id']  : résout le terme et affiche
 *      → scalaire (string/int)    : affiche tel quel
 *  2. Fallback get_the_terms($post_id, $key) pour les taxos WP natives.
 *
 * Le contrôle `taxonomy` conserve son nom pour la rétrocompatibilité
 * avec les pages déjà configurées.
 */
class Tag_Taxonomy extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-taxonomy'; }
    public function get_title():      string { return 'BT: Champ ACF / Taxonomie'; }
    public function get_categories(): array  { return ['text']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // Le SELECT liste maintenant les CHAMPS ACF (pas les slugs WP)
        $opts = $this->acf_field_options();

        $this->add_control('taxonomy', [
            'label'   => __('Champ ACF', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $opts,
            'default' => array_key_first($opts),
        ]);

        $this->add_control('format', [
            'label'       => __('Format (si terme WP)', 'blacktenderscore'),
            'description' => __('Utilisé uniquement si le champ retourne des termes ACF / WP.', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => [
                'name'        => __('Nom du terme', 'blacktenderscore'),
                'slug'        => __('Slug', 'blacktenderscore'),
                'description' => __('Description', 'blacktenderscore'),
            ],
            'default' => 'name',
        ]);

        $this->add_control('separator', [
            'label'   => __('Séparateur', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                ' · ' => '·  (point médian)',
                ' / ' => '/  (slash)',
                ', '  => ',  (virgule)',
                ' '   => 'espace',
                ' — ' => '—  (tiret long)',
                ' - ' => '-  (trait d\'union)',
            ],
            'default' => ' · ',
        ]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): void {
        $key     = (string) ($this->get_settings('taxonomy') ?? '');
        $format  = $this->get_settings('format')    ?: 'name';
        $sep     = $this->get_settings('separator') ?: ' · ';

        if (!$key) return;

        $post_id = (int) get_the_ID();

        // 1. Champ ACF
        $raw = function_exists('get_field') ? get_field($key, $post_id) : null;

        // 2. Fallback : taxonomie WP native (assignée via l'interface standard)
        if (empty($raw)) {
            $native = get_the_terms($post_id, $key);
            if (is_array($native) && !empty($native)) $raw = $native;
        }

        if (empty($raw) && $raw !== '0' && $raw !== 0) return;

        // Normalise en tableau
        if (!is_array($raw) || $raw instanceof \WP_Term) {
            $raw = [$raw];
        }
        // ACF taxonomy "Return format: Object" peut retourner un WP_Term direct
        // (déjà géré ci-dessus), mais "Return format: Array" donne un assoc —
        // si l'assoc contient term_id c'est un seul terme, on le wrappe
        if (is_array($raw) && isset($raw['term_id'])) {
            $raw = [$raw];
        }

        $parts = [];
        foreach ($raw as $item) {
            $str = $this->format_item($item, $format);
            if ($str !== '') $parts[] = $str;
        }

        if (empty($parts)) return;

        echo implode(esc_html($sep), array_map('esc_html', $parts));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Convertit un item (WP_Term, array assoc, int, string…) en chaîne.
     *
     * Pour les termes WP, applique le format (name/slug/description).
     * Pour les scalaires, retourne la valeur brute.
     * Ne tente PAS de get_term_by('slug') sur les strings pour ne pas
     * consommer des valeurs texte simples comme "Captain John".
     */
    private function format_item(mixed $item, string $format): string {

        // WP_Term natif
        if ($item instanceof \WP_Term) {
            return $this->term_prop($item, $format);
        }

        // ACF taxonomy "Return format: Array" — tableau assoc avec term_id
        if (is_array($item) && isset($item['term_id'])) {
            $t = get_term((int) $item['term_id']);
            return $t instanceof \WP_Term ? $this->term_prop($t, $format) : '';
        }

        // ID entier (ACF "Return format: ID")
        if (is_int($item) || (is_string($item) && ctype_digit(ltrim((string) $item)))) {
            $t = get_term((int) $item);
            return $t instanceof \WP_Term ? $this->term_prop($t, $format) : (string) $item;
        }

        // ACF link
        if (is_array($item) && isset($item['url'])) {
            return $item['title'] ?: $item['url'];
        }

        // Scalaire plain (texte, nombre…)
        if (is_scalar($item)) return (string) $item;

        return '';
    }

    private function term_prop(\WP_Term $term, string $format): string {
        return match ($format) {
            'slug'        => $term->slug,
            'description' => wp_strip_all_tags($term->description),
            default       => $term->name,
        };
    }
}
