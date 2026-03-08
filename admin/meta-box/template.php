<?php defined('ABSPATH') || exit;
$map = get_option('bt_widget_map', []);
?>
<div class="bt-meta-box">

    <button type="button" id="bt-load-products" class="button">
        Charger les offres Regiondo
    </button>

    <div id="bt-products-list"></div>

    <?php if (!empty($saved)): ?>
        <div id="bt-saved-tickets">
            <?php foreach ($saved as $i => $ticket):
                $wid = $map[$ticket['product_id']] ?? '';
            ?>
                <div class="bt-ticket-row" data-index="<?= $i ?>">
                    <input type="hidden" name="bt_regiondo_tickets[<?= $i ?>][product_id]" value="<?= esc_attr($ticket['product_id']) ?>">
                    <input type="hidden" name="bt_regiondo_tickets[<?= $i ?>][label]"      value="<?= esc_attr($ticket['label']) ?>">
                    <span class="bt-ticket-label"><?= esc_html($ticket['label'] ?: 'Produit #' . $ticket['product_id']) ?></span>
                    <?php if ($wid): ?>
                        <span class="bt-widget-ok">Widget configuré</span>
                    <?php else: ?>
                        <span class="bt-widget-missing">
                            Widget manquant —
                            <a href="<?= esc_url(admin_url('admin.php?page=bt-regiondo#/settings')) ?>" target="_blank">Configurer</a>
                        </span>
                    <?php endif; ?>
                    <button type="button" class="bt-remove-ticket button-link-delete">Retirer</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div id="bt-dynamic-tickets"></div>

</div>
