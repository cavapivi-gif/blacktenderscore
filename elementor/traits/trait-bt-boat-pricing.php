<?php
/**
 * Trait BtBoatPricing — Boat pricing render methods.
 *
 * Extracted from BoatPricing widget. Provides:
 * - render_pricing_content() — dispatcher (cards / tabs / table + zones)
 * - Internal layout renderers (cards, tabs, table, zones)
 * - Price / deposit / fuel / per-person helpers
 *
 * @package BlackTenders\Elementor\Traits
 */

namespace BlackTenders\Elementor\Traits;

defined('ABSPATH') || exit;

trait BtBoatPricing {

    // ── Entry point ─────────────────────────────────────────────────────────

    /**
     * Rendu du contenu tarification bateau (cartes / tabs / table + zones).
     * Appelé depuis render() via render_pricing_layout().
     */
    protected function render_pricing_content(array $s, int $post_id): void {
        $currency   = esc_html($s['currency'] ?: '€');
        $price_note = (string) get_field('boat_price_note',      $post_id);
        $fuel_incl  = (bool)   get_field('boat_fuel_included',   $post_id);
        $deposit    = (float) (get_field('boat_deposit',          $post_id) ?? 0);
        $price_half = (float) (get_field('boat_price_half',       $post_id) ?? 0);
        $half_time  =          get_field('boat_half_day_time',    $post_id);
        $price_full = (float) (get_field('boat_price_full',       $post_id) ?? 0);
        $full_time  =          get_field('boat_full_day_time',    $post_id);
        $zones      =          get_field('boat_custom_price_by_departure', $post_id);

        $pax_max = ($s['show_per_person'] ?? '') === 'yes'
            ? (int) get_field('boat_pax_max', $post_id)
            : 0;

        $has_content = (($s['show_half'] ?? '') === 'yes' && $price_half)
                    || (($s['show_full'] ?? '') === 'yes' && $price_full)
                    || (($s['show_zones'] ?? '') === 'yes' && !empty($zones));

        if (!$has_content) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucun tarif bateau trouvé. Vérifiez les champs ACF (boat_price_half, boat_price_full).', 'blacktenderscore'));
            }
            return;
        }

        echo '<div class="bt-bprice">';

        $this->render_section_title($s, 'bt-bprice__title');

        $layout = $s['layout'] ?: 'cards';

        $cards = [];
        if (($s['show_half'] ?? '') === 'yes' && $price_half) {
            $cards[] = [
                'label'    => $s['label_half'] ?: __('Demi-journée', 'blacktenderscore'),
                'price'    => $price_half,
                'duration' => $half_time ? "{$half_time} h" : '',
            ];
        }
        if (($s['show_full'] ?? '') === 'yes' && $price_full) {
            $cards[] = [
                'label'    => $s['label_full'] ?: __('Journée complète', 'blacktenderscore'),
                'price'    => $price_full,
                'duration' => $full_time ? "{$full_time} h" : '',
            ];
        }

        if (!empty($cards)) {
            if ($layout === 'tabs') {
                $this->render_boat_tabs($cards, $s, $currency, $price_note, $deposit, $fuel_incl, $pax_max);
            } elseif ($layout === 'table') {
                $this->render_boat_table($cards, $s, $currency, $price_note, $deposit, $fuel_incl, $pax_max);
            } else {
                $this->render_boat_cards($cards, $s, $currency, $price_note, $deposit, $fuel_incl, $pax_max);
            }
        }

        if (($s['show_zones'] ?? '') === 'yes' && $layout !== 'tabs' && !empty($zones)) {
            $this->render_boat_zones($zones, $s, $currency);
        }

        echo '</div>'; // .bt-bprice
    }

    // ── Layout : Cartes ──────────────────────────────────────────────────────

    protected function render_boat_cards(array $cards, array $s, string $currency, string $note, float $deposit, bool $fuel_incl, int $pax_max): void {
        echo '<div class="bt-bprice__cards">';
        foreach ($cards as $card) {
            echo '<div class="bt-bprice__card">';
            echo '<span class="bt-bprice__card-label">' . esc_html($card['label']) . '</span>';
            echo $this->boat_card_body_html($card, $s, $currency, $note, $deposit, $pax_max);
            echo '</div>';
        }
        echo $this->boat_fuel_badge_html($fuel_incl, $s);
        echo '</div>';
    }

    // ── Layout : Onglets ─────────────────────────────────────────────────────

    protected function render_boat_tabs(array $cards, array $s, string $currency, string $note, float $deposit, bool $fuel_incl, int $pax_max): void {
        $uid = 'bt-bprice-' . $this->get_id();

        echo '<div class="bt-bprice__tabs" data-bt-tabs data-bt-panel-class="bt-bprice__panel">';
        echo '<div class="bt-bprice__tablist-wrap">';
        echo '<div class="bt-bprice__tablist" role="tablist">';

        foreach ($cards as $i => $card) {
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-panel-{$i}";
            $active = $i === 0 ? ' bt-bprice__tab--active' : '';
            $sel    = $i === 0 ? 'true' : 'false';
            $tabi   = $i === 0 ? '0' : '-1';
            echo "<button class=\"bt-bprice__tab{$active}\" id=\"{$tab_id}\" role=\"tab\""
               . " aria-selected=\"{$sel}\" aria-controls=\"{$pan_id}\" tabindex=\"{$tabi}\">";
            echo esc_html($card['label']);
            echo '</button>';
        }

        echo '</div></div>'; // .bt-bprice__tablist + .bt-bprice__tablist-wrap

        foreach ($cards as $i => $card) {
            $tab_id     = "{$uid}-tab-{$i}";
            $pan_id     = "{$uid}-panel-{$i}";
            $active_cls = $i === 0 ? ' bt-bprice__panel--active' : '';
            echo "<div class=\"bt-bprice__panel{$active_cls}\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\">";
            echo '<div class="bt-bprice__card">';
            echo $this->boat_card_body_html($card, $s, $currency, $note, $deposit, $pax_max);
            echo '</div></div>';
        }

        echo $this->boat_fuel_badge_html($fuel_incl, $s);
        echo '</div>'; // .bt-bprice__tabs
    }

    // ── Layout : Tableau ─────────────────────────────────────────────────────

    protected function render_boat_table(array $cards, array $s, string $currency, string $note, float $deposit, bool $fuel_incl, int $pax_max): void {
        $col_forfait  = $s['table_col_forfait']  ?: __('Forfait',  'blacktenderscore');
        $col_duration = $s['table_col_duration'] ?: __('Durée',    'blacktenderscore');
        $col_price    = $s['table_col_price']    ?: __('Prix',     'blacktenderscore');

        echo '<div class="bt-bprice__table-wrap"><table class="bt-bprice__table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html($col_forfait)  . '</th>';
        echo '<th>' . esc_html($col_duration) . '</th>';
        echo '<th>' . esc_html($col_price)    . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($cards as $card) {
            echo '<tr>';
            echo '<td class="bt-bprice__card-label">' . esc_html($card['label'])    . '</td>';
            echo '<td class="bt-bprice__duration">'   . esc_html($card['duration']) . '</td>';
            echo '<td class="bt-bprice__amount">'
               . $this->boat_format_price($card['price'], $currency)
               . $this->boat_per_person_html($card['price'], $pax_max, $s, $currency)
               . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
        echo $this->boat_deposit_html($deposit, $s, $currency);
        echo $this->boat_fuel_badge_html($fuel_incl, $s);
    }

    // ── Layout : Zones de navigation ─────────────────────────────────────────

    protected function render_boat_zones(array $zones, array $s, string $currency): void {
        $zones_title  = $s['zones_title']    ?: __('Tarifs par zone de départ', 'blacktenderscore');
        $col_zone     = $s['zones_col_zone'] ?: __('Zone de navigation',       'blacktenderscore');
        $col_half     = $s['zones_col_half'] ?: __('Demi-journée',             'blacktenderscore');
        $col_full     = $s['zones_col_full'] ?: __('Journée',                  'blacktenderscore');

        echo '<div class="bt-bprice__zones">';
        echo '<h4 class="bt-bprice__zones-title">' . esc_html($zones_title) . '</h4>';
        echo '<div class="bt-bprice__table-wrap"><table class="bt-bprice__table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html($col_zone) . '</th>';
        echo '<th>' . esc_html($col_half) . '</th>';
        echo '<th>' . esc_html($col_full) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($zones as $row) {
            $zone_label = $this->boat_resolve_zone_label($row['boat_navigation_zone'] ?? null);
            $p_half     = $row['boat_price_for_half_day'] ?? '';
            $p_full     = $row['boat_price_for_full_day'] ?? '';

            echo '<tr>';
            echo '<td class="bt-bprice__card-label">' . esc_html($zone_label) . '</td>';
            echo '<td class="bt-bprice__amount">' . ($p_half ? $this->boat_format_price((float) $p_half, $currency) : '—') . '</td>';
            echo '<td class="bt-bprice__amount">' . ($p_full ? $this->boat_format_price((float) $p_full, $currency) : '—') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div></div>';
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function boat_deposit_html(float $deposit, array $s, string $currency): string {
        if (($s['show_deposit'] ?? '') !== 'yes' || $deposit <= 0) return '';
        $lbl = esc_html($s['label_deposit'] ?: __('Caution', 'blacktenderscore'));
        return '<p class="bt-bprice__deposit">' . $lbl . ' : <strong>' . $this->boat_format_price($deposit, $currency) . '</strong></p>';
    }

    protected function boat_format_price(float $price, string $currency): string {
        return esc_html(number_format($price, 0, ',', ' ') . ' ' . $currency);
    }

    protected function boat_per_person_html(float $price, int $pax_max, array $s, string $currency): string {
        if (($s['show_per_person'] ?? '') !== 'yes' || $pax_max <= 0) return '';
        $lbl = esc_html($s['per_person_label'] ?: __('/ pers.', 'blacktenderscore'));
        return ' <span class="bt-bprice__per-person">(' . $this->boat_format_price($price / $pax_max, $currency) . ' ' . $lbl . ')</span>';
    }

    protected function boat_fuel_badge_html(bool $fuel_incl, array $s): string {
        if (($s['show_fuel_badge'] ?? '') !== 'yes') return '';
        $cls = $fuel_incl ? 'bt-bprice__fuel--yes' : 'bt-bprice__fuel--no';
        $lbl = $fuel_incl
            ? esc_html($s['label_fuel_yes'] ?: __('Carburant inclus', 'blacktenderscore'))
            : esc_html($s['label_fuel_no']  ?: __('Carburant en sus', 'blacktenderscore'));
        return '<span class="bt-bprice__fuel ' . $cls . '">' . $lbl . '</span>';
    }

    protected function boat_card_body_html(array $card, array $s, string $currency, string $note, float $deposit, int $pax_max): string {
        $out = '';
        if (($s['show_price_note'] ?? '') === 'yes' && $note) {
            $out .= '<span class="bt-bprice__note">' . esc_html($note) . '</span>';
        }
        $out .= '<div class="bt-bprice__amount-block">';
        $out .= '<span class="bt-bprice__amount">' . $this->boat_format_price($card['price'], $currency) . '</span>';
        $out .= $this->boat_per_person_html($card['price'], $pax_max, $s, $currency);
        if ($card['duration']) {
            $out .= ' <span class="bt-bprice__duration">— ' . esc_html($card['duration']) . '</span>';
        }
        $out .= '</div>';
        $out .= $this->boat_deposit_html($deposit, $s, $currency);
        return $out;
    }

    protected function boat_resolve_zone_label(mixed $zone_terms): string {
        if (!$zone_terms) return '';
        $ids   = is_array($zone_terms) ? $zone_terms : [$zone_terms];
        $names = [];
        foreach ($ids as $tid) {
            $t = is_numeric($tid)
                ? get_term((int) $tid)
                : ($tid instanceof \WP_Term ? $tid : null);
            if ($t && !is_wp_error($t)) $names[] = $t->name;
        }
        return implode(', ', $names);
    }
}
