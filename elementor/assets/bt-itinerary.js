/**
 * BlackTenders — Itinéraire v4
 *
 * 1. Accordéon : clic sur .bt-itin__step-trigger → toggle aria-expanded + hidden panel
 * 2. Map↔timeline : clic sur li[data-lat] → Leaflet flyTo (nécessite bt-leaflet-init.js)
 */
(function () {
  'use strict';

  // ── Accordéon ──────────────────────────────────────────────────────────────

  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.bt-itin__step-trigger');
    if (!btn) return;

    var expanded = btn.getAttribute('aria-expanded') === 'true';
    btn.setAttribute('aria-expanded', String(!expanded));

    var panel = btn.nextElementSibling;
    if (panel && panel.classList.contains('bt-itin__step-panel')) {
      panel.hidden = expanded; // expanded=true → on ferme (hidden=true)
    }

    // Si l'étape a des coords, synchroniser la carte aussi
    var li = btn.closest('li[data-lat]');
    if (li) btItinFlyTo(li);
  });

  // ── Map↔timeline : clic sur un li avec coords ─────────────────────────────

  document.addEventListener('click', function (e) {
    // Ignorer si c'est un trigger accordéon (déjà géré ci-dessus)
    if (e.target.closest('.bt-itin__step-trigger')) return;

    var li = e.target.closest('.bt-itin__list > li[data-lat]');
    if (!li) return;
    btItinFlyTo(li);
  });

  /**
   * Fait voler la carte Leaflet du widget parent vers les coords de li[data-lat].
   * Accède à l'instance via el._btLeafletMap (stocké par bt-leaflet-init.js).
   *
   * @param {HTMLElement} li Élément <li data-lat="..." data-lng="...">
   */
  function btItinFlyTo(li) {
    var lat = parseFloat(li.getAttribute('data-lat'));
    var lng = parseFloat(li.getAttribute('data-lng'));
    if (isNaN(lat) || isNaN(lng)) return;

    var widget = li.closest('.bt-itin');
    if (!widget) return;
    var mapEl = widget.querySelector('.bt-itin__map--leaflet');
    if (!mapEl || !mapEl._btLeafletMap) return;

    mapEl._btLeafletMap.flyTo([lat, lng], 14, { duration: 0.8 });
  }
}());
