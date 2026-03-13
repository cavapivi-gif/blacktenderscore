<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiStreams — streaming SSE par provider (Anthropic, OpenAI, Gemini, compatibles OpenAI).
 * Chaque méthode écrit directement sur stdout en format text/event-stream.
 */
trait AiStreams {

    /**
     * Streaming SSE vers Anthropic (claude-sonnet-4-6).
     *
     * @param string $key      Clé API Anthropic
     * @param array  $messages Historique formaté (format Anthropic)
     * @param string $system   Prompt système
     */
    private function stream_anthropic(string $key, array $messages, string $system): void {
        $ch = curl_init('https://api.anthropic.com/v1/messages');

        $body = json_encode([
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 2048,
            'system'     => $system,
            'messages'   => $messages,
            'stream'     => true,
        ]);

        $error_buffer = '';

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_, string $chunk) use (&$error_buffer) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $raw  = substr($line, 6);
                    if ($raw === '[DONE]') continue;
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (($json['type'] ?? '') === 'content_block_delta' && ($json['delta']['type'] ?? '') === 'text_delta') {
                        echo 'data: ' . json_encode(['text' => $json['delta']['text']]) . "\n\n";
                        ob_flush(); flush();
                    } elseif (($json['type'] ?? '') === 'error') {
                        echo 'data: ' . json_encode(['error' => $json['error']['message'] ?? 'Erreur API Anthropic.']) . "\n\n";
                        ob_flush(); flush();
                    }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            echo 'data: ' . json_encode(['error' => $err['error']['message'] ?? "Erreur API Anthropic (HTTP $http_code)."]) . "\n\n";
            ob_flush(); flush();
        }

        echo "data: [DONE]\n\n";
        flush();
    }

    /**
     * Streaming SSE vers OpenAI (gpt-4o).
     *
     * @param string $key      Clé API OpenAI
     * @param array  $messages Historique formaté (format OpenAI)
     * @param string $system   Prompt système
     */
    private function stream_openai(string $key, array $messages, string $system): void {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        $body = json_encode([
            'model'      => 'gpt-4o',
            'max_tokens' => 2048,
            'messages'   => array_merge([['role' => 'system', 'content' => $system]], $messages),
            'stream'     => true,
        ]);

        $error_buffer = '';

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_ch, string $chunk) use (&$error_buffer) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $raw = substr($line, 6);
                    if ($raw === '[DONE]') { echo "data: [DONE]\n\n"; ob_flush(); flush(); continue; }
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        echo 'data: ' . json_encode(['error' => $json['error']['message'] ?? 'Erreur API OpenAI.']) . "\n\n";
                        ob_flush(); flush(); continue;
                    }
                    $content = $json['choices'][0]['delta']['content'] ?? null;
                    if ($content !== null) { echo 'data: ' . json_encode(['text' => $content]) . "\n\n"; ob_flush(); flush(); }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            echo 'data: ' . json_encode(['error' => $err['error']['message'] ?? "Erreur API OpenAI (HTTP $http_code)."]) . "\n\n";
            ob_flush(); flush();
        }

        echo "data: [DONE]\n\n";
        flush();
    }

    /**
     * Streaming SSE générique pour les APIs OpenAI-compatibles (Mistral, Grok, Meta/Groq).
     *
     * @param string $url      Endpoint du provider
     * @param string $key      Clé API Bearer
     * @param string $model    Nom du modèle à utiliser
     * @param array  $messages Historique formaté (format OpenAI)
     * @param string $system   Prompt système
     */
    private function stream_openai_compatible(string $url, string $key, string $model, array $messages, string $system): void {
        $ch   = curl_init($url);
        $body = json_encode([
            'model'      => $model,
            'max_tokens' => 2048,
            'messages'   => array_merge([['role' => 'system', 'content' => $system]], $messages),
            'stream'     => true,
        ]);
        $error_buffer = '';

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($__, string $chunk) use (&$error_buffer) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $raw = substr($line, 6);
                    if ($raw === '[DONE]') { echo "data: [DONE]\n\n"; ob_flush(); flush(); continue; }
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        echo 'data: ' . json_encode(['error' => $json['error']['message'] ?? 'Erreur.']) . "\n\n";
                        ob_flush(); flush(); continue;
                    }
                    $content = $json['choices'][0]['delta']['content'] ?? null;
                    if ($content !== null) { echo 'data: ' . json_encode(['text' => $content]) . "\n\n"; ob_flush(); flush(); }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            echo 'data: ' . json_encode(['error' => $err['error']['message'] ?? "Erreur HTTP $http_code."]) . "\n\n";
            ob_flush(); flush();
        }
        echo "data: [DONE]\n\n";
        flush();
    }

    /**
     * Streaming SSE vers Gemini (gemini-1.5-pro).
     * Le system prompt est injecté en tant que premier échange user/model.
     *
     * @param string $key      Clé API Gemini
     * @param array  $messages Historique formaté (format Gemini)
     * @param string $system   Prompt système
     */
    private function stream_gemini(string $key, array $messages, string $system): void {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:streamGenerateContent?alt=sse&key={$key}";

        $contents = array_merge(
            [
                ['role' => 'user',  'parts' => [['text' => $system]]],
                ['role' => 'model', 'parts' => [['text' => 'OK, je comprends le contexte.']]],
            ],
            $messages
        );

        $ch           = curl_init($url);
        $error_buffer = '';

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['contents' => $contents]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_, string $chunk) use (&$error_buffer) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $json = json_decode(substr($line, 6), true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        echo 'data: ' . json_encode(['error' => $json['error']['message'] ?? 'Erreur API Gemini.']) . "\n\n";
                        ob_flush(); flush(); continue;
                    }
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text !== null) { echo 'data: ' . json_encode(['text' => $text]) . "\n\n"; ob_flush(); flush(); }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            echo 'data: ' . json_encode(['error' => $err['error']['message'] ?? "Erreur API Gemini (HTTP $http_code)."]) . "\n\n";
            ob_flush(); flush();
        }

        echo "data: [DONE]\n\n";
        flush();
    }
}
