<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiJson — appels synchrones (non-streaming) retournant un tableau PHP.
 * Utilisé pour la génération d'événements et tout besoin JSON structuré.
 */
trait AiJson {

    /**
     * Base cURL options for synchronous JSON calls.
     */
    private function base_json_curl_opts(): array {
        return [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
    }

    /**
     * Appel synchrone à Anthropic — retourne tableau PHP ou null.
     *
     * @param string $key        Clé API Anthropic
     * @param string $prompt     Prompt utilisateur
     * @param int    $max_tokens Limite de tokens
     */
    public function call_anthropic_json(string $key, string $prompt, int $max_tokens = 1024): ?array {
        $ch = curl_init('https://api.anthropic.com/v1/messages');

        curl_setopt_array($ch, $this->base_json_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'      => 'claude-sonnet-4-6',
                'max_tokens' => $max_tokens,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
                'Content-Type: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);
        if (!$raw) return null;

        $response = json_decode($raw, true);
        return $this->parse_json_from_text($response['content'][0]['text'] ?? null);
    }

    /**
     * Appel synchrone à OpenAI (gpt-4o) — retourne tableau PHP ou null.
     *
     * @param string $key        Clé API OpenAI
     * @param string $prompt     Prompt utilisateur
     * @param int    $max_tokens Limite de tokens
     */
    public function call_openai_json(string $key, string $prompt, int $max_tokens = 1024): ?array {
        $ch = curl_init('https://api.openai.com/v1/chat/completions');

        curl_setopt_array($ch, $this->base_json_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'      => 'gpt-4o',
                'max_tokens' => $max_tokens,
                'messages'   => [['role' => 'user', 'content' => $prompt]],
            ]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
            ],
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);
        if (!$raw) return null;

        $response = json_decode($raw, true);
        return $this->parse_json_from_text($response['choices'][0]['message']['content'] ?? null);
    }

    /**
     * Appel synchrone à Gemini (gemini-1.5-pro) — retourne tableau PHP ou null.
     *
     * @param string $key        Clé API Gemini
     * @param string $prompt     Prompt utilisateur
     * @param int    $max_tokens Limite de tokens
     */
    public function call_gemini_json(string $key, string $prompt, int $max_tokens = 1024): ?array {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-pro:generateContent';
        $ch  = curl_init($url);

        curl_setopt_array($ch, $this->base_json_curl_opts() + [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['maxOutputTokens' => $max_tokens],
            ]),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . $key],
        ]);

        $raw = curl_exec($ch);
        curl_close($ch);
        if (!$raw) return null;

        $response = json_decode($raw, true);
        return $this->parse_json_from_text($response['candidates'][0]['content']['parts'][0]['text'] ?? null);
    }

    /**
     * Extrait un tableau JSON depuis une réponse texte de l'IA.
     * Gère les cas où la réponse est wrappée dans du markdown ou du texte libre.
     *
     * @param string|null $text Texte brut retourné par l'IA
     * @return array|null Tableau PHP ou null si échec
     */
    private function parse_json_from_text(?string $text): ?array {
        if ($text === null || $text === '') return null;

        // Tentative directe
        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;

        // Premier tableau JSON trouvé
        if (preg_match('/\[[\s\S]*\]/m', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) return $decoded;
        }

        // Premier objet JSON trouvé
        if (preg_match('/\{[\s\S]*\}/m', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) return $decoded;
        }

        return null;
    }
}
