<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Image ACF d'un terme de taxonomie.
 *
 * Pattern identique au tag Elementor Pro "Category Image" (WooCommerce) :
 *   1. get_the_terms(post, taxonomy) → premier terme
 *   2. get_term_meta(term_id, field_name, true) → attachment ID brut
 *   3. wp_get_attachment_image_src(id, 'full') → [url, w, h]
 *   4. return ['id' => id, 'url' => url]
 *
 * NB: on lit le term_meta BRUT pour éviter les filtres ACF (return format)
 * et les URLs CDN transformées que get_field() peut retourner.
 */
class Tag_Acf_Taxonomy_Image extends \Elementor\Core\DynamicTags\Data_Tag {

    public function get_name():       string { return 'bt-acf-taxonomy-image'; }
    public function get_title():      string { return 'BT: Image ACF (taxonomie)'; }
    public function get_group():      string { return 'blacktenderscore'; }
    public function get_categories(): array  {
        return [ \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY ];
    }

    protected function register_controls(): void {

        $tax_opts = [];
        foreach ( get_taxonomies( ['public' => true], 'objects' ) as $tax ) {
            $tax_opts[ $tax->name ] = $tax->label . ' (' . $tax->name . ')';
        }
        asort( $tax_opts );

        $this->add_control( 'taxonomy', [
            'label'   => __( 'Taxonomie', 'blacktenderscore' ),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => $tax_opts,
            'default' => array_key_first( $tax_opts ) ?: '',
        ] );

        $this->add_control( 'field_name', [
            'label'       => __( 'Nom du champ ACF (meta key)', 'blacktenderscore' ),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'taxomonies_image',
            'description' => __( 'Le nom du champ ACF image sur le terme (ex: taxomonies_image). Pas la clé field_xxx.', 'blacktenderscore' ),
        ] );
    }

    /**
     * @return array{id: int, url: string}|array{}
     */
    public function get_value( array $options = [] ): array {

        $taxonomy   = trim( (string) ( $this->get_settings( 'taxonomy' )   ?? '' ) );
        $field_name = trim( (string) ( $this->get_settings( 'field_name' ) ?? '' ) );

        if ( $taxonomy === '' || $field_name === '' ) return [];

        $terms = get_the_terms( (int) get_the_ID(), $taxonomy );
        if ( empty( $terms ) || is_wp_error( $terms ) ) return [];

        $term = reset( $terms );
        if ( ! $term instanceof \WP_Term ) return [];

        // Lit l'ID de l'attachment directement depuis le term_meta brut
        // (get_field peut retourner une URL CDN filtrée selon le return format)
        $image_id = (int) get_term_meta( $term->term_id, $field_name, true );
        if ( $image_id <= 0 ) return [];

        $src = wp_get_attachment_image_src( $image_id, 'full' );
        if ( empty( $src ) ) return [];

        return [ 'id' => $image_id, 'url' => $src[0] ];
    }
}
