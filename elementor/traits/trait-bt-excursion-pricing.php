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

    /** Flag pour enqueue le CSS du template Loop une seule fois par tpl_id. */
    protected static array $loop_css_enqueued = [];

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

    /**
     * Rendu des cards excursion pour le formulaire de devis.
     * Utilise un template Loop Elementor si configuré.
     */
    protected function render_excursion_cards(array $s): void {
        $excursions = get_posts([
            'post_type'      => 'excursion',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($excursions)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucune excursion disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        $tpl_id = (int) ($s['step_exc_loop_tpl'] ?? 0);

        echo '<div class="bt-quote-exc-cards">';

        foreach ($excursions as $exc) {
            echo '<div class="bt-quote-exc-card" data-exc-id="' . esc_attr($exc->ID) . '" tabindex="0" role="option" aria-selected="false">';
            echo $this->render_loop_item($tpl_id, $exc);
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Rendu des cards bateau liés à l'excursion courante via Loop template.
     */
    protected function render_linked_boat_cards(array $s, int $exc_id): void {
        $boat_ids = [];
        $exp_boats = get_field('exp_boats', $exc_id);
        if (is_array($exp_boats)) {
            foreach ($exp_boats as $boat) {
                $boat_ids[] = $boat instanceof \WP_Post ? $boat->ID : (int) $boat;
            }
        }

        if (empty($boat_ids)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucun bateau disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        $tpl_id = (int) ($s['step_boat_loop_tpl'] ?? 0);

        echo '<div class="bt-quote-boat-cards">';
        foreach ($boat_ids as $bid) {
            $boat = get_post($bid);
            if (!$boat || $boat->post_status !== 'publish') continue;

            echo '<div class="bt-quote-boat-card" data-boat-id="' . esc_attr($bid) . '">';
            if ($tpl_id) {
                echo $this->render_loop_item($tpl_id, $boat);
            } else {
                $this->render_default_boat_card($bid, $boat);
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Card bateau par défaut : layout horizontal (30% image | 70% contenu).
     * Enrichie avec données ACF : type, pax, prix/pers journée.
     */
    protected function render_default_boat_card(int $bid, \WP_Post $boat): void {
        $thumb      = get_the_post_thumbnail_url($bid, 'medium');
        $pax        = (int) get_field('boat_pax_max', $bid);
        $price_full = (float) get_field('boat_price_full', $bid);
        $price_half = (float) get_field('boat_price_half', $bid);
        $fuel_incl  = (bool) get_field('boat_fuel_included', $bid);

        $type  = '';
        $types = get_the_terms($bid, 'type-de-bateau');
        if (!empty($types) && !is_wp_error($types)) {
            $type = $types[0]->name;
        }

        // Prix par personne (journée complète prioritaire, sinon demi-journée)
        $base_price = $price_full ?: $price_half;
        $pp_price   = ($base_price && $pax > 0) ? ceil($base_price / $pax) : 0;
        $pp_label   = $price_full ? __('journée complète', 'blacktenderscore') : __('demi-journée', 'blacktenderscore');

        if ($thumb) {
            echo '<div class="bt-quote-boat-card__img">'
               . '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($boat->post_title) . '" loading="lazy">'
               . '</div>';
        }

        echo '<div class="bt-quote-boat-card__body">';

        // Header : titre + badge type
        echo '<div class="bt-quote-boat-card__header">';
        echo '<h4 class="bt-quote-boat-card__title">' . esc_html($boat->post_title) . '</h4>';
        if ($type) {
            echo '<span class="bt-quote-boat-card__type">' . esc_html($type) . '</span>';
        }
        echo '</div>';

        // Pax
        if ($pax) {
            echo '<p class="bt-quote-boat-card__pax">'
               . esc_html(sprintf(__('Jusqu\'à %d passagers', 'blacktenderscore'), $pax))
               . '</p>';
        }

        // Prix par personne
        if ($pp_price) {
            echo '<p class="bt-quote-boat-card__price">'
               . '<span class="bt-quote-boat-card__price-amount">'
               . esc_html(sprintf(__('À partir de %d € / pers.', 'blacktenderscore'), $pp_price))
               . '</span>'
               . ' <span class="bt-quote-boat-card__price-suffix">'
               . esc_html($pp_label)
               . '</span>'
               . '</p>';
        }

        // Carburant
        if ($fuel_incl) {
            echo '<span class="bt-quote-boat-card__fuel-badge">' . esc_html__('Carburant inclus', 'blacktenderscore') . '</span>';
        }

        echo '</div>';
    }

    // ── Loop template helper ────────────────────────────────────────────────

    /**
     * Rendu d'un item via un template Elementor Loop.
     * Enqueue le CSS du template + définit le contexte post.
     */
    protected function render_loop_item(int $tpl_id, \WP_Post $item): string {
        if ($tpl_id && class_exists('\Elementor\Plugin')) {
            // Enqueue le CSS du template (une fois par tpl_id)
            if (!isset(self::$loop_css_enqueued[$tpl_id])) {
                self::$loop_css_enqueued[$tpl_id] = true;
                $css_file = \Elementor\Core\Files\CSS\Post::create($tpl_id);
                $css_file->enqueue();
            }

            global $post;
            $original_post = $post;
            $post = $item;
            setup_postdata($post);

            $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($tpl_id);

            $post = $original_post;
            if ($original_post) wp_reset_postdata();

            return $html ?: '<p>' . esc_html($item->post_title) . '</p>';
        }

        // Fallback sans template
        return '<div class="bt-quote-loop-fallback">'
             . '<strong>' . esc_html($item->post_title) . '</strong>'
             . '</div>';
    }
}
