<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Taxonomie générique.
 *
 * Résolution en deux temps :
 *  1. Taxonomie WP native  → get_the_terms( $post_id, $key )
 *  2. Champ ACF en fallback → get_field( $key, $post_id )
 *     Gère tous les formats de retour ACF :
 *       - WP_Term object
 *       - int / string-digit  (ID)
 *       - string              (slug → get_term_by)
 *       - array assoc         (['term_id' => …])
 *       - tableau de l'un des types ci-dessus (champ multiple)
 *
 * Utilisations typiques :
 *  - boat_skipper  (taxonomie WP sur le CPT bateau)
 *  - exp_skipper   (champ ACF sur le CPT excursion, lié à la taxo exp_skipper)
 */
class Tag_Taxonomy extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-taxonomy'; }
    public function get_title():      string { return 'BT: Taxonomie'; }
    public function get_categories(): array  { return ['text']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        $this->add_control('taxonomy', [
            'label'   => __('Taxonomie', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $this->taxonomy_options(),
            'default' => 'boat_skipper',
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
        $key       = (string) $this->get_settings('taxonomy');
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

        // 1. Taxonomie WP native (termes assignés via l'interface standard)
        $terms = get_the_terms($post_id, $key);
        if (is_array($terms) && !empty($terms)) {
            return $terms;
        }

        // 2. Champ ACF (taxonomy field — stocké en postmeta, pas en term_relationships)
        if (!function_exists('get_field')) return [];

        $raw = get_field($key, $post_id);
        if (empty($raw)) return [];

        // Normalise : scalaire → tableau d'un élément ; tableau assoc (un seul terme) → tableau
        if (!is_array($raw) || isset($raw['term_id'])) {
            $raw = [$raw];
        }

        $out = [];
        foreach ($raw as $item) {
            $term = $this->coerce_term($item, $key);
            if ($term instanceof \WP_Term) {
                $out[] = $term;
            }
        }

        return $out;
    }

    /**
     * Convertit n'importe quel format de retour ACF en WP_Term.
     *
     * @param mixed  $item  WP_Term | int | string (slug) | array assoc
     * @param string $tax   slug de taxonomie, utilisé pour get_term_by('slug')
     */
    private function coerce_term(mixed $item, string $tax): ?\WP_Term {

        if ($item instanceof \WP_Term) {
            return $item;
        }

        // ID entier ou chaîne numérique
        if (is_int($item) || (is_string($item) && ctype_digit((string) $item))) {
            $t = get_term((int) $item);
            return $t instanceof \WP_Term ? $t : null;
        }

        // Slug (ACF "Return format: Slug")
        if (is_string($item) && $item !== '') {
            $t = get_term_by('slug', $item, $tax);
            return $t instanceof \WP_Term ? $t : null;
        }

        // Tableau associatif (ACF "Return format: Array")
        if (is_array($item) && isset($item['term_id'])) {
            $t = get_term((int) $item['term_id']);
            return $t instanceof \WP_Term ? $t : null;
        }

        return null;
    }

    // ── Options de la liste déroulante ────────────────────────────────────────

    /**
     * Retourne toutes les taxonomies enregistrées formatées pour un SELECT.
     * Appelé une seule fois à l'enregistrement du tag.
     *
     * @return array<string, string>
     */
    private function taxonomy_options(): array {
        $all  = get_taxonomies([], 'objects');
        $opts = [];

        foreach ($all as $tax) {
            $label        = $tax->label ?: $tax->name;
            $opts[$tax->name] = $label . '  (' . $tax->name . ')';
        }

        asort($opts);
        return $opts;
    }
}
