/**
 * bt-segmented-control.js
 * Gere : segmented control switch + selection forfait card + swap contenu.
 * Aucune dependance.
 */
(function () {
  'use strict';

  var ACTIVE_SEG   = 'bt-seg__btn--active';
  var ACTIVE_PANEL = 'bt-seg__panel--active';
  var ACTIVE_CARD  = 'bt-forfait-card--active';

  /**
   * Init segmented control (Reserver / Devis).
   */
  function initSegmentedControl(wrapper) {
    var seg = wrapper.querySelector('.bt-seg');
    if (!seg || seg.getAttribute('data-bt-seg-init')) return;
    seg.setAttribute('data-bt-seg-init', '1');

    var btns   = seg.querySelectorAll('.bt-seg__btn');
    var panels = wrapper.querySelectorAll(':scope > .bt-seg__panel');

    btns.forEach(function (btn, i) {
      btn.addEventListener('click', function () {
        // Update buttons
        btns.forEach(function (b) {
          b.classList.remove(ACTIVE_SEG);
          b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add(ACTIVE_SEG);
        btn.setAttribute('aria-selected', 'true');

        // Slide indicator
        seg.dataset.btSegActive = String(i);

        // Switch panels
        var target = btn.dataset.btSegTarget;
        panels.forEach(function (p) {
          var isTarget = p.dataset.btSegPanel === target;
          p.classList.toggle(ACTIVE_PANEL, isTarget);
          p.hidden = !isTarget;
          if (isTarget && window.btActivateLazyMedia) {
            window.btActivateLazyMedia(p);
          }
        });
      });
    });
  }

  /**
   * Init forfait card selection.
   */
  function initForfaitCards(wrapper) {
    var grid = wrapper.querySelector('.bt-forfaits__grid');
    if (!grid || grid.getAttribute('data-bt-fc-init')) return;
    grid.setAttribute('data-bt-fc-init', '1');

    var cards    = wrapper.querySelectorAll('.bt-forfait-card');
    var contents = wrapper.querySelectorAll('.bt-forfait-content');

    if (!cards.length) return;

    cards.forEach(function (card) {
      card.addEventListener('click', function () {
        var idx = card.dataset.btForfaitIndex;

        // Update cards
        cards.forEach(function (c) {
          c.classList.remove(ACTIVE_CARD);
          c.setAttribute('aria-pressed', 'false');
        });
        card.classList.add(ACTIVE_CARD);
        card.setAttribute('aria-pressed', 'true');

        // Switch content + trigger lazy load pour le forfait actif
        var activeContent = null;
        contents.forEach(function (c) {
          var isActive = c.dataset.btForfaitContent === idx;
          c.hidden = !isActive;
          if (isActive) activeContent = c;
        });

        // Déclencher le lazy loading du booking-widget pour ce forfait
        if (activeContent && window.btWidgets && window.btWidgets.injectBookingLazy) {
          window.btWidgets.injectBookingLazy(activeContent);
        }
      });
    });
  }

  function init(scope) {
    var el = (scope && scope !== document) ? scope : document;
    el.querySelectorAll('[data-bt-pricing-body]').forEach(function (wrapper) {
      initSegmentedControl(wrapper);
      initForfaitCards(wrapper);
    });
  }

  // Init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  } else {
    init(document);
  }

  // Elementor frontend handler
  window.addEventListener('elementor/frontend/init', function () {
    if (typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks) {
      elementorFrontend.hooks.addAction(
        'frontend/element_ready/bt-pricing-body.default',
        function ($el) {
          var wrapper = $el[0] || $el;
          initSegmentedControl(wrapper);
          initForfaitCards(wrapper);
        }
      );
    }
  });

  // API publique
  window.btSegControl = { init: init };
}());
