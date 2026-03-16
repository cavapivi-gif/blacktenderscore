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
     * Ouvre le wrapper : tablist [Forfaits | Devis] + panel 1.
     */
    protected function render_wrapper_open(array $s): void {
        $uid = 'bt-bpw-' . $this->get_id();

        echo '<div class="bt-bprice-wrapper" data-bt-tabs data-bt-panel-class="bt-bprice-wrapper__panel">';

        $tab1 = esc_html($s['quote_tab1_label'] ?: __('Forfaits', 'blacktenderscore'));
        $tab2 = esc_html($s['quote_tab2_label'] ?: __('Demande de devis', 'blacktenderscore'));

        echo '<div class="bt-bprice-wrapper__tablist" role="tablist">';
        echo '<button class="bt-bprice-wrapper__tab bt-bprice-wrapper__tab--active" id="' . esc_attr($uid) . '-tab-0" role="tab"'
           . ' aria-selected="true" aria-controls="' . esc_attr($uid) . '-panel-0" tabindex="0">' . $tab1 . '</button>';
        echo '<button class="bt-bprice-wrapper__tab" id="' . esc_attr($uid) . '-tab-1" role="tab"'
           . ' aria-selected="false" aria-controls="' . esc_attr($uid) . '-panel-1" tabindex="-1">' . $tab2 . '</button>';
        echo '</div>';

        // Panel 1 open
        echo '<div class="bt-bprice-wrapper__panel bt-bprice-wrapper__panel--active"'
           . ' id="' . esc_attr($uid) . '-panel-0" role="tabpanel"'
           . ' aria-labelledby="' . esc_attr($uid) . '-tab-0">';
    }

    /** Ferme le panel 1, ouvre le panel 2. */
    protected function render_wrapper_between(array $s): void {
        $uid = 'bt-bpw-' . $this->get_id();
        echo '</div>'; // close panel-0
        echo '<div class="bt-bprice-wrapper__panel"'
           . ' id="' . esc_attr($uid) . '-panel-1" role="tabpanel"'
           . ' aria-labelledby="' . esc_attr($uid) . '-tab-1">';
    }

    /** Ferme le panel 2 et le wrapper. */
    protected function render_wrapper_close(): void {
        echo '</div>'; // close panel-1
        echo '</div>'; // close .bt-bprice-wrapper
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
            echo '<div class="bt-quote-exc-custom" style="display:none"><textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="exc_custom_request" placeholder="' . esc_attr__('Décrivez votre projet...', 'blacktenderscore') . '" rows="3"></textarea></div>';
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
            $this->render_linked_boat_cards(['step_boat_loop_tpl' => ''], $post_id);
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
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--single" data-bt-datepicker data-range="0" style="display:none"><div class="bt-quote-datepicker__labels"><div class="bt-quote-datepicker__field"><label class="bt-quote-datepicker__label">' . esc_html__('Date souhaitée', 'blacktenderscore') . '</label><input type="text" class="bt-quote-datepicker__input" name="date_start" readonly placeholder="jj/mm/aaaa"></div></div><div class="bt-quote-datepicker__calendar"></div>';
        echo '<div class="bt-quote-timeslot" data-bt-timeslot style="display:none"><div class="bt-quote-timeslot__options"><button type="button" class="bt-quote-timeslot__btn" data-timeslot="matin" aria-selected="false">' . esc_html__('Matin', 'blacktenderscore') . '</button><button type="button" class="bt-quote-timeslot__btn" data-timeslot="apres-midi" aria-selected="false">' . esc_html__('Après-midi', 'blacktenderscore') . '</button></div><input type="hidden" name="timeslot" value=""></div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--range" data-bt-datepicker data-range="1" style="display:none"><div class="bt-quote-datepicker__labels"><div class="bt-quote-datepicker__field"><label class="bt-quote-datepicker__label">' . esc_html__('Date de début', 'blacktenderscore') . '</label><input type="text" class="bt-quote-datepicker__input" name="date_start" readonly placeholder="jj/mm/aaaa"></div><div class="bt-quote-datepicker__field"><label class="bt-quote-datepicker__label">' . esc_html__('Date de fin', 'blacktenderscore') . '</label><input type="text" class="bt-quote-datepicker__input" name="date_end" readonly placeholder="jj/mm/aaaa"></div></div><div class="bt-quote-datepicker__calendar"></div></div>';
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
            echo $this->render_shared_loop_item($tpl_id, $exc);
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
                echo $this->render_shared_loop_item($tpl_id, $boat);
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

        // "À partir de" = prix le plus bas (demi-journée) / pax max
        $base_price = $price_half ?: $price_full;
        $pp_price   = ($base_price && $pax > 0) ? ceil($base_price / $pax) : 0;
        $pp_label   = $price_half ? __('demi-journée', 'blacktenderscore') : __('journée complète', 'blacktenderscore');

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
    protected function render_shared_loop_item(int $tpl_id, \WP_Post $item): string {
        if ($tpl_id && class_exists('\Elementor\Plugin')) {
            // Enqueue le CSS du template (une fois par tpl_id)
            if (!isset(self::$shared_loop_css_enqueued[$tpl_id])) {
                self::$shared_loop_css_enqueued[$tpl_id] = true;
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
