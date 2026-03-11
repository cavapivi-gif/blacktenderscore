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
  });

  // Fallback : pages sans Elementor JS (ex. thème sans Elementor)
  document.addEventListener('DOMContentLoaded', function () { boot(document); });

  // API publique (réutilisable par d'autres scripts)
  window.btWidgets = { boot: boot };
}());
