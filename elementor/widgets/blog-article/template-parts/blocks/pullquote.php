<?php
/**
 * Pullquote block.
 *
 * @var array $block
 * @var int   $index
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$quote  = $block['quote']  ?? '';
$author = $block['author'] ?? '';
$source = $block['source'] ?? '';
$style  = in_array( $block['style'] ?? 'bordered', [ 'bordered', 'centered', 'minimal' ], true )
    ? $block['style']
    : 'bordered';

if ( ! $quote ) {
    return;
}
?>
<blockquote class="bt-blog__block bt-blog__block--pullquote bt-blog__pullquote--<?php echo esc_attr( $style ); ?>">
    <p class="bt-blog__pullquote-text">
        <?php echo esc_html( $quote ); ?>
    </p>

    <?php if ( $author || $source ) : ?>
        <footer class="bt-blog__pullquote-cite">
            <?php if ( $author ) : ?>
                <cite class="bt-blog__pullquote-author"><?php echo esc_html( $author ); ?></cite>
            <?php endif; ?>
            <?php if ( $source ) : ?>
                <span class="bt-blog__pullquote-source"><?php echo esc_html( $source ); ?></span>
            <?php endif; ?>
        </footer>
    <?php endif; ?>
</blockquote>
