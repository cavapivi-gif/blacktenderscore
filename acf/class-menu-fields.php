<?php
namespace BlackTenders\Acf;

defined('ABSPATH') || exit;

/**
 * Enregistre les champs ACF pour les items de menu (nav_menu_item).
 */
class MenuFields {

    public function init(): void {
        add_action('acf/init', [$this, 'register_fields']);
    }

    /**
     * Enregistre le groupe de champs "BT Menu Item".
     */
    public function register_fields(): void {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key'      => 'group_bt_menu_item',
            'title'    => 'BT — Menu Item',
            'fields'   => [
                [
                    'key'          => 'field_bt_menu_icon',
                    'label'        => 'Icône',
                    'name'         => 'bt_menu_icon',
                    'type'         => 'image',
                    'instructions' => 'Icône SVG ou image (optionnel).',
                    'return_format'=> 'array',
                    'preview_size' => 'thumbnail',
                    'library'      => 'all',
                    'mime_types'   => 'svg,png,jpg,jpeg,webp',
                ],
                [
                    'key'          => 'field_bt_menu_description',
                    'label'        => 'Description',
                    'name'         => 'bt_menu_description',
                    'type'         => 'textarea',
                    'instructions' => 'Texte affiché sous le titre du menu (optionnel).',
                    'rows'         => 2,
                    'new_lines'    => 'br',
                ],
                [
                    'key'          => 'field_bt_menu_show_count',
                    'label'        => 'Afficher le compteur',
                    'name'         => 'bt_menu_show_count',
                    'type'         => 'true_false',
                    'instructions' => 'Affiche le nombre de sous-items à côté de cet item.',
                    'default_value'=> 0,
                    'ui'           => 1,
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'nav_menu_item',
                        'operator' => '==',
                        'value'    => 'all',
                    ],
                ],
            ],
            'menu_order'            => 10,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
            'active'                => true,
        ]);
    }
}
