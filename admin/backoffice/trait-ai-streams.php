<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiStreams — streaming plain-text par provider (Anthropic, OpenAI, Gemini, compatibles OpenAI).
 * Consommé par @ai-sdk/react useChat (streamProtocol: 'text').
 * Chaque méthode écrit directement sur stdout en UTF-8 brut (pas de wrapping SSE JSON).
 */
trait AiStreams {

    private function base_curl_opts(): array {
        return [
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 120,
        ];
    }

    private function sanitize_error(string $provider, int $http_code, ?string $api_message = null): string {
        if ($api_message) {
            error_log("[BT-AI] {$provider} error (HTTP {$http_code}): {$api_message}");
        }
        return "Erreur API {$provider} (HTTP {$http_code}).";
    }

    private function stream_anthropic(string $key, array $messages, string $system): void {
        $ch = curl_init('https://api.anthropic.com/v1/messages');

        $body = json_encode([
            'model'      => 'claude-sonnet-4-6',
            'max_tokens' => 2048,
            'system'     => $system,
            'messages'   => $messages,
            'stream'     => true,
        ]);

        curl_setopt_array($ch, $this->base_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_, string $chunk): int {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) continue;
                    $raw  = substr($line, 6);
                    if ($raw === '[DONE]') continue;
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (($json['type'] ?? '') === 'content_block_delta' && ($json['delta']['type'] ?? '') === 'text_delta') {
                        echo $json['delta']['text'];
                        ob_flush(); flush();
                    } elseif (($json['type'] ?? '') === 'error') {
                        error_log('[BT-AI] Anthropic mid-stream error: ' . ($json['error']['message'] ?? 'unknown'));
                    }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            http_response_code($http_code ?: 500);
        }
    }

    private function stream_openai(string $key, array $messages, string $system): void {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        $body = json_encode([
            'model'      => 'gpt-4o',
            'max_tokens' => 2048,
            'messages'   => array_merge([['role' => 'system', 'content' => $system]], $messages),
            'stream'     => true,
        ]);

        curl_setopt_array($ch, $this->base_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_ch, string $chunk): int {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) continue;
                    $raw = substr($line, 6);
                    if ($raw === '[DONE]') continue;
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        error_log('[BT-AI] OpenAI mid-stream error: ' . ($json['error']['message'] ?? 'unknown'));
                        continue;
                    }
                    $content = $json['choices'][0]['delta']['content'] ?? null;
                    if ($content !== null) { echo $content; ob_flush(); flush(); }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            http_response_code($http_code ?: 500);
        }
    }

    private function stream_openai_compatible(string $url, string $key, string $model, array $messages, string $system): void {
        $ch   = curl_init($url);
        $body = json_encode([
            'model'      => $model,
            'max_tokens' => 2048,
            'messages'   => array_merge([['role' => 'system', 'content' => $system]], $messages),
            'stream'     => true,
        ]);
        $provider = parse_url($url, PHP_URL_HOST) ?: 'provider';

        curl_setopt_array($ch, $this->base_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($__, string $chunk) use ($provider): int {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) continue;
                    $raw = substr($line, 6);
                    if ($raw === '[DONE]') continue;
                    $json = json_decode($raw, true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        error_log("[BT-AI] {$provider} mid-stream error: " . ($json['error']['message'] ?? 'unknown'));
                        continue;
                    }
                    $content = $json['choices'][0]['delta']['content'] ?? null;
                    if ($content !== null) { echo $content; ob_flush(); flush(); }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            http_response_code($http_code ?: 500);
        }
    }

    private function stream_gemini(string $key, array $messages, string $system): void {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:streamGenerateContent?alt=sse';

        $contents = array_merge(
            [
                ['role' => 'user',  'parts' => [['text' => $system]]],
                ['role' => 'model', 'parts' => [['text' => 'OK, je comprends le contexte.']]],
            ],
            $messages
        );

        $ch = curl_init($url);

        curl_setopt_array($ch, $this->base_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['contents' => $contents]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function($_, string $chunk): int {
                foreach (explode("\n", $chunk) as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data: ')) continue;
                    $json = json_decode(substr($line, 6), true);
                    if (!is_array($json)) continue;
                    if (isset($json['error'])) {
                        error_log('[BT-AI] Gemini mid-stream error: ' . ($json['error']['message'] ?? 'unknown'));
                        continue;
                    }
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text !== null) { echo $text; ob_flush(); flush(); }
                }
                return strlen($chunk);
            },
        ]);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200) {
            http_response_code($http_code ?: 500);
        }
    }
}
