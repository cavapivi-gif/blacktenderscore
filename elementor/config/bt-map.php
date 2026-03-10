<?php
/**
 * Configuration Google Maps pour le widget Itinéraire.
 *
 * ──────────────────────────────────────────────────────────────────────────
 *  CLEF API
 * ──────────────────────────────────────────────────────────────────────────
 *  1. Créez un projet sur https://console.cloud.google.com/
 *  2. Activez l'API : "Maps Embed API"
 *  3. Copiez votre clé ci-dessous
 *
 *  Pour éviter d'exposer la clé dans ce fichier (versionné), vous pouvez
 *  aussi la définir via un filtre WP dans votre thème enfant / mu-plugin :
 *
 *     add_filter('bt_google_maps_api_key', fn() => getenv('GOOGLE_MAPS_KEY'));
 *
 * ──────────────────────────────────────────────────────────────────────────
 */

defined('ABSPATH') || exit;

return [

    // ── Clé API Google Maps ───────────────────────────────────────────────────
    'google_maps_api_key' => '',   // ← collez votre clé ici : 'AIzaSy...'

];
