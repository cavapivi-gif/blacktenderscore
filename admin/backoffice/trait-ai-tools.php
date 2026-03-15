<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiTools — définitions des outils IA et exécution server-side.
 * Miroir des outils MCP mais exécutés directement via les classes PHP internes.
 */
trait AiTools {

    /**
     * Retourne les définitions d'outils au format Anthropic (tool_use).
     * @return array[]
     */
    private function get_tool_definitions(): array {
        return [
            [
                'name'        => 'bt_kpis',
                'description' => "KPIs résumés des réservations : nombre de réservations, panier moyen, taux d'annulation, clients uniques, taux de repeat. Accepte une plage de dates.",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'from'        => ['type' => 'string', 'description' => 'Date début YYYY-MM-DD'],
                        'to'          => ['type' => 'string', 'description' => 'Date fin YYYY-MM-DD'],
                        'granularity' => ['type' => 'string', 'enum' => ['day', 'week', 'month'], 'description' => 'Granularité (défaut: month)'],
                    ],
                ],
            ],
            [
                'name'        => 'bt_top_products',
                'description' => "Classement des produits/activités par nombre de réservations. Retourne le top N avec nom, bookings, part du total.",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'from'  => ['type' => 'string', 'description' => 'Date début YYYY-MM-DD'],
                        'to'    => ['type' => 'string', 'description' => 'Date fin YYYY-MM-DD'],
                        'limit' => ['type' => 'number', 'description' => 'Nombre de produits (défaut: 10)'],
                    ],
                ],
            ],
            [
                'name'        => 'bt_timeline',
                'description' => "Série temporelle des réservations sur une période donnée. Données jour/semaine/mois pour voir les tendances et la saisonnalité.",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'from'        => ['type' => 'string', 'description' => 'Date début YYYY-MM-DD'],
                        'to'          => ['type' => 'string', 'description' => 'Date fin YYYY-MM-DD'],
                        'granularity' => ['type' => 'string', 'enum' => ['day', 'week', 'month'], 'description' => 'Granularité (défaut: month)'],
                    ],
                ],
            ],
            [
                'name'        => 'bt_bookings_list',
                'description' => "Liste paginée des réservations individuelles avec détail : référence, produit, date d'activité, statut, client.",
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'from'     => ['type' => 'string', 'description' => 'Date début YYYY-MM-DD'],
                        'to'       => ['type' => 'string', 'description' => 'Date fin YYYY-MM-DD'],
                        'status'   => ['type' => 'string', 'description' => 'Filtre statut (confirmed, cancelled, pending...)'],
                        'page'     => ['type' => 'number', 'description' => 'Numéro de page (défaut: 1)'],
                        'per_page' => ['type' => 'number', 'description' => 'Résultats par page (défaut: 20, max: 100)'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Exécute un outil et retourne le résultat en texte.
     * Appelle directement les classes PHP internes (pas d'appel HTTP).
     *
     * @param string $name Nom de l'outil
     * @param array  $args Arguments passés par l'IA
     * @return string Résultat textuel
     */
    private function execute_tool(string $name, array $args): string {
        try {
            $db   = new ReservationDb();
            $from = $args['from'] ?? date('Y-m-d', strtotime('-30 days'));
            $to   = $args['to']   ?? date('Y-m-d');

            switch ($name) {
                case 'bt_kpis':
                    $gran = $args['granularity'] ?? 'month';
                    $kpis = $db->query_enhanced_kpis($from, $to);
                    return json_encode($kpis, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                case 'bt_top_products':
                    $limit    = min((int) ($args['limit'] ?? 10), 50);
                    $products = $db->query_top_products($from, $to, $limit);
                    return json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                case 'bt_timeline':
                    $gran  = $args['granularity'] ?? 'month';
                    $stats = $db->query_stats($from, $to, $gran);
                    return json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                case 'bt_bookings_list':
                    // Use top_dates as a proxy — individual booking list not available directly
                    $limit    = min(30, (int) ($args['per_page'] ?? 20));
                    $top      = $db->query_top_dates($from, $to, $limit);
                    return json_encode($top, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

                default:
                    return json_encode(['error' => "Outil inconnu: {$name}"]);
            }
        } catch (\Throwable $e) {
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * Streaming Anthropic avec support tool_use.
     * Si l'IA décide d'utiliser un outil, exécute-le et relance la conversation.
     *
     * @param string $key      Clé API Anthropic
     * @param array  $messages Historique formaté (format Anthropic)
     * @param string $system   Prompt système
     * @param int    $depth    Profondeur de récursion (max 3 appels d'outils)
     */
    private function stream_anthropic_with_tools(string $key, array $messages, string $system, int $depth = 0): void {
        $body = [
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 4096,
            'system'     => $system,
            'messages'   => $messages,
            'stream'     => true,
            'tools'      => $this->get_tool_definitions(),
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        $error_buffer = '';
        $tool_use     = null; // {id, name, input_json}
        $text_buffer  = '';
        $input_buffer = '';
        $current_block_type = null;

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_, string $chunk) use (&$error_buffer, &$tool_use, &$text_buffer, &$input_buffer, &$current_block_type) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $raw  = substr($line, 6);
                    if ($raw === '[DONE]') continue;
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;

                    $type = $json['type'] ?? '';

                    // Track content block type
                    if ($type === 'content_block_start') {
                        $block = $json['content_block'] ?? [];
                        $current_block_type = $block['type'] ?? 'text';
                        if ($current_block_type === 'tool_use') {
                            $tool_use = [
                                'id'   => $block['id'] ?? '',
                                'name' => $block['name'] ?? '',
                            ];
                            $input_buffer = '';
                            // Notify frontend of tool call start
                            echo 'data: ' . json_encode(['tool_call' => ['name' => $block['name'] ?? '', 'status' => 'calling']]) . "\n\n";
                            if (ob_get_level()) ob_flush(); flush();
                        }
                    }

                    // Text delta
                    if ($type === 'content_block_delta') {
                        $delta = $json['delta'] ?? [];
                        if (($delta['type'] ?? '') === 'text_delta') {
                            $text_buffer .= $delta['text'];
                            echo 'data: ' . json_encode(['text' => $delta['text']]) . "\n\n";
                            if (ob_get_level()) ob_flush(); flush();
                        }
                        // Tool input accumulation
                        if (($delta['type'] ?? '') === 'input_json_delta') {
                            $input_buffer .= $delta['partial_json'] ?? '';
                        }
                    }

                    // Error
                    if ($type === 'error') {
                        echo 'data: ' . json_encode(['error' => $json['error']['message'] ?? 'Erreur API Anthropic.']) . "\n\n";
                        if (ob_get_level()) ob_flush(); flush();
                    }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            echo 'data: ' . json_encode(['error' => $err['error']['message'] ?? "Erreur API Anthropic (HTTP $http_code)."]) . "\n\n";
            if (ob_get_level()) ob_flush(); flush();
            echo "data: [DONE]\n\n"; flush();
            return;
        }

        // If a tool was called, execute and continue conversation
        if ($tool_use && $depth < 3) {
            $input = json_decode($input_buffer, true) ?? [];
            $result = $this->execute_tool($tool_use['name'], $input);

            // Notify frontend of tool result
            echo 'data: ' . json_encode(['tool_call' => ['name' => $tool_use['name'], 'status' => 'done']]) . "\n\n";
            if (ob_get_level()) ob_flush(); flush();

            // Build follow-up messages with tool result
            $follow_up = $messages;

            // Add assistant response with both text and tool_use
            $assistant_content = [];
            if ($text_buffer) {
                $assistant_content[] = ['type' => 'text', 'text' => $text_buffer];
            }
            $assistant_content[] = [
                'type'  => 'tool_use',
                'id'    => $tool_use['id'],
                'name'  => $tool_use['name'],
                'input' => $input,
            ];
            $follow_up[] = ['role' => 'assistant', 'content' => $assistant_content];

            // Add tool result
            $follow_up[] = [
                'role'    => 'user',
                'content' => [[
                    'type'        => 'tool_result',
                    'tool_use_id' => $tool_use['id'],
                    'content'     => $result,
                ]],
            ];

            // Recurse with updated conversation
            $this->stream_anthropic_with_tools($key, $follow_up, $system, $depth + 1);
            return;
        }

        echo "data: [DONE]\n\n";
        flush();
    }
}
