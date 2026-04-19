<?php
/**
 * Excursion reference block.
 *
 * @var array $block
 * @var int   $index
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$excursion_id = (int) ( $block['excursion'] ?? 0 );
if ( ! $excursion_id || 'excursion' !== get_post_type( $excursion_id ) ) {
    return;
}

$style = in_array( $block['style'] ?? 'card', [ 'card', 'banner', 'inline_cta' ], true )
    ? $block['style']
    : 'card';

$heading_override = $block['heading_override'] ?? '';
$title      = $heading_override ?: get_the_title( $excursion_id );
$permalink  = get_permalink( $excursion_id );
$thumb_id   = get_post_thumbnail_id( $excursion_id );
$excerpt    = wp_trim_words( get_the_excerpt( $excursion_id ), 25 );
?>
<aside class="bt-blog__block bt-blog__block--excursion-ref bt-blog__excursion-ref bt-blog__excursion-ref--<?php echo esc_attr( $style ); ?>">
    <a class="bt-blog__excursion-ref-link" href="<?php echo esc_url( $permalink ); ?>">
        <?php if ( $thumb_id && 'inline_cta' !== $style ) : ?>
            <div class="bt-blog__excursion-ref-media">
                <?php
                echo wp_get_attachment_image(
                    $thumb_id,
                    'banner' === $style ? 'medium_large' : 'medium',
                    false,
                    [
                        'class'    => 'bt-blog__excursion-ref-img',
                        'loading'  => 'lazy',
                        'decoding' => 'async',
                    ]
                );
                ?>
            </div>
        <?php endif; ?>

        <div class="bt-blog__excursion-ref-body">
            <p class="bt-blog__excursion-ref-eyebrow">
                <?php esc_html_e( 'Excursion associée', 'blacktenderscore' ); ?>
            </p>
            <p class="bt-blog__excursion-ref-title"><?php echo esc_html( $title ); ?></p>

            <?php if ( $excerpt && 'inline_cta' !== $style ) : ?>
                <p class="bt-blog__excursion-ref-excerpt"><?php echo esc_html( $excerpt ); ?></p>
            <?php endif; ?>

            <span class="bt-blog__excursion-ref-cta">
                <?php esc_html_e( 'Découvrir l\'excursion', 'blacktenderscore' ); ?>
                <span aria-hidden="true">→</span>
            </span>
        </div>
    </a>
</aside>
