<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

require_once __DIR__ . '/trait-ai-prompt.php';
require_once __DIR__ . '/trait-ai-formatters.php';
require_once __DIR__ . '/trait-ai-streams.php';
require_once __DIR__ . '/trait-ai-json.php';

/**
 * Classe IA — chat SSE + génération d'événements.
 * Gère les providers Anthropic, OpenAI, Gemini, Mistral, Grok, Meta (Groq).
 *
 * Responsabilités déléguées aux traits :
 * @uses AiPrompt      build_system_prompt() enrichi GA4 + GSC
 * @uses AiFormatters  normalisation des messages par provider
 * @uses AiStreams      streaming SSE par provider
 * @uses AiJson        appels synchrones JSON + parser
 */
class Ai {

    use AiPrompt, AiFormatters, AiStreams, AiJson;

    public function init(): void {
        add_action('wp_ajax_bt_ai_chat', [$this, 'handle_ajax']);
    }

    /**
     * Handler AJAX pour le chat IA en streaming (SSE).
     * Vérifie nonce + capacité, puis délègue au provider configuré.
     */
    public function handle_ajax(): void {
        $nonce = $_SERVER['HTTP_X_WP_NONCE'] ?? '';
        if (!wp_verify_nonce($nonce, 'wp_rest')) { status_header(401); exit; }
        // Vérifie via bt_role_permissions : chat_access requis
        $db = new ChatDb();
        if (!$db->role_has_chat_access(get_current_user_id())) { status_header(403); exit; }

        $data     = json_decode(file_get_contents('php://input'), true) ?? [];
        $messages = $data['messages'] ?? [];
        $from     = sanitize_text_field($data['from'] ?? date('Y-m-d', strtotime('-12 months')));
        $to       = sanitize_text_field($data['to']   ?? date('Y-m-d'));

        $valid_providers = ['anthropic', 'openai', 'gemini', 'mistral', 'grok', 'meta'];
        $saved_provider  = get_option('bt_ai_provider', 'anthropic');
        $requested       = $data['provider'] ?? '';
        $provider        = in_array($requested, $valid_providers, true) ? $requested : $saved_provider;
        $key             = $this->get_api_key($provider);

        if (!$key) {
            wp_send_json_error(['message' => 'Clé API IA manquante.'], 400);
            exit;
        }

        // Vider les buffers WP avant d'ouvrir le stream SSE
        while (ob_get_level()) ob_end_clean();

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $system  = $this->build_system_prompt($from, $to);
        $msgs_oa = $this->format_messages_openai($messages);

        switch ($provider) {
            case 'openai':
                $this->stream_openai($key, $msgs_oa, $system);
                break;
            case 'gemini':
                $this->stream_gemini($key, $this->format_messages_gemini($messages), $system);
                break;
            case 'mistral':
                $this->stream_openai_compatible('https://api.mistral.ai/v1/chat/completions', $key, 'mistral-large-latest', $msgs_oa, $system);
                break;
            case 'grok':
                $this->stream_openai_compatible('https://api.x.ai/v1/chat/completions', $key, 'grok-3', $msgs_oa, $system);
                break;
            case 'meta':
                $this->stream_openai_compatible('https://api.groq.com/openai/v1/chat/completions', $key, 'llama-3.3-70b-versatile', $msgs_oa, $system);
                break;
            default:
                $this->stream_anthropic($key, $this->format_messages_anthropic($messages), $system);
                break;
        }

        exit;
    }

    /**
     * Retourne la clé API pour le provider donné.
     * @param string $provider anthropic|openai|gemini|mistral|grok|meta
     */
    public function get_api_key(string $provider): string {
        return [
            'anthropic' => get_option('bt_anthropic_api_key', ''),
            'openai'    => get_option('bt_openai_api_key', ''),
            'gemini'    => get_option('bt_gemini_api_key', ''),
            'mistral'   => get_option('bt_mistral_api_key', ''),
            'grok'      => get_option('bt_grok_api_key', ''),
            'meta'      => get_option('bt_meta_api_key', ''),
        ][$provider] ?? '';
    }
}
