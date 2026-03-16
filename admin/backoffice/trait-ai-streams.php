<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiStreams — streaming SSE par provider (Anthropic, OpenAI, Gemini, compatibles OpenAI).
 * Chaque méthode écrit directement sur stdout en format text/event-stream.
 */
trait AiStreams {

    /**
     * Base cURL options shared by all streaming methods.
     * Enforces SSL verification and timeouts.
     */
    private function base_curl_opts(): array {
        return [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 120,
        ];
    }

    /**
     * Sanitize API error message to prevent information disclosure.
     * Strips internal details, keeps only a generic provider-level message.
     */
    private function sanitize_error(string $provider, int $http_code, ?string $api_message = null): string {
        // Log full error for debugging (server-side only)
        if ($api_message) {
            error_log("[BT-AI] {$provider} error (HTTP {$http_code}): {$api_message}");
        }
        // Return generic message to client
        return "Erreur API {$provider} (HTTP {$http_code}).";
    }

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

        curl_setopt_array($ch, $this->base_curl_opts() + [
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
                        $msg = $this->sanitize_error('Anthropic', 0, $json['error']['message'] ?? null);
                        echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
                        ob_flush(); flush();
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
            $msg = $this->sanitize_error('Anthropic', $http_code, $err['error']['message'] ?? null);
            echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
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

        curl_setopt_array($ch, $this->base_curl_opts() + [
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
                        $msg = $this->sanitize_error('OpenAI', 0, $json['error']['message'] ?? null);
                        echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
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
        curl_close($ch);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            $msg = $this->sanitize_error('OpenAI', $http_code, $err['error']['message'] ?? null);
            echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
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
        $provider     = parse_url($url, PHP_URL_HOST) ?: 'provider';

        curl_setopt_array($ch, $this->base_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($__, string $chunk) use (&$error_buffer, $provider) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $raw = substr($line, 6);
                    if ($raw === '[DONE]') { echo "data: [DONE]\n\n"; ob_flush(); flush(); continue; }
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        $msg = $this->sanitize_error($provider, 0, $json['error']['message'] ?? null);
                        echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
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
        curl_close($ch);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            $msg = $this->sanitize_error($provider, $http_code, $err['error']['message'] ?? null);
            echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
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
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:streamGenerateContent?alt=sse';

        $contents = array_merge(
            [
                ['role' => 'user',  'parts' => [['text' => $system]]],
                ['role' => 'model', 'parts' => [['text' => 'OK, je comprends le contexte.']]],
            ],
            $messages
        );

        $ch           = curl_init($url);
        $error_buffer = '';

        curl_setopt_array($ch, $this->base_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['contents' => $contents]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_, string $chunk) use (&$error_buffer) {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) { $error_buffer .= $line; continue; }
                    $json = json_decode(substr($line, 6), true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        $msg = $this->sanitize_error('Gemini', 0, $json['error']['message'] ?? null);
                        echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
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
        curl_close($ch);

        if ($http_code !== 200 && $error_buffer !== '') {
            $err = json_decode($error_buffer, true);
            $msg = $this->sanitize_error('Gemini', $http_code, $err['error']['message'] ?? null);
            echo 'data: ' . json_encode(['error' => $msg]) . "\n\n";
            ob_flush(); flush();
        }

        echo "data: [DONE]\n\n";
        flush();
    }
}
