/**
 * BlackTenders — Quote Form (bt-boat-pricing widget)
 *
 * Fonctionnalités :
 *   - Navigation multi-étapes (expand/collapse)
 *   - Chargement AJAX des bateaux par excursion
 *   - Popup bateau (dialog + focus trap)
 *   - Calendrier range-picker vanilla JS
 *   - Soumission AJAX du formulaire
 */
(function () {
  'use strict';

  /* ══════════════════════════════════════════════════════════════════════════
     STEP NAVIGATION
     ══════════════════════════════════════════════════════════════════════════ */

  function initQuoteForm(root) {
    if (root.getAttribute('data-bt-quote-init')) return;
    root.setAttribute('data-bt-quote-init', '1');

    var ajaxUrl = root.getAttribute('data-ajax-url');
    var nonce   = root.getAttribute('data-nonce');
    var config  = {};
    try { config = JSON.parse(root.getAttribute('data-config') || '{}'); } catch (e) {}

    var steps   = Array.from(root.querySelectorAll('.bt-quote-step'));
    var state   = { excursion_id: 0, excursion_name: '', boat_id: 0, boat_name: '', date_start: '', date_end: '', boat_options: {} };
    var pricingMode = config.pricing_mode || 'boat';

    // Auto-select excursion if pre-filled (inside root or in parent wrapper)
    var autoExc = root.querySelector('.bt-quote-exc-auto');
    if (!autoExc) autoExc = root.closest('.bt-bprice-wrapper')?.querySelector('.bt-quote-exc-auto');
    if (autoExc) {
      state.excursion_id   = parseInt(autoExc.getAttribute('data-exc-id'), 10) || 0;
      state.excursion_name = autoExc.querySelector('.bt-quote-exc-auto__name')?.textContent || '';
    }

    // Auto-select boat if pre-filled (excursion mode on boat page)
    var autoBoat = root.querySelector('.bt-quote-boat-auto');
    if (autoBoat) {
      state.boat_id   = parseInt(autoBoat.getAttribute('data-boat-id'), 10) || 0;
      state.boat_name = autoBoat.querySelector('.bt-quote-boat-auto__name')?.textContent || '';
    }

    // Auto-select from excursion dropdown if present
    var excDropdown = root.querySelector('[data-bt-exc-dropdown]');
    if (excDropdown && !state.excursion_id) {
      var selOpt = excDropdown.querySelector('.bt-quote-dd__opt--sel');
      if (selOpt) {
        state.excursion_id   = parseInt(selOpt.getAttribute('data-exc-id'), 10) || 0;
        state.excursion_name = selOpt.getAttribute('data-name') || '';
        state.exc_custom     = selOpt.hasAttribute('data-custom-trip') || state.excursion_id === 0;
      }
    }

    // Auto-select from boat dropdown if present
    var boatDropdown = root.querySelector('[data-bt-boat-dropdown]');
    if (boatDropdown && !state.boat_id) {
      var selOpt = boatDropdown.querySelector('.bt-quote-dd__opt--sel');
      if (selOpt) {
        state.boat_id   = parseInt(selOpt.getAttribute('data-boat-id'), 10) || 0;
        state.boat_name = selOpt.getAttribute('data-name') || '';
      }
    }

    // Step header click → go to that step
    steps.forEach(function (step) {
      var header = step.querySelector('.bt-quote-step__header');
      if (header) {
        header.addEventListener('click', function () {
          var idx = steps.indexOf(step);
          // Only allow clicking on completed or current steps
          var activeIdx = getActiveIndex();
          if (idx <= activeIdx) {
            activateStep(idx);
          }
        });
        header.style.cursor = 'pointer';
      }

      // Next button
      var nextBtn = step.querySelector('[data-step-next]');
      if (nextBtn) {
        nextBtn.addEventListener('click', function () {
          var idx = steps.indexOf(step);
          if (validateStep(step)) {
            collectStepData(step);
            activateStep(idx + 1);
            onStepEnter(steps[idx + 1]);
          }
        });
      }
    });

    function getActiveIndex() {
      for (var i = 0; i < steps.length; i++) {
        if (steps[i].classList.contains('bt-quote-step--active')) return i;
      }
      return 0;
    }

    function activateStep(idx) {
      if (idx < 0 || idx >= steps.length) return;
      steps.forEach(function (s, i) {
        var isActive    = (i === idx);
        var isDone      = (i < idx);
        var isCollapsed = (i > idx);
        s.classList.toggle('bt-quote-step--active', isActive);
        s.classList.toggle('bt-quote-step--done', isDone);
        s.classList.toggle('bt-quote-step--collapsed', isCollapsed);
        s.setAttribute('aria-expanded', isActive ? 'true' : 'false');
        if (isActive) {
          s.setAttribute('aria-current', 'step');
        } else {
          s.removeAttribute('aria-current');
        }
      });
      // Scroll into view
      steps[idx].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function validateStep(step) {
      var type = step.getAttribute('data-step-type');
      if (type === 'excursion') {
        // Check dropdown first
        var excDropdown = step.querySelector('[data-bt-exc-dropdown]');
        if (excDropdown) {
          var selId = parseInt(excDropdown.getAttribute('data-selected-id'), 10);
          var hidden = excDropdown.querySelector('[name="excursion_id"]');
          if (hidden) selId = parseInt(hidden.value, 10) || selId;
          if (selId > 0 || excDropdown.querySelector('.bt-quote-dd__opt--sel[data-custom-trip]')) {
            return true;
          }
        }
        // Fallback to card selection
        if (!state.excursion_id) {
          var selected = step.querySelector('.bt-quote-exc-card[aria-selected="true"]');
          if (!selected) {
            shakeFeedback(step);
            return false;
          }
        }
        return true;
      }
      if (type === 'boat') {
        // Check dropdown first
        var boatDropdown = step.querySelector('[data-bt-boat-dropdown]');
        if (boatDropdown) {
          var selOpt = boatDropdown.querySelector('.bt-quote-dd__opt--sel');
          if (selOpt) {
            var boatId = parseInt(selOpt.getAttribute('data-boat-id'), 10);
            if (boatId > 0) return true;
          }
        }
        // Fallback to card
        if (!state.boat_id) {
          shakeFeedback(step);
          return false;
        }
        return true;
      }
      if (type === 'dates') {
        var durType = step.querySelector('[name="duration_type"]');
        if (!durType || !durType.value) {
          shakeFeedback(step);
          return false;
        }
        // For half/full/multi, need a date
        if (durType.value === 'half' || durType.value === 'full') {
          var dateInp = step.querySelector('.bt-quote-datepicker--single [name="date_start"]');
          if (dateInp && !dateInp.value) { shakeFeedback(step); return false; }
        }
        // Pour demi-journée, le créneau est obligatoire
        if (durType.value === 'half') {
          var tsInp = step.querySelector('[name="timeslot"]');
          if (!tsInp || !tsInp.value) {
            // Highlight la section timeslot
            var tsWrap = step.querySelector('[data-bt-timeslot]');
            if (tsWrap) tsWrap.classList.add('bt-quote-timeslot--error');
            shakeFeedback(step);
            return false;
          }
        }
        if (durType.value === 'multi') {
          var startInp = step.querySelector('.bt-quote-datepicker--range [name="date_start"]');
          if (startInp && !startInp.value) { shakeFeedback(step); return false; }
        }
        return true;
      }
      if (type === 'boat-options') {
        return true; // Toujours valide — "Aucun" est pré-sélectionné sur chaque groupe
      }
      if (type === 'contact') {
        var required = step.querySelectorAll('[required]');
        var valid = true;
        required.forEach(function (input) {
          if (!input.value.trim()) {
            input.classList.add('bt-quote-fields__input--error');
            valid = false;
          } else {
            input.classList.remove('bt-quote-fields__input--error');
          }
        });
        // Email validation
        var emailInput = step.querySelector('[name="client_email"]');
        if (emailInput && emailInput.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
          emailInput.classList.add('bt-quote-fields__input--error');
          valid = false;
        }
        if (!valid) shakeFeedback(step);
        return valid;
      }
      return true;
    }

    function collectStepData(step) {
      var type = step.getAttribute('data-step-type');
      if (type === 'excursion') {
        // Check dropdown first
        var excDropdown = step.querySelector('[data-bt-exc-dropdown]');
        if (excDropdown) {
          var selOpt = excDropdown.querySelector('.bt-quote-dd__opt--sel');
          if (selOpt) {
            state.excursion_id   = parseInt(selOpt.getAttribute('data-exc-id'), 10) || 0;
            state.excursion_name = selOpt.getAttribute('data-name') || '';
            state.exc_custom     = selOpt.hasAttribute('data-custom-trip') || state.excursion_id === 0;
          }
        } else {
          // Fallback to card
          var excCard = step.querySelector('.bt-quote-exc-card[aria-selected="true"]');
          if (excCard) {
            state.excursion_id   = parseInt(excCard.getAttribute('data-exc-id'), 10) || 0;
            state.excursion_name = excCard.querySelector('.bt-quote-exc-card__title')?.textContent || '';
          }
        }
        // Collect custom request text if "sur mesure"
        var customTa = step.querySelector('[name="exc_custom_request"]');
        if (customTa && state.exc_custom) {
          state.exc_custom_text = customTa.value;
        }
        // Summary
        var summary = step.querySelector('.bt-quote-step__summary');
        if (summary) {
          summary.textContent = state.exc_custom ? 'Sur mesure' : state.excursion_name;
        }
      }
      if (type === 'boat') {
        // Check dropdown first
        var boatDropdown = step.querySelector('[data-bt-boat-dropdown]');
        if (boatDropdown) {
          var selOpt = boatDropdown.querySelector('.bt-quote-dd__opt--sel');
          if (selOpt) {
            state.boat_id   = parseInt(selOpt.getAttribute('data-boat-id'), 10) || 0;
            state.boat_name = selOpt.getAttribute('data-name') || '';
          }
        }
        var summary = step.querySelector('.bt-quote-step__summary');
        if (summary) summary.textContent = state.boat_name;
      }
      if (type === 'dates') {
        var durType = step.querySelector('[name="duration_type"]');
        state.duration_type = durType ? durType.value : '';

        // Timeslot (matin/après-midi) for half day
        var tsInp = step.querySelector('[name="timeslot"]');
        state.timeslot = (tsInp && state.duration_type === 'half') ? tsInp.value : '';

        // Collect the right date based on duration type
        if (state.duration_type === 'half' || state.duration_type === 'full') {
          var singleInp = step.querySelector('.bt-quote-datepicker--single [name="date_start"]');
          state.date_start = singleInp ? singleInp.value : '';
          state.date_end   = '';
        } else if (state.duration_type === 'multi') {
          var ms = step.querySelector('.bt-quote-datepicker--range [name="date_start"]');
          var me = step.querySelector('.bt-quote-datepicker--range [name="date_end"]');
          state.date_start = ms ? ms.value : '';
          state.date_end   = me ? me.value : '';
        } else if (state.duration_type === 'custom') {
          var ta = step.querySelector('[name="date_custom"]');
          state.date_start = ta ? ta.value : '';
          state.date_end   = '';
        }

        // Duration label for summary
        var durLabels = config.duration_options || {};
        var durLabel  = durLabels[state.duration_type] || state.duration_type;
        var summary   = step.querySelector('.bt-quote-step__summary');
        if (summary) {
          var sumText = durLabel;
          if (state.timeslot) sumText += ' (' + (state.timeslot === 'matin' ? 'Matin' : 'Après-midi') + ')';
          if (state.date_start) sumText += ' — ' + state.date_start;
          if (state.date_end) sumText += ' → ' + state.date_end;
          summary.textContent = sumText;
        }
      }
      if (type === 'contact') {
        var summary = step.querySelector('.bt-quote-step__summary');
        var email   = step.querySelector('[name="client_email"]');
        if (summary && email) summary.textContent = email.value;
      }
      if (type === 'boat-options') {
        // Lire depuis l'instance BtQuoteSubSteps
        var substepRoot = step.querySelector('[data-bt-substep]');
        var instance    = substepRoot && substepRoot._btSubstep;
        state.boat_options = {};
        if (instance) {
          instance.getSelections().forEach(function (sel, key) {
            var ids   = Array.from(sel).filter(function (id) { return id !== 0; });
            var names = [];
            // Récupérer les noms depuis le config stocké sur l'élément
            var config = [];
            try { config = JSON.parse(substepRoot.getAttribute('data-config') || '[]'); } catch (e) {}
            var group = config.find(function (g) { return g.key === key; });
            if (group) {
              names = ids.map(function (id) {
                var item = group.items.find(function (it) { return it.id === id; });
                return item ? item.name : '';
              }).filter(Boolean);
            }
            state.boat_options[key] = { ids: ids, names: names };
          });
        }
        var summary = step.querySelector('.bt-quote-step__summary');
        if (summary) {
          var chosen = Object.values(state.boat_options)
            .flatMap(function (v) { return v.names; });
          summary.textContent = chosen.length ? chosen.join(', ') : '—';
        }
      }
    }

    function onStepEnter(step) {
      if (!step) return;
      if (window.btActivateLazyMedia) window.btActivateLazyMedia(step);
      var type = step.getAttribute('data-step-type');
      if (type === 'boat') {
        var container = step.querySelector('[data-bt-quote-boats]');
        if (container && state.excursion_id) {
          loadBoats(step);
        } else {
          var staticContainer = step.querySelector('.bt-quote-boat-cards');
          if (staticContainer) {
            bindBoatCards(staticContainer, step);
            // Sync state depuis la card pré-sélectionnée (mode boat)
            if (!state.boat_id) {
              var preSelected = staticContainer.querySelector('.bt-quote-boat-card--selected');
              if (preSelected) {
                state.boat_id   = parseInt(preSelected.getAttribute('data-boat-id'), 10) || 0;
                state.boat_name = preSelected.querySelector('.bt-quote-boat-card__title')?.textContent || '';
              }
            }
          }
        }
      }
      if (type === 'boat-options') {
        var substepEl = step.querySelector('[data-bt-substep]');
        if (substepEl && window.BtQuoteSubSteps) {
          // Callback: passe directement à l'étape suivante quand le récap substep est confirmé
          var substepOnComplete = function () {
            collectStepData(step);
            var idx = steps.indexOf(step);
            activateStep(idx + 1);
            onStepEnter(steps[idx + 1]);
          };
          if (!substepEl.getAttribute('data-bt-substep-init')) {
            // Première init — passe le callback directement
            var substepConfig = [];
            try { substepConfig = JSON.parse(substepEl.getAttribute('data-config') || '[]'); } catch (e) {}
            substepEl.setAttribute('data-bt-substep-init', '1');
            substepEl._btSubstep = new window.BtQuoteSubSteps(substepEl, substepConfig, substepOnComplete);
          } else if (substepEl._btSubstep && substepEl._btSubstep.setOnComplete) {
            // Auto-init déjà passé avec onComplete=null → injecter le vrai callback
            substepEl._btSubstep.setOnComplete(substepOnComplete);
          }
        }
      }
      if (type === 'dates') {
        initDurationCards(step);
      }
      if (type === 'submit') {
        buildRecap(step);
      }
    }

    /* ════════════════════════════════════════════════════════════════════════
       DURATION CARDS SELECTION (Step Dates)
       ════════════════════════════════════════════════════════════════════════ */

    function initDurationCards(step) {
      var wrap = step.querySelector('[data-bt-duration-select]');
      if (!wrap || wrap.getAttribute('data-dur-init')) return;
      wrap.setAttribute('data-dur-init', '1');

      var cards      = wrap.querySelectorAll('.bt-quote-duration-card');
      var singlePick = step.querySelector('.bt-quote-datepicker--single');
      var rangePick  = step.querySelector('.bt-quote-datepicker--range');
      var customArea = step.querySelector('.bt-quote-custom-dates');
      var hiddenInp  = step.querySelector('[name="duration_type"]');
      var timeslot   = step.querySelector('[data-bt-timeslot]');

      // Timeslot (matin/après-midi) buttons
      if (timeslot) {
        var tsBtns    = timeslot.querySelectorAll('.bt-quote-timeslot__btn');
        var tsHidden  = timeslot.querySelector('[name="timeslot"]');
        tsBtns.forEach(function (btn) {
          btn.addEventListener('click', function () {
            // Retirer l'état d'erreur dès qu'on sélectionne
            timeslot.classList.remove('bt-quote-timeslot--error');

            tsBtns.forEach(function (b) {
              b.setAttribute('aria-selected', 'false');
              b.classList.remove('bt-quote-timeslot__btn--selected');
            });
            btn.setAttribute('aria-selected', 'true');
            btn.classList.add('bt-quote-timeslot__btn--selected');
            if (tsHidden) tsHidden.value = btn.getAttribute('data-timeslot');

            // Mettre à jour le résumé de date pour refléter le créneau
            if (singlePick && singlePick._updateDateSummary) {
              singlePick._updateDateSummary();
            }
          });
        });
      }

      cards.forEach(function (card) {
        card.addEventListener('click', function () {
          cards.forEach(function (c) {
            c.setAttribute('aria-selected', 'false');
            c.classList.remove('bt-quote-duration-card--selected');
          });
          card.setAttribute('aria-selected', 'true');
          card.classList.add('bt-quote-duration-card--selected');

          var dur = card.getAttribute('data-duration');
          if (hiddenInp) hiddenInp.value = dur;

          // Show/hide sub-sections
          if (singlePick) singlePick.style.display = (dur === 'half' || dur === 'full') ? '' : 'none';
          if (rangePick)  rangePick.style.display  = (dur === 'multi') ? '' : 'none';
          if (customArea) customArea.style.display = (dur === 'custom') ? '' : 'none';

          // Timeslot visible uniquement pour demi-journée, caché pour journée
          if (timeslot) timeslot.style.display = (dur === 'half') ? '' : 'none';

          // Init datepicker lazily
          if (dur === 'half' || dur === 'full') {
            initDatepicker(step, singlePick);
          } else if (dur === 'multi') {
            initDatepicker(step, rangePick);
          }

          // Update boat card prices based on selected duration
          updateBoatCardPrices(dur);
        });

        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        });
      });
    }

    /* ════════════════════════════════════════════════════════════════════════
       DYNAMIC BOAT CARD PRICE UPDATE
       ════════════════════════════════════════════════════════════════════════ */

    var durLabelsShort = { half: 'demi-journée', full: 'journée complète', multi: 'plusieurs jours' };

    function updateBoatCardPrices(durationType) {
      var boatCards = root.querySelectorAll('.bt-quote-boat-card[data-pax-max]');
      boatCards.forEach(function (card) {
        var priceHalf = parseFloat(card.getAttribute('data-price-half')) || 0;
        var priceFull = parseFloat(card.getAttribute('data-price-full')) || 0;
        var priceMin  = parseFloat(card.getAttribute('data-price-min')) || 0;
        var paxMax    = parseInt(card.getAttribute('data-pax-max'), 10) || 0;

        var priceEl = card.querySelector('.bt-quote-boat-card__price');
        var ppEl    = card.querySelector('.bt-quote-boat-card__pp');
        if (!priceEl || !paxMax) return;

        var basePrice = 0;

        if (durationType === 'half' && priceHalf) {
          basePrice = priceHalf;
        } else if ((durationType === 'full' || durationType === 'multi') && priceFull) {
          basePrice = priceFull;
        } else {
          basePrice = priceMin || priceHalf || priceFull;
        }

        if (basePrice) {
          priceEl.textContent = basePrice.toLocaleString('fr-FR');
          if (ppEl) {
            var pp = Math.ceil(basePrice / paxMax);
            ppEl.textContent = pp + ' € ';
            var perSpan = document.createElement('span');
            perSpan.className = 'bt-quote-boat-card__per';
            perSpan.textContent = '/ pers.';
            ppEl.appendChild(perSpan);
          }
        }
      });
    }

    function shakeFeedback(step) {
      step.classList.add('bt-quote-step--shake');
      setTimeout(function () { step.classList.remove('bt-quote-step--shake'); }, 500);
    }

    /* ════════════════════════════════════════════════════════════════════════
       EXCURSION DROPDOWN — Sélection excursion via dropdown
       ════════════════════════════════════════════════════════════════════════ */

    root.querySelectorAll('[data-bt-exc-dropdown]').forEach(function (dd) {
      initDropdown(dd, function (opt) {
        var excId   = parseInt(opt.getAttribute('data-exc-id'), 10) || 0;
        var excName = opt.getAttribute('data-name') || '';
        var isCustom = opt.hasAttribute('data-custom-trip') || excId === 0;

        state.excursion_id   = excId;
        state.excursion_name = excName;
        state.exc_custom     = isCustom;

        // Update hidden input
        var hidden = dd.querySelector('[name="excursion_id"]');
        if (hidden) hidden.value = excId;
      });
    });

    /* ════════════════════════════════════════════════════════════════════════
       BOAT DROPDOWN — Sélection bateau via dropdown
       ════════════════════════════════════════════════════════════════════════ */

    root.querySelectorAll('[data-bt-boat-dropdown]').forEach(function (dd) {
      initDropdown(dd, function (opt) {
        var boatId   = parseInt(opt.getAttribute('data-boat-id'), 10) || 0;
        var boatName = opt.getAttribute('data-name') || '';

        state.boat_id   = boatId;
        state.boat_name = boatName;

        // Update hidden input
        var hidden = dd.querySelector('[name="boat_id"]');
        if (hidden) hidden.value = boatId;
      });
    });

    /**
     * Initialize a dropdown with open/close, selection, outside click
     */
    function initDropdown(dd, onSelect) {
      var trigger = dd.querySelector('.bt-quote-dd__trigger');
      var menu    = dd.querySelector('.bt-quote-dd__menu');
      if (!trigger || !menu) return;

      // Toggle on trigger click
      trigger.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = trigger.getAttribute('aria-expanded') === 'true';

        // Close all other dropdowns first
        document.querySelectorAll('.bt-quote-dd__trigger[aria-expanded="true"]').forEach(function (t) {
          if (t !== trigger) t.setAttribute('aria-expanded', 'false');
        });

        trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      });

      // Option selection
      menu.querySelectorAll('.bt-quote-dd__opt').forEach(function (opt) {
        opt.addEventListener('click', function () {
          // Remove selection from all
          menu.querySelectorAll('.bt-quote-dd__opt').forEach(function (o) {
            o.classList.remove('bt-quote-dd__opt--sel');
          });
          // Mark as selected
          opt.classList.add('bt-quote-dd__opt--sel');

          // Update trigger display
          var thumb = opt.getAttribute('data-thumb');
          var name  = opt.getAttribute('data-name');
          var sub   = opt.getAttribute('data-sub');

          var trigThumb = trigger.querySelector('.bt-quote-dd__thumb');
          var trigName  = trigger.querySelector('.bt-quote-dd__name');
          var trigSub   = trigger.querySelector('.bt-quote-dd__sub');

          if (trigThumb && thumb) trigThumb.src = thumb;
          if (trigName) trigName.textContent = name || '';
          if (trigSub) {
            trigSub.textContent = sub || '';
            trigSub.style.display = sub ? '' : 'none';
          }

          // Close dropdown
          trigger.setAttribute('aria-expanded', 'false');

          // Callback
          if (typeof onSelect === 'function') onSelect(opt);
        });
      });

      // Close on outside click
      document.addEventListener('click', function (e) {
        if (!dd.contains(e.target)) {
          trigger.setAttribute('aria-expanded', 'false');
        }
      });

      // Keyboard navigation
      trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          trigger.click();
        } else if (e.key === 'Escape') {
          trigger.setAttribute('aria-expanded', 'false');
        }
      });
    }

    /* ════════════════════════════════════════════════════════════════════════
       EXCURSION CHOICE (legacy) — "Cette excursion" / "Expérience sur mesure"
       ════════════════════════════════════════════════════════════════════════ */

    root.querySelectorAll('[data-bt-exc-choice]').forEach(function (wrap) {
      var btns       = wrap.querySelectorAll('.bt-quote-exc-choice__btn');
      var customArea = wrap.parentNode.querySelector('.bt-quote-exc-custom');

      btns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          btns.forEach(function (b) {
            b.setAttribute('aria-selected', 'false');
            b.classList.remove('bt-quote-exc-choice__btn--selected');
          });
          btn.setAttribute('aria-selected', 'true');
          btn.classList.add('bt-quote-exc-choice__btn--selected');

          var choice = btn.getAttribute('data-exc-choice');
          state.exc_custom = (choice === 'custom');

          if (customArea) {
            customArea.style.display = (choice === 'custom') ? '' : 'none';
          }
        });
      });
    });

    /* ════════════════════════════════════════════════════════════════════════
       EXCURSION CARD SELECTION
       ════════════════════════════════════════════════════════════════════════ */

    var excCards = root.querySelectorAll('.bt-quote-exc-card');
    excCards.forEach(function (card) {
      card.addEventListener('click', function () {
        excCards.forEach(function (c) { c.setAttribute('aria-selected', 'false'); });
        card.setAttribute('aria-selected', 'true');
        state.excursion_id   = parseInt(card.getAttribute('data-exc-id'), 10) || 0;
        state.excursion_name = card.querySelector('.bt-quote-exc-card__title')?.textContent || '';
        // Detect "Trajet sur mesure" card (data-custom-trip="1" or data-exc-id="0")
        state.exc_custom = card.hasAttribute('data-custom-trip') || state.excursion_id === 0;
      });
      card.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          card.click();
        }
      });
    });

    /* ════════════════════════════════════════════════════════════════════════
       LOAD BOATS (AJAX)
       ════════════════════════════════════════════════════════════════════════ */

    function loadBoats(step) {
      var container = step.querySelector('[data-bt-quote-boats]');
      if (!container) return;
      container.innerHTML = '<div class="bt-quote__loading"></div>';

      var fd = new FormData();
      fd.append('action', 'bt_get_boats_by_excursion');
      fd.append('nonce', nonce);
      fd.append('excursion_id', state.excursion_id);
      fd.append('config', JSON.stringify(config));

      fetch(ajaxUrl, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res.success) {
            container.innerHTML = res.data.html;
            bindBoatCards(container, step);
          } else {
            container.innerHTML = '<p class="bt-quote__error">' + (res.data?.message || 'Erreur') + '</p>';
          }
        })
        .catch(function () {
          container.innerHTML = '<p class="bt-quote__error">Erreur de connexion</p>';
        });
    }

    function bindBoatCards(container, step) {
      var boatCards = container.querySelectorAll('.bt-quote-boat-card');

      boatCards.forEach(function (card) {
        // Sélection de la card entière
        card.addEventListener('click', function (e) {
          if (e.target.closest('.bt-quote-boat-card__more')) return;
          boatCards.forEach(function (c) {
            c.classList.remove('bt-quote-boat-card--selected', 'bt-forfait-card--active');
            c.setAttribute('aria-pressed', 'false');
          });
          card.classList.add('bt-quote-boat-card--selected', 'bt-forfait-card--active');
          card.setAttribute('aria-pressed', 'true');
          state.boat_id   = parseInt(card.getAttribute('data-boat-id'), 10) || 0;
          state.boat_name = card.querySelector('.bt-quote-boat-card__title')?.textContent || '';
        });

        // "Plus d'infos" button
        var moreBtn = card.querySelector('.bt-quote-boat-card__more');
        if (moreBtn) {
          moreBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            openBoatPopup(
              parseInt(moreBtn.getAttribute('data-boat-id'), 10) || 0,
              parseInt(moreBtn.getAttribute('data-popup-tpl'), 10) || 0
            );
          });
        }
      });
    }

    /* ════════════════════════════════════════════════════════════════════════
       BOAT POPUP (DIALOG)
       ════════════════════════════════════════════════════════════════════════ */

    var popup      = root.closest('.bt-bprice-wrapper')?.querySelector('[data-bt-quote-popup]')
                  || document.querySelector('[data-bt-quote-popup]');
    var popupContent = popup?.querySelector('[data-bt-quote-popup-content]');
    var popupClose   = popup?.querySelector('.bt-quote-popup__close');

    function openBoatPopup(boatId, tplId) {
      if (!popup || !popupContent) return;
      popupContent.innerHTML = '<div class="bt-quote__loading"></div>';
      popup.showModal();

      var fd = new FormData();
      fd.append('action', 'bt_render_boat_popup');
      fd.append('nonce', nonce);
      fd.append('boat_id', boatId);
      fd.append('template_id', tplId);
      fd.append('config', JSON.stringify(config));

      fetch(ajaxUrl, { method: 'POST', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          popupContent.innerHTML = res.success ? res.data.html : '<p>Erreur</p>';
        })
        .catch(function () {
          popupContent.innerHTML = '<p>Erreur de connexion</p>';
        });
    }

    if (popupClose) {
      popupClose.addEventListener('click', function () { popup.close(); });
    }
    if (popup) {
      popup.addEventListener('click', function (e) {
        var rect = popup.getBoundingClientRect();
        if (e.clientX < rect.left || e.clientX > rect.right ||
            e.clientY < rect.top  || e.clientY > rect.bottom) {
          popup.close();
        }
      });
      // Focus trap: Escape handled natively by <dialog>
    }

    /* ════════════════════════════════════════════════════════════════════════
       DATE PICKER (VANILLA JS)
       ════════════════════════════════════════════════════════════════════════ */

    function initDatepicker(step, specificWrap) {
      var wrap = specificWrap || step.querySelector('[data-bt-datepicker]');
      if (!wrap || wrap.getAttribute('data-dp-init')) return;
      wrap.setAttribute('data-dp-init', '1');

      var isRange   = wrap.getAttribute('data-range') === '1';
      var calEl     = wrap.querySelector('.bt-quote-datepicker__calendar');
      var startInp  = wrap.querySelector('[name="date_start"]');
      var endInp    = wrap.querySelector('[name="date_end"]');

      var today      = new Date();
      var viewYear   = today.getFullYear();
      var viewMonth  = today.getMonth();
      var selStart   = null;
      var selEnd     = null;

      var DAYS  = ['Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa', 'Di'];
      var MONTHS = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

      function renderCalendar() {
        var first    = new Date(viewYear, viewMonth, 1);
        var startDay = (first.getDay() + 6) % 7; // Monday = 0
        var daysInMonth = new Date(viewYear, viewMonth + 1, 0).getDate();

        var html = '<div class="bt-dp">';
        html += '<div class="bt-dp__nav">';
        html += '<button type="button" class="bt-dp__prev" data-dp-prev aria-label="Mois précédent">&#8249;</button>';
        html += '<span class="bt-dp__month-label">' + MONTHS[viewMonth] + ' ' + viewYear + '</span>';
        html += '<button type="button" class="bt-dp__next" data-dp-next aria-label="Mois suivant">&#8250;</button>';
        html += '</div>';
        html += '<div class="bt-dp__grid">';

        // Day headers
        DAYS.forEach(function (d) {
          html += '<div class="bt-dp__day-header">' + d + '</div>';
        });

        // Empty cells
        for (var e = 0; e < startDay; e++) {
          html += '<div class="bt-dp__cell bt-dp__cell--empty"></div>';
        }

        // Day cells
        for (var d = 1; d <= daysInMonth; d++) {
          var dt = new Date(viewYear, viewMonth, d);
          var isPast = dt < new Date(today.getFullYear(), today.getMonth(), today.getDate());
          var iso = formatDate(dt);
          var cls = 'bt-dp__cell';

          if (isPast) cls += ' bt-dp__cell--disabled';
          if (selStart && iso === formatDate(selStart)) cls += ' bt-dp__cell--start';
          if (selEnd && iso === formatDate(selEnd)) cls += ' bt-dp__cell--end';
          if (isRange && selStart && selEnd && dt > selStart && dt < selEnd) cls += ' bt-dp__cell--range';
          if (iso === formatDate(today)) cls += ' bt-dp__cell--today';

          html += '<div class="' + cls + '" data-date="' + iso + '">' + d + '</div>';
        }

        html += '</div></div>';
        calEl.innerHTML = html;

        // Bind events
        calEl.querySelector('[data-dp-prev]')?.addEventListener('click', function () {
          viewMonth--;
          if (viewMonth < 0) { viewMonth = 11; viewYear--; }
          renderCalendar();
        });
        calEl.querySelector('[data-dp-next]')?.addEventListener('click', function () {
          viewMonth++;
          if (viewMonth > 11) { viewMonth = 0; viewYear++; }
          renderCalendar();
        });

        calEl.querySelectorAll('.bt-dp__cell:not(.bt-dp__cell--disabled):not(.bt-dp__cell--empty)').forEach(function (cell) {
          cell.addEventListener('click', function () {
            var d = new Date(cell.getAttribute('data-date') + 'T00:00:00');
            if (!isRange) {
              selStart = d;
              selEnd   = null;
              if (startInp) startInp.value = formatDisplayDate(d);
            } else {
              if (!selStart || (selStart && selEnd)) {
                selStart = d;
                selEnd   = null;
                if (startInp) startInp.value = formatDisplayDate(d);
                if (endInp) endInp.value = '';
              } else {
                if (d < selStart) {
                  selEnd   = selStart;
                  selStart = d;
                } else {
                  selEnd = d;
                }
                if (startInp) startInp.value = formatDisplayDate(selStart);
                if (endInp) endInp.value = formatDisplayDate(selEnd);
              }
            }
            renderCalendar();
            updateDateSummary();
          });
        });
      }

      function formatDate(d) {
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
      }

      function formatDisplayDate(d) {
        return pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
      }

      function pad(n) { return n < 10 ? '0' + n : '' + n; }

      /**
       * Met à jour le résumé de date sous le calendrier
       * - 1 jour : "Vendredi 3 avril 2026 • Matin"
       * - Plusieurs jours : "Du 3 au 5 avril 2026"
       */
      function updateDateSummary() {
        var summaryEl = wrap.querySelector('[data-bt-date-summary]');
        if (!summaryEl) return;

        // Pas de date sélectionnée → masquer
        if (!selStart) {
          summaryEl.style.display = 'none';
          summaryEl.innerHTML = '';
          return;
        }

        var DAYS_FULL = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        var MONTHS_FULL = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin',
                          'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        var TIMESLOT_LABELS = { 'matin': 'Matin', 'apres-midi': 'Après-midi', 'soiree': 'Soirée' };

        var html = '';

        if (isRange && selStart && selEnd) {
          // Plusieurs jours : "Du 3 au 5 avril 2026"
          var sameMonth = selStart.getMonth() === selEnd.getMonth() && selStart.getFullYear() === selEnd.getFullYear();
          html = '<span class="bt-quote-date-summary__meta">Du</span> ';
          html += '<span class="bt-quote-date-summary__value">' + selStart.getDate();
          if (!sameMonth) {
            html += ' ' + MONTHS_FULL[selStart.getMonth()];
            if (selStart.getFullYear() !== selEnd.getFullYear()) html += ' ' + selStart.getFullYear();
          }
          html += '</span>';
          html += ' <span class="bt-quote-date-summary__meta">au</span> ';
          html += '<span class="bt-quote-date-summary__value">' + selEnd.getDate() + ' ' + MONTHS_FULL[selEnd.getMonth()] + ' ' + selEnd.getFullYear() + '</span>';
        } else if (selStart) {
          // Jour unique : "Vendredi 3 avril 2026 • Matin"
          var dayName = DAYS_FULL[selStart.getDay()];
          var monthName = MONTHS_FULL[selStart.getMonth()];
          html = '<span class="bt-quote-date-summary__value">' + dayName + ' ' + selStart.getDate() + ' ' + monthName + ' ' + selStart.getFullYear() + '</span>';

          // Ajouter le créneau si sélectionné (uniquement pour single)
          if (!isRange) {
            var tsHidden = wrap.closest('[data-step-type="dates"]')?.querySelector('[name="timeslot"]');
            var tsVal = tsHidden ? tsHidden.value : '';
            if (tsVal && TIMESLOT_LABELS[tsVal]) {
              html += '<span class="bt-quote-date-summary__sep">•</span>';
              html += '<span class="bt-quote-date-summary__value">' + TIMESLOT_LABELS[tsVal] + '</span>';
            }
          }
        }

        summaryEl.innerHTML = html;
        summaryEl.style.display = html ? '' : 'none';
      }

      // Expose pour appels externes (timeslot change)
      wrap._updateDateSummary = updateDateSummary;

      renderCalendar();
    }

    /* ════════════════════════════════════════════════════════════════════════
       RECAP & SUBMIT
       ════════════════════════════════════════════════════════════════════════ */

    function buildRecap(step) {
      var recapEl = step.querySelector('[data-bt-quote-recap]');
      if (!recapEl) return;

      var lines = [];
      if (state.exc_custom) {
        lines.push({ label: 'Excursion', value: 'Expérience sur mesure' });
        if (state.exc_custom_text) lines.push({ label: 'Demande', value: state.exc_custom_text });
      } else if (state.excursion_name) {
        lines.push({ label: 'Excursion', value: state.excursion_name });
      }
      if (state.boat_name) lines.push({ label: 'Bateau', value: state.boat_name });
      if (state.date_start) {
        var dateVal = state.date_start;
        if (state.timeslot) dateVal += ' (' + (state.timeslot === 'matin' ? 'Matin' : 'Après-midi') + ')';
        if (state.date_end) dateVal += ' → ' + state.date_end;
        lines.push({ label: 'Date', value: dateVal });
      }
      // Options bateau (n'affiche que les sélections ≠ "Aucun")
      if (state.boat_options) {
        Object.entries(state.boat_options).forEach(function (entry) {
          var names = entry[1].names || [];
          if (names.length) lines.push({ label: entry[0], value: names.join(', ') });
        });
      }

      var contactStep = root.querySelector('[data-step-type="contact"]');
      if (contactStep) {
        var fn = contactStep.querySelector('[name="client_firstname"]');
        var nm = contactStep.querySelector('[name="client_name"]');
        var em = contactStep.querySelector('[name="client_email"]');
        var ph = contactStep.querySelector('[name="client_phone"]');
        var nt = contactStep.querySelector('[name="client_note"]');
        var fullName = (fn ? fn.value + ' ' : '') + (nm ? nm.value : '');
        if (fullName.trim()) lines.push({ label: 'Nom', value: fullName.trim() });
        if (em && em.value) lines.push({ label: 'E-mail', value: em.value });
        if (ph && ph.value) lines.push({ label: 'Téléphone', value: ph.value });
        if (nt && nt.value.trim()) lines.push({ label: 'Note', value: nt.value.trim() });
      }

      recapEl.innerHTML = lines.map(function (l) {
        return '<div class="bt-quote-recap__line">'
          + '<span class="bt-quote-recap__label">' + escHtml(l.label) + '</span>'
          + '<span class="bt-quote-recap__value">' + escHtml(l.value) + '</span>'
          + '</div>';
      }).join('');
    }

    // Submit button
    var submitBtn = root.querySelector('[data-bt-quote-submit]');
    if (submitBtn) {
      submitBtn.addEventListener('click', function () {
        submitBtn.disabled = true;
        submitBtn.classList.add('bt-quote-submit--loading');

        var contactStep = root.querySelector('[data-step-type="contact"]');
        var fd = new FormData();
        fd.append('action', 'bt_quote_request');
        fd.append('nonce', nonce);
        fd.append('excursion_id', state.excursion_id);
        fd.append('boat_id', state.boat_id);
        fd.append('duration_type', state.duration_type || '');
        fd.append('date_start', state.date_start);
        fd.append('date_end', state.date_end);
        fd.append('timeslot', state.timeslot || '');
        fd.append('recipient', config.recipient || '');
        fd.append('exc_custom', state.exc_custom ? '1' : '');
        fd.append('exc_custom_text', state.exc_custom_text || '');
        fd.append('boat_options', JSON.stringify(state.boat_options || {}));
        fd.append('boat_forfait_label', state.boat_forfait_label || '');
        fd.append('boat_forfait_price', state.boat_forfait_price || '');

        // Date custom (demande spécifique)
        var dateCustomEl = root.querySelector('[name="date_custom"]');
        if (dateCustomEl) fd.append('date_custom', dateCustomEl.value || '');

        if (contactStep) {
          var fields = contactStep.querySelectorAll('.bt-quote-fields__input');
          fields.forEach(function (f) {
            if (f.name) fd.append(f.name, f.value);
          });
        }

        // Acquisition data
        var urlParams = new URLSearchParams(location.search);
        fd.append('utm_source', urlParams.get('utm_source') || '');
        fd.append('utm_medium', urlParams.get('utm_medium') || '');
        fd.append('utm_campaign', urlParams.get('utm_campaign') || '');
        fd.append('referrer', document.referrer || '');
        fd.append('page_url', location.href || '');

        var msgEl = root.querySelector('[data-bt-quote-message]');

        fetch(ajaxUrl, { method: 'POST', body: fd })
          .then(function (r) { return r.json(); })
          .then(function (res) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bt-quote-submit--loading');
            if (res.success) {
              // GTM Event - generate_lead
              window.dataLayer = window.dataLayer || [];
              window.dataLayer.push({
                event: 'generate_lead',
                form_name: 'devis_' + pricingMode,
                excursion_id: state.excursion_id || '',
                boat_id: state.boat_id || '',
                boat_name: state.boat_name || ''
              });

              if (msgEl) {
                msgEl.className = 'bt-quote-message bt-quote-message--success';
                msgEl.textContent = config.msg_success || 'Envoyé !';
              }
              submitBtn.style.display = 'none';
            } else {
              if (msgEl) {
                msgEl.className = 'bt-quote-message bt-quote-message--error';
                msgEl.textContent = config.msg_error || 'Erreur';
              }
            }
          })
          .catch(function () {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bt-quote-submit--loading');
            if (msgEl) {
              msgEl.className = 'bt-quote-message bt-quote-message--error';
              msgEl.textContent = config.msg_error || 'Erreur de connexion';
            }
          });
      });
    }

    // Auto-activate first step and fire onStepEnter if excursion/boat is auto-selected
    if (autoExc && steps.length > 0) {
      var excStep = steps[0];
      if (excStep.getAttribute('data-step-type') === 'excursion') {
        var summary = excStep.querySelector('.bt-quote-step__summary');
        if (summary) summary.textContent = state.excursion_name;
      }
    }

    if (autoBoat && steps.length > 0) {
      for (var bi = 0; bi < steps.length; bi++) {
        if (steps[bi].getAttribute('data-step-type') === 'boat') {
          var bsummary = steps[bi].querySelector('.bt-quote-step__summary');
          if (bsummary) bsummary.textContent = state.boat_name;
          break;
        }
      }
    }

    // In excursion mode, bind statically rendered boat cards on first active step
    if (pricingMode === 'excursion' && steps.length > 0) {
      var firstStep = steps[0];
      if (firstStep.getAttribute('data-step-type') === 'boat') {
        var staticBoats = firstStep.querySelector('.bt-quote-boat-cards');
        if (staticBoats) bindBoatCards(staticBoats, firstStep);
      }
    }
  }

  /* ══════════════════════════════════════════════════════════════════════════
     UTILITIES
     ══════════════════════════════════════════════════════════════════════════ */

  function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  /* ══════════════════════════════════════════════════════════════════════════
     BOOTSTRAP
     ══════════════════════════════════════════════════════════════════════════ */

  function boot(scope) {
    var el = (scope && scope !== document) ? scope : document;
    el.querySelectorAll('[data-bt-quote]:not([data-bt-quote-init])').forEach(initQuoteForm);
  }

  // Elementor handler
  window.addEventListener('elementor/frontend/init', function () {
    var Handler = elementorModules.frontend.handlers.Base.extend({
      onInit: function () {
        elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);
        boot(this.$element[0]);
      },
    });
    elementorFrontend.elementsHandler.attachHandler('bt-quote-form', Handler);
    elementorFrontend.elementsHandler.attachHandler('bt-boat-pricing', Handler);
  });

  // Fallback
  document.addEventListener('DOMContentLoaded', function () { boot(document); });
}());
