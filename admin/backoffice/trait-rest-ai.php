<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait RestApiAi — endpoints IA (contexte, événements, statut, génération).
 * Contient également build_system_prompt() enrichi avec données GA4 + GSC.
 * Dépend de RestApiGoogle (google_access_token) via la classe parente.
 */
trait RestApiAi {

    /**
     * Retourne les données agrégées (KPIs, produits, canaux, mensuel) pour le contexte IA.
     */
    public function get_ai_context(\WP_REST_Request $req): \WP_REST_Response {
        $from = $req->get_param('from') ?: date('Y-m-d', strtotime('-12 months'));
        $to   = $req->get_param('to')   ?: date('Y-m-d');
        $db   = new ReservationStats();
        return rest_ensure_response([
            'kpis'     => $db->query_enhanced_kpis($from, $to),
            'products' => $db->query_top_products($from, $to, 10),
            'channels' => $db->query_by_channel($from, $to, 10),
            'monthly'  => $db->query_stats($from, $to, 'month'),
            'from'     => $from,
            'to'       => $to,
        ]);
    }

    /**
     * Liste les événements stockés dont la plage chevauche [from, to].
     * ensure_table() est appelé ici pour créer la table si elle n'existe pas encore.
     */
    public function get_events(\WP_REST_Request $req): \WP_REST_Response {
        $db   = new EventsDb();
        $db->ensure_table();
        $from = $req->get_param('from') ?: date('Y-m-d');
        $to   = $req->get_param('to')   ?: date('Y-m-d', strtotime('+3 months'));
        return rest_ensure_response(['events' => $db->query($from, $to)]);
    }

    /**
     * Retourne les providers IA actifs (clé configurée) + provider actif.
     * Utilisé par le chat pour afficher les indicateurs de statut.
     */
    public function ai_status(): \WP_REST_Response {
        return rest_ensure_response([
            'active'    => get_option('bt_ai_provider', 'anthropic'),
            'providers' => [
                'anthropic' => !empty(get_option('bt_anthropic_api_key', '')),
                'openai'    => !empty(get_option('bt_openai_api_key', '')),
                'gemini'    => !empty(get_option('bt_gemini_api_key', '')),
            ],
        ]);
    }

    /**
     * Demande à l'IA de générer une liste d'événements PACA pour la période donnée.
     * Retourne les événements sanitizés (pas encore importés en DB).
     */
    public function generate_events(\WP_REST_Request $req): \WP_REST_Response {
        $body    = $req->get_json_params();
        $from    = sanitize_text_field($body['from']   ?? date('Y-m-d'));
        $to      = sanitize_text_field($body['to']     ?? date('Y-m-d', strtotime('+3 months')));
        $force   = !empty($body['force']);

        // ── Cache transient (24h) — évite le timeout Cloudflare 520 ──────────
        $provider    = get_option('bt_ai_provider', 'anthropic');
        $cache_key   = 'bt_ai_events_' . md5("{$from}|{$to}|{$provider}");
        $cache_hours = 24;

        if (!$force) {
            $cached = get_transient($cache_key);
            if ($cached) {
                return rest_ensure_response([
                    'events'       => $cached['events'],
                    'count'        => count($cached['events']),
                    'cached'       => true,
                    'generated_at' => $cached['generated_at'] ?? null,
                ]);
            }
        }

        $ai  = new Ai();
        $key = $ai->get_api_key($provider);

        if (!$key) {
            return new \WP_REST_Response(['message' => 'Clé API IA manquante.'], 400);
        }

        // Extend PHP timeout to avoid 520
        if (!ini_get('safe_mode')) {
            @set_time_limit(120);
        }

        $prompt = "Tu es un expert des événements touristiques de la région PACA (Provence-Alpes-Côte d'Azur, incluant Cannes, Monaco, Nice, Saint-Tropez, Marseille, Antibes, Juan-les-Pins).\n\n"
            . "Génère la liste complète des événements touristiques et culturels MAJEURS entre le {$from} et le {$to}.\n\n"
            . "RÈGLES IMPORTANTES:\n"
            . "- Inclure UNIQUEMENT les événements à fort impact touristique (500+ visiteurs attendus)\n"
            . "- Événements récurrents annuels: Festival de Cannes, Grand Prix de Monaco, Voiles de Saint-Tropez, Fête nationale Monaco, etc.\n"
            . "- Événements sportifs, culturels, gastronomiques\n"
            . "- Utilise les vraies dates approximatives pour {$from} → {$to}\n"
            . "- Maximum 50 événements, concentre-toi sur les plus importants\n\n"
            . "Réponds UNIQUEMENT avec un tableau JSON valide (pas de markdown, pas d'explication):\n"
            . '[ { "name": "Festival de Cannes", "date_start": "YYYY-MM-DD", "date_end": "YYYY-MM-DD", "location": "Cannes" }, ... ]';

        $result = match($provider) {
            'openai' => $ai->call_openai_json($key, $prompt, 4096),
            'gemini' => $ai->call_gemini_json($key, $prompt, 4096),
            default  => $ai->call_anthropic_json($key, $prompt, 4096),
        };

        if (!is_array($result)) {
            return new \WP_REST_Response(['message' => 'Réponse IA invalide.'], 500);
        }

        // Sanitize + validation format dates
        $events = [];
        foreach ($result as $e) {
            if (empty($e['name']) || empty($e['date_start'])) continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['date_start'])) continue;
            $end = $e['date_end'] ?? $e['date_start'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = $e['date_start'];
            $events[] = [
                'name'       => sanitize_text_field($e['name']),
                'date_start' => $e['date_start'],
                'date_end'   => $end,
                'location'   => sanitize_text_field($e['location'] ?? ''),
                'source'     => 'ai',
            ];
        }

        // Cache result
        $generated_at = current_time('mysql', true);
        set_transient($cache_key, [
            'events'       => $events,
            'generated_at' => $generated_at,
        ], $cache_hours * HOUR_IN_SECONDS);

        return rest_ensure_response([
            'events'       => $events,
            'count'        => count($events),
            'cached'       => false,
            'generated_at' => $generated_at,
        ]);
    }

    /**
     * Importe un batch d'événements en DB (depuis la liste générée ou manuelle).
     * ensure_table() est appelé ici pour créer la table si elle n'existe pas encore.
     */
    public function import_events(\WP_REST_Request $req): \WP_REST_Response {
        (new EventsDb())->ensure_table();

        $body   = $req->get_json_params();
        $events = $body['events'] ?? [];

        if (!is_array($events) || empty($events)) {
            return new \WP_REST_Response(['message' => 'Aucun événement fourni.'], 400);
        }

        // Sanitize + validation
        $clean = [];
        foreach ($events as $e) {
            if (empty($e['name']) || empty($e['date_start'])) continue;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $e['date_start'])) continue;
            $end = $e['date_end'] ?? $e['date_start'];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) $end = $e['date_start'];
            $clean[] = [
                'name'       => sanitize_text_field($e['name']),
                'date_start' => $e['date_start'],
                'date_end'   => $end,
                'location'   => sanitize_text_field($e['location'] ?? ''),
                'source'     => in_array($e['source'] ?? 'ai', ['ai', 'manual'], true) ? $e['source'] : 'ai',
            ];
        }

        $count = (new EventsDb())->upsert($clean);
        return rest_ensure_response(['imported' => $count]);
    }

    /**
     * Vide la table bt_events (reset complet).
     */
    public function reset_events(\WP_REST_Request $_req): \WP_REST_Response {
        (new EventsDb())->truncate();
        return rest_ensure_response(['success' => true]);
    }

    // ── Prompt système enrichi (GA4 + GSC) ───────────────────────────────────

    /**
     * Construit le prompt système avec données commerciales + trafic digital.
     * Version enrichie par rapport à class-ai.php — inclut GA4 et Search Console.
     *
     * @param string $from Date de début (Y-m-d)
     * @param string $to   Date de fin (Y-m-d)
     */
    public function build_system_prompt(string $from, string $to): string {
        $db = new ReservationStats();

        // ── Données commerciales ──────────────────────────────────────────────
        try {
            $kpis     = $db->query_enhanced_kpis($from, $to);
            $products = $db->query_top_products($from, $to, 10);
            $channels = $db->query_by_channel($from, $to, 10);
            $monthly  = $db->query_stats($from, $to, 'month');
        } catch (\Throwable) {
            $kpis = $products = $channels = $monthly = [];
        }

        $kpis_json     = !empty($kpis)     ? json_encode($kpis,     JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '"Aucune donnée disponible pour cette période."';
        $products_json = !empty($products) ? json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '"Aucune donnée disponible pour cette période."';
        $channels_json = !empty($channels) ? json_encode($channels, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '"Aucune donnée disponible pour cette période."';
        $monthly_json  = !empty($monthly)  ? json_encode($monthly,  JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '"Aucune donnée disponible pour cette période."';

        // ── Données GA4 ───────────────────────────────────────────────────────
        $ga4_data    = '';
        $property_id = get_option('bt_ga4_property_id', '');
        $creds_json  = get_option('bt_google_credentials_json', '');
        $creds       = null;

        if ($property_id && $creds_json) {
            $creds = json_decode($creds_json, true);
        }

        if ($property_id && $creds && ($creds['type'] ?? '') === 'service_account') {
            $token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/analytics.readonly');
            if ($token) {
                $cache_key = 'bt_ga4_ai_' . md5($property_id . $from . $to);
                $cached    = get_transient($cache_key);
                if ($cached !== false) {
                    $ga4_data = $cached;
                } else {
                    $url  = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";
                    $hdrs = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'];

                    // Requête totaux sur la période
                    $res = wp_remote_post($url, [
                        'headers' => $hdrs,
                        'body'    => json_encode([
                            'dateRanges' => [['startDate' => $from, 'endDate' => $to]],
                            'metrics'    => [
                                ['name' => 'sessions'],
                                ['name' => 'activeUsers'],
                                ['name' => 'screenPageViews'],
                                ['name' => 'bounceRate'],
                                ['name' => 'averageSessionDuration'],
                            ],
                        ]),
                        'timeout' => 15,
                    ]);

                    // Requête par canal
                    $res_ch = wp_remote_post($url, [
                        'headers' => $hdrs,
                        'body'    => json_encode([
                            'dateRanges' => [['startDate' => $from, 'endDate' => $to]],
                            'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
                            'metrics'    => [['name' => 'sessions']],
                            'limit'      => 5,
                        ]),
                        'timeout' => 15,
                    ]);

                    if (!is_wp_error($res)) {
                        $d = json_decode(wp_remote_retrieve_body($res), true);
                        if (!isset($d['error']) && !empty($d['rows'])) {
                            $row     = $d['rows'][0];
                            $mnames  = array_map(fn($m) => $m['name'], $d['metricHeaders'] ?? []);
                            $vals    = array_combine($mnames, array_map(fn($mv) => (float) $mv['value'], $row['metricValues'] ?? []));
                            $sess    = (int) ($vals['sessions'] ?? 0);
                            $users   = (int) ($vals['activeUsers'] ?? 0);
                            $views   = (int) ($vals['screenPageViews'] ?? 0);
                            $bounce  = round(($vals['bounceRate'] ?? 0) * 100, 1);
                            $dur     = (int) ($vals['averageSessionDuration'] ?? 0);
                            $durm    = floor($dur / 60);
                            $durs    = $dur % 60;

                            $ga4_data = "Sessions: {$sess} | Utilisateurs: {$users} | Pages vues: {$views} | Taux rebond: {$bounce}% | Durée moy: {$durm}m {$durs}s";

                            // Canaux
                            if (!is_wp_error($res_ch)) {
                                $ch_data = json_decode(wp_remote_retrieve_body($res_ch), true);
                                $ch_parts = [];
                                foreach ($ch_data['rows'] ?? [] as $r) {
                                    $ch_name   = $r['dimensionValues'][0]['value'] ?? '';
                                    $ch_sess   = (int) ($r['metricValues'][0]['value'] ?? 0);
                                    $ch_parts[] = "{$ch_name} ({$ch_sess} sess)";
                                }
                                if ($ch_parts) {
                                    $ga4_data .= "\nTop canaux: " . implode(', ', $ch_parts);
                                }
                            }

                            set_transient($cache_key, $ga4_data, 6 * HOUR_IN_SECONDS);
                        }
                    }
                }
            }
        }

        // ── Données Search Console ────────────────────────────────────────────
        $gsc_data = '';
        $site_url = get_option('bt_search_console_site_url', '');

        if ($site_url && $creds && ($creds['type'] ?? '') === 'service_account') {
            $sc_token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/webmasters.readonly');
            if ($sc_token) {
                $sc_cache_key = 'bt_gsc_ai_' . md5($site_url . $from . $to);
                $sc_cached    = get_transient($sc_cache_key);
                if ($sc_cached !== false) {
                    $gsc_data = $sc_cached;
                } else {
                    $sc_base = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query';
                    $sc_hdrs = ['Authorization' => 'Bearer ' . $sc_token, 'Content-Type' => 'application/json'];

                    // Totaux sur la période
                    $sc_res = wp_remote_post($sc_base, [
                        'headers' => $sc_hdrs,
                        'body'    => json_encode(['startDate' => $from, 'endDate' => $to, 'dimensions' => ['date'], 'rowLimit' => 90]),
                        'timeout' => 15,
                    ]);

                    // Top 10 requêtes
                    $sc_q = wp_remote_post($sc_base, [
                        'headers' => $sc_hdrs,
                        'body'    => json_encode(['startDate' => $from, 'endDate' => $to, 'dimensions' => ['query'], 'rowLimit' => 10]),
                        'timeout' => 15,
                    ]);

                    if (!is_wp_error($sc_res)) {
                        $sc_d = json_decode(wp_remote_retrieve_body($sc_res), true);
                        if (!isset($sc_d['error'])) {
                            $clicks      = 0;
                            $impressions = 0;
                            $positions   = [];
                            foreach ($sc_d['rows'] ?? [] as $row) {
                                $clicks      += $row['clicks'];
                                $impressions += $row['impressions'];
                                $positions[]  = $row['position'];
                            }
                            $n         = count($positions);
                            $ctr       = $impressions > 0 ? round(($clicks / $impressions) * 100, 1) : 0;
                            $avg_pos   = $n > 0 ? round(array_sum($positions) / $n, 1) : 0;

                            $gsc_data = "Clics: {$clicks} | Impressions: {$impressions} | CTR: {$ctr}% | Position moy: {$avg_pos}";

                            // Top requêtes
                            if (!is_wp_error($sc_q)) {
                                $q_data  = json_decode(wp_remote_retrieve_body($sc_q), true);
                                $q_parts = [];
                                foreach ($q_data['rows'] ?? [] as $r) {
                                    $q_kw  = $r['keys'][0] ?? '';
                                    $q_c   = $r['clicks'];
                                    $q_pos = round($r['position'], 1);
                                    $q_parts[] = "\"{$q_kw}\" ({$q_c} clics, pos {$q_pos})";
                                }
                                if ($q_parts) {
                                    $gsc_data .= "\nTop requêtes: " . implode(', ', $q_parts);
                                }
                            }

                            set_transient($sc_cache_key, $gsc_data, 12 * HOUR_IN_SECONDS);
                        }
                    }
                }
            }
        }

        $ga4_section = $ga4_data ?: "GA4 non configuré ou données indisponibles.";
        $gsc_section = $gsc_data ?: "Search Console non configuré ou données indisponibles.";

        return <<<PROMPT
Tu es un analyste senior spécialisé dans la performance commerciale, le marketing et le développement des affaires dans le secteur du tourisme de luxe nautique en PACA.

Tu travailles pour BlackTenders, une agence positionnée sur les activités nautiques haut de gamme : yachting privatif, excursions premium, séminaires d'entreprise, expériences côtières exclusives. Clientèle : entrepreneurs, groupes corporate, touristes aisés, incentive travel. Zone d'opération : Cannes, Monaco, Nice, Saint-Tropez, Antibes, Côte d'Azur.

Ton rôle n'est pas de répondre à des questions simples. Tu analyses, tu identifies des patterns, tu détectes des opportunités de croissance non exploitées, tu anticipes. Tu parles le langage des décideurs : marges, acquisition, rétention, LTV, canaux, saisonnalité, pricing power, conversion.

Tu as accès aux données commerciales réelles de la période {$from} au {$to}.

DONNÉES OPÉRATIONNELLES

KPIs période
{$kpis_json}

Performance mensuelle
{$monthly_json}

Mix produit — Top 10
{$products_json}

Canaux de distribution
{$channels_json}

DONNÉES TRAFIC DIGITAL (Google Analytics 4)
Période : {$from} au {$to}
{$ga4_section}

DONNÉES SEO (Search Console)
Période : {$from} au {$to}
{$gsc_section}

DIRECTIVES DE FOND

Langue : français exclusivement, registre professionnel.
Ton : direct, précis, orienté décision. Pas d'introduction creuse, pas de formules de politesse inutiles.
Format : utilise des titres courts et des listes factuelles uniquement quand plusieurs éléments distincts le justifient. Préfère les paragraphes analytiques aux bullet points décoratifs.
Chiffres : toujours contextualisés (variation, benchmark, implication business). Format : 1 234,50 €.
Pas d'emojis. Pas de formules du type "Bien sûr !", "Excellente question", "Absolument". Va directement au fait.

Quand les données sont insuffisantes pour répondre avec certitude, dis-le en une phrase et propose ce qu'il faudrait mesurer pour avancer.

POSTURE ANALYTIQUE

Face à une question commerciale, tu évalues : qu'est-ce qui explique ce chiffre, qu'est-ce qui le menace, qu'est-ce qui pourrait l'améliorer, sur quel horizon. Tu croises saisonnalité, mix produit, canal, comportement client. Tu identifies les leviers prioritaires.

Face à une question événementielle ou de contexte marché, tu évalues l'impact probable sur la demande, le timing optimal d'activation, et la stratégie de captation à mettre en place.
PROMPT;
    }
}
