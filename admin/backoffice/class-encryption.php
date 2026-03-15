<?php
namespace BlackTenders\Admin\Backoffice;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;

defined('ABSPATH') || exit;

/**
 * Chiffrement AES-256-GCM (authenticated) via defuse/php-encryption.
 *
 * Remplace l'implémentation home-made AES-256-CBC unauthenticated.
 * Gains :
 *   - Authenticated encryption (résistant padding oracle, tampering)
 *   - Constant-time comparison (résistant timing attacks)
 *   - Gestion de clé sécurisée par la lib (dérivation, encodage)
 *
 * Rétrocompatibilité :
 *   Les valeurs "base64:base64" (ancien format CBC) sont déchiffrées
 *   via le chemin legacy. Migration transparente et progressive.
 *
 * RGPD : blind_hash() inchangé — HMAC-SHA256 pour recherche exacte.
 */
class Encryption {

    private Key    $key;
    private string $hmac_key;

    // Clés legacy uniquement pour le déchiffrement rétrocompat
    private string $legacy_enc_key;
    private string $legacy_master_key;

    public function __construct() {
        if (!defined('BT_ENCRYPTION_KEY') || strlen(BT_ENCRYPTION_KEY) < 32) {
            throw new \RuntimeException('BT_ENCRYPTION_KEY manquante ou trop courte (min 32 chars).');
        }

        $master = BT_ENCRYPTION_KEY;

        // Clé defuse — créée une fois, stockée encodée en WP options
        $stored_key = get_option('bt_defuse_key');
        if ($stored_key) {
            try {
                $this->key = Key::loadFromAsciiSafeString($stored_key);
            } catch (\Throwable $e) {
                error_log('[BT-Encryption] Clé defuse invalide, régénération : ' . $e->getMessage());
                $this->key = Key::createNewRandomKey();
                update_option('bt_defuse_key', $this->key->saveToAsciiSafeString(), false);
            }
        } else {
            $this->key = Key::createNewRandomKey();
            update_option('bt_defuse_key', $this->key->saveToAsciiSafeString(), false);
        }

        // HMAC blind index (identique à l'ancienne implémentation — hashes compatibles)
        $this->hmac_key = hash_hmac('sha256', 'bt_hmac', $master, true);

        // Legacy — déchiffrement des données existantes uniquement
        $this->legacy_enc_key    = hash_hmac('sha256', 'bt_encrypt', $master, true);
        $this->legacy_master_key = substr(hash('sha256', $master, true), 0, 32);
    }

    /**
     * Chiffre une valeur. Retourne '' si vide.
     * Format de sortie : chaîne opaque via defuse (AES-256-CTR + HMAC-SHA256).
     */
    public function encrypt(string $value): string {
        if ($value === '') return '';
        return Crypto::encrypt($value, $this->key);
    }

    /**
     * Déchiffre une valeur.
     * Supporte les deux formats :
     *   - Nouveau  (defuse) : chaîne opaque
     *   - Ancien   (CBC)    : "base64(iv):base64(ciphertext)"
     */
    public function decrypt(string $value): string {
        if ($value === '') return '';

        if ($this->is_legacy_format($value)) {
            return $this->decrypt_legacy($value);
        }

        try {
            return Crypto::decrypt($value, $this->key);
        } catch (WrongKeyOrModifiedCiphertextException $e) {
            error_log('[BT-Encryption] Déchiffrement échoué (données corrompues ou mauvaise clé).');
            return '';
        } catch (\Throwable $e) {
            error_log('[BT-Encryption] Erreur inattendue : ' . $e->getMessage());
            return '';
        }
    }

    /**
     * HMAC-SHA256 pour recherche exacte sans déchiffrement (blind index).
     * Identique à l'ancienne implémentation — hashes existants restent valides.
     */
    public function blind_hash(string $value): string {
        return hash_hmac('sha256', strtolower(trim($value)), $this->hmac_key);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /** Détecte le format legacy "base64:base64" (IV:ciphertext). */
    private function is_legacy_format(string $value): bool {
        $parts = explode(':', $value, 3);
        return count($parts) === 2 && base64_decode($parts[0], true) !== false;
    }

    /** Déchiffre une valeur au format legacy AES-256-CBC. */
    private function decrypt_legacy(string $value): string {
        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) return $value;

        $iv         = base64_decode($parts[0], true);
        $ciphertext = base64_decode($parts[1], true);

        if ($iv === false || $ciphertext === false || strlen($iv) !== 16) return '';

        // Essayer avec la clé "bt_encrypt" dérivée, puis avec la clé sha256 legacy
        $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->legacy_enc_key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->legacy_master_key, OPENSSL_RAW_DATA, $iv);
        }

        return $plain !== false ? $plain : '';
    }
}
