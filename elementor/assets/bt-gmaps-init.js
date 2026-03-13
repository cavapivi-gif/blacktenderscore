/**
 * BlackTenders — Google Maps JS API init.
 *
 * Initialise tous les `.bt-gmaps-js` présents dans le DOM.
 * Callback global `btGmapsReady` appelé par l'API Maps après son chargement.
 * Fallback setInterval si l'API est déjà chargée ou chargée sans callback.
 *
 * Attributs HTML reconnus :
 *   data-bt-latlng      "lat,lng"   — coordonnées directes (pas de Geocoding API)
 *   data-bt-address     "string"    — adresse textuelle (requiert Geocoding API)
 *   data-zoom           number      — zoom initial (défaut : 14)
 *   data-map-type       string      — roadmap | satellite | terrain | hybrid
 *   data-scroll-zoom    "yes"|"no"  — scroll de souris zoome (défaut : no)
 *   data-marker         "yes"       — affiche un marqueur centré
 *   data-marker-title   string      — tooltip du marqueur
 *   data-marker-popup   string      — contenu HTML de l'infobulle (InfoWindow)
 *   data-marker-color   "#rrggbb"   — couleur du pin SVG (défaut : #0066cc)
 *   data-marker-open    "yes"       — ouvre l'infobulle au chargement
 */
(function () {
  'use strict';

  /** Pin SVG coloré encodé en data URI. */
  function pinSvg(color) {
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="40" viewBox="0 0 28 40">'
      + '<path fill="' + color + '" stroke="#fff" stroke-width="1.5"'
      + ' d="M14 0C6.27 0 0 6.27 0 14c0 9.68 14 26 14 26S28 23.68 28 14C28 6.27 21.73 0 14 0z"/>'
      + '<circle fill="#fff" cx="14" cy="14" r="5"/>'
      + '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  /** Initialise une carte dans `el` centrée sur `center`. */
  function buildMap(el, center) {
    var zoom       = parseInt(el.getAttribute('data-zoom') || '14', 10);
    var mapType    = el.getAttribute('data-map-type') || 'roadmap';
    var scrollZoom = el.getAttribute('data-scroll-zoom') === 'yes';

    // new google.maps.Map → intercepté par le monkey-patch BT pour appliquer le style
    var map = new google.maps.Map(el, {
      center:           center,
      zoom:             zoom,
      mapTypeId:        mapType,
      scrollwheel:      scrollZoom,
      gestureHandling:  scrollZoom ? 'auto' : 'cooperative',
    });

    if (el.getAttribute('data-marker') !== 'yes') return;

    var color   = el.getAttribute('data-marker-color') || '#0066cc';
    var title   = el.getAttribute('data-marker-title') || '';
    var popup   = el.getAttribute('data-marker-popup') || '';
    var openNow = el.getAttribute('data-marker-open') === 'yes';

    var marker = new google.maps.Marker({
      position: center,
      map:      map,
      title:    title,
      icon: {
        url:        pinSvg(color),
        scaledSize: new google.maps.Size(28, 40),
        anchor:     new google.maps.Point(14, 40),
      },
    });

    if (!popup) return;

    var infoWindow = new google.maps.InfoWindow({ content: popup });
    marker.addListener('click', function () {
      infoWindow.open({ anchor: marker, map: map });
    });
    if (openNow) infoWindow.open({ anchor: marker, map: map });
  }

  /** Initialise toutes les cartes non encore traitées. */
  function initBtGmaps() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.Map) return;

    document.querySelectorAll('.bt-gmaps-js:not([data-bt-map-init])').forEach(function (el) {
      el.setAttribute('data-bt-map-init', '1');

      var latlng  = el.getAttribute('data-bt-latlng');
      var address = el.getAttribute('data-bt-address');

      if (latlng) {
        var parts = latlng.split(',');
        buildMap(el, { lat: parseFloat(parts[0]), lng: parseFloat(parts[1]) });

      } else if (address && google.maps.Geocoder) {
        new google.maps.Geocoder().geocode({ address: address }, function (results, status) {
          if (status === 'OK' && results.length) {
            buildMap(el, results[0].geometry.location);
          }
        });
      }
    });
  }

  // Callback appelé par l'URL Maps API (&callback=btGmapsReady)
  window.btGmapsReady = initBtGmaps;

  // Fallback : si l'API est déjà chargée (ou chargée sans callback)
  // Garde readyState en compte — les scripts footer tournent APRÈS DOMContentLoaded
  function maybeInit() {
    if (window.google && google.maps && google.maps.Map) {
      initBtGmaps();
    } else {
      // Attend que l'API se charge (chargement asynchrone sans callback)
      var t = setInterval(function () {
        if (window.google && google.maps && google.maps.Map) {
          clearInterval(t);
          initBtGmaps();
        }
      }, 100);
    }
  }

  // Les scripts footer s'exécutent après DOMContentLoaded → appel direct
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', maybeInit);
  } else {
    maybeInit();
  }

  if (window.elementorFrontend) {
    elementorFrontend.on('init', initBtGmaps);
    elementorFrontend.on('components:init', initBtGmaps);
  }

  window.btGmaps = { init: initBtGmaps };
}());
