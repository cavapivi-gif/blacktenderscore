<?php
/**
 * Configuration des fournisseurs de tuiles Leaflet.js.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  PERSONNALISATION
 * ──────────────────────────────────────────────────────────────────────────
 *  Pour ajouter un preset ou remplacer une URL, utilisez les filtres WP
 *  dans votre thème enfant ou un mu-plugin :
 *
 *  // Changer le preset par défaut
 *  add_filter('bt_map_default_tile', fn() => 'positron');
 *
 *  // Ajouter un preset personnalisé
 *  add_filter('bt_map_tile_presets', function(array $presets): array {
 *      $presets['custom'] = [
 *          'label'   => 'Mon style custom',
 *          'url'     => 'https://my-tiles.example.com/{z}/{x}/{y}.png',
 *          'attr'    => '&copy; Mon fournisseur',
 *          'maxZoom' => 20,
 *      ];
 *      return $presets;
 *  });
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  PRESETS DISPONIBLES (sans clé API)
 * ──────────────────────────────────────────────────────────────────────────
 *  voyager   → CartoDB Voyager   — couleurs douces, mer bleue, lisible   ✓ recommandé
 *  positron  → CartoDB Positron  — fond blanc ultra-épuré, minimaliste
 *  dark      → CartoDB Dark      — fond sombre, élégant pour sites premium
 *  osm       → OpenStreetMap     — standard, très détaillé
 */

defined('ABSPATH') || exit;

return [

    // ── Preset actif par défaut (overridable via filtre bt_map_default_tile) ──
    'default' => 'voyager',

    // ── Catalogue de presets ──────────────────────────────────────────────────
    'presets' => [

        'voyager' => [
            'label'   => 'CartoDB Voyager — mer bleue, couleurs douces',
            'url'     => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
            'attr'    => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            'maxZoom' => 20,
        ],

        'positron' => [
            'label'   => 'CartoDB Positron — fond blanc, ultra-épuré',
            'url'     => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            'attr'    => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            'maxZoom' => 20,
        ],

        'dark' => [
            'label'   => 'CartoDB Dark Matter — fond sombre, premium',
            'url'     => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            'attr'    => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            'maxZoom' => 20,
        ],

        'osm' => [
            'label'   => 'OpenStreetMap — standard (détaillé)',
            'url'     => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'attr'    => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            'maxZoom' => 19,
        ],

    ],

];
