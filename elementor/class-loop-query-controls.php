<?php
namespace BlackTenders\Elementor;

defined('ABSPATH') || exit;

/**
 * Ajoute les contrôles Order/Filter manquants pour les Loop widgets.
 * Utilise le système Query ID d'Elementor pour modifier la query.
 */
class LoopQueryControls {

    public function init(): void {
        // Ajouter les contrôles après la section Query
        add_action('elementor/element/loop-grid/section_query/after_section_end', [$this, 'add_query_controls'], 10, 2);
        add_action('elementor/element/loop-carousel/section_query/after_section_end', [$this, 'add_query_controls'], 10, 2);

        // Hook sur le Query ID "bt_override" - toujours actif
        add_action('elementor/query/bt_override', [$this, 'modify_query'], 10, 2);

        // Hook dynamique pour les Query IDs personnalisés commençant par "bt_"
        add_action('pre_get_posts', [$this, 'register_dynamic_hooks'], 1);
    }

    /**
     * Enregistre les hooks dynamiques pour les Query IDs.
     */
    public function register_dynamic_hooks(): void {
        // Les hooks sont déjà enregistrés via l'action elementor/query/{query_id}
    }

    /**
     * Ajoute les contrôles Order/Filter.
     */
    public function add_query_controls(\Elementor\Element_Base $element, array $args): void {
        $element->start_controls_section('section_bt_query_override', [
            'label' => __('BT — Query Override', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $element->add_control('bt_query_override_info', [
            'type'            => \Elementor\Controls_Manager::RAW_HTML,
            'raw'             => sprintf(
                __('Pour activer ces options, mettez <strong>bt_override</strong> dans le champ "Query ID" de la section Query ci-dessus.', 'blacktenderscore')
            ),
            'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
        ]);

        $element->add_control('bt_query_orderby', [
            'label'   => __('Trier par', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                ''              => __('-- Par défaut --', 'blacktenderscore'),
                'date'          => __('Date de publication', 'blacktenderscore'),
                'modified'      => __('Date de modification', 'blacktenderscore'),
                'title'         => __('Titre', 'blacktenderscore'),
                'menu_order'    => __('Menu Order', 'blacktenderscore'),
                'rand'          => __('Aléatoire', 'blacktenderscore'),
                'comment_count' => __('Nombre de commentaires', 'blacktenderscore'),
                'meta_value'    => __('Champ personnalisé (texte)', 'blacktenderscore'),
                'meta_value_num'=> __('Champ personnalisé (numérique)', 'blacktenderscore'),
            ],
        ]);

        $element->add_control('bt_query_meta_key', [
            'label'     => __('Meta Key pour tri', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => '',
            'condition' => [
                'bt_query_orderby' => ['meta_value', 'meta_value_num'],
            ],
        ]);

        $element->add_control('bt_query_order', [
            'label'   => __('Ordre', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => [
                ''     => __('-- Par défaut --', 'blacktenderscore'),
                'ASC'  => __('Croissant (A→Z, 1→9)', 'blacktenderscore'),
                'DESC' => __('Décroissant (Z→A, 9→1)', 'blacktenderscore'),
            ],
        ]);

        $element->add_control('bt_query_divider_1', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        $element->add_control('bt_query_posts_per_page', [
            'label'       => __('Posts par page', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::NUMBER,
            'default'     => '',
            'min'         => -1,
            'description' => __('Vide = défaut. -1 = tous.', 'blacktenderscore'),
        ]);

        $element->add_control('bt_query_offset', [
            'label' => __('Offset (ignorer X posts)', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::NUMBER,
            'min'   => 0,
        ]);

        $element->add_control('bt_query_divider_2', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // Filtres par taxonomie
        $element->add_control('bt_query_tax_heading', [
            'label' => __('Filtre Taxonomie', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ]);

        $element->add_control('bt_query_taxonomy', [
            'label'   => __('Taxonomie', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $this->get_taxonomies(),
        ]);

        $element->add_control('bt_query_terms', [
            'label'       => __('Terms (IDs ou slugs)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'condition'   => ['bt_query_taxonomy!' => ''],
            'description' => __('Séparés par virgules: 12,45 ou slug-1,slug-2', 'blacktenderscore'),
        ]);

        $element->add_control('bt_query_terms_operator', [
            'label'     => __('Opérateur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => 'IN',
            'options'   => [
                'IN'     => __('Inclure (IN)', 'blacktenderscore'),
                'NOT IN' => __('Exclure (NOT IN)', 'blacktenderscore'),
                'AND'    => __('Tous (AND)', 'blacktenderscore'),
            ],
            'condition' => ['bt_query_taxonomy!' => ''],
        ]);

        $element->add_control('bt_query_divider_3', [
            'type' => \Elementor\Controls_Manager::DIVIDER,
        ]);

        // Filtre par meta
        $element->add_control('bt_query_meta_heading', [
            'label' => __('Filtre Meta', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::HEADING,
        ]);

        $element->add_control('bt_query_meta_filter_key', [
            'label' => __('Meta Key', 'blacktenderscore'),
            'type'  => \Elementor\Controls_Manager::TEXT,
        ]);

        $element->add_control('bt_query_meta_filter_value', [
            'label'     => __('Meta Value', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'condition' => ['bt_query_meta_filter_key!' => ''],
        ]);

        $element->add_control('bt_query_meta_compare', [
            'label'     => __('Comparaison', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'default'   => '=',
            'options'   => [
                '='          => '= (égal)',
                '!='         => '!= (différent)',
                '>'          => '> (supérieur)',
                '>='         => '>= (supérieur ou égal)',
                '<'          => '< (inférieur)',
                '<='         => '<= (inférieur ou égal)',
                'LIKE'       => 'LIKE (contient)',
                'NOT LIKE'   => 'NOT LIKE',
                'EXISTS'     => 'EXISTS (existe)',
                'NOT EXISTS' => 'NOT EXISTS (n\'existe pas)',
            ],
            'condition' => ['bt_query_meta_filter_key!' => ''],
        ]);

        $element->end_controls_section();
    }

    /**
     * Liste des taxonomies disponibles.
     */
    private function get_taxonomies(): array {
        $taxonomies = ['' => __('-- Aucun --', 'blacktenderscore')];

        $tax_objects = get_taxonomies(['public' => true], 'objects');
        foreach ($tax_objects as $tax) {
            $taxonomies[$tax->name] = $tax->label;
        }

        return $taxonomies;
    }

    /**
     * Modifie la query via le hook Elementor.
     *
     * @param \WP_Query $query
     * @param \Elementor\Widget_Base $widget
     */
    public function modify_query(\WP_Query $query, \Elementor\Widget_Base $widget): void {
        $settings = $widget->get_settings_for_display();

        // Order by
        $orderby = $settings['bt_query_orderby'] ?? '';
        if ($orderby) {
            $query->set('orderby', $orderby);

            if (in_array($orderby, ['meta_value', 'meta_value_num'], true)) {
                $meta_key = $settings['bt_query_meta_key'] ?? '';
                if ($meta_key) {
                    $query->set('meta_key', $meta_key);
                }
            }
        }

        // Order
        $order = $settings['bt_query_order'] ?? '';
        if ($order) {
            $query->set('order', $order);
        }

        // Posts per page
        $ppp = $settings['bt_query_posts_per_page'] ?? '';
        if ($ppp !== '' && is_numeric($ppp)) {
            $query->set('posts_per_page', (int) $ppp);
        }

        // Offset
        $offset = $settings['bt_query_offset'] ?? '';
        if ($offset !== '' && is_numeric($offset) && (int) $offset > 0) {
            $query->set('offset', (int) $offset);
        }

        // Tax query
        $taxonomy = $settings['bt_query_taxonomy'] ?? '';
        $terms    = $settings['bt_query_terms'] ?? '';
        if ($taxonomy && $terms) {
            $term_list = array_map('trim', explode(',', $terms));
            $field = is_numeric($term_list[0]) ? 'term_id' : 'slug';

            $existing = $query->get('tax_query') ?: [];
            $existing[] = [
                'taxonomy' => $taxonomy,
                'field'    => $field,
                'terms'    => $term_list,
                'operator' => $settings['bt_query_terms_operator'] ?? 'IN',
            ];
            $query->set('tax_query', $existing);
        }

        // Meta query
        $meta_key = $settings['bt_query_meta_filter_key'] ?? '';
        if ($meta_key) {
            $existing = $query->get('meta_query') ?: [];

            $meta_q = [
                'key'     => $meta_key,
                'compare' => $settings['bt_query_meta_compare'] ?? '=',
            ];

            $meta_value = $settings['bt_query_meta_filter_value'] ?? '';
            $compare = $meta_q['compare'];
            if ($meta_value !== '' || !in_array($compare, ['EXISTS', 'NOT EXISTS'], true)) {
                $meta_q['value'] = $meta_value;
            }

            $existing[] = $meta_q;
            $query->set('meta_query', $existing);
        }
    }
}
