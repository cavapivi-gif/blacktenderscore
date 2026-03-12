<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Durée(s) de l'excursion.
 *
 * Lit boat_half_day_time et boat_full_day_time (ACF), identiques aux champs
 * utilisés par le widget BT — Tarifs bateau.
 * Peut afficher la durée minimale, maximale, ou toutes en liste.
 *
 * Options :
 *  - mode      : min / max / all (toutes, séparées)
 *  - suffix    : suffixe affiché après chaque valeur (ex: " h")
 *  - separator : si mode = all
 *  - fallback  : texte si aucune durée
 */
class Tag_Exp_Duration extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-duration'; }
    public function get_title():      string { return 'BT: Durée excursion'; }
    public function get_categories(): array  { return ['text']; }

    protected function register_controls(): void {

        $this->add_control('mode', [
            'label'   => __('Mode', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'min' => __('La plus courte', 'blacktenderscore'),
                'max' => __('La plus longue', 'blacktenderscore'),
                'all' => __('Toutes les durées', 'blacktenderscore'),
            ],
            'default' => 'min',
        ]);

        $this->add_control('suffix', [
            'label'   => __('Suffixe (ex: " h")', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => ' h',
        ]);

        $this->add_control('separator', [
            'label'     => __('Séparateur (mode "toutes")', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => ' · ',
            'condition' => ['mode' => 'all'],
        ]);

        $this->add_control('fallback', [
            'label'   => __('Texte si aucune durée', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ]);
    }

    public function render(): void {
        $post_id = get_the_ID();

        // Même source que le widget BT — Tarifs bateau (class-boat-pricing.php)
        $half = get_field('boat_half_day_time', $post_id);
        $full = get_field('boat_full_day_time', $post_id);

        // Collecte ordonnée : demi-journée en premier (plus courte), journée en second
        $durations = [];
        if ($half !== null && $half !== '') $durations[] = (string) $half;
        if ($full !== null && $full !== '') $durations[] = (string) $full;

        // Déduplique au cas où les deux valeurs seraient identiques
        $durations = array_values(array_unique($durations));

        if (empty($durations)) {
            echo esc_html($this->get_settings('fallback') ?? '');
            return;
        }

        $mode   = $this->get_settings('mode') ?: 'min';
        $suffix = $this->get_settings('suffix') ?? ' h';

        $output = match ($mode) {
            'max'   => end($durations) . $suffix,
            'all'   => implode(
                            $this->get_settings('separator') ?: ' · ',
                            array_map(fn($d) => $d . $suffix, $durations)
                        ),
            default => $durations[0] . $suffix,
        };

        echo esc_html($output);
    }
}
