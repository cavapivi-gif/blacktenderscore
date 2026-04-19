<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * SchemaInjector — Injecte le JSON-LD Schema.org dans wp_head.
 *
 * Lit la configuration bt_schema_settings et génère le markup approprié
 * pour les pages singulières (post types) et archives (taxonomies).
 */
class SchemaInjector {

    /**
     * Initialise le hook wp_head.
     */
    public function init(): void {
        add_action('wp_head', [$this, 'inject_schema'], 5);
        $this->register_offer_filter();
    }

    /**
     * Enregistre le filtre pour enrichir le schema avec les offers.
     */
    private function register_offer_filter(): void {
        add_filter('bt_schema_data', [$this, 'enrich_with_offers'], 20, 3);
    }

    /**
     * Callback du filtre bt_schema_data — ajoute les offers au schema.
     *
     * @param array  $schema    Le schema JSON-LD.
     * @param string $context   'singular' ou 'taxonomy'.
     * @param int    $post_id   Post ID.
     * @return array Le schema enrichi.
     */
    public function enrich_with_offers(array $schema, string $context, int $post_id): array {
        // Uniquement pour les pages singulières
        if ($context !== 'singular') {
            return $schema;
        }

        if (!function_exists('get_field')) {
            return $schema;
        }

        $post_type = get_post_type($post_id);
        if (!$post_type) {
            return $schema;
        }

        $settings = get_option('bt_schema_settings', []);
        $configs  = $settings['post_types'] ?? [];

        // Chercher la config du post type
        $config = null;
        foreach ($configs as $cfg) {
            if (($cfg['post_type'] ?? '') === $post_type && !empty($cfg['enabled'])) {
                $config = $cfg;
                break;
            }
        }

        $offers = [];

        // Détecter le type de source
        if ($post_type === 'bateau') {
            // Mode bateau
            $offers = $this->build_offers_from_boat($post_id);
        } elseif ($config) {
            // Mode excursion (ou autre post type configuré)
            $offers = $this->build_offers_from_excursion($post_id, $config);
        }

        // Wrapper et ajouter au schema
        $wrapped = $this->wrap_offers($offers);
        if ($wrapped !== null) {
            $schema['offers'] = $wrapped;
        }

        return $schema;
    }

    /**
     * Point d'entrée principal — détecte le contexte et injecte le schema.
     */
    public function inject_schema(): void {
        $settings = get_option('bt_schema_settings', []);
        if (empty($settings)) return;

        $provider = $this->get_provider($settings);

        // Page singulière (single post type)
        if (is_singular()) {
            $this->inject_singular($settings, $provider);
            return;
        }

        // Archive taxonomie
        if (is_tax() || is_category() || is_tag()) {
            $this->inject_taxonomy($settings, $provider);
        }
    }

    /**
     * Injecte le schema pour une page singulière.
     */
    private function inject_singular(array $settings, array $provider): void {
        $post_type = get_post_type();
        if (!$post_type) return;

        $configs = $settings['post_types'] ?? [];
        $config  = null;

        foreach ($configs as $cfg) {
            if (($cfg['post_type'] ?? '') === $post_type && !empty($cfg['enabled'])) {
                $config = $cfg;
                break;
            }
        }

        if (!$config) return;

        $post_id     = get_the_ID();
        $schema_type = $config['schema_type'] ?? 'TouristTrip';

        // Récupérer les coordonnées GPS via ACF
        $gps_depart  = $this->get_acf_gps($post_id, $config['field_depart'] ?? '');
        $gps_arrivee = $this->get_acf_gps($post_id, $config['field_arrivee'] ?? '');

        // Au moins un point GPS requis
        if (!$gps_depart && !$gps_arrivee) return;

        // Titre : champ ACF custom ou titre du post en fallback
        $title = '';
        $field_title = $config['field_title'] ?? '';
        if ($field_title && function_exists('get_field')) {
            $title = get_field($field_title, $post_id);
            if (is_string($title)) {
                $title = wp_strip_all_tags($title);
            } else {
                $title = '';
            }
        }
        if (!$title) {
            $title = get_the_title();
        }

        // Description : champ ACF custom ou excerpt ou titre en fallback
        $description = '';
        $field_desc  = $config['field_description'] ?? '';
        if ($field_desc && function_exists('get_field')) {
            $description = get_field($field_desc, $post_id);
            if (is_string($description)) {
                $description = wp_strip_all_tags($description);
            } else {
                $description = '';
            }
        }
        if (!$description) {
            $description = get_the_excerpt() ?: $title;
        }

        // Aggregate rating depuis sj_avis (utilise sj_enriched_stats)
        $aggregate_rating = $this->get_aggregate_rating($post_id);

        $schema = $this->build_singular_schema(
            $schema_type,
            $title,
            $description,
            get_permalink(),
            get_the_post_thumbnail_url($post_id, 'large'),
            $gps_depart,
            $gps_arrivee,
            $provider,
            $aggregate_rating
        );

        $this->output_jsonld($schema, 'singular', $post_id);
    }

    /**
     * Injecte le schema pour une archive taxonomie.
     */
    private function inject_taxonomy(array $settings, array $provider): void {
        $term = get_queried_object();
        if (!$term instanceof \WP_Term) return;

        $configs = $settings['taxonomies'] ?? [];
        $config  = null;

        foreach ($configs as $cfg) {
            if (($cfg['taxonomy'] ?? '') === $term->taxonomy && !empty($cfg['enabled'])) {
                $config = $cfg;
                break;
            }
        }

        if (!$config) return;

        $schema_type = $config['schema_type'] ?? 'TouristDestination';

        // Récupérer les coordonnées GPS via ACF term meta
        $gps = $this->get_acf_term_gps($term, $config['field_gps'] ?? '');
        if (!$gps) return;

        $schema = $this->build_taxonomy_schema(
            $schema_type,
            $term->name,
            $term->description,
            get_term_link($term),
            $gps,
            $provider
        );

        $this->output_jsonld($schema, 'taxonomy', $term->term_id);
    }

    /**
     * Récupère les coordonnées GPS d'un champ ACF google_map.
     *
     * @param int    $post_id   ID du post
     * @param string $field_key Nom du champ ACF
     * @return array|null       ['lat' => float, 'lng' => float, 'address' => string] ou null
     */
    private function get_acf_gps(int $post_id, string $field_key): ?array {
        if (!$field_key || !function_exists('get_field')) return null;

        $value = get_field($field_key, $post_id);
        if (!is_array($value)) return null;

        $lat = $value['lat'] ?? null;
        $lng = $value['lng'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) return null;

        return [
            'lat'     => (float) $lat,
            'lng'     => (float) $lng,
            'address' => $value['address'] ?? '',
        ];
    }

    /**
     * Récupère les coordonnées GPS d'un champ ACF google_map sur un terme.
     */
    private function get_acf_term_gps(\WP_Term $term, string $field_key): ?array {
        if (!$field_key || !function_exists('get_field')) return null;

        // ACF term fields use taxonomy_term_id format
        $value = get_field($field_key, $term->taxonomy . '_' . $term->term_id);
        if (!is_array($value)) return null;

        $lat = $value['lat'] ?? null;
        $lng = $value['lng'] ?? null;

        if (!is_numeric($lat) || !is_numeric($lng)) return null;

        return [
            'lat'     => (float) $lat,
            'lng'     => (float) $lng,
            'address' => $value['address'] ?? '',
        ];
    }

    /**
     * Récupère l'aggregateRating depuis SJ Reviews (enriched stats).
     *
     * Utilise sj_enriched_stats() qui calcule max(CPT, platform) par source,
     * garantissant la cohérence avec les widgets SJ Reviews.
     *
     * @param int $post_id ID du post
     * @return array|null ['avg' => float, 'count' => int] ou null si pas d'avis
     */
    private function get_aggregate_rating(int $post_id): ?array {
        // Méthode 1 : Utiliser sj_enriched_stats() si disponible (SJ Reviews plugin)
        if (function_exists('sj_enriched_stats') && function_exists('sj_resolve_lieu')) {
            // Récupère le(s) lieu(x) associé(s) au post
            $lieu_id = get_post_meta($post_id, 'sj_lieu_id', true);

            // sj_lieu_id peut être un array (multi-select) ou une string
            if (is_array($lieu_id)) {
                $lieu_id = array_filter($lieu_id);
            }

            // Si pas de lieu explicite, tenter la résolution auto
            if (empty($lieu_id)) {
                $lieu_id = sj_resolve_lieu('auto');
            }

            if (empty($lieu_id)) {
                return null;
            }

            $stats = sj_enriched_stats($lieu_id);

            if (empty($stats['count']) || $stats['count'] <= 0) {
                return null;
            }

            return [
                'avg'   => (float) $stats['avg'],
                'count' => (int) $stats['count'],
            ];
        }

        // Fallback : comptage direct des CPT sj_avis (si SJ Reviews n'est pas actif)
        return $this->get_aggregate_rating_fallback($post_id);
    }

    /**
     * Fallback : compte les avis CPT directement (sans enrichissement plateforme).
     */
    private function get_aggregate_rating_fallback(int $post_id): ?array {
        global $wpdb;

        if (!post_type_exists('sj_avis')) {
            return null;
        }

        $direct_reviews = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, pm_rating.meta_value as rating
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm_link ON pm_link.post_id = p.ID
                 AND pm_link.meta_key = 'avis_linked_post' AND pm_link.meta_value = %s
             INNER JOIN {$wpdb->postmeta} pm_rating ON pm_rating.post_id = p.ID
                 AND pm_rating.meta_key = 'avis_rating'
             WHERE p.post_type = 'sj_avis' AND p.post_status = 'publish'",
            $post_id
        ));
        if (!is_array($direct_reviews)) {
            $direct_reviews = [];
        }

        $lieu_id = get_post_meta($post_id, 'sj_lieu_id', true);
        $lieu_reviews = [];

        if ($lieu_id && !is_array($lieu_id)) {
            $result = $wpdb->get_results($wpdb->prepare(
                "SELECT p.ID, pm_rating.meta_value as rating
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm_lieu ON pm_lieu.post_id = p.ID
                     AND pm_lieu.meta_key = 'avis_lieu_id' AND pm_lieu.meta_value = %s
                 INNER JOIN {$wpdb->postmeta} pm_rating ON pm_rating.post_id = p.ID
                     AND pm_rating.meta_key = 'avis_rating'
                 WHERE p.post_type = 'sj_avis' AND p.post_status = 'publish'",
                $lieu_id
            ));
            if (is_array($result)) {
                $lieu_reviews = $result;
            }
        }

        $ratings_by_id = [];
        foreach (array_merge($direct_reviews, $lieu_reviews) as $row) {
            $rating = (float) $row->rating;
            if ($rating >= 1 && $rating <= 5 && !isset($ratings_by_id[$row->ID])) {
                $ratings_by_id[$row->ID] = $rating;
            }
        }

        if (empty($ratings_by_id)) {
            return null;
        }

        $ratings = array_values($ratings_by_id);

        return [
            'avg'   => round(array_sum($ratings) / count($ratings), 1),
            'count' => count($ratings),
        ];
    }

    /**
     * Construit le schema JSON-LD pour une page singulière.
     */
    private function build_singular_schema(
        string $type,
        string $name,
        string $description,
        string $url,
        ?string $image,
        ?array $gps_depart,
        ?array $gps_arrivee,
        array $provider,
        ?array $aggregate_rating = null
    ): array {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => $type,
            'name'        => $name,
            'description' => $description ?: $name,
            'url'         => $url,
        ];

        if ($image) {
            $schema['image'] = $image;
        }

        // TouristTrip : itineraire avec départ et arrivée
        if ($type === 'TouristTrip') {
            if ($gps_depart) {
                $schema['itinerary'] = [
                    '@type'       => 'ItemList',
                    'itemListElement' => [],
                ];

                $schema['itinerary']['itemListElement'][] = [
                    '@type'    => 'ListItem',
                    'position' => 1,
                    'item'     => $this->build_place('Point de départ', $gps_depart),
                ];

                if ($gps_arrivee) {
                    $schema['itinerary']['itemListElement'][] = [
                        '@type'    => 'ListItem',
                        'position' => 2,
                        'item'     => $this->build_place('Point d\'arrivée', $gps_arrivee),
                    ];
                }
            }
        } else {
            // Autres types : location simple
            $gps = $gps_depart ?: $gps_arrivee;
            if ($gps) {
                $schema['geo'] = [
                    '@type'     => 'GeoCoordinates',
                    'latitude'  => $gps['lat'],
                    'longitude' => $gps['lng'],
                ];
                if ($gps['address']) {
                    $schema['address'] = $gps['address'];
                }
            }
        }

        // Aggregate rating (depuis sj_avis)
        if ($aggregate_rating && $aggregate_rating['count'] > 0) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $aggregate_rating['avg'],
                'bestRating'  => 5,
                'worstRating' => 1,
                'ratingCount' => $aggregate_rating['count'],
            ];
        }

        // Provider
        if (!empty($provider['name'])) {
            $schema['provider'] = [
                '@type' => 'Organization',
                'name'  => $provider['name'],
            ];
            if (!empty($provider['url'])) {
                $schema['provider']['url'] = $provider['url'];
            }
        }

        return $schema;
    }

    /**
     * Construit le schema JSON-LD pour une archive taxonomie.
     */
    private function build_taxonomy_schema(
        string $type,
        string $name,
        string $description,
        string $url,
        array $gps,
        array $provider
    ): array {
        $schema = [
            '@context'    => 'https://schema.org',
            '@type'       => $type,
            'name'        => $name,
            'description' => wp_strip_all_tags($description) ?: $name,
            'url'         => $url,
            'geo'         => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $gps['lat'],
                'longitude' => $gps['lng'],
            ],
        ];

        if ($gps['address']) {
            $schema['address'] = $gps['address'];
        }

        // Provider
        if (!empty($provider['name'])) {
            $schema['provider'] = [
                '@type' => 'Organization',
                'name'  => $provider['name'],
            ];
            if (!empty($provider['url'])) {
                $schema['provider']['url'] = $provider['url'];
            }
        }

        return $schema;
    }

    /**
     * Construit un objet Place avec GeoCoordinates.
     */
    private function build_place(string $name, array $gps): array {
        $place = [
            '@type' => 'Place',
            'name'  => $name,
            'geo'   => [
                '@type'     => 'GeoCoordinates',
                'latitude'  => $gps['lat'],
                'longitude' => $gps['lng'],
            ],
        ];

        if ($gps['address']) {
            $place['address'] = $gps['address'];
        }

        return $place;
    }

    /**
     * Retourne les infos provider (nom/URL) depuis les settings.
     */
    private function get_provider(array $settings): array {
        return [
            'name' => $settings['provider_name'] ?: get_bloginfo('name'),
            'url'  => $settings['provider_url'] ?: home_url(),
        ];
    }

    // ── Offers (prix/forfaits) ───────────────────────────────────────────────

    /**
     * Construit les offers depuis le repeater ACF excursion.
     *
     * Lit le repeater configuré (field_repeater) ou fallback 'exp_departure'.
     * Chaque row contient un prix et optionnellement un sous-repeater de départs.
     *
     * @param int   $post_id Post ID.
     * @param array $config  Configuration du post type.
     * @return array Liste d'offers [{@type, name, price, priceCurrency, availability}].
     */
    private function build_offers_from_excursion(int $post_id, array $config): array {
        $repeater_field = $config['field_repeater'] ?? 'exp_departure';
        if (!$repeater_field) {
            return [];
        }

        $rows = get_field($repeater_field, $post_id);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $offers = [];

        foreach ($rows as $row) {
            // Prix du forfait/départ
            $price = $row['exp_price'] ?? null;
            if (!is_numeric($price) || (float) $price <= 0) {
                continue;
            }

            // Nom : première heure de départ du sous-repeater, ou fallback
            $name = 'Départ';
            $sub_repeater = $row['exp_departure_time'] ?? [];
            if (is_array($sub_repeater) && !empty($sub_repeater)) {
                $first_time = $sub_repeater[0]['departure_time_child'] ?? null;
                if ($first_time && is_string($first_time)) {
                    $name = $first_time;
                }
            }

            $offers[] = [
                '@type'         => 'Offer',
                'name'          => $name,
                'price'         => (float) $price,
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
            ];
        }

        return $offers;
    }

    /**
     * Construit les offers depuis le repeater ACF bateau.
     *
     * Lit le repeater 'boat_price'. Chaque row contient :
     * - boat_price_boat (float) : le prix
     * - boat_location_duration (term ID) : la durée/formule (taxonomie)
     *
     * @param int $post_id Post ID.
     * @return array Liste d'offers.
     */
    private function build_offers_from_boat(int $post_id): array {
        $rows = get_field('boat_price', $post_id);
        if (!is_array($rows) || empty($rows)) {
            return [];
        }

        $offers = [];

        foreach ($rows as $row) {
            $price = $row['boat_price_boat'] ?? null;
            if (!is_numeric($price) || (float) $price <= 0) {
                continue;
            }

            // Nom depuis le term de la taxonomie duration
            $name    = 'Forfait';
            $term_id = $row['boat_location_duration'] ?? null;

            if ($term_id) {
                // ACF peut retourner un term ID (int/string) ou un WP_Term object
                if ($term_id instanceof \WP_Term) {
                    $name = $term_id->name;
                } elseif (is_numeric($term_id)) {
                    $term = get_term((int) $term_id);
                    if ($term instanceof \WP_Term) {
                        $name = $term->name;
                    }
                }
            }

            $offers[] = [
                '@type'         => 'Offer',
                'name'          => $name,
                'price'         => (float) $price,
                'priceCurrency' => 'EUR',
                'availability'  => 'https://schema.org/InStock',
            ];
        }

        return $offers;
    }

    /**
     * Wrap les offers : objet direct si 1 seul, AggregateOffer si plusieurs.
     *
     * @param array $offers Liste des offers.
     * @return array|null L'objet offer/AggregateOffer, ou null si vide.
     */
    private function wrap_offers(array $offers): ?array {
        if (empty($offers)) {
            return null;
        }

        // Un seul offer → retourner directement
        if (count($offers) === 1) {
            return $offers[0];
        }

        // Plusieurs offers → AggregateOffer
        $prices = array_column($offers, 'price');

        return [
            '@type'         => 'AggregateOffer',
            'lowPrice'      => min($prices),
            'highPrice'     => max($prices),
            'priceCurrency' => 'EUR',
            'offerCount'    => count($offers),
            'offers'        => $offers,
        ];
    }

    /**
     * Affiche le JSON-LD dans une balise script.
     *
     * Hook 'bt_schema_data' : permet de modifier le schema avant l'output.
     * @param array  $schema    Le schema JSON-LD.
     * @param string $context   'singular' ou 'taxonomy'.
     * @param int    $object_id Post ID ou Term ID.
     *
     * Usage (autre plugin):
     * add_filter('bt_schema_data', function($schema, $context, $id) {
     *     if ($context === 'singular' && isset($schema['aggregateRating'])) {
     *         // Enrichir avec d'autres sources...
     *     }
     *     return $schema;
     * }, 10, 3);
     */
    private function output_jsonld(array $schema, string $context = 'singular', int $object_id = 0): void {
        /**
         * Filtre le schema JSON-LD avant output.
         *
         * @param array  $schema    Le schema JSON-LD complet.
         * @param string $context   'singular' ou 'taxonomy'.
         * @param int    $object_id Post ID ou Term ID.
         */
        $schema = apply_filters('bt_schema_data', $schema, $context, $object_id);

        /**
         * Action avant l'output du schema (pour désactiver ou logguer).
         */
        do_action('bt_schema_before_output', $schema, $context, $object_id);

        $json = wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo "\n<!-- BlackTenders Schema.org -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo $json;
        echo "\n</script>\n";

        // Signal à SJ Reviews qu'un schema a déjà été injecté (évite doublon)
        $GLOBALS['sj_reviews_schema_rendered'] = true;

        /**
         * Action après l'output du schema.
         */
        do_action('bt_schema_after_output', $schema, $context, $object_id);
    }
}
