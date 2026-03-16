<?php
/**
 * Trait BtExcursionPricing — Excursion pricing render methods.
 *
 * Extracted from BoatPricing widget. Provides:
 * - Excursion tabs/buttons layout rendering
 * - Excursion price, heading, discount helpers
 * - Regiondo booking widget (direct + lazy)
 * - Quote form card rendering (excursion cards, boat cards, Loop template helper)
 *
 * @package BlackTenders\Elementor\Traits
 */

namespace BlackTenders\Elementor\Traits;

defined('ABSPATH') || exit;

trait BtExcursionPricing {

    /** Évite d'injecter le script Regiondo plusieurs fois sur la même page. */
    protected static bool $regiondo_script_printed = false;

    // ── Excursion Pricing ───────────────────────────────────────────────────

    /**
     * Dispatcher principal : lit le repeater ACF, résout les UUIDs Regiondo,
     * puis délègue au layout tabs ou buttons.
     */
    protected function render_excursion_pricing(array $s, int $post_id): void {
        $repeater_slug = $s['exc_repeater_slug'] ?: 'tarification_par_forfait';
        $rows = $this->get_acf_rows(
            $repeater_slug,
            sprintf(__('Aucun forfait trouvé pour le champ « %s ».', 'blacktenderscore'), $repeater_slug)
        );
        if (!$rows) return;

        $layout   = $s['exc_layout'] ?? 'tabs';
        $currency = esc_html($s['exc_currency'] ?: '€');
        $uid      = 'bt-pricing-' . $this->get_id();
        $lazy     = ($s['exc_trigger_mode'] ?? 'none') !== 'none';

        // ── Résolution UUID Regiondo ────────────────────────────────────
        $global_uuid = '';
        $tab_uuids   = [];

        if (($s['exc_show_booking'] ?? '') === 'yes') {
            if (($s['exc_booking_per_tab'] ?? '') === 'yes') {
                $subfield = $s['exc_booking_uuid_subfield'] ?: 'exp_booking_uuid';
                foreach ($rows as $row) {
                    $tab_uuids[] = (string) ($row[$subfield] ?? '');
                }
            } else {
                $global_uuid = (string) get_field($s['exc_booking_field'] ?? 'exp_booking_short_url', $post_id);
            }
        }

        $active_uuid = $global_uuid ?: ($tab_uuids[0] ?? '');

        if ($layout === 'buttons') {
            $this->render_exc_buttons_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $lazy);
        } else {
            $this->render_exc_tabs_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $lazy);
        }
    }

    /**
     * Layout onglets pour les forfaits excursion.
     */
    protected function render_exc_tabs_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy): void {
        $uuids_attr = '';
        if (!empty($tab_uuids)) {
            $uuids_attr = " data-tab-uuids='" . esc_attr(wp_json_encode($tab_uuids)) . "'";
        }

        echo "<div class=\"bt-pricing bt-pricing--tabs\" id=\"{$uid}\" data-bt-tabs data-bt-panel-class=\"bt-pricing__panel\"{$uuids_attr}>";

        $this->render_exc_heading_block($s);

        // Tab bar
        $discount_key = ($s['exc_discount_subfield'] ?? '') !== '' ? $s['exc_discount_subfield'] : 'is_a_discount';
        echo '<div class="bt-pricing__tablist" role="tablist">';
        foreach ($rows as $i => $row) {
            $label   = $this->get_exc_tab_label($row, $i, $s, $currency);
            $discount = $this->get_exc_discount_value($row, $discount_key);
            $tab_id  = "{$uid}-tab-{$i}";
            $pan_id  = "{$uid}-panel-{$i}";
            $active  = $i === 0 ? ' bt-pricing__tab--active' : '';
            $sel     = $i === 0 ? 'true' : 'false';
            $tabi    = $i === 0 ? '0' : '-1';
            echo "<button class=\"bt-pricing__tab{$active}\" id=\"{$tab_id}\" role=\"tab\" aria-selected=\"{$sel}\" aria-controls=\"{$pan_id}\" tabindex=\"{$tabi}\">";
            echo esc_html($label);
            if ($discount > 0) {
                echo ' <span class="bt-pricing__discount">-' . (int) $discount . '%</span>';
            }
            echo '</button>';
        }
        echo '</div>';

        // Panels
        foreach ($rows as $i => $row) {
            $tab_id     = "{$uid}-tab-{$i}";
            $pan_id     = "{$uid}-panel-{$i}";
            $active_cls = $i === 0 ? ' bt-pricing__panel--active' : '';

            echo "<div class=\"bt-pricing__panel{$active_cls}\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\">";
            $this->render_exc_price_block($s, $row, $currency);
            echo '</div>';
        }

        // Booking Regiondo — shared, outside panels
        if ($lazy) {
            $this->render_exc_booking_section_lazy($s, $active_uuid, $post_id);
        } else {
            $this->render_exc_booking_section($s, $active_uuid, $post_id);
        }

        echo '</div>'; // .bt-pricing
    }

    /**
     * Layout boutons pill pour les forfaits excursion.
     */
    protected function render_exc_buttons_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy): void {
        $uuids_json = !empty($tab_uuids) ? wp_json_encode($tab_uuids) : '';

        echo "<div class=\"bt-pricing bt-pricing--buttons\" id=\"{$uid}\" data-bt-pricing-buttons";
        if ($uuids_json) {
            echo " data-tab-uuids='" . esc_attr($uuids_json) . "'";
        }
        echo '>';

        $this->render_exc_heading_block($s);

        // Title
        $title = $s['exc_buttons_title'] ?? '';
        if ($title) {
            $tag = esc_attr($s['exc_buttons_title_tag'] ?: 'h4');
            echo "<{$tag} class=\"bt-pricing__btn-title\">" . esc_html($title) . "</{$tag}>";
        }

        $discount_key = ($s['exc_discount_subfield'] ?? '') !== '' ? $s['exc_discount_subfield'] : 'is_a_discount';

        // Pill buttons
        echo '<div class="bt-pricing__slots">';
        foreach ($rows as $i => $row) {
            $label    = $this->get_exc_tab_label($row, $i, $s, $currency);
            $discount = $this->get_exc_discount_value($row, $discount_key);
            $uuid     = $tab_uuids[$i] ?? $active_uuid;

            echo '<button type="button" class="bt-pricing__slot" data-slot-index="' . $i . '" data-uuid="' . esc_attr($uuid) . '">';
            echo esc_html($label);
            if ($discount > 0) {
                echo ' <span class="bt-pricing__discount">-' . (int) $discount . '%</span>';
            }
            echo '</button>';
        }
        echo '</div>'; // .bt-pricing__slots

        // Price panels — hidden by default
        foreach ($rows as $i => $row) {
            $panel_id = "{$uid}-panel-{$i}";
            echo "<div class=\"bt-pricing__panel\" id=\"{$panel_id}\" data-slot-panel=\"{$i}\">";
            $this->render_exc_price_block($s, $row, $currency);
            echo '</div>';
        }

        // Booking — shared, revealed on slot click
        if (($s['exc_show_booking'] ?? '') === 'yes') {
            echo '<div class="bt-pricing__booking-reveal">';
            if ($active_uuid) {
                if ($lazy) {
                    $this->render_exc_booking_section_lazy($s, $active_uuid, $post_id);
                } else {
                    echo $this->render_exc_booking_widget($active_uuid, $post_id, 0);
                }
            } elseif ($this->is_edit_mode()) {
                echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
            }
            echo '</div>';
        }

        echo '</div>'; // .bt-pricing
    }

    // ── Excursion pricing helpers ────────────────────────────────────────────

    /** Heading block pour le mode excursion. */
    protected function render_exc_heading_block(array $s): void {
        $this->render_section_title($s, 'bt-pricing__section-title');
        $desc = trim((string) ($s['exc_section_description'] ?? ''));
        if ($desc !== '') {
            echo '<div class="bt-pricing__section-desc">' . wp_kses_post($desc) . '</div>';
        }
    }

    /** Bloc prix dans un panel excursion. */
    protected function render_exc_price_block(array $s, array $row, string $currency): void {
        $price    = $row['exp_price']        ?? '';
        $note     = $row['exp_pricing_note'] ?? '';
        $deposit  = $row['exp_deposit']      ?? '';
        $discount_key = ($s['exc_discount_subfield'] ?? '') !== '' ? $s['exc_discount_subfield'] : 'is_a_discount';
        $discount = $this->get_exc_discount_value($row, $discount_key);

        if ($price !== '' && ($s['exc_show_price'] ?? 'yes') === 'yes') {
            echo '<div class="bt-pricing__price-block">';
            if ($note && ($s['exc_show_note'] ?? 'yes') === 'yes') {
                echo '<span class="bt-pricing__note">' . esc_html($note) . ' </span>';
            }
            echo '<span class="bt-pricing__price">' . esc_html(number_format((float) $price, 0, ',', ' ')) . ' ' . $currency . '</span>';
            if ($discount > 0) {
                echo ' <span class="bt-pricing__discount">-' . (int) $discount . '%</span>';
            }
            if (($s['exc_show_per_label'] ?? 'yes') === 'yes') {
                $per_lbl = esc_html($s['exc_per_label'] ?: __('/ pers.', 'blacktenderscore'));
                echo '<span class="bt-pricing__per"> ' . $per_lbl . '</span>';
            }
            echo '</div>';
        }

        if ($deposit && ($s['exc_show_deposit'] ?? 'yes') === 'yes') {
            $dep_lbl = esc_html($s['exc_deposit_label'] ?: __('Acompte :', 'blacktenderscore'));
            echo '<p class="bt-pricing__deposit">' . $dep_lbl . ' <strong>' . esc_html($deposit) . ' ' . $currency . '</strong></p>';
        }
    }

    /** Libellé d'onglet / bouton excursion. */
    protected function get_exc_tab_label(array $row, int $i, array $s, string $currency): string {
        $term_val = $row['exp_time'] ?? null;
        $base     = '';

        if ($term_val) {
            if ($term_val instanceof \WP_Term) {
                $base = $term_val->name;
            } elseif (is_numeric($term_val)) {
                $t = get_term((int) $term_val);
                if ($t && !is_wp_error($t)) $base = $t->name;
            } elseif (is_array($term_val)) {
                $first = reset($term_val);
                if ($first instanceof \WP_Term) {
                    $base = $first->name;
                } elseif (is_numeric($first)) {
                    $t = get_term((int) $first);
                    if ($t && !is_wp_error($t)) $base = $t->name;
                }
            } elseif (is_string($term_val) && $term_val !== '') {
                $base = $term_val;
            }
        }
        if ($base === '') {
            $base = sprintf(__('Forfait %d', 'blacktenderscore'), $i + 1);
        }

        $price_raw = $row['exp_price'] ?? '';
        $price_str = $price_raw !== '' ? number_format((float) $price_raw, 0, ',', ' ') . ' ' . $currency : '';

        $mode = $s['exc_tab_title_mode'] ?? 'forfait_et_prix';
        if ($mode === 'prix_seul') {
            return $price_str !== '' ? $price_str : $base;
        }
        return $price_str !== '' ? $base . ' — ' . $price_str : $base;
    }

    /** Valeur du champ remise excursion (%). */
    protected function get_exc_discount_value(array $row, string $subfield): int {
        $v = $row[$subfield] ?? null;
        if ($v === null || $v === '') return 0;
        $n = (int) $v;
        return $n > 0 ? $n : 0;
    }

    // ── Excursion booking helpers ────────────────────────────────────────────

    /** Section booking direct (non lazy). */
    protected function render_exc_booking_section(array $s, string $active_uuid, int $post_id): void {
        if (($s['exc_show_booking'] ?? '') !== 'yes') return;

        if ($active_uuid) {
            echo $this->render_exc_booking_widget($active_uuid, $post_id, 0);
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
        }
    }

    /** Section booking lazy (via <template>). */
    protected function render_exc_booking_section_lazy(array $s, string $active_uuid, int $post_id): void {
        if (($s['exc_show_booking'] ?? '') !== 'yes') return;

        if ($active_uuid) {
            echo '<div class="bt-pricing__booking-lazy">';
            echo '<template class="bt-booking-tpl">';
            echo $this->render_exc_booking_widget_lazy($active_uuid);
            echo '</template>';
            echo '</div>';
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
        }
    }

    /** Génère le HTML du booking-widget Regiondo (avec script). */
    protected function render_exc_booking_widget(string $uuid, int $post_id, int $index): string {
        $widget_id = esc_attr($uuid);
        $script    = '';
        if (!self::$regiondo_script_printed) {
            $script = '<script src="https://widgets.regiondo.net/booking/v1/booking-widget.min.js" async></script>';
            self::$regiondo_script_printed = true;
        }
        $custom_css = get_option('bt_booking_custom_css', '');

        ob_start(); ?>
        <div class="bt-pricing__booking">
            <booking-widget widget-id="<?= $widget_id ?>">
                <style>
                    .regiondo-booking-widget { max-width: 100% !important; }
                    .regiondo-widget .regiondo-button-addtocart,
                    .regiondo-widget .regiondo-button-checkout { border-radius: 40px; }
                    <?php if ($custom_css): ?>
                    <?= wp_strip_all_tags($custom_css) ?>
                    <?php endif; ?>
                </style>
            </booking-widget>
            <?= $script ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /** Génère le HTML du booking-widget sans le <script> (lazy). */
    protected function render_exc_booking_widget_lazy(string $uuid): string {
        $widget_id  = esc_attr($uuid);
        $custom_css = get_option('bt_booking_custom_css', '');

        ob_start(); ?>
        <div class="bt-pricing__booking">
            <booking-widget widget-id="<?= $widget_id ?>">
                <style>
                    .regiondo-booking-widget { max-width: 100% !important; }
                    .regiondo-widget .regiondo-button-addtocart,
                    .regiondo-widget .regiondo-button-checkout { border-radius: 40px; }
                    <?php if ($custom_css): ?>
                    <?= wp_strip_all_tags($custom_css) ?>
                    <?php endif; ?>
                </style>
            </booking-widget>
        </div>
        <?php
        return ob_get_clean();
    }

    // ── Quote form card helpers ─────────────────────────────────────────────
    // Moved to BtPricingShared trait:
    // - render_excursion_cards()
    // - render_linked_boat_cards()
    // - render_default_boat_card()
    // - render_loop_item() → render_shared_loop_item()
}
