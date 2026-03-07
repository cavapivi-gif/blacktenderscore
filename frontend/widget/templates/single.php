<?php defined('ABSPATH') || exit;
/** @var array $ticket { product_id, widget_id, label } */
?>
<div class="bt-regiondo-widget bt-widget-single">
    <?php if (!empty($ticket['widget_id'])): ?>
        <div class="booking-widget-container">
            <template id="bt-booking-styles-<?= esc_attr($ticket['product_id']) ?>">
                <style>
                    .regiondo-booking-widget { max-width: 599px !important; }
                    .regiondo-widget .regiondo-button-addtocart,
                    .regiondo-widget .regiondo-button-checkout { border-radius: 40px; }
                </style>
            </template>
            <booking-widget
                styles-template-id="bt-booking-styles-<?= esc_attr($ticket['product_id']) ?>"
                widget-id="<?= esc_attr($ticket['widget_id']) ?>"
                data-wid="1"
                tabindex="0">
            </booking-widget>
            <script src="https://widgets.regiondo.net/booking/v1/booking-widget.min.js"></script>
        </div>
    <?php else: ?>
        <p class="bt-widget-error">Widget ID manquant pour ce ticket.</p>
    <?php endif; ?>
</div>