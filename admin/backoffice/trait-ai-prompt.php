<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiPrompt — construction du prompt système avec données commerciales + GA4 + GSC.
 * Contient aussi google_access_token() (JWT RS256) pour rester autonome.
 */
trait AiPrompt {

    /**
     * Génère un access token Google OAuth2 via JWT RS256 (service account).
     * Copie autonome — évite de dépendre de RestApiGoogle dans class Ai.
     *
     * @param array  $creds Service account JSON parsé
     * @param string $scope OAuth2 scope requis
     * @return string|false Access token ou false
     */
    private function google_access_token(array $creds, string $scope) {
        if (empty($creds['private_key']) || empty($creds['client_email'])) return false;

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
        if (!openssl_sign($input, $signature, $creds['private_key'], OPENSSL_ALGO_SHA256)) return false;

        $jwt = $input . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $res = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body'    => http_build_query(['grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'assertion' => $jwt]),
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
            'timeout' => 15,
        ]);

        if (is_wp_error($res)) return false;
        $data = json_decode(wp_remote_retrieve_body($res), true);
        return $data['access_token'] ?? false;
    }

    /**
     * Construit le prompt système injecté dans chaque conversation.
     * Enrichi avec données commerciales (réservations) + GA4 + Search Console.
     *
     * @param string $from Date de début (Y-m-d)
     * @param string $to   Date de fin (Y-m-d)
     */
    private function build_system_prompt(string $from, string $to): string {

        // ── Données commerciales ──────────────────────────────────────────────
        try {
            $db       = new ReservationDb();
            $kpis     = $db->query_enhanced_kpis($from, $to);
            $products = $db->query_top_products($from, $to, 10);
            $channels = $db->query_by_channel($from, $to, 10);
            $monthly  = $db->query_stats($from, $to, 'month');
        } catch (\Throwable) {
            $kpis = $products = $channels = $monthly = [];
        }

        $enc          = JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;
        $no_data      = '"Aucune donnée disponible pour cette période."';
        $kpis_json     = !empty($kpis)     ? json_encode($kpis,     $enc) : $no_data;
        $products_json = !empty($products) ? json_encode($products, $enc) : $no_data;
        $channels_json = !empty($channels) ? json_encode($channels, $enc) : $no_data;
        $monthly_json  = !empty($monthly)  ? json_encode($monthly,  $enc) : $no_data;

        // ── Données GA4 ───────────────────────────────────────────────────────
        $ga4_section = $this->fetch_ga4_for_prompt($from, $to);

        // ── Données Search Console ────────────────────────────────────────────
        $gsc_section = $this->fetch_gsc_for_prompt($from, $to);

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

DONNÉES EXCLUES DE L'ANALYSE

Le chiffre d'affaires (CA, revenus, montants en €) n'est pas une donnée fiable dans le système actuel — ne jamais l'utiliser, le mentionner, ni tenter de l'analyser. Concentre-toi exclusivement sur les volumes de réservation, les canaux de distribution, les produits, la saisonnalité, le comportement client et les données de trafic digital.

Quand les données sont insuffisantes pour répondre avec certitude, dis-le en une phrase et propose ce qu'il faudrait mesurer pour avancer.

POSTURE ANALYTIQUE

Face à une question commerciale, tu évalues : qu'est-ce qui explique ce chiffre, qu'est-ce qui le menace, qu'est-ce qui pourrait l'améliorer, sur quel horizon. Tu croises saisonnalité, mix produit, canal, comportement client. Tu identifies les leviers prioritaires.

Face à une question événementielle ou de contexte marché, tu évalues l'impact probable sur la demande, le timing optimal d'activation, et la stratégie de captation à mettre en place.
PROMPT;
    }

    /**
     * Récupère les données GA4 pour le prompt (avec cache 6h).
     * @return string Ligne(s) texte ou message d'indisponibilité
     */
    private function fetch_ga4_for_prompt(string $from, string $to): string {
        $property_id = get_option('bt_ga4_property_id', '');
        $creds_json  = get_option('bt_google_credentials_json', '');
        if (!$property_id || !$creds_json) return 'GA4 non configuré.';

        $creds = json_decode($creds_json, true);
        if (($creds['type'] ?? '') !== 'service_account') return 'GA4 non configuré (type service_account requis).';

        $token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/analytics.readonly');
        if (!$token) return 'GA4 : erreur authentification Google.';

        $cache_key = 'bt_ga4_ai_' . md5($property_id . $from . $to);
        $cached    = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $url  = "https://analyticsdata.googleapis.com/v1beta/properties/{$property_id}:runReport";
        $hdrs = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'];

        // Totaux
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

        // Par canal
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

        if (is_wp_error($res)) return 'GA4 : erreur réseau.';

        $d = json_decode(wp_remote_retrieve_body($res), true);
        if (isset($d['error']) || empty($d['rows'])) return 'GA4 : aucune donnée sur la période.';

        $row    = $d['rows'][0];
        $mnames = array_map(fn($m) => $m['name'], $d['metricHeaders'] ?? []);
        $vals   = array_combine($mnames, array_map(fn($mv) => (float) $mv['value'], $row['metricValues'] ?? []));
        $sess   = (int) ($vals['sessions'] ?? 0);
        $users  = (int) ($vals['activeUsers'] ?? 0);
        $views  = (int) ($vals['screenPageViews'] ?? 0);
        $bounce = round(($vals['bounceRate'] ?? 0) * 100, 1);
        $dur    = (int) ($vals['averageSessionDuration'] ?? 0);

        $result = "Sessions: {$sess} | Utilisateurs: {$users} | Pages vues: {$views} | Taux rebond: {$bounce}% | Durée moy: " . floor($dur / 60) . 'm ' . ($dur % 60) . 's';

        if (!is_wp_error($res_ch)) {
            $ch_data  = json_decode(wp_remote_retrieve_body($res_ch), true);
            $ch_parts = [];
            foreach ($ch_data['rows'] ?? [] as $r) {
                $ch_parts[] = ($r['dimensionValues'][0]['value'] ?? '') . ' (' . (int)($r['metricValues'][0]['value'] ?? 0) . ' sess)';
            }
            if ($ch_parts) $result .= "\nTop canaux: " . implode(', ', $ch_parts);
        }

        set_transient($cache_key, $result, 6 * HOUR_IN_SECONDS);
        return $result;
    }

    /**
     * Récupère les données GSC pour le prompt (avec cache 12h).
     * @return string Ligne(s) texte ou message d'indisponibilité
     */
    private function fetch_gsc_for_prompt(string $from, string $to): string {
        $site_url   = get_option('bt_search_console_site_url', '');
        $creds_json = get_option('bt_google_credentials_json', '');
        if (!$site_url || !$creds_json) return 'Search Console non configuré.';

        $creds = json_decode($creds_json, true);
        if (($creds['type'] ?? '') !== 'service_account') return 'GSC non configuré (type service_account requis).';

        $token = $this->google_access_token($creds, 'https://www.googleapis.com/auth/webmasters.readonly');
        if (!$token) return 'GSC : erreur authentification Google.';

        $cache_key = 'bt_gsc_ai_' . md5($site_url . $from . $to);
        $cached    = get_transient($cache_key);
        if ($cached !== false) return $cached;

        $base = 'https://searchconsole.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query';
        $hdrs = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'];

        // Totaux par date
        $res = wp_remote_post($base, [
            'headers' => $hdrs,
            'body'    => json_encode(['startDate' => $from, 'endDate' => $to, 'dimensions' => ['date'], 'rowLimit' => 90]),
            'timeout' => 15,
        ]);

        // Top 10 requêtes
        $res_q = wp_remote_post($base, [
            'headers' => $hdrs,
            'body'    => json_encode(['startDate' => $from, 'endDate' => $to, 'dimensions' => ['query'], 'rowLimit' => 10]),
            'timeout' => 15,
        ]);

        if (is_wp_error($res)) return 'GSC : erreur réseau.';

        $sc_d = json_decode(wp_remote_retrieve_body($res), true);
        if (isset($sc_d['error'])) return 'GSC : aucune donnée sur la période.';

        $clicks = 0; $impressions = 0; $positions = [];
        foreach ($sc_d['rows'] ?? [] as $row) {
            $clicks      += $row['clicks'];
            $impressions += $row['impressions'];
            $positions[]  = $row['position'];
        }
        $n      = count($positions);
        $ctr    = $impressions > 0 ? round(($clicks / $impressions) * 100, 1) : 0;
        $avgpos = $n > 0 ? round(array_sum($positions) / $n, 1) : 0;

        $result = "Clics: {$clicks} | Impressions: {$impressions} | CTR: {$ctr}% | Position moy: {$avgpos}";

        if (!is_wp_error($res_q)) {
            $q_data  = json_decode(wp_remote_retrieve_body($res_q), true);
            $q_parts = [];
            foreach ($q_data['rows'] ?? [] as $r) {
                $q_parts[] = '"' . ($r['keys'][0] ?? '') . '" (' . $r['clicks'] . ' clics, pos ' . round($r['position'], 1) . ')';
            }
            if ($q_parts) $result .= "\nTop requêtes: " . implode(', ', $q_parts);
        }

        set_transient($cache_key, $result, 12 * HOUR_IN_SECONDS);
        return $result;
    }
}
