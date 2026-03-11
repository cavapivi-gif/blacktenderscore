/**
 * BlackTenders — Elementor Widgets JS
 *
 * Pattern officiel Elementor :
 *   elementorModules.frontend.handlers.Base
 *   elementorFrontend.elementsHandler.attachHandler()
 *
 * Accordion : toggle classe bt-faq__item--active + aria-expanded
 * Tabs      : toggle bt-faq__tabpanel--active / bt-bprice__panel--active
 * Pas d'attribut [hidden] (surchargé par les thèmes).
 * Animation accordion : CSS grid-template-rows 0fr → 1fr.
 */
(function () {
  'use strict';

  /* ── Accordéon ──────────────────────────────────────────────────────────── */

  function getBody(item) {
    return item.querySelector('.bt-faq__body');
  }

  function openItem(item) {
    item.classList.add('bt-faq__item--active');
    var body = getBody(item);
    // Style inline : priorité absolue sur tout CSS thème
    if (body) body.style.maxHeight = body.scrollHeight + 'px';
    var btn = item.querySelector('.bt-faq__header');
    if (btn) btn.setAttribute('aria-expanded', 'true');
  }

  function closeItem(item) {
    item.classList.remove('bt-faq__item--active');
    var body = getBody(item);
    if (body) body.style.maxHeight = '0px';
    var btn = item.querySelector('.bt-faq__header');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  function initAccordion(root) {
    var isFaqMode = root.hasAttribute('data-bt-faq-mode');

    // Pose immédiatement les max-height inline pour éviter tout flash de contenu
    root.querySelectorAll('.bt-faq__item').forEach(function (item) {
      var body = getBody(item);
      if (!body) return;
      if (item.classList.contains('bt-faq__item--active')) {
        body.style.maxHeight = body.scrollHeight + 'px';
      } else {
        body.style.maxHeight = '0px';
      }
    });

    root.querySelectorAll('.bt-faq__header').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.bt-faq__item');
        if (!item) return;
        var isActive = item.classList.contains('bt-faq__item--active');
        if (isFaqMode && !isActive) {
          root.querySelectorAll('.bt-faq__item--active').forEach(closeItem);
        }
        isActive ? closeItem(item) : openItem(item);
      });
    });
  }

  /* ── Tabs ────────────────────────────────────────────────────────────────── */

  function activateTab(root, tabs, activeTab) {
    // Generic panel class from data attr (ex: 'bt-pricing__panel' → toggles 'bt-pricing__panel--active')
    var panelCls = root.getAttribute('data-bt-panel-class');

    tabs.forEach(function (tab) {
      var isActive = tab === activeTab;

      // Tab button active classes (widget-specific, harmless if not present)
      tab.classList.toggle('bt-faq__tab--active',     isActive);
      tab.classList.toggle('bt-pricing__tab--active', isActive);
      tab.classList.toggle('bt-bprice__tab--active',  isActive);

      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex',      isActive ? '0'    : '-1');

      var panelId = tab.getAttribute('aria-controls');
      var panel   = panelId ? document.getElementById(panelId) : null;
      if (panel) {
        if (panelCls) {
          // Generic: use data-bt-panel-class (PricingTabs, new widgets)
          panel.classList.toggle(panelCls + '--active', isActive);
        } else {
          // Legacy hardcoded (FaqAccordion tabs, BoatPricing — no data attr set)
          panel.classList.toggle('bt-faq__tabpanel--active', isActive);
          panel.classList.toggle('bt-bprice__panel--active', isActive);
        }
      }
    });

    activeTab.focus();
  }

  function initTabs(root) {
    var tablist = root.querySelector('[role="tablist"]');
    if (!tablist) return;

    var tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));

    tabs.forEach(function (tab, idx) {
      tab.addEventListener('click', function () {
        activateTab(root, tabs, tab);
      });

      tab.addEventListener('keydown', function (e) {
        var next;
        if (e.key === 'ArrowRight') next = tabs[(idx + 1) % tabs.length];
        if (e.key === 'ArrowLeft')  next = tabs[(idx - 1 + tabs.length) % tabs.length];
        if (e.key === 'Home')       next = tabs[0];
        if (e.key === 'End')        next = tabs[tabs.length - 1];
        if (next) {
          e.preventDefault();
          next.focus();
          activateTab(root, tabs, next);
        }
      });
    });

    // Breakpoint accordion: when tablist is hidden via CSS, show all panels
    initBreakpointAccordion(root, tablist);
  }

  function initBreakpointAccordion(root, tablist) {
    // Find the Elementor widget wrapper that may have bt-tabs-bp-* class
    var widget = root.closest('[class*="bt-tabs-bp-"]');
    if (!widget) return;

    var panelCls = root.getAttribute('data-bt-panel-class');
    var panels = root.querySelectorAll('[role="tabpanel"]');

    function checkBreakpoint() {
      var tablistHidden = window.getComputedStyle(tablist).display === 'none';
      panels.forEach(function (panel) {
        if (tablistHidden) {
          // Show all panels in accordion mode
          if (panelCls) {
            panel.classList.add(panelCls + '--active');
          } else {
            panel.style.display = 'block';
          }
        } else {
          // Restore tab behavior — only active panel visible
          if (panelCls) {
            var tab = tablist.querySelector('[aria-controls="' + panel.id + '"]');
            var isActive = tab && tab.getAttribute('aria-selected') === 'true';
            panel.classList.toggle(panelCls + '--active', isActive);
          } else {
            panel.style.display = '';
          }
        }
      });
    }

    checkBreakpoint();
    window.addEventListener('resize', checkBreakpoint);
  }

  /* ── Pricing Buttons layout — pill selection ────────────────────────────── */

  function initPricingButtons(el) {
    el.querySelectorAll('[data-bt-pricing-buttons]:not([data-bt-pb-init])').forEach(function (root) {
      root.setAttribute('data-bt-pb-init', '1');

      var slots   = Array.from(root.querySelectorAll('.bt-pricing__slot'));
      var panels  = Array.from(root.querySelectorAll('[data-slot-panel]'));
      var reveal  = root.querySelector('.bt-pricing__booking-reveal');

      slots.forEach(function (slot) {
        slot.addEventListener('click', function () {
          var idx = slot.getAttribute('data-slot-index');

          // Toggle active slot
          slots.forEach(function (s) { s.classList.remove('bt-pricing__slot--active'); });
          slot.classList.add('bt-pricing__slot--active');

          // Show matching panel, hide others
          panels.forEach(function (p) {
            p.classList.toggle('bt-pricing__panel--active', p.getAttribute('data-slot-panel') === idx);
          });

          // Reveal booking widget
          if (reveal) {
            reveal.classList.add('bt-pricing__booking-reveal--visible');
            setTimeout(function () {
              reveal.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 80);
          }
        });
      });
    });
  }

  /* ── Gallery Lightbox ────────────────────────────────────────────────────── */
  /*
   * Visionneuse custom : modal plein écran + strip de miniatures en bas.
   * Un seul DOM #bt-lb créé au premier usage (singleton).
   *
   * Déclencheurs :
   *   [data-bt-gallery-images] — wrapper gallery avec JSON des images
   *   [data-bt-lb-index]       — lien vers une image (index dans le tableau)
   *   [data-bt-lb-open]        — bouton "Voir toutes les photos" (ouvre à 0)
   */

  var _lb      = null;  // nœud DOM modal
  var _lbImgs  = [];    // tableau { src, thumb, alt, caption }
  var _lbIdx   = 0;     // index courant

  function _lbCreate() {
    if (document.getElementById('bt-lb')) {
      _lb = document.getElementById('bt-lb');
      return;
    }
    var d = document.createElement('div');
    d.id        = 'bt-lb';
    d.className = 'bt-lb';
    d.setAttribute('role', 'dialog');
    d.setAttribute('aria-modal', 'true');
    d.setAttribute('aria-label', 'Visionneuse photos');
    d.innerHTML =
      '<button class="bt-lb__close" aria-label="Fermer">&times;</button>' +
      '<div class="bt-lb__counter"></div>' +
      '<button class="bt-lb__prev" aria-label="Image précédente">&#8249;</button>' +
      '<button class="bt-lb__next" aria-label="Image suivante">&#8250;</button>' +
      '<div class="bt-lb__stage">' +
        '<img class="bt-lb__img" src="" alt="" />' +
        '<p class="bt-lb__caption"></p>' +
      '</div>' +
      '<div class="bt-lb__thumbs"></div>';
    document.body.appendChild(d);
    _lb = d;

    _lb.querySelector('.bt-lb__close').addEventListener('click', _lbClose);
    _lb.querySelector('.bt-lb__prev').addEventListener('click', function () { _lbGo(-1); });
    _lb.querySelector('.bt-lb__next').addEventListener('click', function () { _lbGo(1); });

    // Click sur le fond → ferme
    _lb.addEventListener('click', function (e) { if (e.target === _lb) _lbClose(); });

    // Keyboard
    document.addEventListener('keydown', function (e) {
      if (!_lb || !_lb.classList.contains('bt-lb--open')) return;
      if (e.key === 'Escape')      _lbClose();
      if (e.key === 'ArrowLeft')   _lbGo(-1);
      if (e.key === 'ArrowRight')  _lbGo(1);
    });

    // Touch swipe
    var _tx = 0;
    _lb.addEventListener('touchstart', function (e) { _tx = e.touches[0].clientX; }, { passive: true });
    _lb.addEventListener('touchend',   function (e) {
      var dx = e.changedTouches[0].clientX - _tx;
      if (Math.abs(dx) > 40) _lbGo(dx < 0 ? 1 : -1);
    }, { passive: true });
  }

  function _lbOpen(images, idx) {
    _lbCreate();
    _lbImgs = images;
    _lbIdx  = idx;
    _lbRenderThumbs();
    _lbSetImg(idx, false);
    _lb.classList.add('bt-lb--open');
    _lb.removeAttribute('aria-hidden');
    document.body.style.overflow = 'hidden';
    _lb.querySelector('.bt-lb__close').focus();
  }

  function _lbClose() {
    if (!_lb) return;
    _lb.classList.remove('bt-lb--open');
    _lb.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  function _lbGo(dir) {
    _lbIdx = (_lbIdx + dir + _lbImgs.length) % _lbImgs.length;
    _lbSetImg(_lbIdx, true);
  }

  function _lbSetImg(idx, fade) {
    var imgEl = _lb.querySelector('.bt-lb__img');
    var capEl = _lb.querySelector('.bt-lb__caption');
    var cntEl = _lb.querySelector('.bt-lb__counter');
    var cur   = _lbImgs[idx];

    if (fade) {
      imgEl.classList.add('bt-lb__img--fade');
      setTimeout(function () {
        imgEl.src = cur.src;
        imgEl.alt = cur.alt || '';
        imgEl.classList.remove('bt-lb__img--fade');
      }, 120);
    } else {
      imgEl.src = cur.src;
      imgEl.alt = cur.alt || '';
    }

    capEl.textContent  = cur.caption || '';
    capEl.style.display = cur.caption ? '' : 'none';
    if (cntEl) cntEl.textContent = (idx + 1) + ' / ' + _lbImgs.length;

    _lbSyncThumb(idx);
  }

  function _lbRenderThumbs() {
    var strip = _lb.querySelector('.bt-lb__thumbs');
    strip.innerHTML = '';
    _lbImgs.forEach(function (im, i) {
      var btn = document.createElement('button');
      btn.className = 'bt-lb__thumb';
      btn.setAttribute('aria-label', 'Image ' + (i + 1));
      var timg = document.createElement('img');
      timg.src     = im.thumb || im.src;
      timg.alt     = '';
      timg.loading = 'lazy';
      btn.appendChild(timg);
      btn.addEventListener('click', function () {
        _lbIdx = i;
        _lbSetImg(i, true);
      });
      strip.appendChild(btn);
    });
  }

  function _lbSyncThumb(idx) {
    if (!_lb) return;
    _lb.querySelectorAll('.bt-lb__thumb').forEach(function (t, i) {
      t.classList.toggle('bt-lb__thumb--active', i === idx);
    });
    var active = _lb.querySelector('.bt-lb__thumb--active');
    if (active) active.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
  }

  function initGallery(el) {
    el.querySelectorAll('[data-bt-gallery]:not([data-bt-g-init])').forEach(function (gallery) {
      gallery.setAttribute('data-bt-g-init', '1');

      var raw = gallery.getAttribute('data-bt-gallery-images');
      if (!raw) return;

      var images;
      try { images = JSON.parse(raw); } catch (e) { return; }
      if (!images || !images.length) return;

      // Click sur une image individuelle
      gallery.querySelectorAll('[data-bt-lb-index]').forEach(function (a) {
        a.addEventListener('click', function (e) {
          e.preventDefault();
          _lbOpen(images, parseInt(a.getAttribute('data-bt-lb-index'), 10) || 0);
        });
      });

      // Click "Voir toutes les photos"
      var btn = gallery.querySelector('[data-bt-lb-open]');
      if (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          _lbOpen(images, 0);
        });
      }
    });
  }

  /* ── Bootstrap ───────────────────────────────────────────────────────────── */

  function boot(scope) {
    var el = (scope && scope !== document) ? scope : document;

    // data-bt-init évite la double initialisation (DOMContentLoaded + handler)
    el.querySelectorAll('[data-bt-accordion]:not([data-bt-init])').forEach(function (root) {
      root.setAttribute('data-bt-init', '1');
      initAccordion(root);
    });
    el.querySelectorAll('[data-bt-tabs]:not([data-bt-init])').forEach(function (root) {
      root.setAttribute('data-bt-init', '1');
      initTabs(root);
    });
    initPricingButtons(el);
    initGallery(el);
    el.querySelectorAll('[data-bt-share]:not([data-bt-share-init])').forEach(function (btn) {
      btn.setAttribute('data-bt-share-init', '1');
      var url    = btn.getAttribute('data-bt-url')    || window.location.href;
      var title  = btn.getAttribute('data-bt-title')  || document.title;
      var copied = btn.getAttribute('data-bt-copied') || 'Lien copié !';

      btn.addEventListener('click', function () {
        if (navigator.share) {
          navigator.share({ title: title, url: url }).catch(function () {});
        } else {
          // Fallback : copier dans le presse-papier
          var fallback = function () {
            // Cible uniquement la span label pour ne pas détruire l'icône SVG
            var labelEl = btn.querySelector('.bt-share__btn-label');
            var orig    = labelEl ? labelEl.textContent : btn.textContent;

            if (labelEl) {
              labelEl.textContent = copied;
            } else {
              btn.setAttribute('data-orig-text', btn.textContent);
              btn.textContent = copied;
            }

            setTimeout(function () {
              if (labelEl) {
                labelEl.textContent = orig;
              } else {
                btn.textContent = btn.getAttribute('data-orig-text') || orig;
              }
            }, 2200);
          };

          if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(fallback).catch(fallback);
          } else {
            // Fallback IE/vieux navigateurs
            try {
              var ta = document.createElement('textarea');
              ta.value = url;
              ta.style.cssText = 'position:fixed;opacity:0;';
              document.body.appendChild(ta);
              ta.select();
              document.execCommand('copy');
              document.body.removeChild(ta);
              fallback();
            } catch (e) {}
          }
        }
      });
    });
  }

  /* ── Elementor Handler — pattern officiel ────────────────────────────────── */
  //
  //  elementorModules.frontend.handlers.Base est disponible après
  //  l'événement 'elementor/frontend/init'.
  //  elementsHandler.attachHandler() remplace hooks.addAction() et gère
  //  automatiquement le re-rendu dans l'éditeur.

  window.addEventListener('elementor/frontend/init', function () {
    var BtWidgetHandler = elementorModules.frontend.handlers.Base.extend({
      onInit: function () {
        elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);
        boot(this.$element[0]);
      },
    });

    elementorFrontend.elementsHandler.attachHandler('bt-faq-accordion', BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-boat-pricing',  BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-pricing-tabs',  BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-itinerary',     BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-share',         BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-gallery',       BtWidgetHandler);
  });

  // Fallback : pages sans Elementor JS (ex. thème sans Elementor)
  document.addEventListener('DOMContentLoaded', function () { boot(document); });

  // API publique (réutilisable par d'autres scripts)
  window.btWidgets = { boot: boot };
}());
