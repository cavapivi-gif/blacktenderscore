<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tags — Prix unifié (Excursion / Bateau).
 *
 * Modes :
 *  - excursion : repeater tarification_par_forfait → exp_price
 *  - boat      : repeater boat_price → boat_price_boat
 *                OU repeater boat_custom_price_by_departure → boat_price_for_full_day / boat_price_for_half_day
 *
 * Options bateau :
 *  - Affichage : total / par personne (÷ boat_pax_max)
 *  - Durée     : toutes / journée seule (filtre full_day > 0)
 *
 * Tags :
 *  - bt-price-from : prix minimum
 *  - bt-price-to   : prix maximum
 */
abstract class Abstract_Price_Tag extends Abstract_BT_Tag {

    public function get_categories(): array { return ['text', 'number']; }

    /** Renvoie min ou max selon le tag. */
    abstract protected function extract_price(array $prices): float;

    protected function register_controls(): void {

        // ── Mode : Excursion ou Bateau ─────────────────────────────────────
        $this->add_control('price_mode', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'excursion' => __('Excursion', 'blacktenderscore'),
                'boat'      => __('Bateau', 'blacktenderscore'),
            ],
            'default' => 'excursion',
        ]);

        // ── Options Bateau ─────────────────────────────────────────────────
        $this->add_control('boat_display', [
            'label'       => __('Affichage', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => [
                'total'      => __('Prix total', 'blacktenderscore'),
                'per_person' => __('Par personne (÷ pax max)', 'blacktenderscore'),
            ],
            'default'     => 'total',
            'condition'   => ['price_mode' => 'boat'],
        ]);

        $this->add_control('boat_duration_filter', [
            'label'       => __('Filtrer par durée', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SELECT,
            'options'     => [
                'all'      => __('Toutes les durées', 'blacktenderscore'),
                'full_day' => __('Journée complète uniquement', 'blacktenderscore'),
                'half_day' => __('Demi-journée uniquement', 'blacktenderscore'),
            ],
            'default'     => 'all',
            'condition'   => ['price_mode' => 'boat'],
        ]);

        // ── Options communes ───────────────────────────────────────────────
        $this->add_control('prefix', [
            'label'   => __('Préfixe', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
            'dynamic' => ['active' => false],
        ]);

        $this->add_control('devise', [
            'label'   => __('Symbole devise', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '€',
        ]);

        $this->add_control('suffix', [
            'label'   => __('Suffixe', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);

        $this->add_control('format', [
            'label'   => __('Format', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'integer' => __('Entier (45)', 'blacktenderscore'),
                'decimal' => __('Décimal (45,00)', 'blacktenderscore'),
                'raw'     => __('Brut (pas de formatage)', 'blacktenderscore'),
            ],
            'default' => 'integer',
        ]);

        $this->add_control('fallback', [
            'label'       => __('Texte si aucun prix', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::TEXT,
            'default'     => '',
            'description' => __('Laissez vide pour n\'afficher rien.', 'blacktenderscore'),
        ]);
    }

    public function render(): void {

        $mode     = $this->get_settings('price_mode') ?: 'excursion';
        $fallback = (string) ($this->get_settings('fallback') ?? '');

        $prices = ($mode === 'boat')
            ? $this->collect_boat_prices()
            : $this->collect_excursion_prices();

        if (empty($prices)) {
            if ($fallback !== '') echo esc_html($fallback);
            return;
        }

        $price  = $this->extract_price($prices);
        $format = $this->get_settings('format') ?: 'integer';
        $prefix = (string) ($this->get_settings('prefix') ?? '');
        $suffix = (string) ($this->get_settings('suffix') ?? '');
        $devise = (string) ($this->get_settings('devise') ?? '€');

        $formatted = match ($format) {
            'decimal' => number_format($price, 2, ',', ' '),
            'raw'     => (string) $price,
            default   => number_format($price, 0, ',', ' '),
        };

        $out = '';
        if ($prefix !== '') {
            $out .= '<span class="bt-price__prefix bt-exp-price__prefix">' . esc_html($prefix) . '</span> ';
        }
        $out .= esc_html($formatted . ' ' . $devise);
        if ($suffix !== '') {
            $out .= ' <span class="bt-price__suffix bt-exp-price__suffix">' . esc_html($suffix) . '</span>';
        }
        echo $out; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Collecte les prix excursion depuis tarification_par_forfait.
     *
     * @return float[]
     */
    private function collect_excursion_prices(): array {

        $repeater_name = 'tarification_par_forfait';
        $rows          = $this->acf($repeater_name);

        if (empty($rows)) return [];

        $structure = $this->acf_detect_repeater_departure_structure($repeater_name);
        $price_key = $structure['price_subfield'] ?? 'exp_price';

        $prices = [];
        foreach ((array) $rows as $row) {
            $p = (float) ($row[$price_key] ?? 0);
            if ($p > 0) $prices[] = $p;
        }

        return $prices;
    }

    /**
     * Collecte les prix bateau.
     *
     * Priorité :
     *  1) Repeater boat_price → boat_price_boat
     *  2) Repeater boat_custom_price_by_departure → boat_price_for_full_day / boat_price_for_half_day
     *  3) Champs plats boat_price_half / boat_price_full
     *
     * @return float[]
     */
    private function collect_boat_prices(): array {

        $post_id         = (int) get_the_ID();
        $display         = $this->get_settings('boat_display') ?: 'total';
        $duration_filter = $this->get_settings('boat_duration_filter') ?: 'all';
        $pax_max         = (int) get_field('boat_pax_max', $post_id);

        $prices = [];

        // ── 1) Repeater simple boat_price ──────────────────────────────────
        $rows_simple = function_exists('get_field') ? get_field('boat_price', $post_id) : null;

        if (is_array($rows_simple) && !empty($rows_simple)) {
            foreach ($rows_simple as $row) {
                $p = (float) ($row['boat_price_boat'] ?? 0);
                if ($p > 0) $prices[] = $p;
            }
        }

        // ── 2) Repeater détaillé boat_custom_price_by_departure ────────────
        if (empty($prices)) {
            $rows_detailed = function_exists('get_field')
                ? get_field('boat_custom_price_by_departure', $post_id)
                : null;

            if (is_array($rows_detailed) && !empty($rows_detailed)) {
                foreach ($rows_detailed as $row) {
                    $p_full = (float) ($row['boat_price_for_full_day'] ?? 0);
                    $p_half = (float) ($row['boat_price_for_half_day'] ?? 0);

                    // Appliquer le filtre de durée
                    if ($duration_filter === 'full_day') {
                        if ($p_full > 0) $prices[] = $p_full;
                    } elseif ($duration_filter === 'half_day') {
                        if ($p_half > 0) $prices[] = $p_half;
                    } else {
                        // all : prendre les deux si disponibles
                        if ($p_full > 0) $prices[] = $p_full;
                        if ($p_half > 0) $prices[] = $p_half;
                    }
                }
            }
        }

        // ── 3) Fallback champs plats ───────────────────────────────────────
        if (empty($prices)) {
            $half = (float) get_post_meta($post_id, 'boat_price_half', true);
            $full = (float) get_post_meta($post_id, 'boat_price_full', true);

            if ($duration_filter === 'full_day') {
                if ($full > 0) $prices[] = $full;
            } elseif ($duration_filter === 'half_day') {
                if ($half > 0) $prices[] = $half;
            } else {
                if ($half > 0) $prices[] = $half;
                if ($full > 0) $prices[] = $full;
            }
        }

        // ── Calcul par personne si demandé ─────────────────────────────────
        if ($display === 'per_person' && $pax_max > 0 && !empty($prices)) {
            $prices = array_map(fn($p) => $p / $pax_max, $prices);
        }

        return $prices;
    }
}

// ── Tag : Prix minimum ────────────────────────────────────────────────────────

class Tag_Exp_Price_From extends Abstract_Price_Tag {

    public function get_name():  string { return 'bt-price-from'; }
    public function get_title(): string { return 'BT: Prix (minimum)'; }

    protected function extract_price(array $prices): float {
        return (float) min($prices);
    }
}

// ── Tag : Prix maximum ────────────────────────────────────────────────────────

class Tag_Exp_Price_To extends Abstract_Price_Tag {

    public function get_name():  string { return 'bt-price-to'; }
    public function get_title(): string { return 'BT: Prix (maximum)'; }

    protected function extract_price(array $prices): float {
        return (float) max($prices);
    }
}
