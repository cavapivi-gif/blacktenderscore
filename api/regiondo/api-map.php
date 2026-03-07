<?php
/**
 * Regiondo API — Carte des endpoints disponibles
 *
 * Référence rapide pour l'IA ou le développeur.
 * BASE URL : https://api.regiondo.com/v1/
 * AUTH     : HMAC-SHA256 → headers X-API-ID, X-API-HASH, X-API-TIME
 *
 * MÉTHODES DISPONIBLES (class-client.php) :
 * ──────────────────────────────────────────────────────────────────
 *
 * PRODUITS
 *   get_products($locale)                  GET /products
 *   get_product($id, $locale)              GET /products/{id}
 *   get_variations($id, $locale)           GET /products/variations/{id}
 *   get_crossselling($id, $locale)         GET /products/crossselling/{id}
 *   get_navigation_attributes()            GET /products/navigationattributes
 *
 * CATALOGUE
 *   get_categories($locale)                GET /categories
 *   get_category($id, $locale)             GET /categories/{id}
 *   get_languages()                        GET /languages
 *   get_locations($query)                  GET /locations
 *
 * PARTENAIRE / REPORTING
 *   get_bookings($params)                  GET /partner/bookings
 *                                              params: page, per_page, from, to, product_id, order_number
 *   get_sold_items($params)                GET /partner/solditems
 *   get_crm_customers($params)             GET /partner/crmcustomers
 *   update_crm_customer($email, $sub)      PUT /partner/crmcustomers
 *
 * COMPTE
 *   get_account_locale()                   GET /account/locale
 *   get_account_currency()                 GET /account/currency
 *
 * CHECKOUT (usage back-office rare)
 *   checkout_totals($items)                POST /checkout/totals
 *   cancel_tickets($ref_ids)               POST /checkout/cancel
 *   get_booking_info($ref)                 GET  /checkout/booking
 *
 * ──────────────────────────────────────────────────────────────────
 * CHAMPS CLÉS — Réponse /products/{id} :
 *   product_id       int
 *   name             string
 *   description      string (HTML)
 *   base_price       float
 *   currency_code    string  (EUR, USD …)
 *   category_id      int
 *   images[]         array { url }
 *   languages[]      array  string
 *
 * CHAMPS CLÉS — Réponse /partner/bookings :
 *   booking_ref      string
 *   product_id       int
 *   product_name     string
 *   booking_date     string (ISO 8601)
 *   customer_name    string
 *   total_price      float
 *   currency_code    string
 *   status           string  (confirmed, cancelled …)
 *
 * CHAMPS CLÉS — Réponse /partner/crmcustomers :
 *   customer_id      int
 *   first_name       string
 *   last_name        string
 *   email            string
 *   newsletter       bool
 *
 * ──────────────────────────────────────────────────────────────────
 * NOTE : Aucun endpoint /reviews ni /ratings n'existe dans l'API.
 */

defined('ABSPATH') || exit;
