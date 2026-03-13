/**
 * BlackTenders — Leaflet map init pour le widget Itinéraire.
 *
 * Chaque `.bt-itin__map--leaflet[data-bt-points]` devient une carte interactive.
 * Tiles : CartoDB Voyager (propre, sans clé API, conforme RGPD).
 * Markers : DivIcon numérotés — aller en bleu, retour avec --bt-itin-return-line-color.
 */
(function () {
  'use strict';

  /**
   * Initialise toutes les cartes Leaflet non encore initialisées dans le DOM.
   */
  function initBtMaps() {
    if (typeof L === 'undefined') return;

    document.querySelectorAll('.bt-itin__map--leaflet:not([data-bt-map-init])').forEach(function (el) {
      var raw = el.getAttribute('data-bt-points');
      var points = [];
      try { points = JSON.parse(raw || '[]'); } catch (_) {}
      if (!points.length) return;

      el.setAttribute('data-bt-map-init', '1');

      var lineColor   = el.getAttribute('data-bt-line-color')   || '#0066cc';
      var returnColor = el.getAttribute('data-bt-return-color') || '#e63946';

      var map = L.map(el, {
        scrollWheelZoom: false, // évite le scroll accidentel sur la page
        zoomControl: true,
      });

      // Tiles CartoDB Voyager — moderna, lisible, RGPD-safe
      L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
        maxZoom: 19,
      }).addTo(map);

      var latlngs = points.map(function (p) { return [p.lat, p.lng]; });

      // Ligne de route maritime (droite, pas de calcul d'itinéraire routier)
      L.polyline(latlngs, { color: lineColor, weight: 3, opacity: 0.8 }).addTo(map);

      // Markers numérotés — bleu aller, teinte retour configurable
      points.forEach(function (p, i) {
        var bg  = p.return ? returnColor : lineColor;
        var num = i + 1;
        var icon = L.divIcon({
          className: '',
          html: '<div class="bt-map-pin" style="background:' + bg + '">' + num + '</div>',
          iconSize:   [28, 28],
          iconAnchor: [14, 14],
          popupAnchor: [0, -16],
        });
        // Use DOM nodes for popup to prevent XSS (audit §C08)
        var popupEl = document.createElement('strong');
        popupEl.textContent = p.title || ('Étape ' + num);
        L.marker([p.lat, p.lng], { icon: icon })
          .addTo(map)
          .bindPopup(popupEl);
      });

      // Ajuste le zoom pour afficher tous les points avec une marge
      map.fitBounds(L.latLngBounds(latlngs).pad(0.2));
    });
  }

  // Init au chargement Elementor front-end + fallback DOMContentLoaded
  if (window.elementorFrontend) {
    elementorFrontend.on('init', initBtMaps);
    elementorFrontend.on('components:init', initBtMaps);
  }
  document.addEventListener('DOMContentLoaded', initBtMaps);

  // API publique si besoin d'un re-init manuel
  window.btLeaflet = { init: initBtMaps };
}());
