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

    // ── SVG icons ──
    const BT_ICON_CLOCK = '<svg class="bt-forfait-card__icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>';
    const BT_ICON_PIN   = '<svg class="bt-forfait-card__icon" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>';
    const BT_ICON_USERS = '<svg class="bt-forfait-card__icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>';

    protected function render_exc_forfait_cards(array $s, int $post_id): void {
        $repeater_slug    = $s['exc_repeater_slug'] ?? 'tarification_par_forfait';
        $rows             = get_field($repeater_slug, $post_id);
        if (empty($rows) || !is_array($rows)) return;
        $currency         = esc_html($s['exc_currency'] ?? '€');
        $discount_field   = $s['exc_discount_subfield'] ?? 'is_a_discount';
        $show_price       = ($s['exc_show_price'] ?? 'yes') === 'yes';
        $show_per         = ($s['exc_show_per_label'] ?? 'yes') === 'yes';
        $per_label        = esc_html($s['exc_per_label'] ?? '/ pers.');
        $show_deposit     = ($s['exc_show_deposit'] ?? 'yes') === 'yes';
        $deposit_label    = esc_html($s['exc_deposit_label'] ?? 'Acompte :');
        $show_note        = ($s['exc_show_note'] ?? 'yes') === 'yes';
        $show_duration    = ($s['exc_show_duration'] ?? 'yes') === 'yes';
        $show_landing     = ($s['exc_show_landing'] ?? 'yes') === 'yes';
        $show_badge       = ($s['exc_show_badge'] ?? 'yes') === 'yes';
        $show_discount    = ($s['exc_show_discount'] ?? 'yes') === 'yes';
        $popular_label    = esc_html($s['exc_popular_badge_label'] ?? 'Populaire');
        $discount_b_label = esc_html($s['exc_discount_badge_label'] ?? 'Promo');
        $show_booking     = ($s['exc_show_booking'] ?? 'yes') === 'yes';
        $booking_per_tab  = ($s['exc_booking_per_tab'] ?? '') === 'yes';
        $booking_field    = $s['exc_booking_field'] ?? 'exp_booking_short_url';
        $booking_sub      = $s['exc_booking_uuid_subfield'] ?? 'exp_booking_uuid';
        $layout           = $s['exc_layout'] ?? 'grid';
        $is_inline        = ($layout === 'inline');
        // Champ post-level (1 seul get_field, pas dans la boucle)
        $landing = $show_landing
            ? esc_html(get_field('exp_landing_point', $post_id) ?: '')
            : '';
        // Grid
        $grid_cls = 'bt-forfaits__grid';
        if ($is_inline) $grid_cls .= ' bt-forfaits__grid--inline';
        printf('<div class="%s">', esc_attr($grid_cls));
        foreach ($rows as $i => $row) {
            $price        = $row['prix'] ?? '';
            $name         = $row['nom_forfait'] ?? '';
            $discount_pct = (float)($row[$discount_field] ?? 0);
            $has_discount = $discount_pct > 0;
            $is_popular   = !empty($row['is_popular']);
            $is_active    = ($i === 0);
            $duration     = $show_duration ? esc_html($row['exc_timeinbot'] ?? '') : '';
            $uuid = '';
            if ($show_booking && $booking_per_tab) {
                $uuid = $row[$booking_sub] ?? '';
            }
            $card_cls = 'bt-forfait-card';
            if ($is_active) $card_cls .= ' bt-forfait-card--active';
            printf(
                '<button class="%s" data-bt-forfait-index="%d" aria-pressed="%s"%s>',
                esc_attr($card_cls),
                $i,
                $is_active ? 'true' : 'false',
                $uuid ? ' data-bt-forfait-uuid="' . esc_attr($uuid) . '"' : ''
            );
            // Badge
            if ($show_badge && ($is_popular || ($has_discount && $show_discount))) {
                $badge_text = ($has_discount && $show_discount)
                    ? $discount_b_label
                    : ($is_popular ? $popular_label : '');
                if ($badge_text !== '') {
                    printf('<span class="bt-forfait-card__badge">%s</span>', $badge_text);
                }
            }
            echo '<div class="bt-forfait-card__content">';
            // Nom forfait
            if ($name !== '') {
                printf('<span class="bt-forfait-card__name">%s</span>', esc_html($name));
            }
            // Prix
            if ($show_price && $price !== '') {
                echo '<div class="bt-forfait-card__pricing">';
                if ($has_discount && $show_discount) {
                    $original = round((float)$price / (1 - $discount_pct / 100));
                    printf(
                        '<span class="bt-forfait-card__original">%s %s</span>',
                        esc_html($original),
                        $currency
                    );
                }
                printf('<span class="bt-forfait-card__price">%s</span>', esc_html($price));
                printf('<span class="bt-forfait-card__currency">%s</span>', $currency);
                if ($show_per) {
                    printf('<span class="bt-forfait-card__per">%s</span>', $per_label);
                }
                echo '</div>';
                if ($has_discount && $show_discount) {
                    printf(
                        '<span class="bt-forfait-card__discount">-%s%%</span>',
                        esc_html((int)$discount_pct)
                    );
                }
            }
            // Séparateur (CSS le rend visible uniquement en inline)
            echo '<span class="bt-forfait-card__separator"></span>';
            // Meta
            $has_meta = ($duration !== '' || $landing !== '');
            if ($has_meta) {
                $meta_cls = 'bt-forfait-card__meta';
                if ($is_inline) $meta_cls .= ' bt-forfait-card__meta--row';
                printf('<div class="%s">', esc_attr($meta_cls));
                if ($duration !== '') {
                    printf(
                        '<span class="bt-forfait-card__meta-item">%s %s</span>',
                        BT_ICON_CLOCK,
                        $duration
                    );
                }
                if ($landing !== '') {
                    printf(
                        '<span class="bt-forfait-card__meta-item">%s %s</span>',
                        BT_ICON_PIN,
                        $landing
                    );
                }
                echo '</div>';
            }
            echo '</div>'; // .content
            echo '<span class="bt-forfait-card__radio"></span>';
            echo '</button>';
        }
        echo '</div>'; // .grid
        // Contenu par forfait
        foreach ($rows as $i => $row) {
            printf(
                '<div class="bt-forfait-content" data-bt-forfait-content="%d"%s>',
                $i,
                $i !== 0 ? ' hidden' : ''
            );
            if ($show_deposit) {
                $deposit_val = $row['acompte'] ?? '';
                if ($deposit_val !== '') {
                    printf(
                        '<p class="bt-pricing__deposit">%s %s %s</p>',
                        $deposit_label,
                        esc_html($deposit_val),
                        $currency
                    );
                }
            }
            if ($show_note) {
                $note = $row['note_tarifaire'] ?? '';
                if ($note !== '') {
                    printf('<p class="bt-pricing__note">%s</p>', esc_html($note));
                }
            }
            if ($show_booking) {
                echo '<div class="bt-pricing__booking">';
                $tab_uuid = $booking_per_tab
                    ? ($row[$booking_sub] ?? '')
                    : (get_field($booking_field, $post_id) ?: '');
                if ($tab_uuid) {
                    $this->render_booking_widget($tab_uuid, $s);
                }
                echo '</div>';
            }
            echo '</div>';
        }
    }

    /**
     * Layout cartes forfait pour les forfaits excursion.
     * Remplace l'ancien layout tabs par des forfait cards cote a cote.
     */
    protected function render_exc_tabs_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy): void {
        $this->render_exc_forfait_cards($s, $post_id);
    }

    /**
     * Nom du forfait pour la card (sans le prix).
     */
    protected function get_exc_card_name(array $row, int $i): string {
        $term_val = $row['exp_time'] ?? null;
        $base = '';

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

        return $base;
    }

    /**
     * Layout boutons pill pour les forfaits excursion.
     * Redirige desormais vers le meme layout cards que le mode tabs.
     */
    protected function render_exc_buttons_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy): void {
        $this->render_exc_forfait_cards($s, $post_id);
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
