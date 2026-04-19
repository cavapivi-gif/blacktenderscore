<?php
/**
 * Video Thumbnail Cache
 *
 * Télécharge et stocke localement les thumbnails YouTube/Vimeo
 * pour éviter les requêtes externes et améliorer les perfs/SEO.
 *
 * @package BlackTenders\Elementor
 */

namespace BlackTenders\Elementor;

defined('ABSPATH') || exit;

class VideoThumbnailCache {

    private const CACHE_DIR    = 'bt-video-cache';
    private const CACHE_EXPIRY = WEEK_IN_SECONDS;

    /**
     * Récupère le thumbnail YouTube, le cache localement si besoin.
     *
     * @param string $video_id ID YouTube
     * @return string URL locale du thumbnail, ou URL externe en fallback
     */
    public static function get_youtube_thumbnail(string $video_id): string {
        if (empty($video_id)) return '';

        $cache_key = 'yt_' . $video_id;

        // Check si déjà en cache local
        $cached = self::get_cached_url($cache_key);
        if ($cached) return $cached;

        // Détermine la meilleure qualité disponible
        $maxres = "https://img.youtube.com/vi/{$video_id}/maxresdefault.jpg";
        $hq     = "https://img.youtube.com/vi/{$video_id}/hqdefault.jpg";

        // Test maxres (n'existe pas pour toutes les vidéos)
        $resp = wp_remote_head($maxres, ['timeout' => 3]);
        $url  = (!is_wp_error($resp) && wp_remote_retrieve_response_code($resp) === 200)
            ? $maxres
            : $hq;

        // Télécharge et cache
        $local_url = self::download_and_cache($url, $cache_key . '.jpg');

        return $local_url ?: $url; // Fallback sur URL externe si échec
    }

    /**
     * Récupère le thumbnail Vimeo, le cache localement si besoin.
     *
     * @param string $video_id ID Vimeo
     * @return string URL locale du thumbnail, ou URL externe en fallback
     */
    public static function get_vimeo_thumbnail(string $video_id): string {
        if (empty($video_id)) return '';

        $cache_key = 'vimeo_' . $video_id;

        // Check si déjà en cache local
        $cached = self::get_cached_url($cache_key);
        if ($cached) return $cached;

        // Récupère l'URL via oEmbed
        $api_url = 'https://vimeo.com/api/v2/video/' . rawurlencode($video_id) . '.json';
        $resp    = wp_remote_get($api_url, ['timeout' => 5]);

        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
            return '';
        }

        $data = json_decode(wp_remote_retrieve_body($resp), true);
        $url  = $data[0]['thumbnail_large']
            ?? $data[0]['thumbnail_medium']
            ?? $data[0]['thumbnail_small']
            ?? '';

        if (empty($url)) return '';

        // Télécharge et cache
        $local_url = self::download_and_cache($url, $cache_key . '.jpg');

        return $local_url ?: $url;
    }

    /**
     * Vérifie si un fichier est en cache et retourne son URL.
     */
    private static function get_cached_url(string $cache_key): string {
        $transient_key = 'bt_vtc_' . $cache_key;
        $cached_file   = get_transient($transient_key);

        if ($cached_file === false) return '';

        // Vérifie que le fichier existe toujours
        $upload_dir = wp_upload_dir();
        $file_path  = $upload_dir['basedir'] . '/' . self::CACHE_DIR . '/' . $cached_file;

        if (!file_exists($file_path)) {
            delete_transient($transient_key);
            return '';
        }

        return $upload_dir['baseurl'] . '/' . self::CACHE_DIR . '/' . $cached_file;
    }

    /**
     * Télécharge une image et la stocke dans le cache.
     *
     * @param string $url       URL source
     * @param string $filename  Nom du fichier local
     * @return string|false     URL locale ou false si échec
     */
    private static function download_and_cache(string $url, string $filename) {
        $upload_dir = wp_upload_dir();
        $cache_dir  = $upload_dir['basedir'] . '/' . self::CACHE_DIR;

        // Crée le répertoire si nécessaire
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
            // Ajoute un index.php pour sécurité
            file_put_contents($cache_dir . '/index.php', '<?php // Silence is golden');
        }

        $file_path = $cache_dir . '/' . $filename;

        // Télécharge l'image
        $resp = wp_remote_get($url, [
            'timeout'  => 10,
            'stream'   => true,
            'filename' => $file_path,
        ]);

        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
            @unlink($file_path); // Nettoie le fichier partiel
            return false;
        }

        // Vérifie que c'est bien une image
        $mime = wp_check_filetype($file_path)['type'] ?? '';
        if (strpos($mime, 'image/') !== 0) {
            // Essaie de détecter via getimagesize
            $info = @getimagesize($file_path);
            if (!$info) {
                @unlink($file_path);
                return false;
            }
        }

        // Stocke le transient pour le lookup rapide
        $transient_key = 'bt_vtc_' . pathinfo($filename, PATHINFO_FILENAME);
        set_transient($transient_key, $filename, self::CACHE_EXPIRY);

        return $upload_dir['baseurl'] . '/' . self::CACHE_DIR . '/' . $filename;
    }

    /**
     * Nettoie les fichiers de cache expirés.
     * À appeler via WP-Cron ou manuellement.
     */
    public static function cleanup_expired(): int {
        $upload_dir = wp_upload_dir();
        $cache_dir  = $upload_dir['basedir'] . '/' . self::CACHE_DIR;

        if (!is_dir($cache_dir)) return 0;

        $deleted = 0;
        $files   = glob($cache_dir . '/*.{jpg,jpeg,png,webp}', GLOB_BRACE);
        $now     = time();

        foreach ($files as $file) {
            $cache_key     = pathinfo($file, PATHINFO_FILENAME);
            $transient_key = 'bt_vtc_' . $cache_key;

            // Si le transient n'existe plus, le fichier est expiré
            if (get_transient($transient_key) === false) {
                // Double-check: ne supprime que si le fichier a plus de 7 jours
                if ($now - filemtime($file) > self::CACHE_EXPIRY) {
                    @unlink($file);
                    $deleted++;
                }
            }
        }

        return $deleted;
    }

    /**
     * Vide tout le cache.
     */
    public static function flush_all(): int {
        global $wpdb;

        $upload_dir = wp_upload_dir();
        $cache_dir  = $upload_dir['basedir'] . '/' . self::CACHE_DIR;

        // Supprime les transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bt_vtc_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_bt_vtc_%'");

        if (!is_dir($cache_dir)) return 0;

        // Supprime les fichiers
        $files   = glob($cache_dir . '/*');
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'index.php') {
                @unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
