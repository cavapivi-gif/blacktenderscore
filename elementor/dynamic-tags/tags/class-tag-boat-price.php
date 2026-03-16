<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tags — Prix bateau (min / max).
 *
 * Source prioritaire : repeater ACF boat_price → sous-champ boat_price_boat.
 * Fallback legacy   : champs plats boat_price_half / boat_price_full.
 *
 * Deux tags :
 *  - bt-boat-price-from : prix minimum
 *  - bt-boat-price-to   : prix maximum
 *
 * Options : prefix, devise, suffix, format, fallback.
 */
abstract class Abstract_Boat_Price_Tag extends Abstract_BT_Tag {

    public function get_categories(): array { return ['text', 'number']; }

    /** Renvoie le prix à afficher parmi la liste collectée. */
    abstract protected function pick_price(array $prices): float;

    protected function register_controls(): void {

        $this->add_control('prefix', [
            'label'   => __('Préfixe', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
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
                'integer' => __('Entier (850)', 'blacktenderscore'),
                'decimal' => __('Décimal (850,00)', 'blacktenderscore'),
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

        $post_id = (int) get_the_ID();
        $prices  = $this->collect_prices($post_id);

        if (empty($prices)) {
            $fb = (string) ($this->get_settings('fallback') ?? '');
            if ($fb !== '') echo esc_html($fb);
            return;
        }

        $price     = $this->pick_price($prices);
        $format    = $this->get_settings('format')  ?: 'integer';
        $prefix    = (string) ($this->get_settings('prefix') ?? '');
        $suffix    = (string) ($this->get_settings('suffix') ?? '');
        $devise    = (string) ($this->get_settings('devise') ?? '€');

        $formatted = match ($format) {
            'decimal' => number_format($price, 2, ',', ' '),
            'raw'     => (string) $price,
            default   => number_format($price, 0, ',', ' '),
        };

        $parts = array_filter([$prefix, $formatted . ' ' . $devise, $suffix]);
        echo esc_html(implode(' ', $parts));
    }

    /**
     * Collecte tous les prix disponibles pour un bateau.
     *
     * 1) Repeater boat_price → sous-champ boat_price_boat (prioritaire).
     * 2) Fallback : champs plats boat_price_half / boat_price_full.
     *
     * @return float[]
     */
    private function collect_prices(int $post_id): array {

        $prices = [];

        /* ── Repeater boat_price ──────────────────────────────────────── */
        $rows = function_exists('get_field') ? get_field('boat_price', $post_id) : null;

        if (is_array($rows) && !empty($rows)) {
            foreach ($rows as $row) {
                $p = (float) ($row['boat_price_boat'] ?? 0);
                if ($p > 0) $prices[] = $p;
            }
        }

        /* ── Fallback champs plats (legacy) ───────────────────────────── */
        if (empty($prices)) {
            $half = (float) get_post_meta($post_id, 'boat_price_half', true);
            $full = (float) get_post_meta($post_id, 'boat_price_full', true);
            if ($half > 0) $prices[] = $half;
            if ($full > 0) $prices[] = $full;
        }

        return $prices;
    }
}

// ── Tag : Prix bateau minimum ────────────────────────────────────────────────

class Tag_Boat_Price_From extends Abstract_Boat_Price_Tag {

    public function get_name():  string { return 'bt-boat-price-from'; }
    public function get_title(): string { return 'BT: Prix bateau (minimum)'; }

    protected function pick_price(array $prices): float {
        return (float) min($prices);
    }
}

// ── Tag : Prix bateau maximum ────────────────────────────────────────────────

class Tag_Boat_Price_To extends Abstract_Boat_Price_Tag {

    public function get_name():  string { return 'bt-boat-price-to'; }
    public function get_title(): string { return 'BT: Prix bateau (maximum)'; }

    protected function pick_price(array $prices): float {
        return (float) max($prices);
    }
}
