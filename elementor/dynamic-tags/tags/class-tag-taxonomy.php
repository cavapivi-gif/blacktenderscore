<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Taxonomie générique.
 *
 * Résolution en deux temps :
 *  1. Taxonomie WP native  → get_the_terms( $post_id, $key )
 *  2. Champ ACF en fallback → get_field( $key, $post_id )
 *     (ACF taxonomy field retourne des WP_Term, des IDs entiers, ou des tableaux assoc)
 *
 * Utilisations typiques :
 *  - boat_skipper  (taxonomie WP sur le CPT bateau)
 *  - exp_skipper   (champ ACF sur le CPT excursion)
 *  - n'importe quelle taxonomie enregistrée sur le post courant
 */
class Tag_Taxonomy extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-taxonomy'; }
    public function get_title():      string { return 'BT: Taxonomie'; }
    public function get_categories(): array  { return ['text']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        $this->add_control('taxonomy', [
            'label'       => __('Clé taxonomie / champ ACF', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => 'boat_skipper',
            'placeholder' => 'boat_skipper, exp_skipper…',
            'label_block' => true,
        ]);

        $this->add_control('format', [
            'label'   => __('Format', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
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
                ' — ' => '—  (tiret)',
            ],
            'default' => ' · ',
        ]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): void {
        $key       = trim((string) $this->get_settings('taxonomy'));
        $format    = $this->get_settings('format')    ?: 'name';
        $separator = $this->get_settings('separator') ?: ' · ';

        if (!$key) return;

        $terms = $this->resolve_terms($key, (int) get_the_ID());
        if (empty($terms)) return;

        $parts = [];
        foreach ($terms as $term) {
            $parts[] = match ($format) {
                'slug'        => $term->slug,
                'description' => $term->description,
                default       => $term->name,
            };
        }

        echo implode(esc_html($separator), array_map('esc_html', $parts));
    }

    // ── Résolution des termes ─────────────────────────────────────────────────

    /**
     * @return \WP_Term[]
     */
    private function resolve_terms(string $key, int $post_id): array {

        // 1. Taxonomie WP native
        $terms = get_the_terms($post_id, $key);
        if (is_array($terms) && !empty($terms)) {
            return $terms;
        }

        // 2. Champ ACF (taxonomy field)
        if (!function_exists('get_field')) return [];

        $raw = get_field($key, $post_id);
        if (empty($raw)) return [];

        // ACF taxonomy field peut retourner un seul terme ou un tableau
        $items = is_array($raw) && !isset($raw['term_id']) ? $raw : [$raw];

        $out = [];
        foreach ($items as $item) {
            if ($item instanceof \WP_Term) {
                $out[] = $item;
                continue;
            }
            // ID entier
            if (is_int($item) || (is_string($item) && ctype_digit((string) $item))) {
                $t = get_term((int) $item);
                if ($t instanceof \WP_Term) $out[] = $t;
                continue;
            }
            // Tableau associatif (ACF "return format: array")
            if (is_array($item) && isset($item['term_id'])) {
                $t = get_term((int) $item['term_id']);
                if ($t instanceof \WP_Term) $out[] = $t;
            }
        }

        return $out;
    }
}
