/**
 * BlackTenders — Elementor Widgets JS
 * Accordion + Tabs — vanilla JS, compatible Elementor frontend + editor preview.
 *
 * Accordion : toggle classe bt-faq__item--active + aria-expanded
 * Tabs      : toggle classe bt-faq__tabpanel--active + aria-selected
 * Aucun attribut [hidden] utilisé (trop facilement surchargé par les thèmes).
 * Animation accordion : CSS grid-template-rows 0fr → 1fr (voir bt-elementor.css).
 */
(function () {
  'use strict';

  /* ── Accordéon ──────────────────────────────────────────────────────────── */

  function initAccordion(root) {
    var isFaqMode = root.hasAttribute('data-bt-faq-mode');

    root.querySelectorAll('.bt-faq__header').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = btn.closest('.bt-faq__item');
        if (!item) return;

        var isActive = item.classList.contains('bt-faq__item--active');

        // FAQ mode : fermer les autres items ouverts avant d'ouvrir le courant
        if (isFaqMode && !isActive) {
          root.querySelectorAll('.bt-faq__item--active').forEach(function (openItem) {
            closeItem(openItem);
          });
        }

        if (isActive) {
          closeItem(item);
        } else {
          openItem(item);
        }
      });

      // Keyboard: Space & Enter (button natif gère déjà Enter, mais sécurité)
      btn.addEventListener('keydown', function (e) {
        if (e.key === ' ' || e.key === 'Enter') {
          e.preventDefault();
          btn.click();
        }
      });
    });
  }

  function openItem(item) {
    item.classList.add('bt-faq__item--active');
    var btn = item.querySelector('.bt-faq__header');
    if (btn) btn.setAttribute('aria-expanded', 'true');
  }

  function closeItem(item) {
    item.classList.remove('bt-faq__item--active');
    var btn = item.querySelector('.bt-faq__header');
    if (btn) btn.setAttribute('aria-expanded', 'false');
  }

  /* ── Tabs ────────────────────────────────────────────────────────────────── */

  function initTabs(root) {
    var tablist = root.querySelector('[role="tablist"]');
    if (!tablist) return;

    var tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));

    tabs.forEach(function (tab, idx) {
      tab.addEventListener('click', function () {
        activateTab(root, tabs, tab);
      });

      // Keyboard navigation ARIA APG pattern
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

  function activateTab(root, tabs, activeTab) {
    tabs.forEach(function (tab) {
      var isActive = tab === activeTab;

      // Tabs FAQ
      tab.classList.toggle('bt-faq__tab--active', isActive);
      // Tabs Pricing (widget séparé)
      tab.classList.toggle('bt-pricing__tab--active', isActive);

      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex',      isActive ? '0'    : '-1');

      var panelId = tab.getAttribute('aria-controls');
      var panel   = panelId ? document.getElementById(panelId) : null;
      if (panel) {
        // Utilise une classe CSS (pas l'attribut hidden, trop souvent surchargé)
        panel.classList.toggle('bt-faq__tabpanel--active',    isActive);
        panel.classList.toggle('bt-pricing__panel--active',   isActive);
      }
    });

    activeTab.focus();
  }

  /* ── Bootstrap ───────────────────────────────────────────────────────────── */

  function boot(scope) {
    var el = (scope && scope !== document) ? scope : document;

    el.querySelectorAll('[data-bt-accordion]').forEach(initAccordion);
    el.querySelectorAll('[data-bt-tabs]').forEach(initTabs);
  }

  // Standard frontend
  document.addEventListener('DOMContentLoaded', function () { boot(document); });

  // Elementor editor live preview
  function registerElementorHooks() {
    window.elementorFrontend.hooks.addAction(
      'frontend/element_ready/bt-faq-accordion.default',
      function ($scope) { boot($scope[0]); }
    );
    window.elementorFrontend.hooks.addAction(
      'frontend/element_ready/bt-pricing-tabs.default',
      function ($scope) { boot($scope[0]); }
    );
  }

  if (window.elementorFrontend && window.elementorFrontend.hooks) {
    registerElementorHooks();
  } else {
    window.addEventListener('elementor/frontend/init', registerElementorHooks);
  }

  // Expose globalement pour réutilisation
  window.btWidgets = { boot: boot };
})();
