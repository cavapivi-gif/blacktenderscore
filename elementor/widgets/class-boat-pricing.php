<?php
namespace BlackTenders\Elementor\Widgets;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Tarification du bateau.
 *
 * Affiche les tarifs demi-journée / journée complète, la caution,
 * l'indicateur carburant inclus, et optionnellement le tableau
 * des tarifs par zone de navigation (repeater boat_custom_price_by_departure).
 */
class BoatPricing extends \Elementor\Widget_Base {

    public function get_name():       string { return 'bt-boat-pricing'; }
    public function get_title():      string { return 'BT — Tarifs bateau'; }
    public function get_icon():       string { return 'eicon-price-list'; }
    public function get_categories(): array  { return ['blacktenderscore']; }
    public function get_keywords():   array  { return ['tarif', 'prix', 'bateau', 'demi-journée', 'journée', 'bt']; }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('Tarifs', 'blacktenderscore'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('title_tag', [
            'label'   => __('Balise du titre', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => ['h2' => 'H2', 'h3' => 'H3', 'h4' => 'H4', 'p' => 'p', 'span' => 'span'],
            'default' => 'h3',
        ]);

        $this->add_control('currency', [
            'label'   => __('Symbole monnaie', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '€',
        ]);

        $this->add_control('layout', [
            'label'   => __('Disposition', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'cards' => __('Cartes côte à côte', 'blacktenderscore'),
                'table' => __('Tableau', 'blacktenderscore'),
            ],
            'default' => 'cards',
        ]);

        $this->end_controls_section();

        // ── Options d'affichage ───────────────────────────────────────────
        $this->start_controls_section('section_options', [
            'label' => __('Forfaits à afficher', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_half', [
            'label'        => __('Demi-journée', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_half', [
            'label'     => __('Label demi-journée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['show_half' => 'yes'],
        ]);

        $this->add_control('show_full', [
            'label'        => __('Journée complète', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_full', [
            'label'     => __('Label journée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Journée complète', 'blacktenderscore'),
            'condition' => ['show_full' => 'yes'],
        ]);

        $this->add_control('show_deposit', [
            'label'        => __('Caution', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_deposit', [
            'label'     => __('Label caution', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Caution', 'blacktenderscore'),
            'condition' => ['show_deposit' => 'yes'],
        ]);

        $this->add_control('show_fuel_badge', [
            'label'        => __('Badge carburant inclus', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('label_fuel_yes', [
            'label'     => __('Label carburant inclus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Carburant inclus', 'blacktenderscore'),
            'condition' => ['show_fuel_badge' => 'yes'],
        ]);

        $this->add_control('label_fuel_no', [
            'label'     => __('Label carburant non inclus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Carburant en sus', 'blacktenderscore'),
            'condition' => ['show_fuel_badge' => 'yes'],
        ]);

        $this->add_control('show_price_note', [
            'label'        => __('Note tarifaire', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Tarifs par zone ───────────────────────────────────────────────
        $this->start_controls_section('section_zones', [
            'label' => __('Tarifs par zone de navigation', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_zones', [
            'label'        => __('Afficher les tarifs par zone', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'description'  => __('Lit le repeater ACF boat_custom_price_by_departure.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('zones_title', [
            'label'     => __('Titre du tableau par zone', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Tarifs par zone de départ', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_zone', [
            'label'     => __('En-tête colonne Zone', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Zone de navigation', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_half', [
            'label'     => __('En-tête colonne ½ journée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Demi-journée', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->add_control('zones_col_full', [
            'label'     => __('En-tête colonne journée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Journée', 'blacktenderscore'),
            'condition' => ['show_zones' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Cartes ────────────────────────────────────────────────
        $this->start_controls_section('style_cards', [
            'label' => __('Style — Cartes / Tableau', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'label'    => __('Typographie titre section', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-bprice__title',
        ]);

        $this->add_control('title_color', [
            'label'     => __('Couleur titre', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__title' => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('cards_gap', [
            'label'      => __('Espacement cartes', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 16, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-bprice__cards' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_control('card_bg', [
            'label'     => __('Fond des cartes', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__card' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'card_border',
            'selector' => '{{WRAPPER}} .bt-bprice__card',
        ]);

        $this->add_responsive_control('card_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'selectors'  => ['{{WRAPPER}} .bt-bprice__card' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('card_padding', [
            'label'      => __('Padding carte', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'default'    => ['top' => '24', 'right' => '24', 'bottom' => '24', 'left' => '24', 'unit' => 'px', 'isLinked' => true],
            'selectors'  => ['{{WRAPPER}} .bt-bprice__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Box_Shadow::get_type(), [
            'name'     => 'card_shadow',
            'selector' => '{{WRAPPER}} .bt-bprice__card',
        ]);

        $this->end_controls_section();

        // ── Style — Prix ──────────────────────────────────────────────────
        $this->start_controls_section('style_price', [
            'label' => __('Style — Prix', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'card_label_typo',
            'label'    => __('Typographie label', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-bprice__card-label',
        ]);

        $this->add_control('card_label_color', [
            'label'     => __('Couleur label', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__card-label' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'price_typo',
            'label'    => __('Typographie prix', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-bprice__amount',
        ]);

        $this->add_control('price_color', [
            'label'     => __('Couleur prix', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__amount' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'duration_typo',
            'label'    => __('Typographie durée', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-bprice__duration',
        ]);

        $this->add_control('duration_color', [
            'label'     => __('Couleur durée', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__duration' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('deposit_color', [
            'label'     => __('Couleur caution', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__deposit' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('fuel_yes_bg', [
            'label'     => __('Fond badge carburant inclus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--yes' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('fuel_yes_color', [
            'label'     => __('Couleur texte badge inclus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--yes' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('fuel_no_bg', [
            'label'     => __('Fond badge carburant en sus', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-bprice__fuel--no' => 'background-color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!function_exists('get_field')) {
            echo '<p class="bt-widget-placeholder">ACF Pro requis.</p>';
            return;
        }

        $currency   = esc_html($s['currency'] ?: '€');
        $price_note = (string) get_field('boat_price_note', $post_id);
        $price_half = get_field('boat_price_half', $post_id);
        $half_time  = get_field('boat_half_day_time', $post_id);
        $price_full = get_field('boat_price_full', $post_id);
        $full_time  = get_field('boat_full_day_time', $post_id);
        $deposit    = get_field('boat_deposit', $post_id);
        $fuel_incl  = get_field('boat_fuel_included', $post_id);
        $zones      = get_field('boat_custom_price_by_departure', $post_id);

        $has_content = ($s['show_half'] === 'yes' && $price_half)
                    || ($s['show_full'] === 'yes' && $price_full)
                    || ($s['show_zones'] === 'yes' && !empty($zones));

        if (!$has_content) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p class="bt-widget-placeholder">Aucun tarif bateau trouvé. Vérifiez que les champs ACF (<code>boat_price_half</code>, <code>boat_price_full</code>) sont remplis sur ce post.</p>';
            }
            return;
        }

        $tag = esc_attr($s['title_tag'] ?: 'h3');

        echo '<div class="bt-bprice">';

        if (!empty($s['section_title'])) {
            echo "<{$tag} class=\"bt-bprice__title\">" . esc_html($s['section_title']) . "</{$tag}>";
        }

        $layout = $s['layout'] ?: 'cards';

        // ── Cartes tarifaires ─────────────────────────────────────────────
        $cards = [];
        if ($s['show_half'] === 'yes' && $price_half) {
            $cards[] = [
                'label'    => $s['label_half'] ?: __('Demi-journée', 'blacktenderscore'),
                'price'    => $price_half,
                'duration' => $half_time ? "{$half_time} h" : '',
            ];
        }
        if ($s['show_full'] === 'yes' && $price_full) {
            $cards[] = [
                'label'    => $s['label_full'] ?: __('Journée complète', 'blacktenderscore'),
                'price'    => $price_full,
                'duration' => $full_time ? "{$full_time} h" : '',
            ];
        }

        if (!empty($cards)) {
            if ($layout === 'table') {
                $this->render_table($cards, $s, $currency, $price_note, $deposit, $fuel_incl);
            } else {
                $this->render_cards($cards, $s, $currency, $price_note, $deposit, $fuel_incl);
            }
        }

        // ── Tableau par zone ──────────────────────────────────────────────
        if ($s['show_zones'] === 'yes' && !empty($zones)) {
            $this->render_zones($zones, $s, $currency);
        }

        echo '</div>'; // .bt-bprice
    }

    // ── Helpers render ────────────────────────────────────────────────────────

    private function render_cards(array $cards, array $s, string $currency, string $note, $deposit, bool $fuel_incl): void {
        echo '<div class="bt-bprice__cards">';
        foreach ($cards as $card) {
            $price_fmt = number_format((float) $card['price'], 0, ',', ' ');
            echo '<div class="bt-bprice__card">';
            echo '<span class="bt-bprice__card-label">' . esc_html($card['label']) . '</span>';
            if ($s['show_price_note'] === 'yes' && $note) {
                echo '<span class="bt-bprice__note">' . esc_html($note) . '</span>';
            }
            echo '<div class="bt-bprice__amount-block">';
            echo '<span class="bt-bprice__amount">' . $price_fmt . ' ' . $currency . '</span>';
            if ($card['duration']) {
                echo ' <span class="bt-bprice__duration">— ' . esc_html($card['duration']) . '</span>';
            }
            echo '</div>';
            if ($s['show_deposit'] === 'yes' && $deposit) {
                $dep_lbl = esc_html($s['label_deposit'] ?: __('Caution', 'blacktenderscore'));
                echo '<p class="bt-bprice__deposit">' . $dep_lbl . ' : <strong>' . esc_html(number_format((float) $deposit, 0, ',', ' ') . ' ' . $currency) . '</strong></p>';
            }
            echo '</div>';
        }

        if ($s['show_fuel_badge'] === 'yes') {
            $cls     = $fuel_incl ? 'bt-bprice__fuel--yes' : 'bt-bprice__fuel--no';
            $lbl     = $fuel_incl
                ? esc_html($s['label_fuel_yes'] ?: __('Carburant inclus', 'blacktenderscore'))
                : esc_html($s['label_fuel_no']  ?: __('Carburant en sus', 'blacktenderscore'));
            echo '<span class="bt-bprice__fuel ' . $cls . '">' . $lbl . '</span>';
        }

        echo '</div>'; // .bt-bprice__cards
    }

    private function render_table(array $cards, array $s, string $currency, string $note, $deposit, bool $fuel_incl): void {
        echo '<div class="bt-bprice__table-wrap"><table class="bt-bprice__table">';
        echo '<thead><tr><th>' . esc_html(__('Forfait', 'blacktenderscore')) . '</th><th>' . esc_html(__('Durée', 'blacktenderscore')) . '</th><th>' . esc_html(__('Prix', 'blacktenderscore')) . '</th></tr></thead><tbody>';
        foreach ($cards as $card) {
            echo '<tr>';
            echo '<td class="bt-bprice__card-label">' . esc_html($card['label']) . '</td>';
            echo '<td class="bt-bprice__duration">' . esc_html($card['duration']) . '</td>';
            echo '<td class="bt-bprice__amount">' . esc_html(number_format((float) $card['price'], 0, ',', ' ') . ' ' . $currency) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';

        if ($s['show_deposit'] === 'yes' && $deposit) {
            $dep_lbl = esc_html($s['label_deposit'] ?: __('Caution', 'blacktenderscore'));
            echo '<p class="bt-bprice__deposit">' . $dep_lbl . ' : <strong>' . esc_html(number_format((float) $deposit, 0, ',', ' ') . ' ' . $currency) . '</strong></p>';
        }
        if ($s['show_fuel_badge'] === 'yes') {
            $cls = $fuel_incl ? 'bt-bprice__fuel--yes' : 'bt-bprice__fuel--no';
            $lbl = $fuel_incl
                ? esc_html($s['label_fuel_yes'] ?: __('Carburant inclus', 'blacktenderscore'))
                : esc_html($s['label_fuel_no']  ?: __('Carburant en sus', 'blacktenderscore'));
            echo '<span class="bt-bprice__fuel ' . $cls . '">' . $lbl . '</span>';
        }
    }

    private function render_zones(array $zones, array $s, string $currency): void {
        $zones_title = $s['zones_title'] ?: __('Tarifs par zone de départ', 'blacktenderscore');
        echo '<div class="bt-bprice__zones">';
        echo '<h4 class="bt-bprice__zones-title">' . esc_html($zones_title) . '</h4>';
        echo '<div class="bt-bprice__table-wrap"><table class="bt-bprice__table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html($s['zones_col_zone'] ?: __('Zone', 'blacktenderscore')) . '</th>';
        echo '<th>' . esc_html($s['zones_col_half'] ?: __('½ journée', 'blacktenderscore')) . '</th>';
        echo '<th>' . esc_html($s['zones_col_full'] ?: __('Journée', 'blacktenderscore')) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($zones as $row) {
            $zone_terms = $row['boat_navigation_zone'] ?? null;
            $zone_label = '';
            if ($zone_terms) {
                $zone_ids   = is_array($zone_terms) ? $zone_terms : [$zone_terms];
                $zone_names = [];
                foreach ($zone_ids as $tid) {
                    $t = is_numeric($tid) ? get_term((int) $tid) : ($tid instanceof \WP_Term ? $tid : null);
                    if ($t && !is_wp_error($t)) $zone_names[] = $t->name;
                }
                $zone_label = implode(', ', $zone_names);
            }

            $p_half = $row['boat_price_for_half_day'] ?? '';
            $p_full = $row['boat_price_for_full_day'] ?? '';

            echo '<tr>';
            echo '<td class="bt-bprice__card-label">' . esc_html($zone_label) . '</td>';
            echo '<td class="bt-bprice__amount">' . ($p_half ? esc_html($p_half . ' ' . $currency) : '—') . '</td>';
            echo '<td class="bt-bprice__amount">' . ($p_full ? esc_html($p_full . ' ' . $currency) : '—') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div></div>';
    }
}
