<?php
namespace BT_Regiondo\Elementor\LoopQueries;

defined('ABSPATH') || exit;

/**
 * Loop Builder Query Sources — BT Regiondo.
 *
 * Fournit 4 sources de requêtes personnalisées pour le Loop Builder Elementor Pro :
 *
 *  1. bt-excursions-by-boat   — Excursions liées au bateau courant (reverse exp_boats).
 *  2. bt-boats-by-excursion   — Bateaux liés à l'excursion courante (exp_boats direct).
 *  3. bt-excursions-by-city   — Excursions dont le départ correspond à la ville courante.
 *  4. bt-similar-excursions   — Excursions partageant des termes de taxonomie communs.
 *
 * Utilisation dans Elementor : Loop Grid → Query → Source → choisir la source BT.
 */
class Loop_Queries {

    public function init(): void {
        add_action('elementor/query/bt-excursions-by-boat',    [$this, 'query_excursions_by_boat']);
        add_action('elementor/query/bt-boats-by-excursion',    [$this, 'query_boats_by_excursion']);
        add_action('elementor/query/bt-excursions-by-city',    [$this, 'query_excursions_by_city']);
        add_action('elementor/query/bt-similar-excursions',    [$this, 'query_similar_excursions']);
    }

    // ── 1. Excursions liées au bateau courant ─────────────────────────────────

    /**
     * Source : bt-excursions-by-boat
     * Post type courant : boat
     * Retourne les excursions dont le champ exp_boats contient ce bateau.
     */
    public function query_excursions_by_boat(\WP_Query $query): void {
        $post_id = get_the_ID();
        if (!$post_id) return;

        $cache_key = 'bt_exc_by_boat_' . $post_id;
        $ids       = get_transient($cache_key);

        if ($ids === false) {
            $q = new \WP_Query([
                'post_type'      => 'excursion',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'meta_query'     => [[
                    'key'     => 'exp_boats',
                    'value'   => '"' . $post_id . '"',
                    'compare' => 'LIKE',
                ]],
            ]);
            $ids = $q->posts ?: [];
            set_transient($cache_key, $ids, HOUR_IN_SECONDS * 6);
        }

        $query->set('post_type', 'excursion');
        $query->set('post__in',  $ids ?: [0]);
        $query->set('orderby',   'post__in');
    }

    // ── 2. Bateaux liés à l'excursion courante ────────────────────────────────

    /**
     * Source : bt-boats-by-excursion
     * Post type courant : excursion
     * Retourne les bateaux listés dans exp_boats de l'excursion.
     */
    public function query_boats_by_excursion(\WP_Query $query): void {
        $post_id = get_the_ID();
        if (!$post_id || !function_exists('get_field')) return;

        $boats = get_field('exp_boats', $post_id);
        $ids   = [];

        if (!empty($boats)) {
            foreach ((array) $boats as $boat) {
                if ($boat instanceof \WP_Post) {
                    $ids[] = $boat->ID;
                } elseif (is_numeric($boat)) {
                    $ids[] = (int) $boat;
                }
            }
        }

        $query->set('post_type', 'boat');
        $query->set('post__in',  $ids ?: [0]);
        $query->set('orderby',   'post__in');
    }

    // ── 3. Excursions par ville de départ ─────────────────────────────────────

    /**
     * Source : bt-excursions-by-city
     * Post type courant : city (taxonomy term page) ou excursion (même ville de départ).
     *
     * Stratégie :
     *  - Si on est sur un terme de taxonomie "city" → cherche toutes les excursions
     *    dont exp_departure_point contient ce terme.
     *  - Si on est sur une excursion → excursions partageant la même ville de départ.
     */
    public function query_excursions_by_city(\WP_Query $query): void {
        $post_id  = get_the_ID();
        $term_ids = [];

        // Contexte : page de terme city
        $queried = get_queried_object();
        if ($queried instanceof \WP_Term && $queried->taxonomy === 'city') {
            $term_ids = [$queried->term_id];
        }

        // Contexte : fiche excursion — récupère les villes de départ
        if (empty($term_ids) && $post_id && function_exists('get_field')) {
            $dep = get_field('exp_departure_point', $post_id);
            if (!empty($dep)) {
                foreach ((array) $dep as $t) {
                    if ($t instanceof \WP_Term) $term_ids[] = $t->term_id;
                    elseif (is_numeric($t))     $term_ids[] = (int) $t;
                }
            }
        }

        if (empty($term_ids)) {
            $query->set('post__in', [0]);
            return;
        }

        $cache_key = 'bt_exc_by_city_' . implode('_', $term_ids);
        $ids       = get_transient($cache_key);

        if ($ids === false) {
            $q = new \WP_Query([
                'post_type'      => 'excursion',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'no_found_rows'  => true,
                'tax_query'      => [[
                    'taxonomy' => 'city',
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                    'operator' => 'IN',
                ]],
                'post__not_in'   => $post_id ? [$post_id] : [],
            ]);
            $ids = $q->posts ?: [];
            set_transient($cache_key, $ids, HOUR_IN_SECONDS * 6);
        }

        $query->set('post_type', 'excursion');
        $query->set('post__in',  $ids ?: [0]);
    }

    // ── 4. Excursions similaires ──────────────────────────────────────────────

    /**
     * Source : bt-similar-excursions
     * Post type courant : excursion
     *
     * Retourne les excursions partageant au moins un terme commun dans les taxonomies :
     * exp-whats-included, whats-excluded, exp-material-to-bring, city.
     * Exclut l'excursion courante. Triées par pertinence (nombre de termes communs).
     */
    public function query_similar_excursions(\WP_Query $query): void {
        $post_id = get_the_ID();
        if (!$post_id) return;

        $cache_key = 'bt_similar_exc_' . $post_id;
        $ids       = get_transient($cache_key);

        if ($ids === false) {
            $taxonomies = ['exp-whats-included', 'whats-excluded', 'exp-material-to-bring', 'city'];
            $tax_queries = ['relation' => 'OR'];
            $has_terms   = false;

            foreach ($taxonomies as $tax) {
                $terms = wp_get_post_terms($post_id, $tax, ['fields' => 'ids']);
                if (!empty($terms) && !is_wp_error($terms)) {
                    $tax_queries[] = [
                        'taxonomy' => $tax,
                        'field'    => 'term_id',
                        'terms'    => $terms,
                        'operator' => 'IN',
                    ];
                    $has_terms = true;
                }
            }

            $ids = [];
            if ($has_terms) {
                $q = new \WP_Query([
                    'post_type'      => 'excursion',
                    'posts_per_page' => 12,
                    'fields'         => 'ids',
                    'no_found_rows'  => true,
                    'post__not_in'   => [$post_id],
                    'tax_query'      => $tax_queries,
                ]);
                $ids = $q->posts ?: [];
            }

            set_transient($cache_key, $ids, HOUR_IN_SECONDS * 6);
        }

        $query->set('post_type', 'excursion');
        $query->set('post__in',  $ids ?: [0]);
        $query->set('orderby',   'post__in');
    }
}
