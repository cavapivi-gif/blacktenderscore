<?php
/**
 * Text block.
 *
 * @var array $block
 * @var int   $index
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$heading       = $block['heading'] ?? '';
$heading_level = in_array( $block['heading_level'] ?? 'h2', [ 'h2', 'h3', 'h4' ], true )
    ? $block['heading_level']
    : 'h2';
$anchor        = ! empty( $block['anchor'] ) ? sanitize_title( $block['anchor'] ) : '';
$layout        = in_array( $block['layout'] ?? 'narrow', [ 'narrow', 'normal', 'wide' ], true )
    ? $block['layout']
    : 'narrow';
$content       = $block['content'] ?? '';
?>
<section class="bt-blog__block bt-blog__block--text bt-blog__block--<?php echo esc_attr( $layout ); ?>"<?php echo $anchor ? ' id="' . esc_attr( $anchor ) . '"' : ''; ?>>
    <?php if ( $heading ) : ?>
        <<?php echo esc_attr( $heading_level ); ?> class="bt-blog__block-heading bt-blog__block-heading--<?php echo esc_attr( $heading_level ); ?>">
            <?php echo esc_html( $heading ); ?>
        </<?php echo esc_attr( $heading_level ); ?>>
    <?php endif; ?>

    <?php if ( $content ) : ?>
        <div class="bt-blog__prose">
            <?php echo wp_kses_post( $content ); ?>
        </div>
    <?php endif; ?>
</section>
