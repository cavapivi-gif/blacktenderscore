<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Carte Google Maps (JS API).
 *
 * Utilise la Maps JavaScript API pour permettre l'application du style
 * BT configuré dans Réglages → Map Style.
 *
 * Le champ "Adresse ou coordonnées" accepte :
 *   • Texte libre       "Cannes, France"   → Geocoding API requise
 *   • Coordonnées       "43.551,7.017"     → aucune API supplémentaire
 *   • Dynamic tag ⚡    "BT: Champ Google Map (ACF)" → retourne lat,lng directement
 *
 * Prérequis :
 *   - Maps JavaScript API  (obligatoire)
 *   - Geocoding API        (uniquement pour les adresses textuelles)
 */
class GoogleMap extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-google-map',
            'title'    => 'BT — Carte Google Maps',
            'icon'     => 'eicon-google-maps',
            'keywords' => ['carte', 'map', 'google', 'localisation', 'acf', 'bt'],
            'js'       => ['bt-gmaps-init'],
        ];
    }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->section_map_content();
        $this->section_marker_content();
        $this->section_map_style();
    }

    // ─ Content → Carte ────────────────────────────────────────────────────────

    private function section_map_content(): void {
        $this->start_controls_section('section_map', [
            'label' => __('Carte', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        // ── Champ ACF Google Map — liste déroulante directe ───────────────────
        $this->add_control('acf_map_field', [
            'label'       => __('Champ ACF Google Map', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => ['' => __('— Saisir manuellement ci-dessous —', 'blacktenderscore')]
                           + $this->google_map_field_options(),
            'default'     => '',
            'description' => __('Sélectionnez un champ ACF de type Google Map. Prioritaire sur le champ texte ci-dessous.', 'blacktenderscore'),
        ]);

        // ── Séparateur visuel ─────────────────────────────────────────────────
        $this->add_control('divider_or', [
            'type'  => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // ── Adresse manuelle ou dynamic tag ───────────────────────────────────
        $this->add_control('address', [
            'label'       => __('Adresse ou coordonnées', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'label_block' => true,
            'dynamic'     => ['active' => true],
            'description' => __('Texte ("Cannes, France"), coordonnées ("43.551,7.017"), ou utilisez ⚡ → BT: Champ Google Map (ACF) pour lier un champ ACF directement.', 'blacktenderscore'),
        ]);

        // ── Communs ───────────────────────────────────────────────────────────
        $this->add_control('zoom', [
            'label'     => __('Zoom', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SLIDER,
            'default'   => ['size' => 14],
            'range'     => ['px' => ['min' => 1, 'max' => 20, 'step' => 1]],
            'separator' => 'before',
        ]);

        $this->add_control('map_type', [
            'label'   => __('Type de carte', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => 'roadmap',
            'options' => [
                'roadmap'   => __('Plan', 'blacktenderscore'),
                'satellite' => __('Satellite', 'blacktenderscore'),
                'terrain'   => __('Terrain', 'blacktenderscore'),
                'hybrid'    => __('Hybride', 'blacktenderscore'),
            ],
        ]);

        $this->add_control('ui_gestures', [
            'label'        => __('Zoom au scroll', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non (conseillé)', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();
    }

    // ─ Content → Marqueur ─────────────────────────────────────────────────────

    private function section_marker_content(): void {
        $this->start_controls_section('section_marker', [
            'label' => __('Marqueur', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_marker', [
            'label'        => __('Afficher un marqueur', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('marker_title', [
            'label'       => __('Titre (tooltip)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'dynamic'     => ['active' => true],
            'label_block' => true,
            'condition'   => ['show_marker' => 'yes'],
        ]);

        $this->add_control('marker_popup', [
            'label'       => __('Infobulle (popup)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXTAREA,
            'rows'        => 3,
            'dynamic'     => ['active' => true],
            'description' => __('HTML basique autorisé. Laissez vide pour désactiver.', 'blacktenderscore'),
            'condition'   => ['show_marker' => 'yes'],
        ]);

        $this->add_control('marker_color', [
            'label'     => __('Couleur du marqueur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#0066cc',
            'condition' => ['show_marker' => 'yes'],
        ]);

        $this->add_control('marker_open', [
            'label'        => __('Ouvrir l\'infobulle au chargement', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_marker' => 'yes', 'marker_popup!' => ''],
        ]);

        $this->end_controls_section();
    }

    // ─ Style ──────────────────────────────────────────────────────────────────

    private function section_map_style(): void {
        $this->start_controls_section('section_style_map', [
            'label' => __('Carte', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('height', [
            'label'      => __('Hauteur', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'default'    => ['size' => 420, 'unit' => 'px'],
            'size_units' => ['px', 'vh', 'em', 'rem'],
            'range'      => ['px' => ['min' => 80, 'max' => 1200], 'vh' => ['min' => 10, 'max' => 100]],
            'selectors'  => ['{{WRAPPER}} .bt-gmap__canvas' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('border_radius', [
            'label'      => __('Arrondi', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'default'    => ['size' => 8, 'unit' => 'px'],
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .bt-gmap' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden;'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'border',
            'selector' => '{{WRAPPER}} .bt-gmap',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'box_shadow',
            'selector' => '{{WRAPPER}} .bt-gmap',
        ]);

        $this->add_group_control(\Elementor\Group_Control_Css_Filter::get_type(), [
            'name'     => 'css_filter',
            'selector' => '{{WRAPPER}} .bt-gmap__canvas',
        ]);

        $this->end_controls_section();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    protected function render(): void {
        $s = $this->get_settings_for_display();

        // ── Résolution de la localisation — priorité : ACF select > adresse manuelle ──
        $lat = null;
        $lng = null;

        // Rétrocompat : ancienne version du widget utilisait source='acf_map' sans acf_map_field
        $acf_field = trim($s['acf_map_field'] ?? '');
        if ($acf_field === '' && ($s['source'] ?? '') === 'acf_map' && function_exists('acf_get_field_groups')) {
            // Auto-détection du premier champ google_map sur ce post
            foreach (acf_get_field_groups(['post_id' => get_the_ID()]) as $group) {
                foreach (acf_get_fields($group['key'] ?? '') ?: [] as $field) {
                    if (($field['type'] ?? '') === 'google_map') {
                        $acf_field = $field['name'];
                        break 2;
                    }
                }
            }
        }

        if ($acf_field !== '' && function_exists('get_field')) {
            $map_data = get_field($acf_field, get_the_ID());
            if (is_array($map_data) && isset($map_data['lat'], $map_data['lng'])
                && $map_data['lat'] !== '' && $map_data['lng'] !== '') {
                $lat = (float) $map_data['lat'];
                $lng = (float) $map_data['lng'];
            }
        }

        // Fallback : champ adresse (texte libre ou retour du dynamic tag "lat,lng")
        $address = '';
        if ($lat === null) {
            $address = trim($s['address'] ?? '');
            // Le dynamic tag Tag_Acf_Map retourne "lat,lng" — détection directe
            if (preg_match('/^(-?\d+\.?\d*)\s*,\s*(-?\d+\.?\d*)$/', $address, $m)) {
                $lat = (float) $m[1];
                $lng = (float) $m[2];
            }
        }

        if ($lat === null && $address === '') {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(
                    __('Sélectionnez un champ ACF ou saisissez une adresse.', 'blacktenderscore')
                );
            }
            return;
        }

        // ── Charge la Maps JS API (une seule fois par page) ───────────────────
        if (!wp_script_is('google-maps-api', 'enqueued')) {
            $api_key  = get_option('elementor_google_maps_api_key', '');
            $maps_url = 'https://maps.googleapis.com/maps/api/js?callback=btGmapsReady&loading=async';
            if ($api_key) $maps_url .= '&key=' . rawurlencode($api_key);
            wp_enqueue_script('google-maps-api', $maps_url, [], null, true);
        }

        $zoom        = (int) ($s['zoom']['size'] ?? 14);
        $map_type    = esc_attr($s['map_type'] ?? 'roadmap');
        $scroll_zoom = ($s['ui_gestures'] ?? '') === 'yes' ? 'yes' : 'no';
        $show_marker = ($s['show_marker'] ?? 'yes') === 'yes';

        // ── Attribut de localisation ──────────────────────────────────────────
        $data_loc = ($lat !== null)
            ? 'data-bt-latlng="' . esc_attr($lat . ',' . $lng) . '"'
            : 'data-bt-address="' . esc_attr($address) . '"';

        // ── Éditeur : iframe Embed pour la preview ────────────────────────────
        if ($this->is_edit_mode()) {
            $api_key = get_option('elementor_google_maps_api_key', '');
            $q       = $lat !== null ? "{$lat},{$lng}" : $address;
            $embed_url = $api_key
                ? 'https://www.google.com/maps/embed/v1/place?key=' . $api_key . '&q=' . rawurlencode($q) . '&zoom=' . $zoom
                : 'https://maps.google.com/maps?q=' . rawurlencode($q) . '&t=m&z=' . $zoom . '&output=embed&iwloc=near';
            ?>
            <div class="bt-gmap bt-gmap--editor">
                <iframe class="bt-gmap__canvas"
                    loading="lazy"
                    src="<?php echo esc_url($embed_url); ?>"
                    title="<?php echo esc_attr($q); ?>"
                ></iframe>
            </div>
            <?php
            return;
        }

        // ── Front-end ─────────────────────────────────────────────────────────
        $marker_attrs = '';
        if ($show_marker) {
            $allowed = ['strong' => [], 'em' => [], 'br' => [], 'a' => ['href' => [], 'target' => []]];
            $marker_attrs = ' data-marker="yes"'
                . ' data-marker-title="'  . esc_attr($s['marker_title'] ?? '') . '"'
                . ' data-marker-popup="'  . esc_attr(wp_kses($s['marker_popup'] ?? '', $allowed)) . '"'
                . ' data-marker-color="'  . esc_attr($s['marker_color'] ?? '#0066cc') . '"'
                . ' data-marker-open="'   . (($s['marker_open'] ?? '') === 'yes' ? 'yes' : '') . '"';
        }
        ?>
        <div class="bt-gmap">
            <div class="bt-gmap__canvas bt-gmaps-js"
                <?php echo $data_loc; ?>
                data-zoom="<?php echo $zoom; ?>"
                data-map-type="<?php echo $map_type; ?>"
                data-scroll-zoom="<?php echo $scroll_zoom; ?>"
                <?php echo $marker_attrs; ?>
            ></div>
        </div>
        <?php
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Retourne tous les champs ACF de type `google_map` pour le SELECT.
     * @return array<string, string>
     */
    private function google_map_field_options(): array {
        if (!function_exists('acf_get_field_groups')) return [];

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

        return $opts;
    }
}
