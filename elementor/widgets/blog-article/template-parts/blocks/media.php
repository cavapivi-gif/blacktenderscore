<?php
/**
 * Media block (image / gallery / oEmbed video).
 *
 * @var array $block
 * @var int   $index
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$type    = $block['type'] ?? 'image';
$display = in_array( $block['display'] ?? 'inline', [ 'inline', 'wide', 'fullbleed' ], true )
    ? $block['display']
    : 'inline';
$ratio   = $block['ratio'] ?? 'auto';
$caption = $block['caption'] ?? '';
$credit  = $block['credit'] ?? '';

$ratio_class = 'auto' === $ratio ? '' : ' bt-blog__media--ratio-' . esc_attr( $ratio );
?>
<figure class="bt-blog__block bt-blog__block--media bt-blog__block--<?php echo esc_attr( $display ); ?>">
    <div class="bt-blog__media<?php echo $ratio_class; ?>">
        <?php if ( 'image' === $type && ! empty( $block['image']['ID'] ) ) : ?>
            <?php
            echo wp_get_attachment_image(
                (int) $block['image']['ID'],
                'fullbleed' === $display ? 'full' : 'large',
                false,
                [
                    'class'    => 'bt-blog__media-img',
                    'loading'  => 'lazy',
                    'decoding' => 'async',
                    'alt'      => $block['image']['alt'] ?? '',
                ]
            );
            ?>
        <?php elseif ( 'gallery' === $type && ! empty( $block['gallery'] ) ) : ?>
            <div class="bt-blog__gallery" data-count="<?php echo count( $block['gallery'] ); ?>">
                <?php foreach ( $block['gallery'] as $item ) : ?>
                    <?php if ( ! empty( $item['ID'] ) ) : ?>
                        <div class="bt-blog__gallery-item">
                            <?php
                            echo wp_get_attachment_image(
                                (int) $item['ID'],
                                'large',
                                false,
                                [
                                    'class'    => 'bt-blog__gallery-img',
                                    'loading'  => 'lazy',
                                    'decoding' => 'async',
                                    'alt'      => $item['alt'] ?? '',
                                ]
                            );
                            ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php elseif ( 'video' === $type && ! empty( $block['video'] ) ) : ?>
            <div class="bt-blog__media-video">
                <?php echo $block['video']; // oEmbed output, already safe ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ( $caption || $credit ) : ?>
        <figcaption class="bt-blog__media-caption">
            <?php if ( $caption ) : ?>
                <span class="bt-blog__media-caption-text"><?php echo esc_html( $caption ); ?></span>
            <?php endif; ?>
            <?php if ( $credit ) : ?>
                <span class="bt-blog__media-credit"><?php echo esc_html( $credit ); ?></span>
            <?php endif; ?>
        </figcaption>
    <?php endif; ?>
</figure>
