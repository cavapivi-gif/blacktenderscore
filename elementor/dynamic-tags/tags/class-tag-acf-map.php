<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Champ ACF Google Map.
 *
 * Liste tous les champs ACF de type `google_map` et retourne
 * les coordonnées au format "lat,lng" — directement utilisable
 * dans le champ "Adresse" du widget BT — Carte Google Maps
 * (aucun appel à la Geocoding API requis).
 *
 * Utilisation :
 *   Widget BT — Carte Google Maps → champ "Adresse" → icône dynamique ⚡
 *   → BlackTenders → "BT: Champ Google Map (ACF)"
 *   → choisir le champ dans la liste
 */
class Tag_Acf_Map extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-acf-map'; }
    public function get_title():      string { return 'BT: Champ Google Map (ACF)'; }
    public function get_categories(): array  { return ['text']; }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $opts = $this->google_map_field_options();

        $this->add_control('field', [
            'label'   => __('Champ ACF Google Map', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $opts,
            'default' => array_key_first($opts) ?? '',
        ]);

        $this->add_control('format', [
            'label'   => __('Format de sortie', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'geo'     => __('Coordonnées (lat,lng)', 'blacktenderscore'),
                'address' => __('Adresse', 'blacktenderscore'),
            ],
            'default' => 'geo',
        ]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    /**
     * Retourne les données du champ ACF Google Map selon le format choisi :
     * - "geo"     → "lat,lng" (coordonnées directes, pas de Geocoding API)
     * - "address" → l'adresse saisie dans le champ ACF
     */
    public function render(): void {
        $field_name = trim((string) ($this->get_settings('field') ?? ''));
        if ($field_name === '' || !function_exists('get_field')) return;

        $map = get_field($field_name, get_the_ID());
        if (!is_array($map)) return;

        $format = $this->get_settings('format') ?: 'geo';

        if ($format === 'address') {
            $address = trim((string) ($map['address'] ?? ''));
            if ($address !== '') {
                echo esc_html($address);
            }
            return;
        }

        /* geo (défaut) */
        if (!isset($map['lat'], $map['lng']) || $map['lat'] === '' || $map['lng'] === '') return;
        echo esc_html($map['lat'] . ',' . $map['lng']);
    }

    // ── Helper ────────────────────────────────────────────────────────────────

    /**
     * Retourne les champs ACF de type google_map pour le SELECT.
     * @return array<string, string>
     */
    private function google_map_field_options(): array {
        if (!function_exists('acf_get_field_groups')) {
            return ['' => __('ACF Pro requis', 'blacktenderscore')];
        }

        $opts = [];
        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group['key'] ?? '');
            if (!is_array($fields)) continue;
            foreach ($fields as $field) {
                if (($field['type'] ?? '') !== 'google_map') continue;
                $opts[$field['name']] = $group['title'] . '  →  ' . $field['label']
                                      . '  (' . $field['name'] . ')';
            }
        }

        return $opts ?: ['' => __('Aucun champ Google Map trouvé', 'blacktenderscore')];
    }
}
