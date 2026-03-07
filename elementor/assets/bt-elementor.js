/**
 * BlackTenders — Elementor Widgets JS
 * Accordion + Tabs — vanilla JS, compatible Elementor frontend + editor preview.
 */
(function () {
  'use strict';

  /* ── Accordéon ──────────────────────────────────────────────────────────── */

  function initAccordion(root) {
    var buttons = root.querySelectorAll('.bt-faq__question');
    buttons.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item  = btn.closest('.bt-faq__item');
        var panel = document.getElementById(btn.getAttribute('aria-controls'));
        if (!item || !panel) return;

        var isOpen = item.classList.contains('bt-faq__item--open');

        if (isOpen) {
          closeItem(item, btn, panel);
        } else {
          openItem(item, btn, panel);
        }
      });
    });
  }

  function openItem(item, btn, panel) {
    item.classList.add('bt-faq__item--open');
    btn.setAttribute('aria-expanded', 'true');
    panel.removeAttribute('hidden');
    // Animate height
    var full = panel.scrollHeight + 'px';
    panel.style.maxHeight = '0';
    panel.style.overflow  = 'hidden';
    panel.style.transition = 'max-height .3s ease';
    requestAnimationFrame(function () {
      panel.style.maxHeight = full;
    });
    panel.addEventListener('transitionend', function onEnd() {
      panel.style.maxHeight = '';
      panel.style.overflow  = '';
      panel.style.transition = '';
      panel.removeEventListener('transitionend', onEnd);
    });
  }

  function closeItem(item, btn, panel) {
    item.classList.remove('bt-faq__item--open');
    btn.setAttribute('aria-expanded', 'false');
    panel.style.maxHeight  = panel.scrollHeight + 'px';
    panel.style.overflow   = 'hidden';
    panel.style.transition = 'max-height .3s ease';
    requestAnimationFrame(function () {
      panel.style.maxHeight = '0';
    });
    panel.addEventListener('transitionend', function onEnd() {
      panel.setAttribute('hidden', '');
      panel.style.maxHeight  = '';
      panel.style.overflow   = '';
      panel.style.transition = '';
      panel.removeEventListener('transitionend', onEnd);
    });
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

      // Keyboard navigation
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
      tab.classList.toggle('bt-faq__tab--active',    isActive);
      tab.classList.toggle('bt-pricing__tab--active', isActive);
      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex', isActive ? '0' : '-1');

      var panel = document.getElementById(tab.getAttribute('aria-controls'));
      if (panel) {
        if (isActive) panel.removeAttribute('hidden');
        else          panel.setAttribute('hidden', '');
      }
    });
    activeTab.focus();
  }

  /* ── Bootstrap ───────────────────────────────────────────────────────────── */

  function boot(scope) {
    var el = scope || document;

    el.querySelectorAll('[data-bt-accordion]').forEach(initAccordion);
    el.querySelectorAll('[data-bt-tabs]').forEach(initTabs);
  }

  // Standard frontend
  document.addEventListener('DOMContentLoaded', function () { boot(); });

  // Elementor editor live preview
  if (window.elementorFrontend) {
    window.elementorFrontend.hooks.addAction('frontend/element_ready/bt-faq-accordion.default', function ($scope) {
      boot($scope[0]);
    });
    window.elementorFrontend.hooks.addAction('frontend/element_ready/bt-pricing-tabs.default', function ($scope) {
      boot($scope[0]);
    });
  }

  // Expose globally pour réutilisation éventuelle
  window.btWidgets = { boot: boot };
})();
