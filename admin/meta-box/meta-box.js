jQuery(function ($) {
    const { ajax_url, nonce } = btRegionado;
    let ticketIndex = parseInt($('.bt-ticket-row').last().data('index') ?? -1) + 1;

    // ── Charger les produits Regiondo ──────────────────────────────
    $('#bt-load-products').on('click', function () {
        const $btn = $(this).text('⏳ Chargement...').prop('disabled', true);

        $.post(ajax_url, { action: 'bt_regiondo_fetch_products', nonce }, function (res) {
            $btn.text('🔄 Recharger les offres').prop('disabled', false);

            if (!res.success) return alert('Erreur API Regiondo.');

            const products = res.data;
            const $list = $('#bt-products-list').empty();

            if (!products.length) {
                $list.html('<p style="color:#999">Aucun produit trouvé.</p>');
                return;
            }

            // Récupère les product_ids déjà sauvegardés
            const savedIds = $('[name*="[product_id]"]').map((_, el) => parseInt(el.value)).get();

            products.forEach(p => {
                const checked = savedIds.includes(p.product_id) ? 'checked' : '';
                $list.append(`
                    <label class="bt-product-row ${checked ? 'is-active' : ''}">
                        <input type="checkbox" class="bt-product-check"
                               data-id="${p.product_id}" data-name="${p.name}"
                               data-price="${p.base_price}" data-currency="${p.currency}"
                               ${checked}>
                        <span class="bt-product-name">${p.name}</span>
                        <span class="bt-product-price">${p.base_price} ${p.currency}</span>
                        <code>#${p.product_id}</code>
                    </label>
                `);
            });
        });
    });

    // ── Ajouter un ticket à la sélection ──────────────────────────
    $(document).on('change', '.bt-product-check', function () {
        const $cb   = $(this);
        const pid   = $cb.data('id');
        const name  = $cb.data('name');
        $cb.closest('.bt-product-row').toggleClass('is-active', $cb.is(':checked'));

        if ($cb.is(':checked')) {
            addTicketRow(ticketIndex++, pid, name);
        } else {
            $(`[data-index]`).filter(function () {
                return $(this).find(`[name*="[product_id]"]`).val() == pid;
            }).remove();
        }
    });

    // ── Supprimer un ticket ────────────────────────────────────────
    $(document).on('click', '.bt-remove-ticket', function () {
        const $row  = $(this).closest('.bt-ticket-row');
        const pid   = $row.find('[name*="[product_id]"]').val();
        $row.remove();

        // Décocher la checkbox correspondante
        $(`.bt-product-check[data-id="${pid}"]`).prop('checked', false)
            .closest('.bt-product-row').removeClass('is-active');
    });

    function addTicketRow(index, productId, label) {
        $('#bt-dynamic-tickets').append(`
            <div class="bt-ticket-row" data-index="${index}">
                <input type="hidden" name="bt_regiondo_tickets[${index}][product_id]" value="${productId}">
                <input type="hidden" name="bt_regiondo_tickets[${index}][label]" value="${label}">
                <span class="bt-ticket-label">${label}</span>
                <div class="bt-widget-id-row">
                    <label>Widget ID</label>
                    <input type="text"
                           name="bt_regiondo_tickets[${index}][widget_id]"
                           placeholder="uuid widget Regiondo (ex: 249dd360-ebee-...)"
                           class="widefat bt-widget-id-input" />
                </div>
                <button type="button" class="bt-remove-ticket button-link-delete">✕ Retirer</button>
            </div>
        `);
    }
});