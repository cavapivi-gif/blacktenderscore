<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Carte Google Maps (JS API).
 *
 * Modes :
 *   single           — une carte centrée sur un champ ACF ou une adresse
 *   all_destinations — carte multi-pins de toutes les excursions publiées
 *
 * Prérequis :
 *   - Maps JavaScript API  (obligatoire)
 *   - Geocoding API        (uniquement pour les adresses textuelles en mode single)
 */
class GoogleMap extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-google-map',
            'title'    => 'BT — Carte Google Maps',
            'icon'     => 'eicon-google-maps',
            'keywords' => ['carte', 'map', 'google', 'localisation', 'acf', 'destinations', 'bt'],
            'css'      => ['bt-google-map'],
            'js'       => ['bt-gmaps-init'],
        ];
    }

    // ── Controls ──────────────────────────────────────────────────────────────

    protected function register_controls(): void {
        $this->section_map_content();
        $this->section_destinations_content();
        $this->section_marker_content();
        $this->section_map_style();
    }

    // ─ Content → Carte ────────────────────────────────────────────────────────

    private function section_map_content(): void {
        $this->start_controls_section('section_map', [
            'label' => __('Carte', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        // ── Mode de la carte ──────────────────────────────────────────────────
        $this->add_control('map_source', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'single'           => __('Carte unique', 'blacktenderscore'),
                'all_destinations' => __('Toutes les destinations', 'blacktenderscore'),
            ],
            'default'     => 'single',
            'description' => __('Mode "Toutes les destinations" : pins interactifs sur toutes les excursions publiées.', 'blacktenderscore'),
        ]);

        // ── Champ ACF Google Map — liste déroulante (mode single) ─────────────
        $this->add_control('acf_map_field', [
            'label'       => __('Champ ACF Google Map', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => ['' => __('— Saisir manuellement ci-dessous —', 'blacktenderscore')]
                           + $this->google_map_field_options(),
            'default'     => '',
            'description' => __('Prioritaire sur le champ texte ci-dessous.', 'blacktenderscore'),
            'condition'   => ['map_source' => 'single'],
        ]);

        $this->add_control('divider_or', [
            'type'      => Controls_Manager::DIVIDER,
            'condition' => ['map_source' => 'single'],
        ]);

        $this->add_control('address', [
            'label'       => __('Adresse ou coordonnées', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'label_block' => true,
            'dynamic'     => ['active' => true],
            'description' => __('Texte ("Cannes, France"), coordonnées ("43.551,7.017"), ou ⚡ → BT: Champ Google Map.', 'blacktenderscore'),
            'condition'   => ['map_source' => 'single'],
        ]);

        // ── Communs ───────────────────────────────────────────────────────────
        $this->add_control('zoom', [
            'label'     => __('Zoom', 'blacktenderscore'),
            'type'      => Controls_Manager::SLIDER,
            'default'   => ['size' => 14],
            'range'     => ['px' => ['min' => 1, 'max' => 20, 'step' => 1]],
            'separator' => 'before',
        ]);

        $this->add_control('map_type', [
            'label'   => __('Type de carte', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
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
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non (conseillé)', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();
    }

    // ─ Content → Destinations ─────────────────────────────────────────────────

    private function section_destinations_content(): void {
        $this->start_controls_section('section_destinations', [
            'label'     => __('Destinations', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['map_source' => 'all_destinations'],
        ]);

        $this->add_control('dest_pin_color', [
            'label'   => __('Couleur des pins', 'blacktenderscore'),
            'type'    => Controls_Manager::COLOR,
            'default' => '#0a0a0a',
        ]);

        $this->add_control('dest_pin_active_color', [
            'label'   => __('Couleur pin actif', 'blacktenderscore'),
            'type'    => Controls_Manager::COLOR,
            'default' => '#1a73e8',
        ]);

        $this->add_control('dest_fit_bounds', [
            'label'        => __('Ajuster la vue aux pins', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
            'description'  => __('La carte s\'ajuste automatiquement pour afficher tous les marqueurs.', 'blacktenderscore'),
        ]);

        $this->add_control('dest_show_image', [
            'label'        => __('Image dans l\'infobulle', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('dest_cta_label', [
            'label'   => __('Label bouton CTA', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Découvrir →', 'blacktenderscore'),
        ]);

        $this->add_control('dest_coords_field', [
            'label'       => __('Champ ACF coordonnées (slug)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exp_departure_coords',
            'separator'   => 'before',
            'description' => __('Champ ACF de type "Google Map" sur le post excursion.', 'blacktenderscore'),
        ]);

        $this->add_control('dest_tagline_field', [
            'label'       => __('Champ ACF accroche (slug)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exp_tagline',
            'description' => __('Champ ACF texte court affiché sous le titre dans l\'infobulle.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();
    }

    // ─ Content → Marqueur (mode single uniquement) ────────────────────────────

    private function section_marker_content(): void {
        $this->start_controls_section('section_marker', [
            'label'     => __('Marqueur', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['map_source' => 'single'],
        ]);

        $this->add_control('show_marker', [
            'label'        => __('Afficher un marqueur', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'label_on'     => __('Oui', 'blacktenderscore'),
            'label_off'    => __('Non', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('marker_title', [
            'label'       => __('Titre (tooltip)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'dynamic'     => ['active' => true],
            'label_block' => true,
            'condition'   => ['show_marker' => 'yes'],
        ]);

        $this->add_control('marker_popup', [
            'label'       => __('Infobulle (popup)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXTAREA,
            'rows'        => 3,
            'dynamic'     => ['active' => true],
            'description' => __('HTML basique autorisé. Laissez vide pour désactiver.', 'blacktenderscore'),
            'condition'   => ['show_marker' => 'yes'],
        ]);

        $this->add_control('marker_color', [
            'label'     => __('Couleur du marqueur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#0066cc',
            'condition' => ['show_marker' => 'yes'],
        ]);

        $this->add_control('marker_open', [
            'label'        => __('Ouvrir l\'infobulle au chargement', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
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
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('height', [
            'label'      => __('Hauteur', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'default'    => ['size' => 420, 'unit' => 'px'],
            'size_units' => ['px', 'vh', 'em', 'rem'],
            'range'      => ['px' => ['min' => 80, 'max' => 1200], 'vh' => ['min' => 10, 'max' => 100]],
            'selectors'  => ['{{WRAPPER}} .bt-gmap__canvas' => 'height: {{SIZE}}{{UNIT}};'],
        ]);

        $this->add_responsive_control('border_radius', [
            'label'      => __('Arrondi', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'default'    => ['size' => 8, 'unit' => 'px'],
            'size_units' => ['px', '%'],
            'range'      => ['px' => ['min' => 0, 'max' => 60]],
            'selectors'  => ['{{WRAPPER}} .bt-gmap' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden;'],
        ]);

        $this->add_group_control(Group_Control_Border::get_type(), [
            'name'     => 'border',
            'selector' => '{{WRAPPER}} .bt-gmap',
        ]);

        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'box_shadow',
            'selector' => '{{WRAPPER}} .bt-gmap',
        ]);

        $this->add_group_control(Group_Control_Css_Filter::get_type(), [
            'name'     => 'css_filter',
            'selector' => '{{WRAPPER}} .bt-gmap__canvas',
        ]);

        $this->end_controls_section();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    protected function render(): void {
        $s          = $this->get_settings_for_display();
        $map_source = $s['map_source'] ?? 'single';

        if ($map_source === 'all_destinations') {
            $this->render_destinations_map($s);
            return;
        }

        $this->render_single_map($s);
    }

    // ── Mode : carte unique ────────────────────────────────────────────────────

    private function render_single_map(array $s): void {
        $lat = null;
        $lng = null;

        // Rétrocompat : ancienne version du widget utilisait source='acf_map' sans acf_map_field
        $acf_field = trim($s['acf_map_field'] ?? '');
        if ($acf_field === '' && ($s['source'] ?? '') === 'acf_map' && function_exists('acf_get_field_groups')) {
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

        $address = '';
        if ($lat === null) {
            $address = trim($s['address'] ?? '');
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

        $this->enqueue_maps_api();

        $zoom        = (int) ($s['zoom']['size'] ?? 14);
        $map_type    = esc_attr($s['map_type'] ?? 'roadmap');
        $scroll_zoom = ($s['ui_gestures'] ?? '') === 'yes' ? 'yes' : 'no';
        $show_marker = ($s['show_marker'] ?? 'yes') === 'yes';

        $data_loc = ($lat !== null)
            ? 'data-bt-latlng="' . esc_attr($lat . ',' . $lng) . '"'
            : 'data-bt-address="' . esc_attr($address) . '"';

        // Éditeur : iframe Embed
        if ($this->is_edit_mode()) {
            $api_key   = get_option('elementor_google_maps_api_key', '');
            $q         = $lat !== null ? "{$lat},{$lng}" : $address;
            $embed_url = $api_key
                ? 'https://www.google.com/maps/embed/v1/place?key=' . $api_key . '&q=' . rawurlencode($q) . '&zoom=' . $zoom
                : 'https://maps.google.com/maps?q=' . rawurlencode($q) . '&t=m&z=' . $zoom . '&output=embed&iwloc=near';
            ?>
            <div class="bt-gmap bt-gmap--editor">
                <iframe class="bt-gmap__canvas" loading="lazy"
                    src="<?php echo esc_url($embed_url); ?>"
                    title="<?php echo esc_attr($q); ?>"></iframe>
            </div>
            <?php
            return;
        }

        $marker_attrs = '';
        if ($show_marker) {
            $allowed = ['strong' => [], 'em' => [], 'br' => [], 'a' => ['href' => ['http', 'https', 'mailto'], 'target' => []]];
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
                data-zoom="<?php echo (int) $zoom; ?>"
                data-map-type="<?php echo esc_attr($map_type); ?>"
                data-scroll-zoom="<?php echo esc_attr($scroll_zoom); ?>"
                <?php echo $marker_attrs; ?>
            ></div>
        </div>
        <?php
    }

    // ── Mode : toutes les destinations ────────────────────────────────────────

    /**
     * Charge toutes les excursions publiées, extrait les coordonnées ACF,
     * l'accroche et l'image et les passe au canvas en JSON pour le JS.
     */
    private function render_destinations_map(array $s): void {
        $coords_field  = trim($s['dest_coords_field']  ?? '') ?: 'exp_departure_coords';
        $tagline_field = trim($s['dest_tagline_field'] ?? '') ?: 'exp_tagline';
        $pin_color     = $s['dest_pin_color']       ?? '#0a0a0a';
        $pin_active    = $s['dest_pin_active_color'] ?? '#1a73e8';
        $fit_bounds    = ($s['dest_fit_bounds']  ?? 'yes') === 'yes';
        $show_image    = ($s['dest_show_image']  ?? 'yes') === 'yes';
        $cta_label     = esc_html($s['dest_cta_label'] ?: __('Découvrir →', 'blacktenderscore'));

        // Requête toutes excursions (cache 6h)
        $cache_key = 'bt_dest_map_posts';
        $exc_posts = get_transient($cache_key);
        if ($exc_posts === false) {
            $exc_posts = get_posts([
                'post_type'      => 'excursion',
                'posts_per_page' => 200,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);
            set_transient($cache_key, $exc_posts, 6 * HOUR_IN_SECONDS);
        }

        // Construire le tableau de pins
        $pins = [];
        foreach ($exc_posts as $exc) {
            $coords = function_exists('get_field') ? get_field($coords_field, $exc->ID) : null;
            if (empty($coords['lat']) || empty($coords['lng'])) continue;

            $tagline = function_exists('get_field') ? (string) get_field($tagline_field, $exc->ID) : '';
            $thumb   = $show_image ? (get_the_post_thumbnail_url($exc->ID, 'medium') ?: '') : '';

            $pins[] = [
                'lat'     => round((float) $coords['lat'], 6),
                'lng'     => round((float) $coords['lng'], 6),
                'title'   => get_the_title($exc->ID),
                'tagline' => wp_strip_all_tags($tagline),
                'image'   => $thumb,
                'url'     => get_permalink($exc->ID),
            ];
        }

        if (empty($pins)) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(
                    __('Aucune excursion avec coordonnées trouvée. Vérifiez le champ ACF "' . esc_html($coords_field) . '".', 'blacktenderscore')
                );
            }
            return;
        }

        // Éditeur : iframe centrée sur le centroïde + notice
        if ($this->is_edit_mode()) {
            $center_lat = array_sum(array_column($pins, 'lat')) / count($pins);
            $center_lng = array_sum(array_column($pins, 'lng')) / count($pins);
            $zoom_prev  = (int) ($s['zoom']['size'] ?? 10);
            $api_key    = get_option('elementor_google_maps_api_key', '');
            $q          = "{$center_lat},{$center_lng}";
            $embed_url  = $api_key
                ? 'https://www.google.com/maps/embed/v1/place?key=' . $api_key . '&q=' . rawurlencode($q) . '&zoom=' . $zoom_prev
                : 'https://maps.google.com/maps?q=' . rawurlencode($q) . '&t=m&z=' . $zoom_prev . '&output=embed&iwloc=near';
            $count = count($pins);
            ?>
            <div class="bt-gmap bt-gmap--editor" style="position:relative">
                <iframe class="bt-gmap__canvas" loading="lazy"
                    src="<?php echo esc_url($embed_url); ?>"
                    title="Destinations"></iframe>
                <div style="position:absolute;top:10px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.65);color:#fff;font-size:12px;padding:5px 12px;border-radius:20px;pointer-events:none;white-space:nowrap;">
                    <?php printf(_n('%d destination sera affichée', '%d destinations seront affichées', $count, 'blacktenderscore'), $count); ?>
                </div>
            </div>
            <?php
            return;
        }

        // Front-end : canvas avec JSON destinations
        $this->enqueue_maps_api();

        $zoom        = (int) ($s['zoom']['size'] ?? 10);
        $map_type    = esc_attr($s['map_type'] ?? 'roadmap');
        $scroll_zoom = ($s['ui_gestures'] ?? '') === 'yes' ? 'yes' : 'no';

        $opts = [
            'pinColor'   => $pin_color,
            'pinActive'  => $pin_active,
            'fitBounds'  => $fit_bounds,
            'ctaLabel'   => $cta_label,
        ];
        ?>
        <div class="bt-gmap bt-gmap--destinations">
            <div class="bt-gmap__canvas bt-gmaps-js"
                data-bt-destinations="<?php echo esc_attr(wp_json_encode($pins)); ?>"
                data-bt-dest-opts="<?php echo esc_attr(wp_json_encode($opts)); ?>"
                data-zoom="<?php echo (int) $zoom; ?>"
                data-map-type="<?php echo esc_attr($map_type); ?>"
                data-scroll-zoom="<?php echo esc_attr($scroll_zoom); ?>"
            ></div>
        </div>
        <?php
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /** Enqueue la Maps JavaScript API une seule fois par page. */
    /**
     * Transmet l'URL de l'API Google Maps à bt-gmaps-init.js via wp_localize_script.
     * Le chargement effectif est délégué à l'IntersectionObserver côté JS (lazy).
     */
    private function enqueue_maps_api(): void {
        static $done = false;
        if ($done) return;
        $done     = true;
        $api_key  = get_option('elementor_google_maps_api_key', '');
        $maps_url = 'https://maps.googleapis.com/maps/api/js?callback=btGmapsReady&loading=async';
        if ($api_key) $maps_url .= '&key=' . rawurlencode($api_key);
        // Pas de wp_enqueue_script — le JS charge l'API dynamiquement au scroll
        wp_localize_script('bt-gmaps-init', 'BT_GMaps', ['apiUrl' => $maps_url]);
    }

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
