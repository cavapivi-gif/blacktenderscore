<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Schema.org TouristTrip.
 *
 * Widget invisible (aucun rendu HTML visible) qui injecte un JSON-LD
 * de type TouristTrip complet basé sur les champs ACF de l'excursion courante.
 *
 * Supporte min/max price (auto depuis tarification_par_forfait, ou override manuel),
 * les langues, la capacité, le point de départ, le prestataire.
 */
class ExcursionSchema extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-excursion-schema',
            'title'    => 'BT — Schema TouristTrip (SEO)',
            'icon'     => 'eicon-code',
            'keywords' => ['schema', 'seo', 'json-ld', 'touristtrip', 'structured data', 'bt'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Source données ────────────────────────────────────────────────
        $this->start_controls_section('section_schema', [
            'label' => __('Configuration Schema.org', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('schema_info', [
            'type'            => Controls_Manager::RAW_HTML,
            'raw'             => '<div style="padding:10px;background:#f0f6ff;border-radius:4px;font-size:12px;line-height:1.6">'
                . '<strong>Widget invisible</strong><br>'
                . 'Ce widget n\'affiche rien visuellement. Il injecte uniquement un JSON-LD <code>TouristTrip</code> dans la page pour les moteurs de recherche.<br><br>'
                . 'Placez-le n\'importe où sur la page excursion — il sera transparent pour les visiteurs.'
                . '</div>',
            'content_classes' => '',
        ]);

        $this->add_control('provider_name', [
            'label'       => __('Nom du prestataire', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Vide = nom du site WordPress automatiquement.', 'blacktenderscore'),
            'dynamic'     => ['active' => true],
        ]);

        $this->add_control('provider_url', [
            'label'       => __('URL du prestataire', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Vide = URL du site WordPress automatiquement.', 'blacktenderscore'),
        ]);

        $this->add_control('currency', [
            'label'   => __('Devise (ISO 4217)', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => 'EUR',
        ]);

        $this->add_control('audience_type', [
            'label'   => __('Type d\'audience', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Touristes, familles, groupes', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Prix ──────────────────────────────────────────────────────────
        $this->start_controls_section('section_price', [
            'label' => __('Prix', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('price_source', [
            'label'       => __('Source du prix', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => [
                'auto'   => __('Auto (depuis tarification_par_forfait)', 'blacktenderscore'),
                'manual' => __('Manuel (saisir les valeurs)', 'blacktenderscore'),
            ],
            'default'     => 'auto',
        ]);

        $this->add_control('manual_min_price', [
            'label'     => __('Prix minimum', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 0,
            'step'      => 1,
            'default'   => '',
            'condition' => ['price_source' => 'manual'],
            'dynamic'   => ['active' => true],
        ]);

        $this->add_control('manual_max_price', [
            'label'     => __('Prix maximum', 'blacktenderscore'),
            'type'      => Controls_Manager::NUMBER,
            'min'       => 0,
            'step'      => 1,
            'default'   => '',
            'condition' => ['price_source' => 'manual'],
            'dynamic'   => ['active' => true],
        ]);

        $this->add_control('price_note_in_schema', [
            'label'       => __('Description offre (optionnel)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Par personne', 'blacktenderscore'),
            'description' => __('Texte affiché dans le champ description de l\'Offer.', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Données additionnelles ────────────────────────────────────────
        $this->start_controls_section('section_extra', [
            'label' => __('Données additionnelles', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('include_languages', [
            'label'        => __('Inclure les langues parlées', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Lit le champ ACF exp_languages.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('include_capacity', [
            'label'        => __('Inclure capacité (pax min/max)', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('include_departure', [
            'label'        => __('Inclure le point de départ', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Lit exp_departure_point (city taxonomy).', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('include_image', [
            'label'        => __('Inclure l\'image', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Lit exp_cover ou la featured image.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('include_boats', [
            'label'        => __('Inclure les bateaux (isPartOf / vehicle)', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Liste les bateaux liés via exp_boats dans subjectOf.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        // En mode éditeur, afficher un placeholder visible
        if ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder" style="text-align:left">'
                . '<strong>🔍 Schema.org TouristTrip</strong><br>'
                . '<small>Widget invisible — injecte un JSON-LD structuré en front-end pour les moteurs de recherche.</small>'
                . '</div>';
        }

        if (is_admin()) return;

        if (!$this->acf_required()) return;

        $schema = $this->build_schema($s, $post_id);
        if (empty($schema)) return;

        echo '<script type="application/ld+json">'
            . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            . '</script>';
    }

    // ── Builder ───────────────────────────────────────────────────────────────

    private function build_schema(array $s, int $post_id): array {
        $post     = get_post($post_id);
        $url      = get_permalink($post_id);
        $title    = get_the_title($post_id);
        $currency = strtoupper(trim($s['currency'] ?: 'EUR'));

        // Description : accroche ou extrait du wysiwyg
        $tagline = (string) get_field('exp_tagline', $post_id);
        $desc    = $tagline ?: wp_strip_all_tags((string) get_field('exp_description', $post_id));
        $desc    = wp_trim_words($desc, 60, '…');

        // Prestataire
        $provider_name = !empty($s['provider_name']) ? $s['provider_name'] : get_bloginfo('name');
        $provider_url  = !empty($s['provider_url'])  ? $s['provider_url']  : home_url();

        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => 'TouristTrip',
            'name'        => $title,
            'url'         => $url,
            'description' => $desc,
            'provider'    => [
                '@type' => 'TravelAgency',
                'name'  => $provider_name,
                'url'   => $provider_url,
            ],
            'touristType' => [
                '@type'        => 'Audience',
                'audienceType' => $s['audience_type'] ?: 'Touristes',
            ],
        ];

        // Image
        if ($s['include_image'] === 'yes') {
            $cover = get_field('exp_cover', $post_id);
            if (is_array($cover) && !empty($cover['url'])) {
                $schema['image'] = $cover['url'];
            } elseif ($thumb = get_post_thumbnail_id($post_id)) {
                $src = wp_get_attachment_image_src($thumb, 'large');
                if ($src) $schema['image'] = $src[0];
            }
        }

        // Capacité
        if ($s['include_capacity'] === 'yes') {
            $pax_min = get_field('exp_pax_min', $post_id);
            $pax_max = get_field('exp_pax_max', $post_id);
            if ($pax_max) $schema['maximumAttendeeCapacity'] = (int) $pax_max;
            if ($pax_min) $schema['minimumAttendeeCapacity'] = (int) $pax_min;
        }

        // Langues
        if ($s['include_languages'] === 'yes') {
            $langs = get_field('exp_languages', $post_id);
            if (!empty($langs)) {
                $lang_map = ['fr' => 'Français', 'en' => 'English', 'it' => 'Italiano'];
                $avail    = [];
                foreach ((array) $langs as $code) {
                    $avail[] = [
                        '@type' => 'Language',
                        'name'  => $lang_map[$code] ?? $code,
                    ];
                }
                if ($avail) $schema['availableLanguage'] = $avail;
            }
        }

        // Point de départ
        if ($s['include_departure'] === 'yes') {
            $dep_terms  = get_field('exp_departure_point', $post_id);
            $dep_coords = (string) get_field('exp_departure_coords', $post_id);

            if ($dep_terms) {
                $dep_ids = is_array($dep_terms) ? $dep_terms : [$dep_terms];
                $places  = [];
                foreach ($dep_ids as $tid) {
                    $term = is_numeric($tid) ? get_term((int) $tid, 'city') : ($tid instanceof \WP_Term ? $tid : null);
                    if (!$term || is_wp_error($term)) continue;

                    $place = ['@type' => 'Place', 'name' => $term->name];

                    // GPS coords → GeoCoordinates
                    if ($dep_coords) {
                        $parts = array_map('trim', explode(',', $dep_coords));
                        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                            $place['geo'] = [
                                '@type'     => 'GeoCoordinates',
                                'latitude'  => (float) $parts[0],
                                'longitude' => (float) $parts[1],
                            ];
                        }
                    }
                    $places[] = $place;
                }

                if (!empty($places)) {
                    $schema['itinerary'] = count($places) === 1 ? $places[0] : $places;
                    $schema['departurePoint'] = count($places) === 1 ? $places[0] : $places[0];
                }
            }
        }

        // Offres / Prix
        $offers = $this->build_offers($s, $post_id, $currency);
        if (!empty($offers)) {
            $schema['offers'] = count($offers) === 1 ? $offers[0] : $offers;
        }

        // Bateaux liés
        if ($s['include_boats'] === 'yes') {
            $boats = get_field('exp_boats', $post_id);
            if (!empty($boats)) {
                $boat_schemas = [];
                foreach ((array) $boats as $boat) {
                    if (!($boat instanceof \WP_Post)) $boat = get_post($boat);
                    if (!$boat) continue;
                    $pax  = get_field('boat_pax_max', $boat->ID);
                    $item = [
                        '@type' => 'Vehicle',
                        'name'  => $boat->post_title,
                        'url'   => get_permalink($boat->ID),
                    ];
                    if ($pax) $item['vehicleSeatingCapacity'] = (int) $pax;
                    $boat_schemas[] = $item;
                }
                if ($boat_schemas) {
                    $schema['subjectOf'] = $boat_schemas;
                }
            }
        }

        return $schema;
    }

    private function build_offers(array $s, int $post_id, string $currency): array {
        $base_offer = [
            '@type'              => 'Offer',
            'priceCurrency'      => $currency,
            'availability'       => 'https://schema.org/InStock',
            'priceValidUntil'    => date('Y-12-31'),
            'url'                => get_permalink($post_id),
        ];

        $note = $s['price_note_in_schema'] ?? '';
        if ($note) $base_offer['description'] = $note;

        if ($s['price_source'] === 'manual') {
            $min = $s['manual_min_price'] ?? '';
            $max = $s['manual_max_price'] ?? '';

            if ($min === '' && $max === '') return [];

            if ($min !== '' && $max !== '' && $min !== $max) {
                // PriceSpecification avec min/max
                return [array_merge($base_offer, [
                    'priceSpecification' => [
                        '@type'    => 'PriceSpecification',
                        'minPrice' => (float) $min,
                        'maxPrice' => (float) $max,
                        'priceCurrency' => $currency,
                    ],
                ])];
            }

            $price = $min !== '' ? $min : $max;
            return [array_merge($base_offer, ['price' => (float) $price])];
        }

        // Auto : lire tarification_par_forfait
        $rows = get_field('tarification_par_forfait', $post_id);
        if (empty($rows)) return [];

        $prices = [];
        foreach ($rows as $row) {
            $p = (float) ($row['exp_price'] ?? 0);
            if ($p > 0) $prices[] = $p;
        }

        if (empty($prices)) return [];

        $min = min($prices);
        $max = max($prices);

        if (count($prices) === 1 || $min === $max) {
            return [array_merge($base_offer, ['price' => $min])];
        }

        // Plusieurs forfaits → un Offer avec PriceSpecification min/max
        return [array_merge($base_offer, [
            'priceSpecification' => [
                '@type'         => 'PriceSpecification',
                'minPrice'      => $min,
                'maxPrice'      => $max,
                'priceCurrency' => $currency,
            ],
        ])];
    }
}
