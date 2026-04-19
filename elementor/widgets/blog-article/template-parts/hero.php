<?php
/**
 * Hero template.
 *
 * @var int    $post_id
 * @var array  $settings
 * @var string $post_title
 * @var int    $featured_image_id
 * @var array  $hero
 * @var int    $reading_time
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$title = ! empty( $hero['title_override'] ) ? $hero['title_override'] : $post_title;

$media_id  = 0;
$media_alt = '';
if ( 'featured' === $hero['media_type'] && $featured_image_id ) {
    $media_id  = $featured_image_id;
    $media_alt = get_post_meta( $featured_image_id, '_wp_attachment_image_alt', true );
} elseif ( 'image' === $hero['media_type'] && ! empty( $hero['image']['ID'] ) ) {
    $media_id  = (int) $hero['image']['ID'];
    $media_alt = $hero['image']['alt'] ?? '';
}
?>
<header class="bt-blog__hero">
    <div class="bt-blog__hero-inner">
        <?php if ( 'yes' === $settings['show_kicker'] && ! empty( $hero['kicker'] ) ) : ?>
            <p class="bt-blog__kicker"><?php echo esc_html( $hero['kicker'] ); ?></p>
        <?php endif; ?>

        <?php if ( 'yes' === $settings['show_title'] ) : ?>
        <h1 class="bt-blog__title"><?php echo esc_html( $title ); ?></h1>
        <?php endif; ?>

        <?php if ( 'yes' === $settings['show_reading_time'] && $reading_time ) : ?>
            <p class="bt-blog__meta">
                <span class="bt-blog__reading-time">
                    <?php
                    printf(
                        /* translators: %d: reading time in minutes */
                        esc_html__( '%d min de lecture', 'blacktenderscore' ),
                        (int) $reading_time
                    );
                    ?>
                </span>
            </p>
        <?php endif; ?>

        <?php if ( ! empty( $hero['lead'] ) ) : ?>
            <p class="bt-blog__lead"><?php echo esc_html( $hero['lead'] ); ?></p>
        <?php endif; ?>
    </div>

    <?php if ( $media_id && 'yes' === $settings['show_image'] ) : ?>
        <figure class="bt-blog__hero-media">
            <?php
            echo wp_get_attachment_image(
                $media_id,
                'full',
                false,
                [
                    'class'    => 'bt-blog__hero-image',
                    'loading'  => 'eager',
                    'decoding' => 'async',
                    'alt'      => $media_alt,
                ]
            );
            ?>
            <?php if ( ! empty( $hero['caption'] ) ) : ?>
                <figcaption class="bt-blog__hero-caption"><?php echo esc_html( $hero['caption'] ); ?></figcaption>
            <?php endif; ?>
        </figure>
    <?php elseif ( 'yes' === $settings['show_image'] && 'video' === $hero['media_type'] && ! empty( $hero['video'] ) ) : ?>
        <figure class="bt-blog__hero-media bt-blog__hero-media--video">
            <?php echo wp_oembed_get( esc_url( $hero['video'] ) ); ?>
        </figure>
    <?php endif; ?>
</header>
