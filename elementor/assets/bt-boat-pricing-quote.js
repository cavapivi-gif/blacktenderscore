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
    var state   = { excursion_id: 0, excursion_name: '', boat_id: 0, boat_name: '', date_start: '', date_end: '' };
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
        var isActive = (i === idx);
        s.classList.toggle('bt-quote-step--active', isActive);
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
        if (durType.value === 'multi') {
          var startInp = step.querySelector('.bt-quote-datepicker--range [name="date_start"]');
          if (startInp && !startInp.value) { shakeFeedback(step); return false; }
        }
        return true;
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
        var excCard = step.querySelector('.bt-quote-exc-card[aria-selected="true"]');
        if (excCard) {
          state.excursion_id   = parseInt(excCard.getAttribute('data-exc-id'), 10) || 0;
          state.excursion_name = excCard.querySelector('.bt-quote-exc-card__title')?.textContent || '';
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
    }

    function onStepEnter(step) {
      if (!step) return;
      var type = step.getAttribute('data-step-type');
      if (type === 'boat') {
        // In excursion mode, boats may be statically rendered — just bind click
        var container = step.querySelector('[data-bt-quote-boats]');
        if (container && state.excursion_id) {
          loadBoats(step);
        } else {
          // Static boats (excursion mode) — bind selection
          var staticContainer = step.querySelector('.bt-quote-boat-cards');
          if (staticContainer) bindBoatCards(staticContainer, step);
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
            tsBtns.forEach(function (b) { b.setAttribute('aria-selected', 'false'); });
            btn.setAttribute('aria-selected', 'true');
            if (tsHidden) tsHidden.value = btn.getAttribute('data-timeslot');
          });
        });
      }

      cards.forEach(function (card) {
        card.addEventListener('click', function () {
          cards.forEach(function (c) { c.setAttribute('aria-selected', 'false'); });
          card.setAttribute('aria-selected', 'true');

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
        });

        card.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); card.click(); }
        });
      });
    }

    function shakeFeedback(step) {
      step.classList.add('bt-quote-step--shake');
      setTimeout(function () { step.classList.remove('bt-quote-step--shake'); }, 500);
    }

    /* ════════════════════════════════════════════════════════════════════════
       EXCURSION CHOICE — "Cette excursion" / "Expérience sur mesure"
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
        card.addEventListener('click', function (e) {
          // Don't select if clicking the "more" button
          if (e.target.closest('.bt-quote-boat-card__more')) return;
          boatCards.forEach(function (c) { c.classList.remove('bt-quote-boat-card--selected'); });
          card.classList.add('bt-quote-boat-card--selected');
          state.boat_id   = parseInt(card.getAttribute('data-boat-id'), 10) || 0;
          state.boat_name = card.querySelector('.bt-quote-boat-card__title')?.textContent || '';
        });

        // "En savoir plus" button
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
        lines.push('<strong>Excursion :</strong> Expérience sur mesure');
        if (state.exc_custom_text) lines.push('<strong>Demande :</strong> ' + escHtml(state.exc_custom_text));
      } else if (state.excursion_name) {
        lines.push('<strong>Excursion :</strong> ' + escHtml(state.excursion_name));
      }
      if (state.boat_name)      lines.push('<strong>Bateau :</strong> ' + escHtml(state.boat_name));
      if (state.date_start) {
        var dateLine = '<strong>Dates :</strong> ' + escHtml(state.date_start);
        if (state.timeslot) dateLine += ' (' + (state.timeslot === 'matin' ? 'Matin' : 'Après-midi') + ')';
        if (state.date_end) dateLine += ' → ' + escHtml(state.date_end);
        lines.push(dateLine);
      }

      var contactStep = root.querySelector('[data-step-type="contact"]');
      if (contactStep) {
        var fn = contactStep.querySelector('[name="client_firstname"]');
        var nm = contactStep.querySelector('[name="client_name"]');
        var em = contactStep.querySelector('[name="client_email"]');
        var ph = contactStep.querySelector('[name="client_phone"]');
        var fullName = (fn ? fn.value + ' ' : '') + (nm ? nm.value : '');
        if (fullName.trim()) lines.push('<strong>Nom :</strong> ' + escHtml(fullName.trim()));
        if (em && em.value) lines.push('<strong>E-mail :</strong> ' + escHtml(em.value));
        if (ph && ph.value) lines.push('<strong>Téléphone :</strong> ' + escHtml(ph.value));
      }

      recapEl.innerHTML = lines.map(function (l) { return '<p class="bt-quote-recap__line">' + l + '</p>'; }).join('');
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
