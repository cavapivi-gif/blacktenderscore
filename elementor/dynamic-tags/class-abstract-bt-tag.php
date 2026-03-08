<?php
namespace BT_Regiondo\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Classe abstraite pour tous les Dynamic Tags BT.
 * Centralise le groupe, la guard ACF, et les helpers communs.
 */
abstract class Abstract_BT_Tag extends \Elementor\Core\DynamicTags\Tag {

    final public function get_group(): string {
        return 'bt-regiondo';
    }

    /**
     * Retourne la valeur ACF du post courant ou null si indisponible.
     */
    protected function acf(string $field_name, ?int $post_id = null): mixed {
        if (!function_exists('get_field')) return null;
        return get_field($field_name, $post_id ?? get_the_ID());
    }

    /**
     * Affiche une valeur ou rien — utilisé dans render() pour du texte simple.
     */
    protected function print_value(string $value): void {
        echo esc_html($value);
    }
}
