<?php
/**
 * Callout block.
 *
 * @var array $block
 * @var int   $index
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$default_icons = [
    'info'    => 'ℹ',
    'tip'     => '💡',
    'warning' => '⚠',
    'cta'     => '→',
];

$variant = in_array( $block['variant'] ?? 'info', array_keys( $default_icons ), true )
    ? $block['variant']
    : 'info';
$icon    = $block['icon'] ?? '';
if ( '' === $icon ) {
    $icon = $default_icons[ $variant ];
}
$title = $block['title'] ?? '';
$body  = $block['body']  ?? '';
$link  = $block['link']  ?? null;

if ( ! $body && ! $title ) {
    return;
}
?>
<aside class="bt-blog__block bt-blog__block--callout bt-blog__callout bt-blog__callout--<?php echo esc_attr( $variant ); ?>" role="note">
    <div class="bt-blog__callout-icon" aria-hidden="true"><?php echo esc_html( $icon ); ?></div>

    <div class="bt-blog__callout-body">
        <?php if ( $title ) : ?>
            <p class="bt-blog__callout-title"><?php echo esc_html( $title ); ?></p>
        <?php endif; ?>

        <?php if ( $body ) : ?>
            <div class="bt-blog__prose">
                <?php echo wp_kses_post( $body ); ?>
            </div>
        <?php endif; ?>

        <?php if ( ! empty( $link['url'] ) ) : ?>
            <a
                class="bt-blog__callout-link"
                href="<?php echo esc_url( $link['url'] ); ?>"
                <?php echo ! empty( $link['target'] ) ? 'target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : ''; ?>
            >
                <?php echo esc_html( $link['title'] ?? __( 'En savoir plus', 'blacktenderscore' ) ); ?>
            </a>
        <?php endif; ?>
    </div>
</aside>
