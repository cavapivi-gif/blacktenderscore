<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Classe abstraite pour tous les Dynamic Tags BT.
 * Centralise le groupe, la guard ACF, et les helpers communs.
 */
abstract class Abstract_BT_Tag extends \Elementor\Core\DynamicTags\Tag {

    final public function get_group(): string {
        return 'blacktenderscore';
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

    /**
     * Retourne tous les champs ACF scalaires pour un SELECT Elementor.
     *
     * Exclut les types structurels (repeater, group, flexible_content…).
     * Utilisé par Tag_Taxonomy et Tag_Acf_Range.
     *
     * @return array<string, string>  clé = field name ACF, valeur = label affiché
     */
    protected function acf_field_options(): array {

        if (!function_exists('acf_get_field_groups')) return [];

        // Types non-scalaires : pas de valeur directe utilisable
        $skip = ['repeater', 'group', 'flexible_content', 'clone', 'tab', 'message', 'accordion'];

        $opts = [];
        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group['key'] ?? '');
            if (!is_array($fields)) continue;
            foreach ($fields as $field) {
                if (in_array($field['type'] ?? '', $skip, true)) continue;
                $opts[$field['name']] = $group['title'] . '  →  ' . $field['label'] . '  (' . $field['name'] . ')';
            }
        }

        asort($opts);
        return $opts ?: ['' => __('Aucun champ ACF trouvé', 'blacktenderscore')];
    }

    /**
     * Retourne les champs ACF de type repeater pour un SELECT.
     *
     * @return array<string, string>  name => label
     */
    protected function acf_repeater_field_options(): array {

        if (!function_exists('acf_get_field_groups')) return [];

        $opts = [];
        foreach (acf_get_field_groups() as $group) {
            $fields = acf_get_fields($group['key'] ?? '');
            if (!is_array($fields)) continue;
            foreach ($fields as $field) {
                if (($field['type'] ?? '') !== 'repeater') continue;
                $opts[$field['name']] = $group['title'] . '  →  ' . $field['label'] . '  (' . $field['name'] . ')';
            }
        }

        asort($opts);
        return $opts ?: ['' => __('Aucun repeater ACF trouvé', 'blacktenderscore')];
    }

    /**
     * Détecte la structure "horaires" d'un repeater ACF : 1 sous-repeater (N départs) + 1 champ prix.
     *
     * Parcourt les sub_fields du repeater, trouve le 1er sous-champ de type repeater (liste d'horaires),
     * puis le 1er sous-champ texte/number dedans (valeur horaire). Et le 1er number dans le repeater principal (prix).
     *
     * @param string $repeater_name Nom du champ repeater (ex. tarification_par_forfait)
     * @return array{times_subfield: string, time_value_subfield: string, price_subfield: string}|null
     */
    protected function acf_detect_repeater_departure_structure(string $repeater_name): ?array {

        if (!function_exists('acf_get_field') || $repeater_name === '') return null;

        $main = acf_get_field($repeater_name);
        if (!is_array($main) || empty($main['sub_fields'])) return null;

        $times_subfield     = '';
        $time_value_subfield = '';
        $price_subfield     = '';

        foreach ($main['sub_fields'] as $sub) {
            $type = $sub['type'] ?? '';
            $name = $sub['name'] ?? '';
            if ($type === 'repeater' && $times_subfield === '') {
                $times_subfield = $name;
                $inner = acf_get_field($sub['key'] ?? $name);
                if (is_array($inner) && !empty($inner['sub_fields'])) {
                    foreach ($inner['sub_fields'] as $inner_sub) {
                        $inner_type = $inner_sub['type'] ?? '';
                        if (in_array($inner_type, ['text', 'number', 'url'], true)) {
                            $time_value_subfield = $inner_sub['name'] ?? '';
                            break;
                        }
                    }
                }
            }
            if ($type === 'number' && strpos($name, 'price') !== false) {
                $price_subfield = $name;
                break;
            }
        }
        if ($price_subfield === '') {
            foreach ($main['sub_fields'] as $sub) {
                if (($sub['type'] ?? '') === 'number') {
                    $price_subfield = $sub['name'] ?? '';
                    break;
                }
            }
        }

        if ($times_subfield === '' || $time_value_subfield === '') return null;

        return [
            'times_subfield'      => $times_subfield,
            'time_value_subfield' => $time_value_subfield,
            'price_subfield'      => $price_subfield,
        ];
    }

    /**
     * Convertit n'importe quelle valeur ACF en chaîne affichable.
     *
     *  - WP_Term ou tableau de WP_Term → nom(s) du/des terme(s)
     *  - array assoc ACF ['term_id' => …]  → nom du terme
     *  - array assoc ACF ['url' => …]      → titre ou URL
     *  - scalaire (int, float, string)      → cast string
     *  - autre tableau / null               → ''
     */
    protected function acf_scalar(mixed $val, string $term_sep = ', '): string {

        if ($val instanceof \WP_Term) {
            return $val->name;
        }

        if (is_array($val)) {
            // Tableau de WP_Term (ACF taxonomy multi-select)
            if (isset($val[0]) && $val[0] instanceof \WP_Term) {
                return implode($term_sep, array_column($val, 'name'));
            }
            // ACF taxonomy "Return format: Array" — un seul terme
            if (isset($val['term_id'])) {
                $t = get_term((int) $val['term_id']);
                return $t instanceof \WP_Term ? $t->name : '';
            }
            // ACF link
            if (isset($val['url'])) {
                return $val['title'] ?: $val['url'];
            }
            // ACF image
            if (isset($val['filename'])) {
                return $val['title'] ?? $val['filename'];
            }
            return '';
        }

        if (is_scalar($val)) return (string) $val;

        return '';
    }
}

/**
 * Classe abstraite pour les Dynamic Tags affichant une plage de deux champs.
 *
 * Les sous-classes définissent uniquement `sides()` (les clés de borne :
 * [1, 2] ou ['min', 'max']) ; le rendu et le séparateur sont partagés.
 *
 * Override `with_taxonomy_fallback()` pour activer la résolution WP native.
 */
abstract class Abstract_Range_Tag extends Abstract_BT_Tag {

    /** @return array Les deux clés de borne, ex. [1, 2] ou ['min', 'max']. */
    abstract protected function sides(): array;

    /**
     * Si true, tente get_the_terms() quand get_field() renvoie vide.
     * Activé uniquement pour Tag_Acf_Range (champs génériques pouvant être
     * des taxonomies WP natives).
     */
    protected function with_taxonomy_fallback(): bool { return false; }

    public function render(): void {
        $post_id  = (int) get_the_ID();
        $sep      = (string) ($this->get_settings('separator') ?: ' - ');
        $fallback = (string) ($this->get_settings('fallback')  ?? '');

        $parts = [];

        foreach ($this->sides() as $side) {
            $key = trim((string) ($this->get_settings("field_{$side}") ?? ''));
            if (!$key) continue;

            $raw = function_exists('get_field') ? get_field($key, $post_id) : null;

            if ($this->with_taxonomy_fallback() && empty($raw) && $raw !== '0' && $raw !== 0) {
                $native = get_the_terms($post_id, $key);
                if (is_array($native) && !empty($native)) $raw = $native;
            }

            $str = $this->acf_scalar($raw);
            if ($str === '') continue;

            $prefix  = (string) ($this->get_settings("prefix_{$side}") ?? '');
            $suffix  = (string) ($this->get_settings("suffix_{$side}") ?? '');
            $parts[] = $prefix . $str . $suffix;
        }

        if (empty($parts)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        echo esc_html(implode($sep, $parts));
    }
}
