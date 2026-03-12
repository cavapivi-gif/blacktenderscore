<?php
namespace BlackTenders\Admin\Backoffice;

defined('ABSPATH') || exit;

/**
 * Couche base de données pour les participations importées (CSV externe).
 *
 * Table : {prefix}bt_participations
 * Clé unique : dedup_hash — MD5(date|produit|email|prix_brut)
 * → permet le réimport du même fichier sans doublon,
 *   tout en acceptant deux achats distincts si l'un des quatre champs diffère.
 *
 * RGPD : buyer_firstname, buyer_lastname, buyer_email, phone chiffrés AES-256-CBC.
 * buyer_email_hash : HMAC-SHA256 (blind index) pour la recherche exacte.
 */
class ParticipationDb {

    private string $table;
    private string $option_key = 'bt_participation_import_status';

    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'bt_participations';
    }

    // ─── Schéma ───────────────────────────────────────────────────────────────

    /**
     * Crée ou met à jour la table via dbDelta (idempotent).
     * @uses dbDelta()
     */
    public function ensure_table(): void {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();
        $table   = $this->table;

        $sql = "CREATE TABLE {$table} (
            id                 bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            dedup_hash         varchar(32)         NOT NULL,
            participation_date date                DEFAULT NULL,
            product_name       varchar(255)        NOT NULL DEFAULT '',
            buyer_firstname    text                DEFAULT NULL,
            buyer_lastname     text                DEFAULT NULL,
            buyer_email        text                DEFAULT NULL,
            buyer_email_hash   varchar(64)         DEFAULT NULL,
            price_net          decimal(10,2)       DEFAULT NULL,
            price_gross        decimal(10,2)       DEFAULT NULL,
            phone              text                DEFAULT NULL,
            imported_at        datetime            NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY dedup_hash (dedup_hash),
            KEY participation_date (participation_date),
            KEY product_name (product_name(100)),
            KEY buyer_email_hash (buyer_email_hash)
        ) ENGINE=InnoDB {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    // ─── Écriture ─────────────────────────────────────────────────────────────

    /**
     * Insère un lot de participations (INSERT IGNORE — dédup par hash).
     * Pas d'"update" : une ligne existante est simplement ignorée (skipped).
     *
     * @param array $items Tableau de participations normalisées
     * @return array { inserted: int, skipped: int, errors: string[] }
     */
    public function upsert(array $items): array {
        global $wpdb;

        $stats = ['inserted' => 0, 'skipped' => 0, 'errors' => []];
        $now   = current_time('mysql', true);
        $enc   = new Encryption();

        // Même approche que ReservationDb : éviter $wpdb->prepare() sur des
        // valeurs produit/chiffré qui contiennent des % (false positives vsprintf).
        $conn = $wpdb->dbh;
        $q    = function (?string $v) use ($conn): string {
            if ($v === null || $v === '') return 'NULL';
            $escaped = ($conn instanceof \mysqli)
                ? mysqli_real_escape_string($conn, $v)
                : addslashes($v);
            return "'" . $escaped . "'";
        };

        foreach ($items as $item) {
            $product = trim((string) ($item['product_name'] ?? ''));
            if ($product === '') {
                $stats['skipped']++;
                continue;
            }

            $email_raw = (string) ($item['buyer_email'] ?? '');
            $date_raw  = (string) ($item['participation_date'] ?? '');
            $price_g   = (string) ($item['price_gross'] ?? '');

            // Clé de déduplication (calculée sur les données brutes avant chiffrement)
            $dedup_hash = md5($date_raw . '|' . $product . '|' . strtolower(trim($email_raw)) . '|' . $price_g);

            // Chiffrement RGPD
            $firstname_enc = ($item['buyer_firstname'] ?? '') !== '' ? $enc->encrypt((string) $item['buyer_firstname']) : '';
            $lastname_enc  = ($item['buyer_lastname']  ?? '') !== '' ? $enc->encrypt((string) $item['buyer_lastname'])  : '';
            $email_enc     = $email_raw !== ''                        ? $enc->encrypt($email_raw)                       : '';
            $phone_enc     = ($item['phone'] ?? '') !== ''            ? $enc->encrypt((string) $item['phone'])          : '';

            // Blind index email pour la recherche exacte future
            $email_hash = $email_raw !== '' ? $enc->blind_hash($email_raw) : null;

            // Prix → NULL si absent ou invalide
            $price_net_sql   = ($item['price_net']   !== null) ? number_format((float) $item['price_net'],   2, '.', '') : 'NULL';
            $price_gross_sql = ($item['price_gross']  !== null) ? number_format((float) $item['price_gross'], 2, '.', '') : 'NULL';

            $sql = "INSERT IGNORE INTO `{$this->table}`
                        (dedup_hash, participation_date, product_name,
                         buyer_firstname, buyer_lastname, buyer_email, buyer_email_hash,
                         price_net, price_gross, phone, imported_at)
                     VALUES (
                        {$q($dedup_hash)},
                        {$q($date_raw ?: null)},
                        {$q($product)},
                        {$q($firstname_enc)},
                        {$q($lastname_enc)},
                        {$q($email_enc)},
                        {$q($email_hash)},
                        {$price_net_sql},
                        {$price_gross_sql},
                        {$q($phone_enc)},
                        {$q($now)}
                     )";

            if ($conn instanceof \mysqli) {
                $conn->query($sql);
                $affected = $conn->errno ? false : $conn->affected_rows;
                $db_error = $conn->error;
            } else {
                $affected = $wpdb->query($sql);
                $db_error = $wpdb->last_error;
            }

            if ($affected === false) {
                $stats['errors'][] = "Erreur DB : {$product} / {$email_raw} — " . ($db_error ?: 'query failed');
            } elseif ($affected === 0) {
                $stats['skipped']++; // dédup : ligne déjà présente
            } else {
                $stats['inserted']++;
            }
        }

        // Met à jour le timestamp de dernier import
        update_option($this->option_key, ['last_import' => $now], false);

        return $stats;
    }

    // ─── Lecture ──────────────────────────────────────────────────────────────

    /**
     * Retourne les stats de la table bt_participations.
     *
     * @return array { total_in_db, date_min, date_max, last_import }
     */
    public function get_status(): array {
        global $wpdb;

        $row = $wpdb->get_row(
            "SELECT COUNT(*) as total,
                    MIN(participation_date) as date_min,
                    MAX(participation_date) as date_max
             FROM {$this->table}",
            ARRAY_A
        );

        $opt = get_option($this->option_key, []);

        return [
            'total_in_db' => (int) ($row['total'] ?? 0),
            'date_min'    => $row['date_min'] ?? null,
            'date_max'    => $row['date_max'] ?? null,
            'last_import' => $opt['last_import'] ?? null,
        ];
    }

    // ─── Reset ────────────────────────────────────────────────────────────────

    /** Vide la table et supprime le statut de dernier import. */
    public function truncate(): void {
        global $wpdb;
        $wpdb->query("TRUNCATE TABLE {$this->table}");
        delete_option($this->option_key);
    }
}
