<?php
/**
 * Conclusion template.
 *
 * @var array $conclusion
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$text      = $conclusion['text']         ?? '';
$cta_label = $conclusion['cta_label']    ?? '';
$cta_link  = $conclusion['cta_link']     ?? null;

if ( ! $text && empty( $cta_link['url'] ) ) {
    return;
}
?>
<section class="bt-blog__conclusion">
    <?php if ( $text ) : ?>
        <div class="bt-blog__prose bt-blog__conclusion-text">
            <?php echo wp_kses_post( $text ); ?>
        </div>
    <?php endif; ?>

    <?php if ( $cta_label && ! empty( $cta_link['url'] ) ) : ?>
        <a
            class="bt-blog__conclusion-cta"
            href="<?php echo esc_url( $cta_link['url'] ); ?>"
            <?php echo ! empty( $cta_link['target'] ) ? 'target="' . esc_attr( $cta_link['target'] ) . '" rel="noopener"' : ''; ?>
        >
            <?php echo esc_html( $cta_label ); ?>
            <span aria-hidden="true">→</span>
        </a>
    <?php endif; ?>
</section>
