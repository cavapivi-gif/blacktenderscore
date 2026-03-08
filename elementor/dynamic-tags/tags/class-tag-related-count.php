<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag Number — Nombre de relations (bateaux ou excursions liés).
 *
 * Sur une fiche excursion : compte les bateaux liés via exp_boats.
 * Sur une fiche bateau    : compte les excursions liées (reverse lookup exp_boats).
 * Peut aussi forcer un sens via le contrôle "mode".
 */
class Tag_Related_Count extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-related-count'; }
    public function get_title():      string { return 'BT: Nombre de relations (bateaux/excursions)'; }
    public function get_categories(): array  { return ['number']; }

    protected function register_controls(): void {

        $this->add_control('mode', [
            'label'   => __('Ce que je compte', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'auto'            => __('Auto (détecte le type de post)', 'blacktenderscore'),
                'boats_on_exp'    => __('Bateaux liés à cette excursion', 'blacktenderscore'),
                'excursions_on_boat' => __('Excursions liées à ce bateau', 'blacktenderscore'),
            ],
            'default' => 'auto',
        ]);

        $this->add_control('fallback', [
            'label'   => __('Valeur si 0', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '0',
        ]);
    }

    public function render(): void {
        $mode    = $this->get_settings('mode') ?: 'auto';
        $post_id = get_the_ID();
        $count   = 0;

        if ($mode === 'auto') {
            $post_type = get_post_type($post_id);
            $mode = $post_type === 'excursion' ? 'boats_on_exp' : 'excursions_on_boat';
        }

        if ($mode === 'boats_on_exp') {
            $boats = $this->acf('exp_boats', $post_id);
            $count = is_array($boats) ? count($boats) : 0;

        } elseif ($mode === 'excursions_on_boat') {
            $cache_key = 'bt_rel_count_' . $post_id;
            $count     = get_transient($cache_key);

            if ($count === false) {
                $q = new \WP_Query([
                    'post_type'      => 'excursion',
                    'posts_per_page' => -1,
                    'fields'         => 'ids',
                    'no_found_rows'  => false,
                    'meta_query'     => [[
                        'key'     => 'exp_boats',
                        'value'   => '"' . $post_id . '"',
                        'compare' => 'LIKE',
                    ]],
                ]);
                $count = (int) $q->found_posts;
                set_transient($cache_key, $count, HOUR_IN_SECONDS * 6);
            }
        }

        $count = (int) $count;
        echo $count > 0 ? $count : esc_html($this->get_settings('fallback') ?: '0');
    }
}
