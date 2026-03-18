<?php
/**
 * Trait BtPricingShared -- Shared render methods for BoatPricing & ExcursionPricing widgets.
 *
 * Provides:
 * - Unified trigger open/close (reveal button pattern)
 * - Wrapper tabs Forfaits/Devis (two-panel layout)
 * - Embedded quote form rendering
 *
 * @package BlackTenders\Elementor\Traits
 */

namespace BlackTenders\Elementor\Traits;

use BlackTenders\Elementor\Widgets\QuoteForm;

defined('ABSPATH') || exit;

trait BtPricingShared {

    // ── Layout orchestrator ─────────────────────────────────────────────────

    /**
     * Pattern commun : trigger(optionnel) → wrapper(optionnel) → contenu → quote(optionnel).
     *
     * Factorise render_boat_mode() et render_excursion_mode() qui étaient quasi-identiques.
     *
     * @param array    $s        Widget settings.
     * @param int      $post_id  Post ID courant.
     * @param array    $keys     Mapping des clés settings du trigger :
     *                             mode, label, label_default, target, target_id,
     *                             hide_sel, fullwidth, wrap_prefix
     * @param callable $content  fn(array $s, int $post_id): void — rendu du contenu tarifs.
     */
    protected function render_pricing_layout(array $s, int $post_id, array $keys, callable $content): void {
        $mode = $s[$keys['mode']] ?? 'none';

        if ($mode !== 'none') {
            $this->render_trigger_open(
                $s,
                $mode,
                $keys['label']         ?? 'trigger_label',
                $keys['label_default'] ?? 'Voir les tarifs',
                $keys['target']        ?? 'reveal_target',
                $keys['target_id']     ?? 'reveal_target_id',
                $keys['hide_sel']      ?? 'reveal_hide_selector',
                $keys['fullwidth']     ?? 'trigger_fullwidth',
                $keys['wrap_prefix']   ?? 'bt-bprice-trigger'
            );
        }

        if (($s['show_quote_form'] ?? '') === 'yes') {
            $this->render_wrapper_open($s);
        }

        $content($s, $post_id);

        if (($s['show_quote_form'] ?? '') === 'yes') {
            $this->render_wrapper_between($s);
            $this->render_embedded_quote_form($s, $post_id);
            $this->render_wrapper_close();
        }

        if ($mode !== 'none') {
            $this->render_trigger_close($mode);
        }
    }

    // ── Trigger (bouton reveal) ─────────────────────────────────────────────

    /**
     * Ouvre le wrapper trigger/reveal.
     *
     * Unifie les anciens render_trigger_open() et render_exc_trigger_open().
     * Les deux etaient quasi-identiques — seuls les noms de settings differaient.
     *
     * @param array  $s              Widget settings.
     * @param string $mode           Trigger mode ('reveal', etc.).
     * @param string $label_key      Settings key for trigger label.
     * @param string $label_default  Default label text.
     * @param string $target_key     Settings key for reveal target type.
     * @param string $target_id_key  Settings key for custom target ID.
     * @param string $hide_sel_key   Settings key for hide selector.
     * @param string $fullwidth_key  Settings key for fullwidth toggle.
     * @param string $wrap_prefix    CSS ID prefix for the wrapper element.
     */
    protected function render_trigger_open(
        array  $s,
        string $mode,
        string $label_key     = 'trigger_label',
        string $label_default = 'Voir les tarifs',
        string $target_key    = 'reveal_target',
        string $target_id_key = 'reveal_target_id',
        string $hide_sel_key  = 'reveal_hide_selector',
        string $fullwidth_key = 'trigger_fullwidth',
        string $wrap_prefix   = 'bt-bprice-trigger'
    ): void {
        $trigger_label   = esc_html($s[$label_key] ?: __($label_default, 'blacktenderscore'));
        $reveal_dest     = $s[$target_key] ?? 'body';
        $reveal_target   = ($mode === 'reveal' && $reveal_dest === 'custom')
                         ? trim((string) ($s[$target_id_key] ?? ''))
                         : '';
        $hide_sel        = trim((string) ($s[$hide_sel_key] ?? ''));
        $wrap_id         = $wrap_prefix . '-' . $this->get_id();
        $data_reveal_tgt = $reveal_target !== '' ? ' data-bt-reveal-target="' . esc_attr($reveal_target) . '"' : '';
        $data_hide_sel   = $hide_sel !== '' ? ' data-bt-reveal-hide="' . esc_attr($hide_sel) . '"' : '';
        $fullwidth_cls   = ($s[$fullwidth_key] ?? '') === 'yes' ? ' bt-pricing__trigger--fullwidth' : '';
        $data_inline     = ($reveal_dest === 'inline') ? ' data-bt-reveal-inline' : '';

        // Use bt-pricing-trigger-wrap for excursion (keeps CSS selectors backward-compat)
        $wrap_cls = ($wrap_prefix === 'bt-bprice-trigger') ? 'bt-bprice-trigger-wrap' : 'bt-pricing-trigger-wrap';

        echo '<div class="' . $wrap_cls . '" id="' . esc_attr($wrap_id) . '"'
           . ' data-bt-trigger="' . esc_attr($mode) . '"' . $data_reveal_tgt . $data_hide_sel . $data_inline . '>';
        echo '<button type="button" class="bt-pricing__trigger' . $fullwidth_cls . '" aria-expanded="false">'
           . $trigger_label . '</button>';

        echo '<div class="bt-pricing__reveal-content">';
        echo '<div>'; // inner block for animation
    }

    /**
     * Ferme le wrapper trigger/reveal.
     */
    protected function render_trigger_close(string $mode): void {
        echo '</div>'; // inner block
        echo '</div>'; // .bt-pricing__reveal-content
        echo '</div>'; // wrapper
    }

    // ── Wrapper tabs Forfaits / Devis ────────────────────────────────────────

    /**
     * Ouvre le wrapper : segmented control [Reserver | Devis] + panel forfaits.
     */
    protected function render_wrapper_open(array $s): void {
        $tab1 = esc_html($s['quote_tab1_label'] ?: __('Réserver', 'blacktenderscore'));
        $tab2 = esc_html($s['quote_tab2_label'] ?: __('Demande de devis', 'blacktenderscore'));

        // Segmented control
        echo '<div class="bt-seg" role="tablist" aria-label="' . esc_attr__('Mode de réservation', 'blacktenderscore') . '" data-bt-seg-active="0">';
        echo '<button class="bt-seg__btn bt-seg__btn--active" role="tab" aria-selected="true" data-bt-seg-target="forfaits">' . $tab1 . '</button>';
        echo '<button class="bt-seg__btn" role="tab" aria-selected="false" data-bt-seg-target="devis">' . $tab2 . '</button>';
        echo '<span class="bt-seg__indicator" aria-hidden="true"></span>';
        echo '</div>';

        // Panel forfaits open
        echo '<div class="bt-seg__panel bt-seg__panel--active" data-bt-seg-panel="forfaits" role="tabpanel">';
    }

    /** Ferme le panel forfaits, ouvre le panel devis. */
    protected function render_wrapper_between(array $s): void {
        echo '</div>'; // close panel forfaits
        echo '<div class="bt-seg__panel" data-bt-seg-panel="devis" role="tabpanel" hidden>';
    }

    /** Ferme le panel devis. */
    protected function render_wrapper_close(): void {
        echo '</div>'; // close panel devis
    }

    // ── Formulaire de devis embarque ─────────────────────────────────────────

    /**
     * Rendu du formulaire de devis integre (simplifie).
     * Instancie le widget BT -- Devis et appelle son render.
     */
    protected function render_embedded_quote_form(array $s, int $post_id): void {
        $quote_settings = [
            'destination_mode'    => 'auto',
            'step_exc_enable'     => 'yes',
            'step_boat_enable'    => 'yes',
            'step_dates_enable'   => 'yes',
            'step_contact_enable' => 'yes',
            'step_submit_email'   => $s['quote_recipient'] ?? get_option('admin_email'),
            'boat_popup_tpl'     => (int) ($s['boat_popup_tpl']     ?? 0),
            'show_boat_more_btn' => ($s['show_boat_more_btn'] ?? '') === 'yes' ? 'yes' : '',
            'boat_tags'          => $s['boat_tags'] ?? [],
        ];

        $this->render_quote_form_html($quote_settings, $post_id);
    }

    /**
     * HTML du formulaire de devis (version embarquee, simplifiee).
     * Meme structure que QuoteForm::render() pour compatibilite JS.
     */
    protected function render_quote_form_html(array $qs, int $post_id): void {
        $post_type    = get_post_type($post_id);
        $is_excursion = ($post_type === 'excursion');
        $is_boat      = ($post_type === 'boat');

        $config = [
            'duration_options' => [
                'half'   => __('Demi-journée', 'blacktenderscore'),
                'full'   => __('Journée entière', 'blacktenderscore'),
                'multi'  => __('Plusieurs jours', 'blacktenderscore'),
                'custom' => __('Demande spécifique', 'blacktenderscore'),
            ],
            'recipient'    => $qs['step_submit_email'] ?? get_option('admin_email'),
            'msg_success'  => __('Votre demande a bien été envoyée !', 'blacktenderscore'),
            'msg_error'    => __('Une erreur est survenue.', 'blacktenderscore'),
            'pricing_mode' => $is_excursion ? 'excursion' : 'boat',
        ];

        echo '<div class="bt-quote" role="list" data-bt-quote'
           . ' data-ajax-url="' . esc_attr(admin_url('admin-ajax.php')) . '"'
           . ' data-nonce="' . esc_attr(wp_create_nonce('bt_quote_nonce')) . '"'
           . ' data-config="' . esc_attr(wp_json_encode($config)) . '">';

        $step = 0;

        // Step 1 -- Destination
        $step++;
        echo '<div class="bt-quote-step bt-quote-step--active" role="listitem" aria-expanded="true" aria-current="step" data-step="' . $step . '" data-step-type="excursion">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Votre excursion', 'blacktenderscore') . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        if ($is_excursion) {
            echo '<div class="bt-quote-exc-auto" data-exc-id="' . esc_attr($post_id) . '">';
            echo '<p class="bt-quote-exc-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
            echo '<input type="hidden" name="excursion_id" value="' . esc_attr($post_id) . '">';
            echo '</div>';
            echo '<div class="bt-quote-exc-choice" data-bt-exc-choice>';
            echo '<button type="button" class="bt-quote-exc-choice__btn bt-quote-exc-choice__btn--selected" data-exc-choice="current" aria-selected="true">' . esc_html__('Cette excursion', 'blacktenderscore') . '</button>';
            echo '<button type="button" class="bt-quote-exc-choice__btn" data-exc-choice="custom" aria-selected="false">' . esc_html__('Expérience sur mesure', 'blacktenderscore') . '</button>';
            echo '</div>';
            // "Expérience sur mesure" → liste des excursions sans le post courant
            echo '<div class="bt-quote-exc-custom" style="display:none">';
            $this->render_excursion_cards(['step_exc_loop_tpl' => ''], $post_id);
            echo '</div>';
        } else {
            $this->render_excursion_cards(['step_exc_loop_tpl' => '']);
        }

        echo '</div>';
        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>';

        // Step 2 -- Bateau
        $step++;
        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="boat">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Choix du bateau', 'blacktenderscore') . '</span>';
        echo '<span class="bt-quote-step__summary"></span>';
        echo '</div>';
        echo '<div class="bt-quote-step__content">';

        if ($is_boat) {
            echo '<div class="bt-quote-boat-auto" data-boat-id="' . esc_attr($post_id) . '">';
            echo '<p class="bt-quote-boat-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
            echo '<input type="hidden" name="boat_id" value="' . esc_attr($post_id) . '">';
            echo '</div>';
        } elseif ($is_excursion) {
            $this->render_linked_boat_cards($qs, $post_id);
        } else {
            echo '<div class="bt-quote-boat-cards" data-bt-quote-boats></div>';
        }

        echo '</div>';
        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>';

        // Step 3 -- Dates
        $step++;
        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="dates">';
        echo '<div class="bt-quote-step__header"><span class="bt-quote-step__number">' . $step . '</span><span class="bt-quote-step__title">' . esc_html__('Dates', 'blacktenderscore') . '</span><span class="bt-quote-step__summary"></span></div>';
        echo '<div class="bt-quote-step__content">';
        echo '<div class="bt-quote-duration-cards" data-bt-duration-select>';
        echo '<div class="bt-quote-duration-card" data-duration="half" tabindex="0" role="option" aria-selected="false"><span class="bt-quote-duration-card__label">' . esc_html__('Demi-journée', 'blacktenderscore') . '</span></div>';
        echo '<div class="bt-quote-duration-card" data-duration="full" tabindex="0" role="option" aria-selected="false"><span class="bt-quote-duration-card__label">' . esc_html__('Journée entière', 'blacktenderscore') . '</span></div>';
        echo '<div class="bt-quote-duration-card" data-duration="multi" tabindex="0" role="option" aria-selected="false"><span class="bt-quote-duration-card__label">' . esc_html__('Plusieurs jours', 'blacktenderscore') . '</span></div>';
        echo '<div class="bt-quote-duration-card" data-duration="custom" tabindex="0" role="option" aria-selected="false"><span class="bt-quote-duration-card__label">' . esc_html__('Demande spécifique', 'blacktenderscore') . '</span></div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--single" data-bt-datepicker data-range="0" style="display:none"><input type="hidden" name="date_start"><div class="bt-quote-datepicker__calendar"></div>';
        echo '<div class="bt-quote-timeslot" data-bt-timeslot style="display:none"><div class="bt-quote-timeslot__options"><button type="button" class="bt-quote-timeslot__btn" data-timeslot="matin" aria-selected="false">' . esc_html__('Matin', 'blacktenderscore') . '</button><button type="button" class="bt-quote-timeslot__btn" data-timeslot="apres-midi" aria-selected="false">' . esc_html__('Après-midi', 'blacktenderscore') . '</button></div><input type="hidden" name="timeslot" value=""></div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--range" data-bt-datepicker data-range="1" style="display:none"><input type="hidden" name="date_start"><input type="hidden" name="date_end"><div class="bt-quote-datepicker__calendar"></div></div>';
        echo '<div class="bt-quote-custom-dates" style="display:none"><textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="date_custom" placeholder="' . esc_attr__('Décrivez vos disponibilités...', 'blacktenderscore') . '" rows="3"></textarea></div>';
        echo '<input type="hidden" name="duration_type" value="">';
        echo '</div>';
        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>';

        // Step 4 -- Coordonnees
        $step++;
        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="contact">';
        echo '<div class="bt-quote-step__header"><span class="bt-quote-step__number">' . $step . '</span><span class="bt-quote-step__title">' . esc_html__('Vos coordonnées', 'blacktenderscore') . '</span><span class="bt-quote-step__summary"></span></div>';
        echo '<div class="bt-quote-step__content"><div class="bt-quote-fields">';
        echo '<div class="bt-quote-fields__row"><div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('Prénom', 'blacktenderscore') . '</label><input type="text" class="bt-quote-fields__input" name="client_firstname" placeholder="' . esc_attr__('Votre prénom', 'blacktenderscore') . '" required></div>';
        echo '<div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('Nom', 'blacktenderscore') . '</label><input type="text" class="bt-quote-fields__input" name="client_name" placeholder="' . esc_attr__('Votre nom', 'blacktenderscore') . '" required></div></div>';
        echo '<div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('E-mail', 'blacktenderscore') . '</label><input type="email" class="bt-quote-fields__input" name="client_email" placeholder="votre@email.com" required></div>';
        echo '<div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('Téléphone', 'blacktenderscore') . '</label><input type="tel" class="bt-quote-fields__input" name="client_phone" placeholder="06 12 34 56 78"></div>';
        echo '</div></div>';
        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>';

        // Step 5 -- Envoi
        $step++;
        echo '<div class="bt-quote-step" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="submit">';
        echo '<div class="bt-quote-step__header"><span class="bt-quote-step__number">' . $step . '</span><span class="bt-quote-step__title">' . esc_html__('Confirmation', 'blacktenderscore') . '</span><span class="bt-quote-step__summary"></span></div>';
        echo '<div class="bt-quote-step__content">';
        echo '<div class="bt-quote-recap" data-bt-quote-recap></div>';
        echo '<button type="button" class="bt-quote-submit" data-bt-quote-submit>' . esc_html__('Envoyer ma demande', 'blacktenderscore') . '</button>';
        echo '<div class="bt-quote-message" data-bt-quote-message></div>';
        echo '</div></div>';

        echo '</div>'; // .bt-quote

        // Dialog popup
        echo '<dialog class="bt-quote-popup" data-bt-quote-popup role="dialog" aria-modal="true">';
        echo '<button type="button" class="bt-quote-popup__close" aria-label="' . esc_attr__('Fermer', 'blacktenderscore') . '">&times;</button>';
        echo '<div class="bt-quote-popup__content" data-bt-quote-popup-content></div>';
        echo '</dialog>';
    }

    // ── Quote form card helpers ─────────────────────────────────────────────

    /** Flag pour enqueue le CSS du template Loop une seule fois par tpl_id. */
    protected static array $shared_loop_css_enqueued = [];

    /**
     * Rendu des cards excursion pour le formulaire de devis.
     * Utilise un template Loop Elementor si configuré.
     */
    /**
     * @param int $exclude_id  ID à exclure de la liste (ex: post courant en mode "sur mesure").
     */
    protected function render_excursion_cards(array $s, int $exclude_id = 0): void {
        $cache_key  = 'bt_exc_list_50';
        $excursions = get_transient($cache_key);
        if ($excursions === false) {
            $excursions = get_posts([
                'post_type'      => 'excursion',
                'posts_per_page' => 50,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);
            set_transient($cache_key, $excursions, 12 * HOUR_IN_SECONDS);
        }

        if (empty($excursions)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucune excursion disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        $tpl_id = (int) ($s['step_exc_loop_tpl'] ?? 0);

        echo '<div class="bt-quote-exc-cards">';

        foreach ($excursions as $exc) {
            if ($exclude_id && $exc->ID === $exclude_id) continue;
            echo '<div class="bt-quote-exc-card" data-exc-id="' . esc_attr($exc->ID) . '" tabindex="0" role="option" aria-selected="false">';
            if ($tpl_id) {
                echo $this->render_shared_loop_item($tpl_id, $exc);
            } else {
                $this->render_default_exc_card($exc->ID, $exc, $s);
            }
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Card excursion par défaut : image + titre + prix indicatif.
     * Pax/capacité intentionnellement omis — les bateaux portent cette info.
     * Discount non affiché — le devis est une demande, pas une promo.
     */
    protected function render_default_exc_card(int $eid, \WP_Post $exc, array $s = []): void {
        $thumb     = get_the_post_thumbnail_url($eid, 'medium');
        $departure = get_field('exp_departure_zone', $eid) ?: '';

        // Prix min depuis le repeater tarification_par_forfait
        $repeater  = get_field('tarification_par_forfait', $eid) ?: [];
        $min_price = 0;
        $min_time  = '';
        foreach ($repeater as $row) {
            $p = (float) ($row['exp_price'] ?? 0);
            if ($p > 0 && ($min_price === 0 || $p < $min_price)) {
                $min_price = $p;
                $min_time  = $row['exp_time'] ?? '';
            }
        }
        if (!$min_price) {
            $min_price = (float) get_field('exp_price_per_person', $eid);
        }

        $show_price = ($s['qt_exc_card_show_price'] ?? 'yes') !== 'no';

        // Image
        if ($thumb) {
            echo '<div class="bt-quote-exc-card__img">'
               . '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($exc->post_title) . '" loading="lazy">'
               . '</div>';
        }

        echo '<div class="bt-quote-exc-card__body">';

        // Titre complet
        echo '<h3 class="bt-quote-exc-card__title">' . esc_html($exc->post_title) . '</h3>';

        // Prix indicatif (affiché plus petit via CSS)
        if ($show_price && $min_price) {
            echo '<p class="bt-quote-exc-card__price">';
            echo '<span class="bt-quote-exc-card__price-amount">'
               . esc_html(sprintf(__('À partir de %d €', 'blacktenderscore'), (int) $min_price))
               . '</span>';
            if ($min_time) {
                echo ' <span class="bt-quote-exc-card__price-suffix">' . esc_html($min_time) . '</span>';
            }
            echo '</p>';
        }

        // Zone de départ
        if ($departure) {
            echo '<span class="bt-quote-exc-card__departure">' . esc_html($departure) . '</span>';
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

            $card_attrs = 'data-boat-id="' . esc_attr($bid) . '"';
            // Pricing data for JS dynamic calculation
            $card_price_half = (float) get_field('boat_price_half', $bid);
            $card_price_full = (float) get_field('boat_price_full', $bid);
            $card_pax_max    = (int) get_field('boat_pax_max', $bid);
            // Min price from repeater
            $card_repeater = get_field('boat_price', $bid) ?: [];
            $card_min_rep  = 0;
            foreach ($card_repeater as $cr) {
                $cp = (float) ($cr['boat_price_boat'] ?? 0);
                if ($cp > 0 && ($card_min_rep === 0 || $cp < $card_min_rep)) $card_min_rep = $cp;
            }
            if ($card_price_half) $card_attrs .= ' data-price-half="' . esc_attr($card_price_half) . '"';
            if ($card_price_full) $card_attrs .= ' data-price-full="' . esc_attr($card_price_full) . '"';
            if ($card_min_rep)    $card_attrs .= ' data-price-min="' . esc_attr($card_min_rep) . '"';
            if ($card_pax_max)    $card_attrs .= ' data-pax-max="' . esc_attr($card_pax_max) . '"';

            echo '<div class="bt-quote-boat-card bt-forfait-card bt-forfait-card--has-image" ' . $card_attrs . '>';
            if ($tpl_id) {
                echo $this->render_shared_loop_item($tpl_id, $boat);
            } else {
                $this->render_default_boat_card($bid, $boat, $s);
            }
            echo '</div>';
        }
        echo '</div>';
    }

    /**
     * Card bateau par défaut : image + titre + prix + tags taxonomie + meta (pax · année).
     * Prix calculé depuis le repeater boat_price (min), fallback sur boat_price_half/full.
     *
     * @param int      $bid  ID du post bateau
     * @param \WP_Post $boat Objet post bateau
     * @param array    $s    Settings Elementor (boat_tags, show_boat_more_btn, boat_popup_tpl, …)
     */
    /**
     * Card bateau par defaut : image + titre + subtitle + prix/pers + meta.
     * Utilise les classes unifiees .bt-forfait-card (le wrapper ajoute --has-image).
     */
    protected function render_default_boat_card(int $bid, \WP_Post $boat, array $s = []): void {
        $thumb      = get_the_post_thumbnail_url($bid, 'medium');
        $pax        = (int) get_field('boat_pax_max', $bid);
        $boat_year  = (int) get_field('boat_year', $bid);

        // Modele du bateau (taxo boat-model)
        $model_terms = get_the_terms($bid, 'boat-model');
        $model_name  = (!empty($model_terms) && !is_wp_error($model_terms))
            ? $model_terms[0]->name : '';
        $subtitle = trim($model_name . ($boat_year ? ' · ' . $boat_year : ''));

        // Prix : premier row du repeater boat_price, fallback half/full
        $repeater   = get_field('boat_price', $bid) ?: [];
        $first_price = 0;
        foreach ($repeater as $row) {
            $p = (float) ($row['boat_price_boat'] ?? 0);
            if ($p > 0) { $first_price = $p; break; }
        }
        if (!$first_price) {
            $first_price = (float) (get_field('boat_price_half', $bid) ?: get_field('boat_price_full', $bid));
        }

        $pp_price = ($first_price && $pax > 0) ? (int) ceil($first_price / $pax) : 0;

        // Image
        if ($thumb) {
            echo '<div class="bt-forfait-card__image bt-quote-boat-card__img">';
            echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($boat->post_title) . '" loading="lazy">';
            echo '</div>';
        }

        echo '<div class="bt-forfait-card__body bt-quote-boat-card__body">';

        // Titre
        echo '<p class="bt-forfait-card__title bt-quote-boat-card__title">' . esc_html($boat->post_title) . '</p>';

        // Subtitle (modele · annee)
        if ($subtitle) {
            echo '<p class="bt-forfait-card__subtitle">' . esc_html($subtitle) . '</p>';
        }

        // Prix par personne
        if ($pp_price) {
            echo '<div class="bt-forfait-card__pricing bt-quote-boat-card__price">';
            echo '<span class="bt-forfait-card__price bt-quote-boat-card__price-amount">' . esc_html($pp_price) . '</span>';
            echo '<span class="bt-forfait-card__currency">€</span>';
            echo '<span class="bt-forfait-card__per">' . esc_html__('/ pers.', 'blacktenderscore') . '</span>';
            echo '</div>';
        }

        // Tags taxonomie
        $this->render_boat_tag_pills($bid, $s['boat_tags'] ?? []);

        // Meta : pax · duree
        echo '<div class="bt-forfait-card__meta bt-quote-boat-card__meta">';
        if ($pax > 0) {
            echo '<span class="bt-forfait-card__meta-item">'
               . '<svg class="bt-forfait-card__icon" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
               . esc_html(sprintf(__('%d pers.', 'blacktenderscore'), $pax))
               . '</span>';
        }
        echo '</div>';

        echo '</div>'; // .bt-forfait-card__body

        // Bouton "Plus d'infos"
        if (($s['show_boat_more_btn'] ?? '') === 'yes') {
            $popup_tpl = (int) ($s['boat_popup_tpl'] ?? 0);
            echo '<button type="button" class="bt-quote-boat-card__more"'
               . ' data-boat-id="' . esc_attr($bid) . '"'
               . ($popup_tpl ? ' data-popup-tpl="' . esc_attr($popup_tpl) . '"' : '')
               . '>'
               . esc_html__('Plus d\'infos', 'blacktenderscore')
               . '</button>';
        }
    }

    /**
     * Rendu des pills de taxonomie pour une card bateau.
     *
     * @param int   $bid          ID du post bateau
     * @param array $tags_repeater Repeater Elementor (boat_tags) : [{tag_taxonomy, tag_terms[]}, …]
     */
    protected function render_boat_tag_pills(int $bid, array $tags_repeater): void {
        if (empty($tags_repeater)) return;

        $pills = [];
        foreach ($tags_repeater as $row) {
            $taxonomy     = $row['tag_taxonomy'] ?? '';
            $allowed_slugs = is_array($row['tag_terms'] ?? null)
                ? $row['tag_terms']
                : array_filter(explode(',', (string) ($row['tag_terms'] ?? '')));

            if (!$taxonomy || empty($allowed_slugs)) continue;

            $terms = get_the_terms($bid, $taxonomy);
            if (empty($terms) || is_wp_error($terms)) continue;

            foreach ($terms as $term) {
                if (in_array($term->slug, $allowed_slugs, true)) {
                    $pills[] = $term->name;
                }
            }
        }

        if (empty($pills)) return;

        echo '<div class="bt-quote-boat-card__tags">';
        foreach ($pills as $pill) {
            echo '<span class="bt-quote-boat-card__tag">' . esc_html($pill) . '</span>';
        }
        echo '</div>';
    }

    /**
     * Retourne toutes les options de termes des taxonomies bateau (slug => "Taxo : Nom").
     * Utilisé pour peupler le SELECT2 du repeater boat_tags.
     *
     * @return array<string,string>
     */
    protected static function get_all_boat_term_options(): array {
        $taxonomies = [
            'boat_equipment'  => __('Équipement', 'blacktenderscore'),
            'type-de-bateau'  => __('Type', 'blacktenderscore'),
            'boat_fuel'       => __('Carburant', 'blacktenderscore'),
            'boat_skipper'    => __('Skipper', 'blacktenderscore'),
        ];

        $options = [];
        foreach ($taxonomies as $tax_slug => $tax_label) {
            $terms = get_terms(['taxonomy' => $tax_slug, 'hide_empty' => false]);
            if (empty($terms) || is_wp_error($terms)) continue;
            foreach ($terms as $term) {
                $options[$term->slug] = $tax_label . ' : ' . $term->name;
            }
        }

        return $options;
    }

    // ── Loop template helper ────────────────────────────────────────────────

    /**
     * Rendu d'un item via un template Elementor Loop.
     * Enqueue le CSS du template + définit le contexte post.
     */
    protected function render_shared_loop_item(int $tpl_id, \WP_Post $item): string {
        if ($tpl_id && class_exists('\Elementor\Plugin')) {
            // Enqueue le CSS du template (une fois par tpl_id)
            if (!isset(self::$shared_loop_css_enqueued[$tpl_id])) {
                self::$shared_loop_css_enqueued[$tpl_id] = true;
                $css_file = \Elementor\Core\Files\CSS\Post::create($tpl_id);
                $css_file->enqueue();
            }

            // Cache le rendu Elementor (coûteux) — clé : template + post + statut
            $cache_key = 'bt_tpl_' . $tpl_id . '_' . $item->ID . '_' . $item->post_modified;
            $html = wp_cache_get($cache_key, 'bt_loop_items');

            if ($html === false) {
                global $post;
                $original_post = $post;
                $post = $item;
                setup_postdata($post);

                $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($tpl_id);

                $post = $original_post;
                if ($original_post) wp_reset_postdata();

                wp_cache_set($cache_key, $html ?: '', 'bt_loop_items', HOUR_IN_SECONDS);
            }

            return $html ?: '<p>' . esc_html($item->post_title) . '</p>';
        }

        // Fallback sans template
        return '<div class="bt-quote-loop-fallback">'
             . '<strong>' . esc_html($item->post_title) . '</strong>'
             . '</div>';
    }
}
