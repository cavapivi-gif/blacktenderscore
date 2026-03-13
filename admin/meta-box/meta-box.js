jQuery(function ($) {
    if (typeof btRegionado === 'undefined') return;
    const { ajax_url, nonce, widgetMap, settingsUrl } = btRegionado;
    let ticketIndex = parseInt($('.bt-ticket-row').last().data('index') ?? -1) + 1;

    /** Escape HTML to prevent XSS from API responses (audit §C08). */
    function esc(str) {
        var d = document.createElement('div');
        d.textContent = str;
        return d.innerHTML;
    }

    // ── Charger les produits Regiondo ──────────────────────────────
    $('#bt-load-products').on('click', function () {
        const $btn = $(this).text('Chargement…').prop('disabled', true);

        $.post(ajax_url, { action: 'bt_regiondo_fetch_products', nonce }, function (res) {
            $btn.text('Recharger les offres').prop('disabled', false);

            if (!res.success) return alert('Erreur API Regiondo.');

            const products = res.data;
            const $list = $('#bt-products-list').empty();

            if (!products.length) {
                $list.html('<p style="color:#999">Aucun produit trouvé.</p>');
                return;
            }

            const savedIds = $('[name*="[product_id]"]').map((_, el) => parseInt(el.value)).get();

            products.forEach(p => {
                const checked = savedIds.includes(p.product_id) ? 'checked' : '';
                $list.append(`
                    <label class="bt-product-row ${checked ? 'is-active' : ''}">
                        <input type="checkbox" class="bt-product-check"
                               data-id="${esc(String(p.product_id))}" data-name="${esc(p.name)}"
                               ${checked}>
                        <span class="bt-product-name">${esc(p.name)}</span>
                        <span class="bt-product-price">${esc(String(p.base_price))} ${esc(p.currency)}</span>
                    </label>
                `);
            });
        });
    });

    // ── Ajouter / retirer un ticket ────────────────────────────────
    $(document).on('change', '.bt-product-check', function () {
        const $cb  = $(this);
        const pid  = $cb.data('id');
        const name = $cb.data('name');
        $cb.closest('.bt-product-row').toggleClass('is-active', $cb.is(':checked'));

        if ($cb.is(':checked')) {
            addTicketRow(ticketIndex++, pid, name);
        } else {
            $('[data-index]').filter(function () {
                return $(this).find('[name*="[product_id]"]').val() == pid;
            }).remove();
        }
    });

    $(document).on('click', '.bt-remove-ticket', function () {
        const $row = $(this).closest('.bt-ticket-row');
        const pid  = $row.find('[name*="[product_id]"]').val();
        $row.remove();
        $(`.bt-product-check[data-id="${pid}"]`).prop('checked', false)
            .closest('.bt-product-row').removeClass('is-active');
    });

    // ── Widget status (auto depuis le map configuré) ───────────────
    function widgetStatus(productId) {
        if (widgetMap[productId]) {
            return '<span class="bt-widget-ok">Widget configuré</span>';
        }
        return `<span class="bt-widget-missing">Widget manquant — <a href="${settingsUrl}" target="_blank">Configurer</a></span>`;
    }

    function addTicketRow(index, productId, label) {
        $('#bt-dynamic-tickets').append(`
            <div class="bt-ticket-row" data-index="${index}">
                <input type="hidden" name="bt_regiondo_tickets[${index}][product_id]" value="${esc(String(productId))}">
                <input type="hidden" name="bt_regiondo_tickets[${index}][label]" value="${esc(label)}">
                <span class="bt-ticket-label">${esc(label)}</span>
                ${widgetStatus(productId)}
                <button type="button" class="bt-remove-ticket button-link-delete">Retirer</button>
            </div>
        `);
    }
});
