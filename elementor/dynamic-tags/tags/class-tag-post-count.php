<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag Number — Compteur de posts (par type).
 *
 * Retourne le nombre de posts publiés pour un type donné (CPT ou natif).
 *
 * Exemple d'usage : total d'avis, total d'excursions, etc.
 */
class Tag_Post_Count extends Abstract_BT_Tag {

    public function get_name(): string       { return 'bt-post-count'; }
    public function get_title(): string      { return 'BT: Compteur de POST'; }
    // Même logique que les tags prix : utilisable sur texte ET nombre.
    public function get_categories(): array  { return ['text', 'number']; }

    protected function register_controls(): void {

        $post_types = get_post_types(['public' => true], 'objects');
        $options    = [];
        foreach ($post_types as $pt) {
            $options[$pt->name] = $pt->labels->singular_name . ' (' . $pt->name . ')';
        }

        $this->add_control('post_type', [
            'label'   => __('Type de post', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $options ?: ['post' => 'Post (post)'],
            'default' => 'post',
        ]);

        $this->add_control('fallback', [
            'label'   => __('Valeur si 0', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '0',
        ]);
    }

    public function render(): void {
        $post_type = $this->get_settings('post_type') ?: 'post';

        if (!post_type_exists($post_type)) {
            echo esc_html($this->get_settings('fallback') ?: '0');
            return;
        }

        $q = new \WP_Query([
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ]);
        $count = (int) $q->post_count;

        $count = (int) $count;
        echo $count > 0 ? $count : esc_html($this->get_settings('fallback') ?: '0');
    }
}

