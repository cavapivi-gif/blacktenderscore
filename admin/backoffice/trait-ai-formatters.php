<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Trait AiFormatters — normalisation des messages par provider.
 * Gère les images (vision) dans le dernier message utilisateur.
 */
trait AiFormatters {

    /**
     * Extrait la partie base64 pure d'un data-URL (ex: "data:image/jpeg;base64,...")
     * ou retourne la chaîne telle quelle si elle n'est pas un data-URL.
     */
    private function strip_data_url(string $data): string {
        return preg_replace('/^data:[^;]+;base64,/', '', $data);
    }

    /**
     * Normalise un MIME type image vers les valeurs acceptées par Anthropic.
     * Anthropic n'accepte que : image/jpeg, image/png, image/gif, image/webp.
     *
     * @param string $mime MIME type brut (ex: "image/jpg", "IMAGE/JPEG", "image/jpg;charset=utf-8")
     * @return string MIME type normalisé (défaut: image/jpeg)
     */
    private function normalize_image_mime(string $mime): string {
        // Retirer les paramètres éventuels (ex: "image/jpeg; charset=utf-8")
        $base = strtolower(trim(explode(';', $mime)[0]));

        return match($base) {
            'image/jpg', 'image/jpeg', 'image/pjpeg' => 'image/jpeg',
            'image/png', 'image/x-png'               => 'image/png',
            'image/gif'                               => 'image/gif',
            'image/webp'                              => 'image/webp',
            default                                   => 'image/jpeg',
        };
    }

    /**
     * Formate les messages pour Anthropic.
     * Images (vision) intégrées uniquement dans le dernier message user.
     *
     * @param array $messages Messages bruts du client [{role, content, images?}]
     * @return array Messages au format Anthropic
     */
    private function format_messages_anthropic(array $messages): array {
        $formatted = [];
        $last_idx  = count($messages) - 1;

        foreach ($messages as $i => $msg) {
            $role   = ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $images = ($i === $last_idx && $role === 'user') ? ($msg['images'] ?? []) : [];

            if (!empty($images)) {
                $content = [];
                foreach ($images as $img) {
                    $content[] = [
                        'type'   => 'image',
                        'source' => [
                            'type'       => 'base64',
                            'media_type' => $this->normalize_image_mime($img['type'] ?? 'image/jpeg'),
                            'data'       => $this->strip_data_url($img['data']),
                        ],
                    ];
                }
                $content[]   = ['type' => 'text', 'text' => $msg['content'] ?? ''];
                $formatted[] = ['role' => $role, 'content' => $content];
            } else {
                $formatted[] = ['role' => $role, 'content' => $msg['content'] ?? ''];
            }
        }

        return $formatted;
    }

    /**
     * Formate les messages pour OpenAI (gpt-4o vision).
     * Images encodées en data-URL dans le champ content du dernier message user.
     *
     * @param array $messages Messages bruts du client
     * @return array Messages au format OpenAI
     */
    private function format_messages_openai(array $messages): array {
        $formatted = [];
        $last_idx  = count($messages) - 1;

        foreach ($messages as $i => $msg) {
            $role   = ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $images = ($i === $last_idx && $role === 'user') ? ($msg['images'] ?? []) : [];

            if (!empty($images)) {
                $content = [['type' => 'text', 'text' => $msg['content'] ?? '']];
                foreach ($images as $img) {
                    $mime      = $this->normalize_image_mime($img['type'] ?? 'image/jpeg');
                    $b64       = $this->strip_data_url($img['data']);
                    $content[] = ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$b64}"]];
                }
                $formatted[] = ['role' => $role, 'content' => $content];
            } else {
                $formatted[] = ['role' => $role, 'content' => $msg['content'] ?? ''];
            }
        }

        return $formatted;
    }

    /**
     * Formate les messages pour Gemini (role user/model, images via inline_data).
     *
     * @param array $messages Messages bruts du client
     * @return array Messages au format Gemini
     */
    private function format_messages_gemini(array $messages): array {
        $formatted = [];
        $last_idx  = count($messages) - 1;

        foreach ($messages as $i => $msg) {
            $role   = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $images = ($i === $last_idx && $role === 'user') ? ($msg['images'] ?? []) : [];
            $parts  = [['text' => $msg['content'] ?? '']];

            foreach ($images as $img) {
                $parts[] = ['inline_data' => [
                    'mime_type' => $this->normalize_image_mime($img['type'] ?? 'image/jpeg'),
                    'data'      => $this->strip_data_url($img['data']),
                ]];
            }

            $formatted[] = ['role' => $role, 'parts' => $parts];
        }

        return $formatted;
    }
}
