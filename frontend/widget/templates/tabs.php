<?php defined('ABSPATH') || exit;
/** @var array $tickets[] { product_id, widget_id, label } */
$uid = 'bt-tabs-' . uniqid();
?>
<div class="bt-regiondo-widget bt-widget-tabs" id="<?= esc_attr($uid) ?>">

    <div class="bt-tabs-nav" role="tablist">
        <?php foreach ($tickets as $i => $ticket): ?>
            <button
                class="bt-tab-btn <?= $i === 0 ? 'is-active' : '' ?>"
                role="tab"
                data-target="<?= esc_attr($uid) ?>-panel-<?= $i ?>"
                aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                type="button">
                <?= esc_html($ticket['label'] ?: 'Ticket ' . ($i + 1)) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="bt-tabs-panels">
        <?php foreach ($tickets as $i => $ticket): ?>
            <div
                class="bt-tab-panel <?= $i !== 0 ? 'is-hidden' : '' ?>"
                id="<?= esc_attr($uid) ?>-panel-<?= $i ?>"
                role="tabpanel">

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
                            data-wid="<?= $i + 1 ?>"
                            tabindex="0">
                        </booking-widget>
                    </div>
                <?php else: ?>
                    <p class="bt-widget-error">Widget ID manquant.</p>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>
    </div>

</div>

<script src="https://widgets.regiondo.net/booking/v1/booking-widget.min.js"></script>