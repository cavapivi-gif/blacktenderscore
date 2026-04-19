/**
 * BlackTenders — Fixed CTA Widget JS
 *
 * AJAX store status via btcFixedCta.ajaxUrl / btcFixedCta.nonce.
 * Met à jour le dot de statut + texte du bouton selon les horaires du store.
 * Bouton fermer avec persistance sessionStorage.
 *
 * @global btcFixedCta { ajaxUrl: string, nonce: string }
 */
(function () {
  'use strict';

  var SEL = {
    ROOT:  '.btc-fixed-cta',
    DOT:   '.btc-cta__dot',
    BTN:   '.btc-cta__btn',
    WRAP:  '.btc-cta__btn-wrap',
    CLOSE: '.btc-cta__close',
  };

  var STORAGE_KEY = 'btc_cta_dismissed';

  function initWidget(el) {
    if (el.getAttribute('data-btc-init')) return;
    el.setAttribute('data-btc-init', '1');

    var dot   = el.querySelector(SEL.DOT);
    var btn   = el.querySelector(SEL.BTN);
    var wrap  = el.querySelector(SEL.WRAP);
    var close = el.querySelector(SEL.CLOSE);

    var storeId     = el.getAttribute('data-store-id') || '';
    var textOnline  = el.getAttribute('data-text-online') || 'Nous appeler';
    var textOffline = el.getAttribute('data-text-offline') || 'Nous contacter';
    var showDot     = el.getAttribute('data-show-dot');
    var closable    = el.getAttribute('data-closable') === 'true';
    var scrollColor = el.getAttribute('data-scroll-color') || '';

    // Rey header — surcharge couleur texte btn quand header scrollé (--shrank)
    // Utilise setProperty(..., 'important') pour battre le !important du theme Rey
    if (scrollColor && btn) {
      var reyHeader = document.querySelector('.rey-siteHeader');
      if (reyHeader) {
        var applyReyScrollColor = function () {
          if (reyHeader.classList.contains('--shrank')) {
            btn.style.setProperty('color', scrollColor, 'important');
          } else {
            btn.style.removeProperty('color');
          }
        };
        new MutationObserver(applyReyScrollColor).observe(reyHeader, {
          attributes: true,
          attributeFilter: ['class'],
        });
        applyReyScrollColor(); // état initial
      }
    }

    // Vérifier si déjà fermé pendant cette session
    if (closable && sessionStorage.getItem(STORAGE_KEY) === '1') {
      el.classList.add('btc-fixed-cta--hidden');
    }

    // Bouton fermer
    if (close && closable) {
      close.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        el.classList.add('btc-fixed-cta--hidden');
        sessionStorage.setItem(STORAGE_KEY, '1');
      });
    }

    // AJAX status store
    if (storeId && storeId !== '0' && typeof btcFixedCta !== 'undefined') {
      var body = new URLSearchParams({
        action:   'btc_get_store_status',
        nonce:    btcFixedCta.nonce,
        store_id: storeId,
      });

      fetch(btcFixedCta.ajaxUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body,
      })
        .then(function (r) { return r.json(); })
        .then(function (resp) {
          if (!resp || !resp.success) return;
          var online = !!resp.data.online;

          if (btn) btn.textContent = online ? textOnline : textOffline;

          if (dot) {
            if (showDot === 'false') {
              dot.setAttribute('data-hidden', 'true');
              return;
            }
            dot.setAttribute('data-status', online ? 'online' : 'offline');
            dot.setAttribute('data-hidden', 'false');
          }
        })
        .catch(function () {});
    }

    // Hover/active interactions sur le bouton wrap
    if (wrap) {
      wrap.addEventListener('mouseenter', function () { wrap.classList.add('is-hovered'); });
      wrap.addEventListener('mouseleave', function () {
        wrap.classList.remove('is-hovered');
        wrap.classList.remove('is-active');
      });
      wrap.addEventListener('mousedown', function () { wrap.classList.add('is-active'); });
      wrap.addEventListener('mouseup', function () { wrap.classList.remove('is-active'); });
    }
  }

  // Init au chargement
  document.querySelectorAll(SEL.ROOT).forEach(initWidget);

  // Elementor handler — re-init au re-rendu dans l'éditeur
  window.addEventListener('elementor/frontend/init', function () {
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
      elementorFrontend.hooks.addAction('frontend/element_ready/btc-fixed-cta.default', function ($el) {
        initWidget($el[0]);
      });
    }
  });
}());
