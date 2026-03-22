/**
 * BtQuoteSubSteps — Progressive disclosure intra-step
 *
 * Affiche un groupe de termes taxonomiques à la fois avec transitions CSS.
 * Groupes superposés via CSS grid (opacity/transform, pas de display:none).
 *
 * @param {Element}  container  — élément DOM (vidé + rempli par le composant)
 * @param {Array}    taxoGroups — [{key, label, multi, items:[{id,name}]}]
 * @param {Function} onComplete — appelé(selections) quand le récap est affiché
 */
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════════════════════
     COMPOSANT
     ══════════════════════════════════════════════════════════════════════ */

  function BtQuoteSubSteps(container, taxoGroups, onComplete) {
    if (!container || !Array.isArray(taxoGroups) || !taxoGroups.length) return;

    var self         = this;
    var total        = taxoGroups.length;
    var currentIndex = 0;
    var showingRecap = false;

    /** @type {Map<string, Set<number>>} */
    var selections = new Map();
    taxoGroups.forEach(function (g) { selections.set(g.key, new Set([0])); });

    /* ── Build DOM ──────────────────────────────────────────────────────── */

    container.innerHTML = '';
    container.className = 'bt-quote-substep';

    // Barre de progression
    var progBar = el('div', 'bt-quote-substep__progress');
    progBar.setAttribute('role', 'progressbar');
    progBar.setAttribute('aria-valuemin', '0');
    progBar.setAttribute('aria-valuemax', String(total));
    container.appendChild(progBar);

    // Compteur + libellé
    var header  = el('div', 'bt-quote-substep__header');
    var counter = el('span', 'bt-quote-substep__counter');
    var labelEl = el('p',    'bt-quote-substep__label');
    header.appendChild(counter);
    header.appendChild(labelEl);
    container.appendChild(header);

    // Groupes (tous dans la grille CSS, superposés)
    var groupsWrap = el('div', 'bt-quote-substep__groups');
    container.appendChild(groupsWrap);

    var groupEls = taxoGroups.map(buildGroupEl);
    groupEls.forEach(function (g) { groupsWrap.appendChild(g); });

    // Navigation prev / next
    var nav     = el('div', 'bt-quote-substep__nav');
    var btnBack = el('button', 'bt-quote-substep__btn-back');
    var btnNext = el('button', 'bt-quote-substep__btn-next');
    btnBack.type = 'button';
    btnNext.type = 'button';
    btnBack.setAttribute('hidden', '');
    nav.appendChild(btnBack);
    nav.appendChild(btnNext);
    container.appendChild(nav);

    // Zone récap (cachée initialement, sans display:none — on utilise aria-hidden)
    var recapEl = el('div', 'bt-quote-substep__recap');
    recapEl.setAttribute('aria-hidden', 'true');
    recapEl.style.display = 'none'; // display:none hors animation — OK ici (statique)
    container.appendChild(recapEl);

    /* ── Build group element ────────────────────────────────────────────── */

    function buildGroupEl(group, idx) {
      var wrap = el('div', 'bt-quote-substep__group');
      wrap.setAttribute('data-group-key', group.key);
      wrap.setAttribute('data-group-idx', String(idx));
      setGroupHidden(wrap, idx !== 0);

      // Chips container (événements délégués)
      var chips = el('div', 'bt-quote-substep__chips');
      chips.setAttribute('role', 'listbox');
      chips.setAttribute('aria-label', group.label);
      chips.setAttribute('aria-multiselectable', group.multi ? 'true' : 'false');

      chips.addEventListener('click', function (e) {
        var chip = e.target.closest('.bt-quote-substep__chip');
        if (chip) toggleChip(group, chip, chips);
      });
      chips.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          var chip = e.target.closest('.bt-quote-substep__chip');
          if (chip) { e.preventDefault(); toggleChip(group, chip, chips); }
        }
      });

      // Items : "Aucun" toujours en premier
      var allItems = [{ id: 0, name: 'Aucun' }].concat(
        group.items.filter(function (it) { return it.id !== 0; })
      );
      allItems.forEach(function (item) {
        var chip = el('div', 'bt-quote-substep__chip');
        chip.setAttribute('role', 'option');
        chip.setAttribute('tabindex', '0');
        chip.setAttribute('data-term-id', String(item.id));
        chip.setAttribute('data-term-name', item.name);
        chip.setAttribute('aria-selected', item.id === 0 ? 'true' : 'false');
        chip.textContent = item.name;
        if (item.id === 0) chip.classList.add('bt-quote-substep__chip--selected');
        chips.appendChild(chip);
      });

      wrap.appendChild(chips);
      return wrap;
    }

    /* ── Chip toggle ────────────────────────────────────────────────────── */

    function toggleChip(group, chip, chipsEl) {
      var termId  = parseInt(chip.getAttribute('data-term-id'), 10);
      var sel     = selections.get(group.key);
      var isAucun = (termId === 0);

      if (group.multi) {
        if (isAucun) {
          sel.clear();
          sel.add(0);
        } else {
          sel.delete(0);
          if (sel.has(termId)) {
            sel.delete(termId);
            if (sel.size === 0) sel.add(0);
          } else {
            sel.add(termId);
          }
        }
      } else {
        sel.clear();
        sel.add(termId);
      }

      // Sync aria-selected + classe --selected
      chipsEl.querySelectorAll('.bt-quote-substep__chip').forEach(function (c) {
        var id       = parseInt(c.getAttribute('data-term-id'), 10);
        var selected = sel.has(id);
        c.setAttribute('aria-selected', selected ? 'true' : 'false');
        c.classList.toggle('bt-quote-substep__chip--selected', selected);
      });
    }

    /* ── Navigation ─────────────────────────────────────────────────────── */

    function goTo(nextIdx, forward) {
      if (showingRecap) return;

      // Dernier "Passer" → afficher récap
      if (nextIdx >= total) {
        animGroup(groupEls[currentIndex], forward ? 'leave-left' : 'leave-right', function () {
          setGroupHidden(groupEls[currentIndex], true);
          showRecap();
        });
        updateUI(nextIdx); // progress bar + labels
        return;
      }

      if (nextIdx < 0) return;

      var oldGroup = groupEls[currentIndex];
      var newGroup = groupEls[nextIdx];
      var dir = forward ? 'right' : 'left';

      // Positionner le nouveau groupe hors champ (sans transition)
      setGroupTransform(newGroup, dir === 'right' ? 40 : -40, 0);
      setGroupHidden(newGroup, false);

      // Double rAF pour que le browser prenne en compte la position initiale
      raf2(function () {
        // Sortie du groupe actuel
        animGroup(oldGroup, forward ? 'leave-left' : 'leave-right', function () {
          setGroupHidden(oldGroup, true);
          setGroupTransform(oldGroup, 0, 0); // reset pour ré-utilisation
        });
        // Entrée du nouveau groupe
        setGroupTransform(newGroup, 0, 1);
      });

      currentIndex = nextIdx;
      updateUI(currentIndex);
    }

    /** Anime un groupe vers sa position de sortie, appelle cb après 350ms. */
    function animGroup(groupEl, direction, cb) {
      var tx = (direction === 'leave-left') ? -40 : 40;
      setGroupTransform(groupEl, tx, 0);
      setTimeout(cb, 360);
    }

    /** Applique transform + opacity inline (hors transition ou via transition CSS). */
    function setGroupTransform(groupEl, tx, opacity) {
      groupEl.style.transform = tx !== 0 ? 'translateX(' + tx + 'px)' : '';
      groupEl.style.opacity   = opacity < 1 ? String(opacity) : '';
    }

    /** aria-hidden + pointer-events. */
    function setGroupHidden(groupEl, hidden) {
      groupEl.setAttribute('aria-hidden', hidden ? 'true' : 'false');
      // pointer-events géré par CSS via aria-hidden="true"
    }

    function raf2(fn) {
      requestAnimationFrame(function () { requestAnimationFrame(fn); });
    }

    /* ── Récap ──────────────────────────────────────────────────────────── */

    function showRecap() {
      progBar.style.display    = 'none';
      header.style.display     = 'none';
      groupsWrap.style.display = 'none';
      nav.style.display        = 'none';
      recapEl.style.display    = '';
      recapEl.setAttribute('aria-hidden', 'false');
      recapEl.innerHTML = '';
      showingRecap = true;

      taxoGroups.forEach(function (group) {
        var sel           = selections.get(group.key);
        var selectedItems = group.items.filter(function (it) { return sel.has(it.id); });

        var groupWrap = el('div', 'bt-quote-substep__recap-group');
        var groupLbl  = el('span', 'bt-quote-substep__recap-group-label');
        groupLbl.textContent = group.label;

        var tagsWrap = el('div', 'bt-quote-substep__recap-tags');
        if (selectedItems.length === 0 || sel.has(0)) {
          var noneTag = el('span', 'bt-quote-substep__recap-tag bt-quote-substep__recap-tag--none');
          noneTag.textContent = 'Aucun';
          tagsWrap.appendChild(noneTag);
        } else {
          selectedItems.forEach(function (item) {
            var tag = el('span', 'bt-quote-substep__recap-tag');
            tag.textContent = item.name;
            tagsWrap.appendChild(tag);
          });
        }

        groupWrap.appendChild(groupLbl);
        groupWrap.appendChild(tagsWrap);
        recapEl.appendChild(groupWrap);
      });

      var recapActions = el('div', 'bt-quote-substep__recap-actions');

      var btnModify = el('button', 'bt-quote-substep__btn-modify');
      btnModify.type = 'button';
      btnModify.textContent = '← Modifier';
      btnModify.addEventListener('click', resetToStart);
      recapActions.appendChild(btnModify);

      var btnConfirm = el('button', 'bt-quote-substep__btn-confirm');
      btnConfirm.type = 'button';
      btnConfirm.textContent = 'Confirmer →';
      btnConfirm.addEventListener('click', function () {
        if (typeof onComplete === 'function') onComplete(selections);
      });
      recapActions.appendChild(btnConfirm);

      recapEl.appendChild(recapActions);
    }

    function resetToStart() {
      showingRecap = false;
      currentIndex = 0;

      progBar.style.display    = '';
      header.style.display     = '';
      groupsWrap.style.display = '';
      nav.style.display        = '';
      recapEl.style.display    = 'none';
      recapEl.setAttribute('aria-hidden', 'true');

      groupEls.forEach(function (g, i) {
        setGroupHidden(g, i !== 0);
        setGroupTransform(g, 0, i === 0 ? 1 : 0);
      });

      updateUI(0);
    }

    /* ── Mise à jour UI ─────────────────────────────────────────────────── */

    function updateUI(idx) {
      if (idx === undefined) idx = currentIndex;

      // Barre de progression
      progBar.innerHTML = '';
      progBar.setAttribute('aria-valuenow', String(idx + 1));
      for (var i = 0; i < total; i++) {
        var seg   = el('div', 'bt-quote-substep__prog-seg');
        var state = i < idx ? 'done' : (i === idx ? 'active' : 'pending');
        seg.classList.add('bt-quote-substep__prog-seg--' + state);
        progBar.appendChild(seg);
      }

      if (idx < total) {
        counter.textContent = (idx + 1) + ' / ' + total;
        labelEl.textContent  = taxoGroups[idx].label;
      }

      // Back button
      if (idx === 0) btnBack.setAttribute('hidden', '');
      else           btnBack.removeAttribute('hidden');

      // Next button label
      btnNext.textContent = (idx >= total - 1) ? 'Valider →' : 'Suivant →';
    }

    /* ── Bind navigation ────────────────────────────────────────────────── */

    btnNext.addEventListener('click', function () { goTo(currentIndex + 1, true);  });
    btnBack.addEventListener('click', function () { goTo(currentIndex - 1, false); });

    /* ── Init ───────────────────────────────────────────────────────────── */

    // Le premier groupe démarre visible
    setGroupTransform(groupEls[0], 0, 1);
    // Autres : position hors champ droite (sans transition initiale)
    groupEls.forEach(function (g, i) {
      if (i > 0) setGroupTransform(g, 40, 0);
    });
    updateUI(0);

    /* ── API publique ───────────────────────────────────────────────────── */

    /** @returns {Map<string, Set<number>>} */
    self.getSelections = function () { return selections; };

    /** Permet d'injecter le callback après construction (ex: auto-init avant wizard). */
    self.setOnComplete = function (fn) { onComplete = fn; };

    /** Réinitialise complètement le composant. */
    self.reset = function () {
      selections = new Map();
      taxoGroups.forEach(function (g) { selections.set(g.key, new Set([0])); });
      // Reset chips visuellement
      groupEls.forEach(function (g) {
        g.querySelectorAll('.bt-quote-substep__chip').forEach(function (chip) {
          var id = parseInt(chip.getAttribute('data-term-id'), 10);
          var selected = (id === 0);
          chip.setAttribute('aria-selected', selected ? 'true' : 'false');
          chip.classList.toggle('bt-quote-substep__chip--selected', selected);
        });
      });
      resetToStart();
    };
  }

  /* ══════════════════════════════════════════════════════════════════════
     UTILITAIRE
     ══════════════════════════════════════════════════════════════════════ */

  /** Crée un élément avec className. */
  function el(tag, cls) {
    var node = document.createElement(tag);
    if (cls) node.className = cls;
    return node;
  }

  /* ══════════════════════════════════════════════════════════════════════
     EXPORT GLOBAL
     ══════════════════════════════════════════════════════════════════════ */

  window.BtQuoteSubSteps = BtQuoteSubSteps;

  /* ══════════════════════════════════════════════════════════════════════
     AUTO-INIT (pour usage sans wizard parent)
     ══════════════════════════════════════════════════════════════════════ */

  function bootSubSteps(scope) {
    var root = (scope && scope !== document) ? scope : document;
    root.querySelectorAll('[data-bt-substep]:not([data-bt-substep-init])').forEach(function (el) {
      var config = [];
      try { config = JSON.parse(el.getAttribute('data-config') || '[]'); } catch (e) {}
      if (config.length) {
        el.setAttribute('data-bt-substep-init', '1');
        el._btSubstep = new BtQuoteSubSteps(el, config, null);
      }
    });
  }

  window.addEventListener('elementor/frontend/init', function () {
    var Handler = elementorModules.frontend.handlers.Base.extend({
      onInit: function () {
        elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);
        bootSubSteps(this.$element[0]);
      },
    });
    elementorFrontend.elementsHandler.attachHandler('bt-boat-pricing', Handler);
    elementorFrontend.elementsHandler.attachHandler('bt-quote-form',   Handler);
  });

  document.addEventListener('DOMContentLoaded', function () { bootSubSteps(document); });
}());
