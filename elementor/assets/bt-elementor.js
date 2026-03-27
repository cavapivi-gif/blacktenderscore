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

  /**
   * Remplace le <booking-widget> dans root par un nouveau avec le uuid donné.
   * Idempotent : ne fait rien si widget-id est déjà correct.
   * Utilisé par les layouts tabs et buttons de bt-pricing-tabs (booking_per_tab).
   */
  function _swapBookingWidget(root, uuid) {
    var container = root.querySelector('.bt-pricing__booking');
    if (!container) return;
    var old = container.querySelector('booking-widget');
    if (old && old.getAttribute('widget-id') === uuid) return;

    var fresh = document.createElement('booking-widget');
    fresh.setAttribute('widget-id', uuid);
    // Préserver le <style> injecté (CSS custom Regiondo)
    var oldStyle = old && old.querySelector('style');
    if (oldStyle) {
      var s = document.createElement('style');
      s.textContent = oldStyle.textContent;
      fresh.appendChild(s);
    }
    if (old) {
      old.parentNode.replaceChild(fresh, old);
    } else {
      container.appendChild(fresh);
    }
  }

  function activateTab(root, tabs, activeTab) {
    // Generic panel class from data attr (ex: 'bt-pricing__panel' → toggles 'bt-pricing__panel--active')
    var panelCls = root.getAttribute('data-bt-panel-class');

    // Déduire la classe tab active depuis panelCls (ex: 'bt-pricing__panel' → 'bt-pricing__tab--active')
    var tabActiveCls = panelCls
      ? panelCls.replace(/__panel$/, '__tab--active')
      : '';

    tabs.forEach(function (tab) {
      var isActive = tab === activeTab;

      if (tabActiveCls) {
        // Scoped : ne toggle que la classe correspondant à CE niveau de tabs
        tab.classList.toggle(tabActiveCls, isActive);
      } else {
        // Legacy (pas de data-bt-panel-class) : toggle toutes les variantes
        tab.classList.toggle('bt-faq__tab--active',             isActive);
        tab.classList.toggle('bt-pricing__tab--active',         isActive);
        tab.classList.toggle('bt-bprice__tab--active',          isActive);
        tab.classList.toggle('bt-bprice-wrapper__tab--active',  isActive);
      }

      tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
      tab.setAttribute('tabindex',      isActive ? '0'    : '-1');

      var panelId = tab.getAttribute('aria-controls');
      var panel   = panelId ? document.getElementById(panelId) : null;
      if (panel) {
        if (panelCls) {
          panel.classList.toggle(panelCls + '--active', isActive);
        } else {
          panel.classList.toggle('bt-faq__tabpanel--active', isActive);
          panel.classList.toggle('bt-bprice__panel--active', isActive);
          panel.classList.toggle('bt-bprice-wrapper__panel--active', isActive);
        }
      }
    });

    activeTab.focus();

    // Mise à jour UUID Regiondo quand booking_per_tab est actif
    var uuidsRaw = root.getAttribute('data-tab-uuids');
    if (uuidsRaw) {
      try {
        var uuids = JSON.parse(uuidsRaw);
        var tabIdx = tabs.indexOf(activeTab);
        if (uuids[tabIdx]) _swapBookingWidget(root, uuids[tabIdx]);
      } catch (e) {}
    }
  }

  function initTabs(root) {
    var tablist = root.querySelector(':scope > [role="tablist"]');
    if (!tablist) return;

    var tabs = Array.from(tablist.querySelectorAll(':scope > [role="tab"]'));
    if (!tabs.length) return;

    if (window.btDebug) console.log('[BT tabs] init', root.className, '→', tabs.length, 'tabs', tabs.map(function(t){return t.textContent.trim();}));

    tabs.forEach(function (tab, idx) {
      tab.addEventListener('click', function () {
        if (window.btDebug) console.log('[BT tabs] click', tab.textContent.trim(), 'in', root.className);
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

          // Reveal booking widget + injection lazy si nécessaire
          if (reveal) {
            reveal.classList.add('bt-pricing__booking-reveal--visible');
            injectBookingLazy(reveal);
            // Mise à jour UUID Regiondo si booking_per_tab actif
            var slotUuid = slot.getAttribute('data-uuid');
            if (slotUuid) _swapBookingWidget(reveal, slotUuid);
            setTimeout(function () {
              reveal.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 80);
          }
        });
      });
    });
  }

  /* ── Pricing Trigger — bouton reveal / modal ────────────────────────────── */

  /**
   * Injecte le booking-widget depuis le <template> lazy dans son conteneur.
   * Ajoute le script Regiondo s'il n'est pas encore sur la page.
   * Idempotent : marqueur data-bt-bk-loaded empêche la double injection.
   */
  function injectBookingLazy(root) {
    root.querySelectorAll('.bt-pricing__booking-lazy:not([data-bt-bk-loaded])').forEach(function (placeholder) {
      placeholder.setAttribute('data-bt-bk-loaded', '1');
      var tpl = placeholder.querySelector('template.bt-booking-tpl');
      if (!tpl) return;

      // Cloner le contenu du template
      var clone = document.importNode(tpl.content, true);
      placeholder.appendChild(clone);

      // Injecter le script Regiondo si absent (async — chargement en arrière-plan)
      if (!document.querySelector('script[src*="regiondo.net"]')) {
        var s = document.createElement('script');
        s.src   = 'https://widgets.regiondo.net/booking/v1/booking-widget.min.js';
        s.async = true;
        document.head.appendChild(s);
      }
    });
  }

  /**
   * Active les images/backgrounds différés dans un conteneur.
   * - data-lazy-src  → src  (img)
   * - data-lazy-bg   → background-image (div)
   * Idempotent : supprime l'attribut data-lazy-* après activation.
   */
  function btActivateLazyMedia(root) {
    if (!root) return;
    root.querySelectorAll('[data-lazy-src]').forEach(function (img) {
      img.src = img.getAttribute('data-lazy-src');
      img.removeAttribute('data-lazy-src');
    });
    root.querySelectorAll('[data-lazy-bg]').forEach(function (el) {
      el.style.backgroundImage = 'url(' + el.getAttribute('data-lazy-bg') + ')';
      el.removeAttribute('data-lazy-bg');
    });
  }
  window.btActivateLazyMedia = btActivateLazyMedia;

  /**
   * Gère les wrappers [data-bt-trigger="reveal|modal"].
   * - reveal : ouvre un panneau sous le bouton (grid animation)
   * - modal  : ouvre un <dialog> natif
   * - Support ancre #id pour scroll + ouverture automatique
   */
  function initPricingTrigger(el) {
    el.querySelectorAll('[data-bt-trigger]:not([data-bt-tr-init])').forEach(function (wrap) {
      wrap.setAttribute('data-bt-tr-init', '1');

      var mode    = wrap.getAttribute('data-bt-trigger');
      var btn     = wrap.querySelector('.bt-pricing__trigger');
      if (!btn) return;

      var wrapperId = wrap.id || '';

      if (mode === 'reveal') {
        var content = wrap.querySelector('.bt-pricing__reveal-content');
        if (!content) return;

        var hideSel = wrap.getAttribute('data-bt-reveal-hide') || '';
        var hideClasses = wrap.getAttribute('data-bt-reveal-hide-classes') || '';
        var showClasses = wrap.getAttribute('data-bt-reveal-show-classes') || '';
        var mobileBreakpoint = 727;

        // Masquer les éléments "show" par défaut (ils n'apparaissent qu'au clic)
        if (showClasses) {
          showClasses.split(',').forEach(function (sel) {
            sel = sel.trim();
            if (!sel) return;
            document.querySelectorAll(sel).forEach(function (el) {
              el.style.display = 'none';
            });
          });
        }

        function toggleHiddenEl(show) {
          if (hideSel) {
            var el = document.querySelector(hideSel);
            if (el) {
              var isMobile = (window.innerWidth || document.documentElement.clientWidth) <= mobileBreakpoint;
              if (!show && isMobile) {
                el.style.visibility = 'hidden';
                el.style.pointerEvents = 'none';
              } else {
                el.style.visibility = '';
                el.style.pointerEvents = '';
              }
            }
          }
          // Hide trigger button + custom selectors
          if (!show) {
            btn.style.display = 'none';
          } else {
            btn.style.display = '';
          }
          if (hideClasses) {
            hideClasses.split(',').forEach(function (sel) {
              sel = sel.trim();
              if (!sel) return;
              document.querySelectorAll(sel).forEach(function (el) {
                el.style.display = show ? '' : 'none';
              });
            });
          }
          if (showClasses) {
            showClasses.split(',').forEach(function (sel) {
              sel = sel.trim();
              if (!sel) return;
              document.querySelectorAll(sel).forEach(function (el) {
                // Inverse : quand on ferme (show=true) → masquer, quand on ouvre (show=false) → afficher
                el.style.display = show ? 'none' : '';
              });
            });
          }
        }

        // Déplacer le contenu dans un conteneur cible :
        // 1. data-bt-reveal-inline → ne pas déplacer (contenu reste sous le bouton)
        // 2. data-bt-reveal-target="id" → ID explicite (legacy)
        // 3. Auto-détection du widget BT — Tarifs Body ([data-bt-pricing-body])
        // Après le déplacement, on copie la classe elementor-element-XXX
        // du widget source pour que les sélecteurs {{WRAPPER}} restent valides.
        var isInline = wrap.hasAttribute('data-bt-reveal-inline');
        var targetId = wrap.getAttribute('data-bt-reveal-target');
        var target   = isInline ? null : (targetId ? document.getElementById(targetId) : document.querySelector('[data-bt-pricing-body]'));
        if (target && target !== wrap && !wrap.contains(target)) {
          target.appendChild(content);

          // Propager le scope CSS Elementor du widget source
          var sourceWidget = wrap.closest('.elementor-widget');
          if (sourceWidget) {
            sourceWidget.classList.forEach(function (cls) {
              if (cls.indexOf('elementor-element-') === 0 && cls !== 'elementor-element') {
                target.classList.add(cls);
              }
            });
          }
        }

        function openReveal(andScroll) {
          content.classList.add('bt-pricing__reveal-content--open');
          btn.setAttribute('aria-expanded', 'true');
          toggleHiddenEl(false);
          injectBookingLazy(content);
          btActivateLazyMedia(content);
          // Unload forfait cards from DOM if option is set
          if (wrap.hasAttribute('data-bt-hide-cards')) {
            var cards = wrap.parentNode && wrap.parentNode.querySelector('.bt-bprice');
            if (cards) cards.remove();
          }
          if (andScroll) {
            setTimeout(function () {
              var scrollEl = content.parentNode;
              if (scrollEl && scrollEl.nodeType === 1) scrollEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 150);
          }
        }

        btn.addEventListener('click', function () {
          var isOpen = content.classList.contains('bt-pricing__reveal-content--open');
          if (isOpen) {
            content.classList.remove('bt-pricing__reveal-content--open');
            btn.setAttribute('aria-expanded', 'false');
            toggleHiddenEl(true);
          } else {
            openReveal(true);
          }
        });

        function hashMatches() {
          var hash = window.location.hash.slice(1);
          return (wrapperId && hash === wrapperId) || (targetId && hash === targetId);
        }
        if (hashMatches()) openReveal(true);
        window.addEventListener('hashchange', function () {
          if (hashMatches()) openReveal(true);
        });

      } else if (mode === 'modal') {
        var dialog   = wrap.querySelector('.bt-pricing-modal');
        var closeBtn = dialog && dialog.querySelector('.bt-pricing-modal__close');
        if (!dialog) return;

        function openModal() {
          injectBookingLazy(dialog);
          btActivateLazyMedia(dialog);
          dialog.showModal();
        }

        btn.addEventListener('click', openModal);

        if (closeBtn) {
          closeBtn.addEventListener('click', function () { dialog.close(); });
        }

        // Clic sur le backdrop (en dehors du contenu) ferme la modal
        dialog.addEventListener('click', function (e) {
          var rect = dialog.getBoundingClientRect();
          var outsideX = e.clientX < rect.left || e.clientX > rect.right;
          var outsideY = e.clientY < rect.top  || e.clientY > rect.bottom;
          if (outsideX || outsideY) dialog.close();
        });

        // Ouverture auto via ancre #id
        if (wrapperId && window.location.hash === '#' + wrapperId) openModal();
        window.addEventListener('hashchange', function () {
          if (wrapperId && window.location.hash === '#' + wrapperId) openModal();
        });
      }
    });
  }

  /* ── Gallery Lightbox ────────────────────────────────────────────────────── */
  /*
   * Visionneuse custom : modal plein écran + strip de miniatures en bas.
   * Un seul DOM #bt-lb créé au premier usage (singleton).
   *
   * Deux templates :
   *   Template 1 — Slideshow : image unique + prev/next + strip thumbnails
   *   Template 2 — Grille CSS : 2 cols, nth-child(5n+1) span 2 (16:9), autres 4:3
   *                             Clic sur une image → bascule en template 1
   *
   * Déclencheurs :
   *   [data-bt-gallery-images] — wrapper gallery avec JSON des images
   *   [data-bt-lb-index]       — lien vers une image (index dans le tableau)
   *   [data-bt-lb-open]        — bouton "Voir toutes les photos" (ouvre à 0)
   *
   * Config (data attrs sur le wrapper gallery) :
   *   data-bt-lb-tpl="1|2"   — template par défaut (défaut: 1)
   *   data-bt-lb-toggle="yes" — afficher le bouton toggle (défaut: caché)
   *   data-bt-lb-gap="3"      — gap en px pour la grille template 2 (défaut: 3)
   */

  var _lb          = null;  // nœud DOM modal (singleton)
  var _lbImgs      = [];    // tableau { src, thumb, alt, caption }
  var _lbIdx       = 0;     // index courant (template 1)
  var _lbTpl       = 1;     // template actif : 1 ou 2
  var _lbHasToggle = false; // bouton toggle visible

  /* SVG icons pour le toggle bar */
  var _lbSvgSlide =
    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<rect x="2" y="5" width="20" height="14" rx="2"/>' +
      '<path d="M10 9l6 3-6 3V9z"/>' +
    '</svg>';

  var _lbSvgGrid =
    '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<rect x="3" y="3" width="7" height="7" rx="1"/>' +
      '<rect x="14" y="3" width="7" height="7" rx="1"/>' +
      '<rect x="3" y="14" width="7" height="7" rx="1"/>' +
      '<rect x="14" y="14" width="7" height="7" rx="1"/>' +
    '</svg>';

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
      /* Toggle bar Template 1 / 2 — visible uniquement si .bt-lb--has-toggle */
      '<div class="bt-lb__tpl-bar" role="group" aria-label="Mode d\'affichage">' +
        '<button class="bt-lb__tpl-btn bt-lb__tpl-btn--active" data-tpl="1" aria-label="Diaporama" aria-pressed="true">' +
          _lbSvgSlide +
        '</button>' +
        '<button class="bt-lb__tpl-btn" data-tpl="2" aria-label="Grille" aria-pressed="false">' +
          _lbSvgGrid +
        '</button>' +
      '</div>' +
      '<div class="bt-lb__counter"></div>' +
      '<button class="bt-lb__prev" aria-label="Image précédente">&#8249;</button>' +
      '<button class="bt-lb__next" aria-label="Image suivante">&#8250;</button>' +
      /* Template 1 — zone image principale */
      '<div class="bt-lb__stage">' +
        '<img class="bt-lb__img" src="" alt="" />' +
        '<p class="bt-lb__caption"></p>' +
      '</div>' +
      /* Template 2 — grille CSS */
      '<div class="bt-lb__grid" role="list"></div>' +
      '<div class="bt-lb__thumbs"></div>';
    document.body.appendChild(d);
    _lb = d;

    _lb.querySelector('.bt-lb__close').addEventListener('click', _lbClose);
    _lb.querySelector('.bt-lb__prev').addEventListener('click', function () { _lbGo(-1); });
    _lb.querySelector('.bt-lb__next').addEventListener('click', function () { _lbGo(1); });

    /* Toggle bar — délégation de clic unique */
    _lb.querySelector('.bt-lb__tpl-bar').addEventListener('click', function (e) {
      var btn = e.target.closest('.bt-lb__tpl-btn');
      if (!btn) return;
      var tpl = parseInt(btn.getAttribute('data-tpl'), 10);
      if (tpl && tpl !== _lbTpl) _lbSetTemplate(tpl, true);
    });

    /* Click sur le fond → ferme */
    _lb.addEventListener('click', function (e) { if (e.target === _lb) _lbClose(); });

    /* Keyboard */
    document.addEventListener('keydown', function (e) {
      if (!_lb || !_lb.classList.contains('bt-lb--open')) return;
      if (e.key === 'Escape') _lbClose();
      /* Flèches : navigation uniquement en template 1 */
      if (_lbTpl === 1) {
        if (e.key === 'ArrowLeft')  _lbGo(-1);
        if (e.key === 'ArrowRight') _lbGo(1);
      }
    });

    /* Touch swipe — template 1 uniquement */
    var _tx = 0;
    _lb.addEventListener('touchstart', function (e) { _tx = e.touches[0].clientX; }, { passive: true });
    _lb.addEventListener('touchend',   function (e) {
      if (_lbTpl !== 1) return;
      var dx = e.changedTouches[0].clientX - _tx;
      if (Math.abs(dx) > 40) _lbGo(dx < 0 ? 1 : -1);
    }, { passive: true });
  }

  /**
   * Ouvre la visionneuse.
   *
   * @param {Array}  images   Tableau { src, thumb, alt, caption }
   * @param {number} idx      Index de l'image à afficher (template 1)
   * @param {Object} opts     Options : tpl (1|2), showToggle (bool), gap (px)
   */
  function _lbOpen(images, idx, opts) {
    opts = opts || {};
    _lbCreate();
    _lbImgs = images;
    _lbIdx  = idx;

    /* Réinitialise la grille pour le nouveau jeu d'images */
    var grid = _lb.querySelector('.bt-lb__grid');
    if (grid) grid.removeAttribute('data-lb-rendered');

    _lbRenderThumbs();

    /* Toggle bar — ajoute/retire la classe selon la config du widget */
    _lbHasToggle = opts.showToggle === true;
    _lb.classList.toggle('bt-lb--has-toggle', _lbHasToggle);

    /* Gap grille template 2 (appliqué dans _lbRenderGrid via inline style) */
    _lb._btGap = (opts.gap !== undefined && !isNaN(opts.gap)) ? parseFloat(opts.gap) : 3;

    /* Applique le template initial */
    _lbSetTemplate(opts.tpl === 2 ? 2 : 1, false);

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

    capEl.textContent   = cur.caption || '';
    capEl.style.display = cur.caption ? '' : 'none';
    if (cntEl) cntEl.textContent = (idx + 1) + ' / ' + _lbImgs.length;

    _lbSyncThumb(idx);
  }

  /**
   * Bascule entre template 1 (slideshow) et template 2 (grille).
   *
   * @param {number}  tpl   1 ou 2
   * @param {boolean} fade  Animer la transition image (template 1)
   */
  function _lbSetTemplate(tpl, fade) {
    _lbTpl = tpl;
    _lb.classList.toggle('bt-lb--tpl2', tpl === 2);

    /* Sync aria-pressed + classe active sur les boutons toggle */
    _lb.querySelectorAll('.bt-lb__tpl-btn').forEach(function (btn) {
      var isActive = parseInt(btn.getAttribute('data-tpl'), 10) === tpl;
      btn.classList.toggle('bt-lb__tpl-btn--active', isActive);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    if (tpl === 2) {
      _lbRenderGrid();
    } else {
      /* Template 1 : affiche l'image courante */
      _lbSetImg(_lbIdx, !!fade);
      _lbSyncThumb(_lbIdx);
    }
  }

  /**
   * Construit la grille Template 2.
   * Idempotent : marqueur data-lb-rendered empêche la reconstruction.
   */
  function _lbRenderGrid() {
    var grid = _lb.querySelector('.bt-lb__grid');
    if (!grid) return;
    if (grid.getAttribute('data-lb-rendered') === 'true') return;

    /* Gap configurable depuis le widget */
    var gap = (_lb._btGap !== undefined) ? _lb._btGap : 3;
    grid.style.gap = gap + 'px';

    var frag = document.createDocumentFragment();
    _lbImgs.forEach(function (im, i) {
      var item = document.createElement('div');
      item.className = 'bt-lb__grid-item';
      item.setAttribute('role', 'listitem');
      item.setAttribute('tabindex', '0');
      item.setAttribute('aria-label', 'Photo ' + (i + 1) + ' sur ' + _lbImgs.length);
      item.setAttribute('data-lb-grid-i', i);

      var img = document.createElement('img');
      img.src     = im.thumb || im.src;
      img.alt     = im.alt || '';
      img.loading = i < 10 ? 'eager' : 'lazy';
      item.appendChild(img);
      frag.appendChild(item);
    });

    grid.innerHTML = '';
    grid.appendChild(frag);
    grid.setAttribute('data-lb-rendered', 'true');

    /* Délégation de clic unique : click → bascule en template 1 à cet index */
    grid.addEventListener('click', function (e) {
      var item = e.target.closest('.bt-lb__grid-item');
      if (!item) return;
      var i = parseInt(item.getAttribute('data-lb-grid-i'), 10);
      if (!isNaN(i)) {
        _lbIdx = i;
        _lbSetTemplate(1, false);
      }
    });

    /* Support clavier sur les items de la grille (Enter / Space) */
    grid.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      var item = e.target.closest('.bt-lb__grid-item');
      if (!item) return;
      e.preventDefault();
      var i = parseInt(item.getAttribute('data-lb-grid-i'), 10);
      if (!isNaN(i)) {
        _lbIdx = i;
        _lbSetTemplate(1, false);
      }
    });
  }

  function _lbRenderThumbs() {
    var strip = _lb.querySelector('.bt-lb__thumbs');
    strip.innerHTML = '';
    var frag = document.createDocumentFragment();
    _lbImgs.forEach(function (im, i) {
      var btn = document.createElement('button');
      btn.className = 'bt-lb__thumb';
      btn.setAttribute('aria-label', 'Image ' + (i + 1));
      btn.setAttribute('data-lb-i', i);
      var timg = document.createElement('img');
      timg.src     = im.thumb || im.src;
      timg.alt     = '';
      timg.loading = 'lazy';
      btn.appendChild(timg);
      frag.appendChild(btn);
    });
    strip.appendChild(frag);
    /* Délégation de clic unique */
    strip.addEventListener('click', function (e) {
      var btn = e.target.closest('.bt-lb__thumb');
      if (!btn) return;
      var i = parseInt(btn.getAttribute('data-lb-i'), 10);
      if (!isNaN(i)) { _lbIdx = i; _lbSetImg(i, true); }
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

  /* ── Gallery skeleton — détecte le chargement des images ──────────────── */
  function initGallerySkeleton(el) {
    el.querySelectorAll('.bt-gallery__link:not(.bt-gallery__link--loaded)').forEach(function (link) {
      var img = link.querySelector('.bt-gallery__img');
      if (!img) return;
      if (img.complete && img.naturalWidth > 0) {
        link.classList.add('bt-gallery__link--loaded');
      } else {
        img.addEventListener('load',  function () { link.classList.add('bt-gallery__link--loaded'); });
        img.addEventListener('error', function () { link.classList.add('bt-gallery__link--loaded'); });
      }
    });
  }

  function initGallery(el) {
    initGallerySkeleton(el);
    el.querySelectorAll('[data-bt-gallery]:not([data-bt-g-init])').forEach(function (gallery) {
      gallery.setAttribute('data-bt-g-init', '1');

      var raw = gallery.getAttribute('data-bt-gallery-images');
      if (!raw) return;

      var images;
      try { images = JSON.parse(raw); } catch (e) { return; }
      if (!images || !images.length) return;

      /**
       * Lit la configuration popup depuis les data-attributes du wrapper.
       * @returns {{ tpl: number, showToggle: boolean, gap: number }}
       */
      function _galleryOpts() {
        return {
          tpl:        parseInt(gallery.getAttribute('data-bt-lb-tpl')    || '1', 10),
          showToggle: gallery.getAttribute('data-bt-lb-toggle')           === 'yes',
          gap:        parseFloat(gallery.getAttribute('data-bt-lb-gap')  || '3'),
        };
      }

      /* Click sur une image individuelle */
      gallery.querySelectorAll('[data-bt-lb-index]').forEach(function (a) {
        a.addEventListener('click', function (e) {
          e.preventDefault();
          _lbOpen(images, parseInt(a.getAttribute('data-bt-lb-index'), 10) || 0, _galleryOpts());
        });
      });

      /* Click "Voir toutes les photos" — ouvre à l'index 0 */
      var openBtn = gallery.querySelector('[data-bt-lb-open]');
      if (openBtn) {
        openBtn.addEventListener('click', function (e) {
          e.preventDefault();
          _lbOpen(images, 0, _galleryOpts());
        });
      }
    });
  }

  /* ── Bootstrap ───────────────────────────────────────────────────────────── */

  function initCollapsibleBlock(block) {
    if (block.getAttribute('data-bt-collapse-init')) return;
    block.setAttribute('data-bt-collapse-init', '1');

    var mode = block.getAttribute('data-bt-collapsible') || '';
    var trigger = block.querySelector('.bt-collapsible-block__trigger');
    var panel = block.querySelector('.bt-collapsible-block__panel');
    if (!trigger || !panel) return;

    function isActive() {
      var w = window.innerWidth || document.documentElement.clientWidth;
      if (mode === 'mobile') return w < 768;
      if (mode === 'pc') return w >= 768;
      if (mode === 'mobile_and_pc') return true;
      return false;
    }

    function setPanelHeight(open) {
      if (open) {
        panel.style.maxHeight = panel.scrollHeight + 'px';
        block.classList.add('bt-collapsible-block--open');
        trigger.setAttribute('aria-expanded', 'true');
      } else {
        panel.style.maxHeight = '0px';
        block.classList.remove('bt-collapsible-block--open');
        trigger.setAttribute('aria-expanded', 'false');
      }
    }

    if (!isActive()) {
      panel.style.maxHeight = 'none';
      return;
    }
    setPanelHeight(false);

    trigger.addEventListener('click', function () {
      if (!isActive()) return;
      var open = block.classList.contains('bt-collapsible-block--open');
      setPanelHeight(!open);
    });

    window.addEventListener('resize', function () {
      if (!isActive()) {
        panel.style.maxHeight = 'none';
        block.classList.add('bt-collapsible-block--open');
        trigger.setAttribute('aria-expanded', 'true');
      } else {
        if (!block.classList.contains('bt-collapsible-block--open')) {
          panel.style.maxHeight = '0px';
        }
      }
    });
  }

  /* ══════════════════════════════════════════════════════════════════════════
     PRICING BODY TRIGGER — data-bt-pricing-trigger sur n'importe quel élément
     ══════════════════════════════════════════════════════════════════════════ */

  function initPricingBodyTrigger(el) {
    el.querySelectorAll('[data-bt-pricing-trigger]:not([data-bt-ptr-init])').forEach(function (trigger) {
      trigger.setAttribute('data-bt-ptr-init', '1');

      var targetId = trigger.getAttribute('data-bt-pricing-trigger');

      function handleTrigger(e) {
        e.preventDefault();
        e.stopPropagation();

        var body = targetId
          ? document.getElementById(targetId)
          : document.querySelector('[data-bt-pricing-body]');
        if (!body) return;

        var isHidden = body.classList.contains('bt-pricing-body--hidden');
        body.classList.toggle('bt-pricing-body--hidden');
        body.setAttribute('aria-hidden', isHidden ? 'false' : 'true');
        trigger.setAttribute('aria-expanded', isHidden ? 'true' : 'false');

        // Masquer le bouton trigger quand le body est ouvert
        trigger.style.display = isHidden ? 'none' : '';

        // ── Hide/Show classes (lues depuis le body pricing) ─────────────
        var hideRaw = body.getAttribute('data-bt-body-hide') || '';
        var showRaw = body.getAttribute('data-bt-body-show') || '';

        if (hideRaw) {
          hideRaw.split(',').forEach(function (sel) {
            sel = sel.trim();
            if (!sel) return;
            document.querySelectorAll(sel).forEach(function (el) {
              // isHidden = était masqué → on ouvre → cacher les éléments
              el.style.display = isHidden ? 'none' : '';
            });
          });
        }

        if (showRaw) {
          showRaw.split(',').forEach(function (sel) {
            sel = sel.trim();
            if (!sel) return;
            document.querySelectorAll(sel).forEach(function (el) {
              // isHidden = était masqué → on ouvre → afficher les éléments
              el.style.display = isHidden ? '' : 'none';
            });
          });
        }

        // Masquer les divs [data-bt-pricing-div] sur mobile quand le body s'ouvre
        if (window.innerWidth < 768) {
          document.querySelectorAll('[data-bt-pricing-div]').forEach(function (div) {
            var divTarget = div.getAttribute('data-bt-pricing-div');
            // Cibler si : pas de valeur (global), ou valeur correspond au targetId du trigger
            if (!divTarget || divTarget === targetId || (!targetId && !divTarget)) {
              div.classList.toggle('bt-pricing-div--hidden', isHidden);
            }
          });
        }

        if (isHidden) {
          btActivateLazyMedia(body);
          body.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      }

      // Écouter le clic sur le wrapper ET sur tous les liens/boutons enfants
      trigger.addEventListener('click', handleTrigger);
      trigger.querySelectorAll('a, button').forEach(function (child) {
        child.addEventListener('click', handleTrigger);
      });
    });
  }

  /* ── Highlights Slider (Swiper) ─────────────────────────────────────────── */

  /**
   * Breakpoints : desktop ≥ 1025, tablet 768–1024, mobile < 768.
   * Si le device courant n'est pas dans la liste slider_devices, on détruit
   * Swiper et on remet le fallback grid/list. Sinon on init Swiper.
   */
  var _hlSwipers = {}; // uid → { swiper, config, wrap }

  function _hlGetDevice() {
    var w = window.innerWidth || document.documentElement.clientWidth;
    if (w >= 1025) return 'desktop';
    if (w >= 768)  return 'tablet';
    return 'mobile';
  }

  function _hlBuildSwiperOpts(config, wrap) {
    var device = _hlGetDevice();
    var opts = {
      slidesPerView: config.slidesPerView[device] || 1,
      spaceBetween:  config.spaceBetween[device]  || 16,
      speed:         config.speed || 400,
      loop:          config.loop,
      grabCursor:    true,
      breakpoints: {
        0: {
          slidesPerView: config.slidesPerView.mobile  || 1,
          spaceBetween:  config.spaceBetween.mobile   || 8,
        },
        768: {
          slidesPerView: config.slidesPerView.tablet  || 2,
          spaceBetween:  config.spaceBetween.tablet   || 12,
        },
        1025: {
          slidesPerView: config.slidesPerView.desktop || 3,
          spaceBetween:  config.spaceBetween.desktop  || 16,
        },
      },
    };

    if (config.autoplay) {
      opts.autoplay = {
        delay: config.autoplaySpeed || 4000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      };
    }

    if (config.arrows) {
      opts.navigation = {
        prevEl: wrap.querySelector('.bt-highlights__arrow--prev'),
        nextEl: wrap.querySelector('.bt-highlights__arrow--next'),
      };
    }

    if (config.dots) {
      var dotsEl = wrap.querySelector('.bt-highlights__dots');
      opts.pagination = {
        el: dotsEl,
        clickable: true,
        bulletClass: 'bt-highlights__dot',
        bulletActiveClass: 'bt-highlights__dot--active',
      };
    }

    return opts;
  }

  function _hlInitOne(wrap) {
    var uid = wrap.id;
    var raw = wrap.getAttribute('data-bt-highlights-slider');
    if (!raw) return;

    var config;
    try { config = JSON.parse(raw); } catch (e) { return; }

    var swiperEl = wrap.querySelector('.bt-highlights__swiper');
    if (!swiperEl) return;

    // Stocke les données
    _hlSwipers[uid] = { swiper: null, config: config, wrap: wrap, swiperEl: swiperEl };

    _hlCheckDevice(uid);
  }

  function _hlApplyFallback(data) {
    var device  = _hlGetDevice();
    var config  = data.config;
    var wrapper = data.swiperEl.querySelector('.swiper-wrapper');
    if (!wrapper) return;

    var isListLayout = config.layout === 'list';
    var cols = config.columns[device] || 3;
    var gap  = config.spaceBetween[device] || 16;

    if (isListLayout) {
      wrapper.style.display = 'flex';
      wrapper.style.flexDirection = 'column';
      wrapper.style.gap = gap + 'px';
      wrapper.style.gridTemplateColumns = '';
    } else {
      wrapper.style.display = 'grid';
      wrapper.style.gridTemplateColumns = 'repeat(' + cols + ', 1fr)';
      wrapper.style.gap = gap + 'px';
      wrapper.style.flexDirection = '';
    }
    wrapper.style.transform = 'none';

    // Reset inline widths que Swiper met sur les slides
    var slides = wrapper.querySelectorAll('.swiper-slide');
    for (var i = 0; i < slides.length; i++) {
      slides[i].style.width = '';
      slides[i].style.marginRight = '';
    }
  }

  function _hlClearFallback(data) {
    var wrapper = data.swiperEl.querySelector('.swiper-wrapper');
    if (!wrapper) return;
    wrapper.style.display = '';
    wrapper.style.gridTemplateColumns = '';
    wrapper.style.gap = '';
    wrapper.style.flexDirection = '';
    wrapper.style.transform = '';
  }

  function _hlCheckDevice(uid) {
    var data = _hlSwipers[uid];
    if (!data) return;

    var device  = _hlGetDevice();
    var devices = data.config.devices || ['desktop', 'tablet', 'mobile'];
    var shouldSlide = devices.indexOf(device) !== -1;

    if (shouldSlide && !data.swiper) {
      // Activer le slider
      _hlClearFallback(data);
      data.wrap.classList.add('bt-highlights__slider-wrap--active');
      data.wrap.classList.remove('bt-highlights__slider-wrap--fallback');

      if (typeof Swiper !== 'undefined') {
        var opts = _hlBuildSwiperOpts(data.config, data.wrap);
        data.swiper = new Swiper(data.swiperEl, opts);
      }
    } else if (!shouldSlide && data.swiper) {
      // Désactiver le slider → fallback grid/list
      data.swiper.destroy(true, true);
      data.swiper = null;
      data.wrap.classList.remove('bt-highlights__slider-wrap--active');
      data.wrap.classList.add('bt-highlights__slider-wrap--fallback');
      _hlApplyFallback(data);
    } else if (!shouldSlide && !data.swiper) {
      data.wrap.classList.remove('bt-highlights__slider-wrap--active');
      data.wrap.classList.add('bt-highlights__slider-wrap--fallback');
      _hlApplyFallback(data);
    }
  }

  // Debounced resize
  var _hlResizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(_hlResizeTimer);
    _hlResizeTimer = setTimeout(function () {
      Object.keys(_hlSwipers).forEach(_hlCheckDevice);
    }, 200);
  });

  function initHighlightsSlider(el) {
    el.querySelectorAll('[data-bt-highlights-slider]:not([data-bt-hl-init])').forEach(function (wrap) {
      wrap.setAttribute('data-bt-hl-init', '1');
      _hlInitOne(wrap);
    });
  }

  function boot(scope) {
    var el = (scope && scope !== document) ? scope : document;

    el.querySelectorAll('.bt-collapsible-block[data-bt-collapsible]').forEach(initCollapsibleBlock);

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
    initPricingTrigger(el);
    initPricingBodyTrigger(el);

    // Masquer les éléments "show" au chargement si le body pricing est hidden
    el.querySelectorAll('[data-bt-pricing-body][data-bt-body-show]').forEach(function (body) {
      if (!body.classList.contains('bt-pricing-body--hidden')) return;
      var showRaw = body.getAttribute('data-bt-body-show') || '';
      showRaw.split(',').forEach(function (sel) {
        sel = sel.trim();
        if (!sel) return;
        document.querySelectorAll(sel).forEach(function (e) { e.style.display = 'none'; });
      });
    });

    initGallery(el);
    initHighlightsSlider(el);
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
    elementorFrontend.elementsHandler.attachHandler('bt-highlights',    BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-included-excluded', BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-title-icon-desc',   BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-taxonomy-display', BtWidgetHandler);
    elementorFrontend.elementsHandler.attachHandler('bt-pricing-body',    BtWidgetHandler);
  });

  // Fallback : pages sans Elementor JS (ex. thème sans Elementor)
  document.addEventListener('DOMContentLoaded', function () { boot(document); });

  // API publique (réutilisable par d'autres scripts)
  window.btWidgets = { boot: boot };
}());
