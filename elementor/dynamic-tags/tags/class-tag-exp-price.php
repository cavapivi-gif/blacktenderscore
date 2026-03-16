<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tags — Prix de l'excursion.
 *
 * Deux tags distincts dans ce fichier :
 *  - bt-exp-price-from : prix minimum depuis tarification_par_forfait
 *  - bt-exp-price-to   : prix maximum depuis tarification_par_forfait
 *
 * Options communes :
 *  - prefix   : "Dès", "À partir de", "", …
 *  - suffix   : "€ / pers.", "€", ""
 *  - devise   : symbole monnaie (défaut €)
 *  - format   : nombre brut / formaté (séparateur milliers)
 *  - fallback : texte si aucun prix
 */

abstract class Abstract_Price_Tag extends Abstract_BT_Tag {

    public function get_categories(): array { return ['text', 'number']; }

    abstract protected function extract_price(array $rows): ?float;

    protected function register_controls(): void {

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
        // Nouveau schéma ACF : repeater principal (ex. tarification_par_forfait)
        // avec un sous-champ prix détecté automatiquement (même logique que Tag_Exp_Duration).
        $repeater_name = 'tarification_par_forfait';
        $rows          = $this->acf($repeater_name);

        if (empty($rows)) {
            echo esc_html($this->get_settings('fallback') ?? '');
            return;
        }

        // Essaie de détecter la structure horaires+prix sur ce repeater,
        // puis récupère la clé de prix. Fallback gracieux sur exp_price.
        $structure = $this->acf_detect_repeater_departure_structure($repeater_name);
        $price_key = $structure['price_subfield'] ?? 'exp_price';

        $prices = [];
        foreach ((array) $rows as $row) {
            $p = (float) ($row[$price_key] ?? 0);
            if ($p > 0) $prices[] = $p;
        }

        if (empty($prices)) {
            echo esc_html($this->get_settings('fallback') ?? '');
            return;
        }

        $price   = $this->extract_price($prices);
        $format  = $this->get_settings('format')  ?: 'integer';
        $prefix  = (string) ($this->get_settings('prefix')  ?? '');
        $suffix  = (string) ($this->get_settings('suffix')  ?? '');
        $devise  = (string) ($this->get_settings('devise')  ?? '€');

        $formatted = match ($format) {
            'decimal' => number_format($price, 2, ',', ' '),
            'raw'     => (string) $price,
            default   => number_format($price, 0, ',', ' '),
        };

        $parts = array_filter([$prefix, $formatted . ' ' . $devise, $suffix]);
        echo esc_html(implode(' ', $parts));
    }
}

// ── Tag : Prix minimum ────────────────────────────────────────────────────────

class Tag_Exp_Price_From extends Abstract_Price_Tag {

    public function get_name():  string { return 'bt-exp-price-from'; }
    public function get_title(): string { return 'BT: Prix excursion (minimum)'; }

    protected function extract_price(array $prices): float {
        return (float) min($prices);
    }
}

// ── Tag : Prix maximum ────────────────────────────────────────────────────────

class Tag_Exp_Price_To extends Abstract_Price_Tag {

    public function get_name():  string { return 'bt-exp-price-to'; }
    public function get_title(): string { return 'BT: Prix excursion (maximum)'; }

    protected function extract_price(array $prices): float {
        return (float) max($prices);
    }
}
