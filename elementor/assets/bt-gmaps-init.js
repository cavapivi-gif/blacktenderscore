/**
 * BlackTenders — Google Maps JS API init.
 *
 * Initialise tous les `.bt-gmaps-js` présents dans le DOM.
 * Callback global `btGmapsReady` appelé par l'API Maps après son chargement.
 * Fallback setInterval si l'API est déjà chargée ou chargée sans callback.
 *
 * Attributs HTML reconnus (mode single) :
 *   data-bt-latlng      "lat,lng"   — coordonnées directes
 *   data-bt-address     "string"    — adresse textuelle (requiert Geocoding API)
 *   data-zoom           number      — zoom initial (défaut : 14)
 *   data-map-type       string      — roadmap | satellite | terrain | hybrid
 *   data-scroll-zoom    "yes"|"no"  — scroll de souris zoome (défaut : no)
 *   data-marker         "yes"       — affiche un marqueur centré
 *   data-marker-title   string      — tooltip du marqueur
 *   data-marker-popup   string      — contenu HTML de l'infobulle
 *   data-marker-color   "#rrggbb"   — couleur du pin SVG (défaut : #0066cc)
 *   data-marker-open    "yes"       — ouvre l'infobulle au chargement
 *
 * Attributs HTML reconnus (mode destinations) :
 *   data-bt-destinations  JSON[]    — tableau de pins {lat,lng,title,tagline,image,url}
 *   data-bt-dest-opts     JSON{}    — options {pinColor,pinActive,fitBounds,ctaLabel}
 */
(function () {
  'use strict';

  // ── Styles InfoWindow (injectés une fois dans <head>) ────────────────────────

  var _iwStylesInjected = false;
  function injectMapStyles() {
    if (_iwStylesInjected) return;
    _iwStylesInjected = true;
    var css = [
      /* Carte destinations — curseur pointer sur le canvas */
      '.bt-gmap--destinations .bt-gmap__canvas { cursor: default; }',

      /* InfoWindow card */
      '.bt-gmap-iw { font-family: inherit; width: 220px; line-height: 1; }',
      '.bt-gmap-iw__img { width: 100%; height: 120px; object-fit: cover; display: block; }',
      '.bt-gmap-iw__body { padding: 11px 13px 12px; }',
      '.bt-gmap-iw__title { font-size: 13px; font-weight: 700; color: #111; display: block; margin: 0 0 5px; line-height: 1.3; }',
      '.bt-gmap-iw__tagline { font-size: 11.5px; color: #555; margin: 0 0 9px; line-height: 1.45;',
        'display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }',
      '.bt-gmap-iw__cta { font-size: 11.5px; font-weight: 600; color: #1a73e8; text-decoration: none; }',
      '.bt-gmap-iw__cta:hover { text-decoration: underline; }',

      /* Supprimer le padding par défaut de l'InfoWindow Google */
      '.gm-style .gm-style-iw-c { padding: 0 !important; border-radius: 12px !important; overflow: hidden !important; box-shadow: 0 4px 20px rgba(0,0,0,.18) !important; }',
      '.gm-style .gm-style-iw-d { overflow: hidden !important; }',
      '.gm-style .gm-style-iw-chr { display: none !important; }',
      /* Petite flèche de l'InfoWindow — fond blanc */
      '.gm-style .gm-style-iw-t::after { background: #fff !important; }',
    ].join('\n');

    var style = document.createElement('style');
    style.textContent = css;
    document.head.appendChild(style);
  }

  // ── SVG pins ─────────────────────────────────────────────────────────────────

  /** Pin single : teardrop simple avec point intérieur. */
  function pinSvg(color) {
    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="40" viewBox="0 0 28 40">'
      + '<path fill="' + color + '" stroke="#fff" stroke-width="1.5"'
      + ' d="M14 0C6.27 0 0 6.27 0 14c0 9.68 14 26 14 26S28 23.68 28 14C28 6.27 21.73 0 14 0z"/>'
      + '<circle fill="#fff" cx="14" cy="14" r="5"/>'
      + '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  /**
   * Pin destinations : teardrop moderne avec point blanc intérieur.
   * Plus grand (36×50) et légèrement aplati pour se distinguer du pin single.
   * @param {string} color   couleur du remplissage
   * @param {boolean} active pin en surbrillance (couleur active)
   */
  function pinSvgDest(color, active) {
    var c = active ? color : color;
    var innerR = active ? 7 : 5.5;
    var stroke = active ? '#fff' : '#fff';
    var sw     = active ? 2 : 1.5;
    var drop   = active ? 'M18 0C8.059 0 0 8.059 0 18c0 12.318 18 32 18 32S36 30.318 36 18C36 8.059 27.941 0 18 0z'
                        : 'M16 0C7.163 0 0 7.163 0 16c0 11 16 28 16 28S32 27 32 16C32 7.163 24.837 0 16 0z';
    var w  = active ? 36 : 32;
    var h  = active ? 50 : 44;
    var cx = active ? 18 : 16;
    var cy = active ? 18 : 16;
    var vb = '0 0 ' + w + ' ' + h;

    var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + w + '" height="' + h + '" viewBox="' + vb + '">'
      /* Ombre portée */
      + '<ellipse cx="' + cx + '" cy="' + (h - 3) + '" rx="' + (w * 0.28) + '" ry="' + (active ? 3.5 : 2.8) + '" fill="rgba(0,0,0,0.22)"/>'
      /* Corps */
      + '<path fill="' + c + '" stroke="' + stroke + '" stroke-width="' + sw + '" d="' + drop + '"/>'
      /* Cercle intérieur */
      + '<circle fill="#fff" cx="' + cx + '" cy="' + cy + '" r="' + innerR + '"/>'
      /* Vague (icône mer) */
      + '<path stroke="' + c + '" stroke-width="1.8" stroke-linecap="round" fill="none"'
      + ' d="M' + (cx - 6) + ' ' + cy + ' q3-3 6 0 q3 3 6 0"/>'
      + '</svg>';
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(svg);
  }

  // ── Construit l'HTML de la card InfoWindow ────────────────────────────────────

  function buildInfoWindowContent(pin, ctaLabel) {
    var html = '<div class="bt-gmap-iw">';
    if (pin.image) {
      html += '<img class="bt-gmap-iw__img" src="' + pin.image + '" alt="" loading="lazy">';
    }
    html += '<div class="bt-gmap-iw__body">';
    html += '<strong class="bt-gmap-iw__title">' + escHtml(pin.title) + '</strong>';
    if (pin.tagline) {
      html += '<p class="bt-gmap-iw__tagline">' + escHtml(pin.tagline) + '</p>';
    }
    if (pin.url) {
      html += '<a class="bt-gmap-iw__cta" href="' + pin.url + '">' + ctaLabel + '</a>';
    }
    html += '</div></div>';
    return html;
  }

  /** Échappe les caractères HTML dangereux. */
  function escHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  // ── Map single ────────────────────────────────────────────────────────────────

  /** Initialise une carte dans `el` centrée sur `center`. */
  function buildMap(el, center) {
    var zoom       = parseInt(el.getAttribute('data-zoom') || '14', 10);
    var mapType    = el.getAttribute('data-map-type') || 'roadmap';
    var scrollZoom = el.getAttribute('data-scroll-zoom') === 'yes';

    var map = new google.maps.Map(el, {
      center:          center,
      zoom:            zoom,
      mapTypeId:       mapType,
      scrollwheel:     scrollZoom,
      gestureHandling: scrollZoom ? 'auto' : 'cooperative',
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

  // ── Map destinations ──────────────────────────────────────────────────────────

  /**
   * Construit la carte multi-pins toutes destinations.
   * @param {HTMLElement} el    Le canvas .bt-gmaps-js
   * @param {Array}       pins  [{lat,lng,title,tagline,image,url}, …]
   * @param {Object}      opts  {pinColor,pinActive,fitBounds,ctaLabel}
   */
  function buildDestinationsMap(el, pins, opts) {
    injectMapStyles();

    var zoom       = parseInt(el.getAttribute('data-zoom') || '10', 10);
    var mapType    = el.getAttribute('data-map-type') || 'roadmap';
    var scrollZoom = el.getAttribute('data-scroll-zoom') === 'yes';

    var pinColor  = opts.pinColor  || '#0a0a0a';
    var pinActive = opts.pinActive || '#1a73e8';
    var ctaLabel  = opts.ctaLabel  || 'Découvrir →';

    /* Centre initial = centroïde */
    var centerLat = 0, centerLng = 0;
    pins.forEach(function (p) { centerLat += p.lat; centerLng += p.lng; });
    centerLat /= pins.length;
    centerLng /= pins.length;

    var map = new google.maps.Map(el, {
      center:          { lat: centerLat, lng: centerLng },
      zoom:            zoom,
      mapTypeId:       mapType,
      scrollwheel:     scrollZoom,
      gestureHandling: scrollZoom ? 'auto' : 'cooperative',
    });

    var openInfoWindow = null; /* InfoWindow actuellement ouverte */
    var activeMarker   = null; /* Marqueur actuellement actif */

    var bounds = opts.fitBounds ? new google.maps.LatLngBounds() : null;

    pins.forEach(function (pin) {
      var pos = { lat: pin.lat, lng: pin.lng };
      if (bounds) bounds.extend(pos);

      var marker = new google.maps.Marker({
        position: pos,
        map:      map,
        title:    pin.title,
        icon: {
          url:        pinSvgDest(pinColor, false),
          scaledSize: new google.maps.Size(32, 44),
          anchor:     new google.maps.Point(16, 44),
        },
      });

      var iw = new google.maps.InfoWindow({
        content:     buildInfoWindowContent(pin, ctaLabel),
        pixelOffset: new google.maps.Size(0, -4),
      });

      marker.addListener('click', function () {
        /* Fermer l'InfoWindow précédente */
        if (openInfoWindow) openInfoWindow.close();

        /* Restaurer le pin précédent */
        if (activeMarker && activeMarker !== marker) {
          activeMarker.setIcon({
            url:        pinSvgDest(pinColor, false),
            scaledSize: new google.maps.Size(32, 44),
            anchor:     new google.maps.Point(16, 44),
          });
        }

        /* Activer ce marqueur */
        marker.setIcon({
          url:        pinSvgDest(pinActive, true),
          scaledSize: new google.maps.Size(36, 50),
          anchor:     new google.maps.Point(18, 50),
        });

        iw.open({ anchor: marker, map: map });
        openInfoWindow = iw;
        activeMarker   = marker;
      });

      /* Survol léger */
      marker.addListener('mouseover', function () {
        if (marker === activeMarker) return;
        marker.setIcon({
          url:        pinSvgDest(pinActive, false),
          scaledSize: new google.maps.Size(32, 44),
          anchor:     new google.maps.Point(16, 44),
        });
      });
      marker.addListener('mouseout', function () {
        if (marker === activeMarker) return;
        marker.setIcon({
          url:        pinSvgDest(pinColor, false),
          scaledSize: new google.maps.Size(32, 44),
          anchor:     new google.maps.Point(16, 44),
        });
      });

      /* Clic en dehors → réinitialise */
      map.addListener('click', function () {
        if (openInfoWindow) { openInfoWindow.close(); openInfoWindow = null; }
        if (activeMarker) {
          activeMarker.setIcon({
            url:        pinSvgDest(pinColor, false),
            scaledSize: new google.maps.Size(32, 44),
            anchor:     new google.maps.Point(16, 44),
          });
          activeMarker = null;
        }
      });
    });

    /* Ajuster la vue pour montrer tous les pins */
    if (bounds && !bounds.isEmpty()) {
      map.fitBounds(bounds, 40); /* 40px de padding autour */
    }
  }

  // ── Initialisation globale ────────────────────────────────────────────────────

  /** Initialise toutes les cartes non encore traitées. */
  function initBtGmaps() {
    if (typeof google === 'undefined' || !google.maps || !google.maps.Map) return;

    document.querySelectorAll('.bt-gmaps-js:not([data-bt-map-init])').forEach(function (el) {
      el.setAttribute('data-bt-map-init', '1');

      /* ── Mode destinations ── */
      var destRaw = el.getAttribute('data-bt-destinations');
      if (destRaw) {
        try {
          var pins = JSON.parse(destRaw);
          var optsRaw = el.getAttribute('data-bt-dest-opts') || '{}';
          var opts = JSON.parse(optsRaw);
          if (pins && pins.length) buildDestinationsMap(el, pins, opts);
        } catch (e) {
          if (window.btDebug) console.error('[BT gmaps] destinations parse error', e);
        }
        return;
      }

      /* ── Mode single ── */
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

  // ── Lazy load de l'API Maps via IntersectionObserver ──────────────────────

  var _mapsApiQueued = false;

  /**
   * Charge dynamiquement le script Google Maps API.
   * L'URL (avec clé API) est passée par PHP via wp_localize_script → BT_GMaps.apiUrl.
   */
  function _loadMapsApi() {
    if (_mapsApiQueued) return;
    if (typeof BT_GMaps === 'undefined' || !BT_GMaps.apiUrl) return;
    _mapsApiQueued = true;
    var s   = document.createElement('script');
    s.src   = BT_GMaps.apiUrl;
    s.async = true;
    document.head.appendChild(s);
  }

  /**
   * Observe chaque .bt-gmaps-js et déclenche le chargement de l'API
   * dès que l'un d'eux entre dans la zone viewport + 200px.
   * Fallback immédiat si IntersectionObserver absent.
   */
  function _observeMaps() {
    var maps = document.querySelectorAll('.bt-gmaps-js');
    if (!maps.length) return;

    if (!('IntersectionObserver' in window)) {
      _loadMapsApi();
      return;
    }

    var obs = new IntersectionObserver(function (entries) {
      if (!entries.some(function (e) { return e.isIntersecting; })) return;
      obs.disconnect();
      _loadMapsApi();
    }, { rootMargin: '200px' });

    maps.forEach(function (el) { obs.observe(el); });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', _observeMaps);
  } else {
    _observeMaps();
  }

  // Réinitialisation dans l'éditeur Elementor si l'API est déjà chargée
  if (window.elementorFrontend) {
    elementorFrontend.on('components:init', initBtGmaps);
  }

  window.btGmaps = { init: initBtGmaps, loadApi: _loadMapsApi };
}());
