<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Chiffrement AES-256-CBC pour les données personnelles (RGPD).
 *
 * Utilise la constante BT_ENCRYPTION_KEY définie dans wp-config.php (min 32 chars).
 * Chaque valeur chiffrée embarque son IV en base64 : "base64(iv):base64(ciphertext)".
 * Le blind_hash() produit un HMAC-SHA256 pour la recherche exacte sans déchiffrer.
 */
class Encryption {

    private string $enc_key;
    private string $hmac_key;
    /** @var string Ancienne clé unifiée — rétrocompat déchiffrement uniquement. */
    private string $legacy_key;

    public function __construct() {
        if (!defined('BT_ENCRYPTION_KEY') || strlen(BT_ENCRYPTION_KEY) < 32) {
            throw new \RuntimeException('BT_ENCRYPTION_KEY manquante ou trop courte (min 32 chars).');
        }
        $master = BT_ENCRYPTION_KEY;
        // Clés séparées pour chiffrement et HMAC (audit §C06)
        $this->enc_key    = hash_hmac('sha256', 'bt_encrypt', $master, true);
        $this->hmac_key   = hash_hmac('sha256', 'bt_hmac',    $master, true);
        // Ancienne clé unique — utilisée uniquement pour déchiffrer les données existantes
        $this->legacy_key = substr(hash('sha256', $master, true), 0, 32);
    }

    /**
     * Chiffre une valeur en AES-256-CBC.
     * Retourne une chaîne vide si $value est vide.
     *
     * @param string $value Valeur en clair.
     * @return string "base64(iv):base64(ciphertext)" ou '' si vide.
     */
    public function encrypt(string $value): string {
        if ($value === '') return '';

        $iv         = random_bytes(16);
        $ciphertext = openssl_encrypt($value, 'AES-256-CBC', $this->enc_key, OPENSSL_RAW_DATA, $iv);

        if ($ciphertext === false) {
            throw new \RuntimeException('Échec du chiffrement AES-256-CBC.');
        }

        return base64_encode($iv) . ':' . base64_encode($ciphertext);
    }

    /**
     * Déchiffre une valeur produite par encrypt().
     * Retourne une chaîne vide si $value est vide ou invalide.
     *
     * @param string $value "base64(iv):base64(ciphertext)".
     * @return string Valeur en clair.
     */
    public function decrypt(string $value): string {
        if ($value === '') return '';

        $parts = explode(':', $value, 2);
        if (count($parts) !== 2) return $value; // Valeur non chiffrée (rétrocompat)

        $iv         = base64_decode($parts[0], true);
        $ciphertext = base64_decode($parts[1], true);

        if ($iv === false || $ciphertext === false || strlen($iv) !== 16) {
            return ''; // Données corrompues
        }

        // Essayer d'abord avec la nouvelle clé, puis la clé legacy (rétrocompat)
        $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->enc_key, OPENSSL_RAW_DATA, $iv);
        if ($plain === false) {
            $plain = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->legacy_key, OPENSSL_RAW_DATA, $iv);
        }

        return $plain !== false ? $plain : '';
    }

    /**
     * Produit un HMAC-SHA256 (blind index) pour la recherche exacte sans déchiffrement.
     * Normalise en minuscules avant le hash pour les emails.
     *
     * @param string $value Valeur en clair.
     * @return string Hash hexadécimal de 64 chars.
     */
    public function blind_hash(string $value): string {
        return hash_hmac('sha256', strtolower(trim($value)), $this->hmac_key);
    }
}
