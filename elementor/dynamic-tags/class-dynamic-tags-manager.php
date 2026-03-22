<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

// ── Abstract base ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/class-abstract-bt-tag.php';

// ── Tag classes ───────────────────────────────────────────────────────────────
require_once __DIR__ . '/tags/class-tag-exp-tagline.php';
require_once __DIR__ . '/tags/class-tag-boat-tagline.php';
require_once __DIR__ . '/tags/class-tag-exp-languages.php';
require_once __DIR__ . '/tags/class-tag-exp-price.php';
require_once __DIR__ . '/tags/class-tag-exp-duration.php';
require_once __DIR__ . '/tags/class-tag-exp-departure.php';
require_once __DIR__ . '/tags/class-tag-boat-specs.php';
require_once __DIR__ . '/tags/class-tag-boat-price.php';
require_once __DIR__ . '/tags/class-tag-exp-booking-url.php';
require_once __DIR__ . '/tags/class-tag-related-count.php';
require_once __DIR__ . '/tags/class-tag-taxonomy.php';
require_once __DIR__ . '/tags/class-tag-acf-range.php';
require_once __DIR__ . '/tags/class-tag-acf-map.php';
require_once __DIR__ . '/tags/class-tag-post-count.php';
require_once __DIR__ . '/tags/class-tag-acf-taxonomy-image.php';

/**
 * Dynamic Tags Manager — BT Regiondo.
 *
 * - Enregistre le groupe "bt-regiondo" dans le panneau Elementor.
 * - Enregistre tous les Dynamic Tags du plugin.
 * - Invalide les transients de comptage lors de la sauvegarde d'un post.
 */
class Dynamic_Tags_Manager {

    public function init(): void {
        add_action('elementor/dynamic_tags/register', [$this, 'register_group']);
        add_action('elementor/dynamic_tags/register', [$this, 'register_tags']);
        add_action('save_post',                        [$this, 'invalidate_count_transient'], 10, 2);
    }

    // ── Groupe ────────────────────────────────────────────────────────────────

    public function register_group(\Elementor\Core\DynamicTags\Manager $manager): void {
        $manager->register_group('blacktenderscore', [
            'title' => __('BlackTenders', 'blacktenderscore'),
        ]);
    }

    // ── Tags ──────────────────────────────────────────────────────────────────

    public function register_tags(\Elementor\Core\DynamicTags\Manager $manager): void {

        // Texte simple
        $manager->register(new Tag_Exp_Tagline());
        $manager->register(new Tag_Boat_Tagline());

        // Texte complexe
        $manager->register(new Tag_Exp_Languages());
        $manager->register(new Tag_Exp_Departure());

        // Prix (min + max)
        $manager->register(new Tag_Exp_Price_From());
        $manager->register(new Tag_Exp_Price_To());

        // Durée
        $manager->register(new Tag_Exp_Duration());

        // Prix bateau (min + max)
        $manager->register(new Tag_Boat_Price_From());
        $manager->register(new Tag_Boat_Price_To());

        // Specs bateau
        $manager->register(new Tag_Boat_Pax());
        $manager->register(new Tag_Boat_Engine());

        // Champ ACF générique (texte, nombre, taxonomie…)
        $manager->register(new Tag_Taxonomy());

        // Plage de deux champs ACF (min - max, De X€ à Y€…)
        $manager->register(new Tag_Acf_Range());

        // Champ ACF Google Map → retourne "lat,lng"
        $manager->register(new Tag_Acf_Map());

        // URL
        $manager->register(new Tag_Exp_Booking_Url());

        // Nombre (number category)
        $manager->register(new Tag_Related_Count());
        $manager->register(new Tag_Post_Count());

        // Image d'un terme de taxonomie via champ ACF
        $manager->register(new Tag_Acf_Taxonomy_Image());
    }

    // ── Invalidation transients ───────────────────────────────────────────────

    public function invalidate_count_transient(int $post_id, \WP_Post $post): void {
        if (in_array($post->post_type, ['excursion', 'boat'], true)) {
            delete_transient('bt_rel_count_' . $post_id);

            // Si on sauvegarde une excursion, invalider aussi les bateaux liés
            if ($post->post_type === 'excursion' && function_exists('get_field')) {
                $boats = get_field('exp_boats', $post_id);
                if (is_array($boats)) {
                    foreach ($boats as $boat) {
                        $bid = $boat instanceof \WP_Post ? $boat->ID : (int) $boat;
                        if ($bid) delete_transient('bt_rel_count_' . $bid);
                    }
                }
            }
        }
    }
}
