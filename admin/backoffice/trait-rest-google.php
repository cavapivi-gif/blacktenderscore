<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiGoogle — Google OAuth2, GA4, Search Console handlers.
 * Fournit l'accès token JWT RS256 + les endpoints de stats Google.
 */
trait RestApiGoogle {

    // ── Google OAuth2 helper ──────────────────────────────────────────────────

    /**
     * Génère un JWT RS256 et l'échange contre un access token Google OAuth2.
     * Utilisé pour GA4 et Search Console (même service account).
     *
     * @param array  $creds Service account JSON parsé
     * @param string $scope OAuth2 scope requis
     * @return string|false Access token ou false en cas d'erreur
     */
    private function google_access_token(array $creds, string $scope) {
        if (empty($creds['private_key']) || empty($creds['client_email'])) {
            return false;
        }

        $now    = time();
        $header = rtrim(strtr(base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'])), '+/', '-_'), '=');
        $claims = rtrim(strtr(base64_encode(json_encode([
            'iss'   => $creds['client_email'],
            'scope' => $scope,
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
        ])), '+/', '-_'), '=');

        $input = $header . '.' . $claims;
        if (!openssl_sign($input, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) {
            return false;
        }
        $jwt = $input . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $res = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body'    => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'timeout' => 15,
        ]);

        if (is_wp_error($res)) return false;
        $data = json_decode(wp_remote_retrieve_body($res), true);
        return $data['access_token'] ?? false;
    }

    // ── Google Analytics 4 ────────────────────────────────────────────────────

    /**
     * Retourne sessions, activeUsers, pageViews, bounceRate et durée moyenne sur la période.
     * Nécessite un service account avec accès en lecture au property GA4.
     */
    public function get_ga4_stats(\WP_REST_Request $req): \WP_REST_Response {
        $from         = sanitize_text_field($req->get_param('from')         ?: date('Y-m-d', strtotime('-30 days')));
        $to           = sanitize_text_field($req->get_param('to')           ?: date('Y-m-d'));
        $compare_from = sanitize_text_field($req->get_param('compare_from') ?: '');
        $compare_to   = sanitize_text_field($req->get_param('compare_to')   ?: '');
        $has_compare  = $compare_from && $compare_to;
        $property_id  = get_option('bt_ga4_property_id', '');
        $creds_json   = get_option('bt_google_credentials_json', '');

        if (!$property_id || !$creds_json) {
            return rest_ensure_response(['configured' => false, 'error' => 'GA4 non configuré.']);
        }
        // Pas de stripslashes : le JSON est stocké brut (wp_slash() non utilisé à l'écriture)
        $creds = json_decode($creds_json, true);
        if (!$creds || ($creds['type'] ?? '') !== 'service_account') {
            return rest_ensure_response(['configured' => false, 'error' => 'Credentials invalides.']);
        }

        // Transient cache — clé inclut les params de comparaison
        $cache_key = 'bt_ga4_' . md5("{$property_id}_{$from}_{$to}_{$compare_from}_{$compare_to}");
        $cached    = get_transient($cache_key);
        if ($cached !== false) return rest_ensure_response($cached);

        $token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/analytics.readonly');
        if (!$token) {
            return rest_ensure_response(['configured' => true, 'error' => 'Impossible d\'obtenir le token Google.']);
        }

        $url     = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";
        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'];

        // ── Requête principale : timeline par date ─────────────────────────
        $res = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode([
                'dateRanges' => [['startDate' => $from, 'endDate' => $to]],
                'metrics'    => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'screenPageViews'],
                    ['name' => 'bounceRate'],
                    ['name' => 'averageSessionDuration'],
                    ['name' => 'newUsers'],
                    ['name' => 'conversions'],
                    ['name' => 'engagementRate'],
                ],
                'dimensions' => [['name' => 'date']],
                'orderBys'   => [['dimension' => ['dimensionName' => 'date']]],
                'limit'      => 365,
            ]),
            'timeout' => 20,
        ]);

        if (is_wp_error($res)) {
            return rest_ensure_response(['configured' => true, 'error' => $res->get_error_message()]);
        }
        $data = json_decode(wp_remote_retrieve_body($res), true);
        if (isset($data['error'])) {
            return rest_ensure_response(['configured' => true, 'error' => $data['error']['message'] ?? 'Erreur GA4']);
        }

        $metric_names = array_map(fn($m) => $m['name'], $data['metricHeaders'] ?? []);
        $totals       = array_fill_keys($metric_names, 0);
        $timeline     = [];

        foreach ($data['rows'] ?? [] as $row) {
            $raw  = $row['dimensionValues'][0]['value'] ?? '';
            $date = strlen($raw) === 8
                ? substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2)
                : $raw;
            $entry = ['date' => $date];
            foreach ($row['metricValues'] as $i => $mv) {
                $key           = $metric_names[$i] ?? "m{$i}";
                $val           = (float) ($mv['value'] ?? 0);
                $totals[$key] += $val;
                $entry[$key]   = $val;
            }
            $timeline[] = $entry;
        }
        // bounceRate et engagementRate = moyenne pondérée, pas somme
        if (!empty($timeline)) {
            $n = count($timeline);
            $totals['bounceRate']             = round($totals['bounceRate'] / $n, 3);
            $totals['averageSessionDuration'] = round($totals['averageSessionDuration'] / $n, 1);
            $totals['engagementRate']         = round($totals['engagementRate'] / $n, 3);
        }

        // ── Requête par canal d'acquisition ───────────────────────────────
        $res_channels = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode([
                'dateRanges' => [['startDate' => $from, 'endDate' => $to]],
                'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
                'metrics'    => [
                    ['name' => 'sessions'],
                    ['name' => 'activeUsers'],
                    ['name' => 'conversions'],
                ],
                'limit' => 10,
            ]),
            'timeout' => 20,
        ]);
        $channels_data = is_wp_error($res_channels) ? [] : (json_decode(wp_remote_retrieve_body($res_channels), true)['rows'] ?? []);
        $by_channel = array_map(fn($r) => [
            'channel'     => $r['dimensionValues'][0]['value'] ?? '',
            'sessions'    => (int) ($r['metricValues'][0]['value'] ?? 0),
            'users'       => (int) ($r['metricValues'][1]['value'] ?? 0),
            'conversions' => (int) ($r['metricValues'][2]['value'] ?? 0),
        ], $channels_data);

        // ── Requête top pages ──────────────────────────────────────────────
        $res_pages = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode([
                'dateRanges' => [['startDate' => $from, 'endDate' => $to]],
                'dimensions' => [['name' => 'pagePath']],
                'metrics'    => [
                    ['name' => 'screenPageViews'],
                    ['name' => 'sessions'],
                    ['name' => 'bounceRate'],
                    ['name' => 'averageSessionDuration'],
                ],
                'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                'limit'    => 20,
            ]),
            'timeout' => 20,
        ]);
        $pages_data = is_wp_error($res_pages) ? [] : (json_decode(wp_remote_retrieve_body($res_pages), true)['rows'] ?? []);
        $top_pages = array_map(fn($r) => [
            'page'        => $r['dimensionValues'][0]['value'] ?? '',
            'views'       => (int) ($r['metricValues'][0]['value'] ?? 0),
            'sessions'    => (int) ($r['metricValues'][1]['value'] ?? 0),
            'bounceRate'  => round((float) ($r['metricValues'][2]['value'] ?? 0), 3),
            'avgDuration' => round((float) ($r['metricValues'][3]['value'] ?? 0), 1),
        ], $pages_data);

        // ── Période de comparaison (optionnelle) ───────────────────────────
        $totals_compare  = null;
        $timeline_compare = null;
        if ($has_compare) {
            $res_cmp = wp_remote_post($url, [
                'headers' => $headers,
                'body'    => json_encode([
                    'dateRanges' => [['startDate' => $compare_from, 'endDate' => $compare_to]],
                    'metrics'    => [
                        ['name' => 'sessions'],
                        ['name' => 'activeUsers'],
                        ['name' => 'screenPageViews'],
                        ['name' => 'bounceRate'],
                        ['name' => 'averageSessionDuration'],
                        ['name' => 'newUsers'],
                        ['name' => 'conversions'],
                        ['name' => 'engagementRate'],
                    ],
                    'dimensions' => [['name' => 'date']],
                    'orderBys'   => [['dimension' => ['dimensionName' => 'date']]],
                    'limit'      => 365,
                ]),
                'timeout' => 20,
            ]);
            if (!is_wp_error($res_cmp)) {
                $cmp_data    = json_decode(wp_remote_retrieve_body($res_cmp), true);
                $cmp_metrics = array_map(fn($m) => $m['name'], $cmp_data['metricHeaders'] ?? []);
                $totals_compare   = array_fill_keys($cmp_metrics, 0);
                $timeline_compare = [];
                foreach ($cmp_data['rows'] ?? [] as $row) {
                    $raw  = $row['dimensionValues'][0]['value'] ?? '';
                    $date = strlen($raw) === 8
                        ? substr($raw, 0, 4) . '-' . substr($raw, 4, 2) . '-' . substr($raw, 6, 2)
                        : $raw;
                    $entry = ['date' => $date];
                    foreach ($row['metricValues'] as $i => $mv) {
                        $key                    = $cmp_metrics[$i] ?? "m{$i}";
                        $val                    = (float) ($mv['value'] ?? 0);
                        $totals_compare[$key]  += $val;
                        $entry[$key]            = $val;
                    }
                    $timeline_compare[] = $entry;
                }
                if (!empty($timeline_compare)) {
                    $n = count($timeline_compare);
                    $totals_compare['bounceRate']             = round($totals_compare['bounceRate'] / $n, 3);
                    $totals_compare['averageSessionDuration'] = round($totals_compare['averageSessionDuration'] / $n, 1);
                    $totals_compare['engagementRate']         = round($totals_compare['engagementRate'] / $n, 3);
                }
            }
        }

        $result = array_filter([
            'configured'       => true,
            'totals'           => $totals,
            'timeline'         => $timeline,
            'by_channel'       => $by_channel,
            'top_pages'        => $top_pages,
            'totals_compare'   => $totals_compare,
            'timeline_compare' => $timeline_compare,
        ], fn($v) => $v !== null);

        // Mise en cache selon la durée configurée
        $ttl = (int) get_option('bt_ga4_cache_hours', 6) * HOUR_IN_SECONDS;
        set_transient($cache_key, $result, $ttl);

        return rest_ensure_response($result);
    }

    // ── Google Search Console ─────────────────────────────────────────────────

    /**
     * Retourne clics, impressions, CTR, position moyenne + top 20 requêtes.
     * Nécessite un service account avec accès au site dans Search Console.
     */
    public function get_search_console_stats(\WP_REST_Request $req): \WP_REST_Response {
        $from         = sanitize_text_field($req->get_param('from')         ?: date('Y-m-d', strtotime('-30 days')));
        $to           = sanitize_text_field($req->get_param('to')           ?: date('Y-m-d'));
        $compare_from = sanitize_text_field($req->get_param('compare_from') ?: '');
        $compare_to   = sanitize_text_field($req->get_param('compare_to')   ?: '');
        $has_compare  = $compare_from && $compare_to;
        $site_url     = get_option('bt_search_console_site_url', '');
        $creds_json   = get_option('bt_google_credentials_json', '');

        if (!$site_url || !$creds_json) {
            return rest_ensure_response(['configured' => false, 'error' => 'Search Console non configuré.']);
        }
        $creds = json_decode($creds_json, true);
        if (!$creds) {
            return rest_ensure_response(['configured' => false, 'error' => 'Credentials invalides.']);
        }

        // Transient cache
        $cache_key = 'bt_gsc_' . md5("{$site_url}_{$from}_{$to}_{$compare_from}_{$compare_to}");
        $cached    = get_transient($cache_key);
        if ($cached !== false) return rest_ensure_response($cached);

        $token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/webmasters.readonly');
        if (!$token) {
            return rest_ensure_response(['configured' => true, 'error' => 'Impossible d\'obtenir le token Google.']);
        }

        $base    = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query';
        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'];

        // ── Timeline (par date) ────────────────────────────────────────────
        $res_timeline = wp_remote_post($base, [
            'headers' => $headers,
            'body'    => json_encode(['startDate' => $from, 'endDate' => $to, 'dimensions' => ['date'], 'rowLimit' => 90]),
            'timeout' => 20,
        ]);

        // ── Top requêtes (50 rows) ─────────────────────────────────────────
        $res_queries = wp_remote_post($base, [
            'headers' => $headers,
            'body'    => json_encode([
                'startDate'  => $from,
                'endDate'    => $to,
                'dimensions' => ['query'],
                'rowLimit'   => 50,
            ]),
            'timeout' => 20,
        ]);

        // ── Top pages ──────────────────────────────────────────────────────
        $res_pages = wp_remote_post($base, [
            'headers' => $headers,
            'body'    => json_encode([
                'startDate'  => $from,
                'endDate'    => $to,
                'dimensions' => ['page'],
                'rowLimit'   => 25,
                'orderBy'    => 'CLICKS_DESCENDING',
            ]),
            'timeout' => 20,
        ]);

        // ── Requête étendue query+page pour quick_wins + cannibalisation ──
        $res_qp = wp_remote_post($base, [
            'headers' => $headers,
            'body'    => json_encode([
                'startDate'  => $from,
                'endDate'    => $to,
                'dimensions' => ['query', 'page'],
                'rowLimit'   => 200,
            ]),
            'timeout' => 25,
        ]);

        if (is_wp_error($res_timeline)) {
            return rest_ensure_response(['configured' => true, 'error' => $res_timeline->get_error_message()]);
        }
        $tdata = json_decode(wp_remote_retrieve_body($res_timeline), true);
        if (isset($tdata['error'])) {
            return rest_ensure_response(['configured' => true, 'error' => $tdata['error']['message'] ?? 'Erreur Search Console']);
        }

        // ── Construire timeline + totals ───────────────────────────────────
        $timeline = [];
        $totals   = ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];
        foreach ($tdata['rows'] ?? [] as $row) {
            $totals['clicks']      += $row['clicks'];
            $totals['impressions'] += $row['impressions'];
            $timeline[] = [
                'date'        => $row['keys'][0],
                'clicks'      => $row['clicks'],
                'impressions' => $row['impressions'],
                'ctr'         => round($row['ctr'] * 100, 1),
                'position'    => round($row['position'], 1),
            ];
        }
        $n = count($tdata['rows'] ?? []);
        if ($n > 0) {
            $totals['ctr']      = round(($totals['clicks'] / max(1, $totals['impressions'])) * 100, 1);
            $totals['position'] = round(array_sum(array_column($tdata['rows'], 'position')) / $n, 1);
        }

        // ── Top requêtes ───────────────────────────────────────────────────
        $qdata   = json_decode(wp_remote_retrieve_body($res_queries), true);
        $queries = array_map(fn($r) => [
            'query'       => $r['keys'][0],
            'clicks'      => $r['clicks'],
            'impressions' => $r['impressions'],
            'ctr'         => round($r['ctr'] * 100, 1),
            'position'    => round($r['position'], 1),
        ], $qdata['rows'] ?? []);

        // ── Top pages ──────────────────────────────────────────────────────
        $pdata     = json_decode(wp_remote_retrieve_body($res_pages), true);
        $top_pages = array_map(fn($r) => [
            'page'        => $r['keys'][0],
            'clicks'      => $r['clicks'],
            'impressions' => $r['impressions'],
            'ctr'         => round($r['ctr'] * 100, 1),
            'position'    => round($r['position'], 1),
        ], $pdata['rows'] ?? []);

        // ── Quick wins + cannibalisation depuis query+page ─────────────────
        $qp_rows      = is_wp_error($res_qp) ? [] : (json_decode(wp_remote_retrieve_body($res_qp), true)['rows'] ?? []);
        $quick_wins   = [];
        $cannib_map   = [];

        foreach ($qp_rows as $row) {
            $query       = $row['keys'][0] ?? '';
            $page        = $row['keys'][1] ?? '';
            $clicks      = $row['clicks'];
            $impressions = $row['impressions'];
            $ctr         = round($row['ctr'] * 100, 1);
            $position    = round($row['position'], 1);

            // Quick wins : position 4–15 et impressions >= 50
            if ($position >= 4 && $position <= 15 && $impressions >= 50) {
                $quick_wins[] = [
                    'query'           => $query,
                    'page'            => $page,
                    'clicks'          => $clicks,
                    'impressions'     => $impressions,
                    'ctr'             => $ctr,
                    'position'        => $position,
                    'potential_clicks' => (int) round($impressions * 0.03),
                ];
            }

            // Cannibalisation : grouper par requête
            if (!isset($cannib_map[$query])) {
                $cannib_map[$query] = [];
            }
            $cannib_map[$query][] = ['page' => $page, 'clicks' => $clicks, 'impressions' => $impressions, 'position' => $position];
        }

        // Trier quick_wins par impressions DESC, garder top 20
        usort($quick_wins, fn($a, $b) => $b['impressions'] - $a['impressions']);
        $quick_wins = array_slice($quick_wins, 0, 20);

        // Garder seulement les requêtes avec ≥2 pages, limiter à 15
        $cannibalisation = [];
        foreach ($cannib_map as $query => $pages) {
            if (count($pages) >= 2) {
                $cannibalisation[] = ['query' => $query, 'pages' => $pages];
            }
            if (count($cannibalisation) >= 15) break;
        }

        // ── Comparaison (optionnelle) ──────────────────────────────────────
        $totals_compare   = null;
        $timeline_compare = null;
        if ($has_compare) {
            $res_cmp = wp_remote_post($base, [
                'headers' => $headers,
                'body'    => json_encode(['startDate' => $compare_from, 'endDate' => $compare_to, 'dimensions' => ['date'], 'rowLimit' => 90]),
                'timeout' => 20,
            ]);
            if (!is_wp_error($res_cmp)) {
                $cdata            = json_decode(wp_remote_retrieve_body($res_cmp), true);
                $totals_compare   = ['clicks' => 0, 'impressions' => 0, 'ctr' => 0, 'position' => 0];
                $timeline_compare = [];
                foreach ($cdata['rows'] ?? [] as $row) {
                    $totals_compare['clicks']      += $row['clicks'];
                    $totals_compare['impressions'] += $row['impressions'];
                    $timeline_compare[] = [
                        'date'        => $row['keys'][0],
                        'clicks'      => $row['clicks'],
                        'impressions' => $row['impressions'],
                        'ctr'         => round($row['ctr'] * 100, 1),
                        'position'    => round($row['position'], 1),
                    ];
                }
                $nc = count($cdata['rows'] ?? []);
                if ($nc > 0) {
                    $totals_compare['ctr']      = round(($totals_compare['clicks'] / max(1, $totals_compare['impressions'])) * 100, 1);
                    $totals_compare['position'] = round(array_sum(array_column($cdata['rows'] ?? [], 'position')) / $nc, 1);
                }
            }
        }

        $result = array_filter([
            'configured'      => true,
            'totals'          => $totals,
            'timeline'        => $timeline,
            'queries'         => $queries,
            'top_pages'       => $top_pages,
            'quick_wins'      => $quick_wins,
            'cannibalisation' => $cannibalisation,
            'totals_compare'   => $totals_compare,
            'timeline_compare' => $timeline_compare,
        ], fn($v) => $v !== null);

        // Mise en cache
        $ttl = (int) get_option('bt_gsc_cache_hours', 12) * HOUR_IN_SECONDS;
        set_transient($cache_key, $result, $ttl);

        return rest_ensure_response($result);
    }

    // ── Google — test de connexion ────────────────────────────────────────────

    /**
     * Teste les credentials Google, liste les propriétés/sites accessibles,
     * et vérifie l'accès à la propriété GA4 + site Search Console configurés.
     *
     * Retourne :
     *   ga4.accessible_properties  — toutes les propriétés GA4 accessibles au service account
     *   search_console.accessible_sites — tous les sites SC accessibles
     */
    public function test_google(\WP_REST_Request $req): \WP_REST_Response {
        $creds_json  = get_option('bt_google_credentials_json', '');
        $property_id = trim(get_option('bt_ga4_property_id', ''));
        $site_url    = get_option('bt_search_console_site_url', '');

        if (!$creds_json) {
            return rest_ensure_response(['success' => false, 'error' => 'Service account JSON non configuré.']);
        }

        $creds = json_decode($creds_json, true);
        if (!$creds || ($creds['type'] ?? '') !== 'service_account') {
            return rest_ensure_response(['success' => false, 'error' => 'JSON invalide — doit être un service_account Google.']);
        }

        // ── Validation locale du Property ID avant tout appel réseau ─────────
        if ($property_id && !ctype_digit($property_id)) {
            return rest_ensure_response([
                'success' => false,
                'error'   => "Property ID invalide : « {$property_id} ». Le Property ID est un nombre (ex: 412345678). "
                           . "Si vous avez un ID commençant par G-, c'est un Measurement ID — placez-le dans le champ dédié.",
            ]);
        }

        $ga4_result = ['configured' => (bool) $property_id];
        $sc_result  = ['configured' => (bool) $site_url];

        // ── GA4 : token + liste des propriétés accessibles + test propriété configurée ──
        $ga4_token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/analytics.readonly');
        if (!$ga4_token) {
            $ga4_result['error'] = 'Impossible d\'obtenir le token OAuth2 — vérifiez la clé privée du service account.';
        } else {
            // 1. Liste tous les comptes/propriétés accessibles (GA4 Admin API)
            $summary_res = wp_remote_get(
                'https://analyticsadmin.googleapis.com/v1beta/accountSummaries',
                ['headers' => ['Authorization' => 'Bearer ' . $ga4_token], 'timeout' => 10]
            );
            if (!is_wp_error($summary_res)) {
                $summary_data = json_decode(wp_remote_retrieve_body($summary_res), true);
                $accessible   = [];
                foreach ($summary_data['accountSummaries'] ?? [] as $account) {
                    foreach ($account['propertySummaries'] ?? [] as $prop) {
                        // name = "properties/123456789"
                        $pid = str_replace('properties/', '', $prop['property'] ?? '');
                        $accessible[] = [
                            'property_id'   => $pid,
                            'display_name'  => $prop['displayName'] ?? '',
                            'account_name'  => $account['displayName'] ?? '',
                        ];
                    }
                }
                $ga4_result['accessible_properties'] = $accessible;
            }

            // 2. Test la propriété configurée (si renseignée)
            if ($property_id) {
                $prop_res = wp_remote_get(
                    "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}",
                    ['headers' => ['Authorization' => 'Bearer ' . $ga4_token], 'timeout' => 10]
                );
                if (is_wp_error($prop_res)) {
                    $ga4_result['error'] = $prop_res->get_error_message();
                } else {
                    $data = json_decode(wp_remote_retrieve_body($prop_res), true);
                    if (isset($data['error'])) {
                        $code = $data['error']['code'] ?? 0;
                        $msg  = $data['error']['message'] ?? 'Erreur GA4';
                        $ga4_result['error'] = $code === 403
                            ? "Accès refusé pour la propriété {$property_id} — le service account n'a pas été ajouté dans GA4 → Administration → Gestion des accès (rôle Lecteur)."
                            : $msg;
                    } else {
                        $ga4_result['ok']           = true;
                        $ga4_result['display_name'] = $data['displayName'] ?? '';
                    }
                }
            }
        }

        // ── Search Console : token + liste des sites accessibles + test site configuré ──
        $sc_token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/webmasters.readonly');
        if (!$sc_token) {
            $sc_result['error'] = 'Impossible d\'obtenir le token OAuth2 pour Search Console.';
        } else {
            // 1. Liste tous les sites accessibles (toujours — même si le site configuré échoue)
            $sites_res  = wp_remote_get(
                'https://searchconsole.googleapis.com/webmasters/v3/sites',
                ['headers' => ['Authorization' => 'Bearer ' . $sc_token], 'timeout' => 10]
            );
            if (is_wp_error($sites_res)) {
                $sc_result['list_error'] = $sites_res->get_error_message();
            } else {
                $sites_data = json_decode(wp_remote_retrieve_body($sites_res), true);
                if (isset($sites_data['error'])) {
                    // Expose l'erreur brute pour diagnostic (ex: API non activée)
                    $sc_result['list_error'] = $sites_data['error']['message'] ?? 'Erreur lors du listing des sites';
                } else {
                    $sc_result['accessible_sites'] = array_map(fn($s) => [
                        'url'              => $s['siteUrl'],
                        'permission_level' => $s['permissionLevel'] ?? '',
                    ], $sites_data['siteEntry'] ?? []);
                }
            }

            // 2. Test le site configuré — cross-référence avec la liste accessible
            // (plus fiable que les appels individuels qui peuvent renvoyer 403 même avec accès)
            if ($site_url) {
                // Construit un index URL → permission depuis la liste (si disponible)
                $accessible_map = [];
                foreach ($sc_result['accessible_sites'] ?? [] as $s) {
                    $accessible_map[$s['url']] = $s['permission_level'];
                }

                if (!empty($accessible_map)) {
                    // Priorité 1 : match exact
                    if (isset($accessible_map[$site_url])) {
                        $sc_result['ok']               = true;
                        $sc_result['permission_level'] = $accessible_map[$site_url];

                    // Priorité 2 : variante sc-domain: (propriété Domain vérifiée par DNS)
                    } elseif (preg_match('#^https?://(?:www\.)?([^/]+?)/?$#i', $site_url, $m)) {
                        $sc_domain_key = 'sc-domain:' . $m[1];
                        if (isset($accessible_map[$sc_domain_key])) {
                            $sc_result['ok']               = true;
                            $sc_result['permission_level'] = $accessible_map[$sc_domain_key];
                            $sc_result['correct_url']      = $sc_domain_key; // UI proposera la correction
                        } else {
                            $sc_result['ok']    = false;
                            $sc_result['error'] = "Le site « {$site_url} » n'est pas accessible à ce service account. Utilisez « Utiliser » sur le bon site dans la liste ci-dessous.";
                        }
                    } else {
                        $sc_result['ok']    = false;
                        $sc_result['error'] = "Le site « {$site_url} » n'est pas accessible à ce service account.";
                    }
                } else {
                    // Liste non disponible (API SC non activée ?) : appel direct en fallback
                    $try_urls = [$site_url];
                    if (preg_match('#^https?://(?:www\.)?([^/]+)#i', $site_url, $m)) {
                        $try_urls[] = 'sc-domain:' . $m[1];
                    }
                    $found = false;
                    foreach ($try_urls as $try_url) {
                        $site_res = wp_remote_get(
                            'https://searchconsole.googleapis.com/webmasters/v3/sites/' . urlencode($try_url),
                            ['headers' => ['Authorization' => 'Bearer ' . $sc_token], 'timeout' => 10]
                        );
                        if (is_wp_error($site_res)) { $sc_result['error'] = $site_res->get_error_message(); break; }
                        $data = json_decode(wp_remote_retrieve_body($site_res), true);
                        if (!isset($data['error'])) {
                            $sc_result['ok']               = true;
                            $sc_result['permission_level'] = $data['permissionLevel'] ?? '';
                            if ($try_url !== $site_url) { $sc_result['correct_url'] = $try_url; }
                            $found = true; break;
                        }
                    }
                    if (!$found && !isset($sc_result['error'])) {
                        $sc_result['error'] = "Accès refusé pour « {$site_url} ». Vérifiez les droits du service account dans Search Console.";
                    }
                }
            }
        }

        return rest_ensure_response([
            'success'        => true,
            'client_email'   => $creds['client_email'] ?? '',
            'ga4'            => $ga4_result,
            'search_console' => $sc_result,
        ]);
    }

    /** Vide tous les transients GA4 (bt_ga4_*). */
    public function flush_ga4_cache(): \WP_REST_Response {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bt_ga4_%' OR option_name LIKE '_transient_timeout_bt_ga4_%'");
        return rest_ensure_response(['success' => true]);
    }

    /** Vide tous les transients Search Console (bt_gsc_*). */
    public function flush_gsc_cache(): \WP_REST_Response {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bt_gsc_%' OR option_name LIKE '_transient_timeout_bt_gsc_%'");
        return rest_ensure_response(['success' => true]);
    }
}
