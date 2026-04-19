<?php
/**
 * Separator block.
 *
 * @var array $block
 * @var int   $index
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$style = in_array( $block['style'] ?? 'space', [ 'space', 'line', 'ornament' ], true )
    ? $block['style']
    : 'space';
?>
<hr class="bt-blog__block bt-blog__block--separator bt-blog__separator bt-blog__separator--<?php echo esc_attr( $style ); ?>" aria-hidden="true" />
