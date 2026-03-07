<?php defined('ABSPATH') || exit; ?>
<div class="bt-meta-box">

    <button type="button" id="bt-load-products" class="button button-secondary" style="width:100%; margin-bottom:10px">
        🔄 Charger les offres Regiondo
    </button>

    <div id="bt-products-list"></div>

    <?php if (!empty($saved)): ?>
        <div id="bt-saved-tickets">
            <p><strong>Tickets liés :</strong></p>
            <?php foreach ($saved as $i => $ticket): ?>
                <?php
                $map = get_option('bt_regiondo_widget_map', []);
                $wid = $map[$ticket['product_id']] ?? '';
                ?>
                <div class="bt-ticket-row" data-index="<?= $i ?>">
                    <input type="hidden" name="bt_regiondo_tickets[<?= $i ?>][product_id]" value="<?= esc_attr($ticket['product_id']) ?>">
                    <input type="hidden" name="bt_regiondo_tickets[<?= $i ?>][label]"      value="<?= esc_attr($ticket['label']) ?>">
                    <span class="bt-ticket-label"><?= esc_html($ticket['label'] ?: 'Produit #' . $ticket['product_id']) ?></span>
                    <span style="font-size:11px; color:<?= $wid ? '#4caf50' : '#cc0000' ?>">
                        <?= $wid ? '✅ Widget ID configuré' : '⚠️ Widget ID manquant — configurer dans Réglages → Regiondo' ?>
                    </span>
                    <button type="button" class="bt-remove-ticket button-link-delete">✕</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Container pour les tickets ajoutés dynamiquement -->
    <div id="bt-dynamic-tickets"></div>

</div>