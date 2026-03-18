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
     * Layout cartes forfait pour les forfaits excursion.
     * Remplace l'ancien layout tabs par des forfait cards cote a cote.
     */
    protected function render_exc_tabs_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy): void {
        $layout    = ($s['exc_layout'] ?? 'grid') === 'inline' ? 'inline' : 'grid';
        $is_inline = $layout === 'inline';

        echo "<div class=\"bt-pricing bt-pricing--cards\" id=\"{$uid}\">";

        $this->render_exc_heading_block($s);

        $discount_key  = ($s['exc_discount_subfield'] ?? '') !== '' ? $s['exc_discount_subfield'] : 'is_a_discount';
        $show_badges   = ($s['exc_show_badge'] ?? 'yes') === 'yes';
        $show_discount = ($s['exc_show_discount'] ?? 'yes') === 'yes';

        // Switchers meta (durée par forfait, lieu post-level)
        $show_duration = ($s['exc_show_duration'] ?? 'yes') === 'yes';
        $show_landing  = ($s['exc_show_landing'] ?? 'yes') === 'yes';
        $post_location = $show_landing ? trim((string) get_field('exp_landing_point', $post_id)) : '';

        // Label au-dessus des cards
        $cards_title = $s['exc_buttons_title'] ?? __('Choisissez votre formule', 'blacktenderscore');
        if ($cards_title) {
            echo '<p class="bt-forfaits__label">' . esc_html($cards_title) . '</p>';
        }

        // Grid ou liste
        $grid_cls = $is_inline ? 'bt-forfaits__grid bt-forfaits__grid--inline' : 'bt-forfaits__grid';
        echo '<div class="' . $grid_cls . '">';

        foreach ($rows as $i => $row) {
            $price     = $row['exp_price'] ?? '';
            $discount  = $this->get_exc_discount_value($row, $discount_key);
            $label     = $this->get_exc_card_name($row, $i);
            $uuid      = $tab_uuids[$i] ?? '';
            $active    = $i === 0 ? ' bt-forfait-card--active' : '';
            $pressed   = $i === 0 ? 'true' : 'false';

            // Durée par forfait (sous-champ repeater), lieu post-level
            $row_duration = $show_duration ? trim((string) ($row['exc_timeinbot'] ?? '')) : '';
            $has_meta     = ($row_duration !== '' || $post_location !== '');

            echo '<button class="bt-forfait-card' . $active . '"'
               . ' data-bt-forfait-index="' . $i . '"'
               . ' aria-pressed="' . $pressed . '"';
            if ($uuid) {
                echo ' data-bt-forfait-uuid="' . esc_attr($uuid) . '"';
            }
            echo '>';

            // Badge : promo > populaire (conditionné par switcher)
            if ($show_badges) {
                $is_popular   = !empty($row['is_popular']);
                $has_discount = $discount > 0;
                if ($is_popular || $has_discount) {
                    $badge_text = $has_discount
                        ? esc_html($s['exc_discount_badge_label'] ?? __('Promo', 'blacktenderscore'))
                        : esc_html($s['exc_popular_badge_label'] ?? __('Populaire', 'blacktenderscore'));
                    echo '<span class="bt-forfait-card__badge">' . $badge_text . '</span>';
                }
            }

            // Contenu card — inline = flex row, grid = flex column
            echo '<div class="bt-forfait-card__content">';

            // Bloc prix
            if ($price !== '' && ($s['exc_show_price'] ?? 'yes') === 'yes') {
                $price_val    = (float) $price;
                $discount_pct = (float) ($row[$discount_key] ?? 0);

                echo '<div class="bt-forfait-card__pricing">';

                // Prix barré (conditionné par switcher)
                if ($show_discount && $discount_pct > 0) {
                    $original_price = (int) round($price_val / (1 - $discount_pct / 100));
                    echo '<span class="bt-forfait-card__original">' . esc_html($original_price) . ' ' . $currency . '</span>';
                }

                $formatted = number_format($price_val, 0, ',', ' ');
                echo '<span class="bt-forfait-card__price">' . esc_html($formatted) . '</span>';
                echo '<span class="bt-forfait-card__currency">' . $currency . '</span>';

                if (($s['exc_show_per_label'] ?? 'yes') === 'yes') {
                    $per_lbl = esc_html($s['exc_per_label'] ?: __('/ pers.', 'blacktenderscore'));
                    echo '<span class="bt-forfait-card__per">' . $per_lbl . '</span>';
                }
                echo '</div>';

                // Badge discount inline (conditionné par switcher)
                if ($show_discount && $discount_pct > 0) {
                    echo '<span class="bt-forfait-card__discount">-' . (int) $discount_pct . '%</span>';
                }
            }

            // Séparateur vertical en mode inline (entre prix et meta)
            if ($is_inline && $has_meta) {
                echo '<span class="bt-forfait-card__separator" aria-hidden="true"></span>';
            }

            // Meta (durée par forfait + lieu post-level)
            if ($has_meta) {
                echo '<div class="bt-forfait-card__meta' . ($is_inline ? ' bt-forfait-card__meta--row' : '') . '">';
                if ($row_duration !== '') {
                    echo '<div class="bt-forfait-card__meta-item">'
                       . '<svg class="bt-forfait-card__icon" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>'
                       . esc_html($row_duration)
                       . '</div>';
                }
                if ($post_location !== '') {
                    echo '<div class="bt-forfait-card__meta-item">'
                       . '<svg class="bt-forfait-card__icon" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>'
                       . esc_html($post_location)
                       . '</div>';
                }
                echo '</div>';
            }

            echo '</div>'; // .bt-forfait-card__content

            // Radio dot en mode inline
            if ($is_inline) {
                echo '<span class="bt-forfait-card__radio" aria-hidden="true"></span>';
            }

            echo '</button>';
        }
        echo '</div>'; // .bt-forfaits__grid

        // Contenu dynamique par forfait (note, acompte, booking)
        foreach ($rows as $i => $row) {
            $hidden = $i !== 0 ? ' hidden' : '';
            echo '<div class="bt-forfait-content" data-bt-forfait-content="' . $i . '"' . $hidden . '>';
            $this->render_exc_price_block($s, $row, $currency);

            // Booking Regiondo par forfait
            if (($s['exc_show_booking'] ?? '') === 'yes') {
                $row_uuid = $tab_uuids[$i] ?? $active_uuid;
                if ($row_uuid) {
                    if ($lazy) {
                        $this->render_exc_booking_section_lazy($s, $row_uuid, $post_id);
                    } else {
                        echo $this->render_exc_booking_widget($row_uuid, $post_id, $i);
                    }
                }
            }

            echo '</div>';
        }

        // Booking Regiondo global (quand pas per_tab, un seul widget partage)
        if (($s['exc_show_booking'] ?? '') === 'yes' && empty($tab_uuids) && $active_uuid) {
            if ($lazy) {
                $this->render_exc_booking_section_lazy($s, $active_uuid, $post_id);
            } else {
                $this->render_exc_booking_section($s, $active_uuid, $post_id);
            }
        }

        echo '</div>'; // .bt-pricing
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
        // Unifie sur le meme layout cards
        $this->render_exc_tabs_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $lazy);
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
