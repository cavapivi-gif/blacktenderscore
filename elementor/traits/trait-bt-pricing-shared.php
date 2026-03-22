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

        // SVG check réutilisable dans les headers d'étapes
        $check_svg = '<svg class="bt-quote-step__check" viewBox="0 0 24 24" fill="none" stroke="currentColor"'
                   . ' stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
                   . '<path d="M20 6L9 17l-5-5"/></svg>';

        $step = 0;

        // Step 1 -- Destination
        $step++;
        echo '<div class="bt-quote-step bt-quote-step--active" role="listitem" aria-expanded="true" aria-current="step" data-step="' . $step . '" data-step-type="excursion">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Votre excursion', 'blacktenderscore') . '</span>';
        echo $check_svg;
        echo '</div>';
        echo '<div class="bt-quote-step__summary"></div>';
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

        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>'; // .bt-quote-step__content
        echo '</div>'; // step 1

        // Step 2 -- Bateau
        $step++;
        echo '<div class="bt-quote-step bt-quote-step--collapsed" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="boat">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Choix du bateau', 'blacktenderscore') . '</span>';
        echo $check_svg;
        echo '</div>';
        echo '<div class="bt-quote-step__summary"></div>';
        echo '<div class="bt-quote-step__content">';

        if ($is_boat) {
            // Div caché pour init JS (state.boat_id / state.boat_name)
            echo '<div class="bt-quote-boat-auto" data-boat-id="' . esc_attr($post_id) . '" style="display:none">';
            echo '<span class="bt-quote-boat-auto__name">' . esc_html(get_the_title($post_id)) . '</span>';
            echo '</div>';
            $this->render_all_boat_cards($qs, $post_id);
        } elseif ($is_excursion) {
            $this->render_linked_boat_cards($qs, $post_id);
        } else {
            echo '<div class="bt-quote-boat-cards" data-bt-quote-boats></div>';
        }

        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>'; // .bt-quote-step__content
        echo '</div>'; // step 2

        // Step 3 -- Options bateau via BtQuoteSubSteps (seulement en mode boat)
        if ($is_boat) {
            $step++;

            // Construire le JSON de config pour le composant JS
            $taxo_defs = [
                'boat_comfort'        => ['label' => __('Pour votre confort ?', 'blacktenderscore'),   'multi' => true],
                'activite'            => ['label' => __('Vos activités ?', 'blacktenderscore'),        'multi' => true],
                'boat_board_services' => ['label' => __('Services à bord ?', 'blacktenderscore'),     'multi' => true],
                'boat_onboard_food'   => ['label' => __('Restauration à bord ?', 'blacktenderscore'), 'multi' => true],
            ];

            $substep_config = [];
            foreach ($taxo_defs as $slug => $def) {
                $terms = get_terms(['taxonomy' => $slug, 'hide_empty' => false]);
                if (is_wp_error($terms) || empty($terms)) continue;
                $substep_config[] = [
                    'key'   => $slug,
                    'label' => $def['label'],
                    'multi' => $def['multi'],
                    'items' => array_map(fn($t) => ['id' => $t->term_id, 'name' => $t->name], $terms),
                ];
            }

            echo '<div class="bt-quote-step bt-quote-step--collapsed" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="boat-options">';
            echo '<div class="bt-quote-step__header">';
            echo '<span class="bt-quote-step__number">' . $step . '</span>';
            echo '<span class="bt-quote-step__title">' . esc_html__('Options', 'blacktenderscore') . '</span>';
            echo $check_svg;
            echo '</div>';
            echo '<div class="bt-quote-step__summary"></div>';
            echo '<div class="bt-quote-step__content">';

            // Container vide — BtQuoteSubSteps le remplit côté JS
            echo '<div data-bt-substep data-config="' . esc_attr(wp_json_encode($substep_config)) . '"></div>';

            // Bouton wizard "Suivant" — caché jusqu'à ce que le récap substep soit affiché
            echo '<div class="bt-quote-step__actions" data-substep-wizard-next style="display:none">';
            echo '<button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button>';
            echo '</div>';

            echo '</div>'; // .bt-quote-step__content
            echo '</div>'; // step boat-options
        }

        // Step 4 (ou 3 en mode excursion) -- Dates
        $step++;
        echo '<div class="bt-quote-step bt-quote-step--collapsed" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="dates">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Dates', 'blacktenderscore') . '</span>';
        echo $check_svg;
        echo '</div>';
        echo '<div class="bt-quote-step__summary"></div>';
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
        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>'; // .bt-quote-step__content
        echo '</div>'; // step 3

        // Step 4 -- Coordonnees
        $step++;
        echo '<div class="bt-quote-step bt-quote-step--collapsed" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="contact">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Vos coordonnées', 'blacktenderscore') . '</span>';
        echo $check_svg;
        echo '</div>';
        echo '<div class="bt-quote-step__summary"></div>';
        echo '<div class="bt-quote-step__content"><div class="bt-quote-fields">';
        echo '<div class="bt-quote-fields__row"><div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('Prénom', 'blacktenderscore') . '</label><input type="text" class="bt-quote-fields__input" name="client_firstname" placeholder="' . esc_attr__('Votre prénom', 'blacktenderscore') . '" required></div>';
        echo '<div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('Nom', 'blacktenderscore') . '</label><input type="text" class="bt-quote-fields__input" name="client_name" placeholder="' . esc_attr__('Votre nom', 'blacktenderscore') . '" required></div></div>';
        echo '<div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('E-mail', 'blacktenderscore') . '</label><input type="email" class="bt-quote-fields__input" name="client_email" placeholder="votre@email.com" required></div>';
        echo '<div class="bt-quote-fields__group"><label class="bt-quote-fields__label">' . esc_html__('Téléphone', 'blacktenderscore') . '</label><input type="tel" class="bt-quote-fields__input" name="client_phone" placeholder="06 12 34 56 78"></div>';
        echo '</div>';
        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>'; // .bt-quote-step__content
        echo '</div>'; // step 4

        // Step 5 -- Envoi
        $step++;
        echo '<div class="bt-quote-step bt-quote-step--collapsed" role="listitem" aria-expanded="false" data-step="' . $step . '" data-step-type="submit">';
        echo '<div class="bt-quote-step__header">';
        echo '<span class="bt-quote-step__number">' . $step . '</span>';
        echo '<span class="bt-quote-step__title">' . esc_html__('Confirmation', 'blacktenderscore') . '</span>';
        echo $check_svg;
        echo '</div>';
        echo '<div class="bt-quote-step__summary"></div>';
        echo '<div class="bt-quote-step__content">';
        echo '<div class="bt-quote-recap" data-bt-quote-recap></div>';
        echo '<button type="button" class="bt-quote-submit" data-bt-quote-submit>' . esc_html__('Envoyer ma demande', 'blacktenderscore') . '</button>';
        echo '<div class="bt-quote-message" data-bt-quote-message></div>';
        echo '</div></div>'; // .bt-quote-step__content + step 5

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

        // Image (lazy — activée par JS au reveal)
        if ($thumb) {
            echo '<div class="bt-quote-exc-card__img">'
               . '<img data-lazy-src="' . esc_url($thumb) . '" alt="' . esc_attr($exc->post_title) . '" src="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 1 1\'/%3E" loading="lazy">'
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

        // Skipper (taxo boat_skipper)
        if (($s['qt_exc_card_show_skipper'] ?? 'yes') !== 'no') {
            $skipper_terms = get_the_terms($eid, 'boat_skipper');
            if (!empty($skipper_terms) && !is_wp_error($skipper_terms)) {
                $names = array_map(fn($t) => $t->name, $skipper_terms);
                echo '<span class="bt-quote-exc-card__skipper">' . esc_html(implode(', ', $names)) . '</span>';
            }
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
            $card_price_half = (float) get_field('boat_price_half', $bid);
            $card_price_full = (float) get_field('boat_price_full', $bid);
            $card_pax_max    = (int) get_field('boat_pax_max', $bid);
            $card_repeater   = get_field('boat_price', $bid) ?: [];
            $card_min_rep    = 0;
            foreach ($card_repeater as $cr) {
                $cp = (float) ($cr['boat_price_boat'] ?? 0);
                if ($cp > 0 && ($card_min_rep === 0 || $cp < $card_min_rep)) $card_min_rep = $cp;
            }
            if ($card_price_half) $card_attrs .= ' data-price-half="' . esc_attr($card_price_half) . '"';
            if ($card_price_full) $card_attrs .= ' data-price-full="' . esc_attr($card_price_full) . '"';
            if ($card_min_rep)    $card_attrs .= ' data-price-min="'  . esc_attr($card_min_rep)    . '"';
            if ($card_pax_max)    $card_attrs .= ' data-pax-max="'    . esc_attr($card_pax_max)    . '"';

            echo '<div class="bt-quote-boat-card" '
               . $card_attrs . ' tabindex="0" role="option" aria-pressed="false">';
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
     * Toutes les cards bateau publiées — utilisé en mode devis boat.
     *
     * Structure HTML identique à trait-bt-boat-pricing.php : __image + __body.
     * Grille 2 colonnes (.bt-forfaits__grid), image en haut, corps en dessous.
     * Le bateau courant ($current_id) est pré-sélectionné (--active).
     */
    protected function render_all_boat_cards(array $s, int $current_id = 0): void {
        $boats = get_posts([
            'post_type'      => 'boat',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        if (empty($boats)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucun bateau disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        // Bateau courant en premier
        if ($current_id) {
            usort($boats, fn($a, $b) => ($a->ID === $current_id ? -1 : ($b->ID === $current_id ? 1 : 0)));
        }

        echo '<div class="bt-quote-boat-cards">';

        foreach ($boats as $boat) {
            $bid        = $boat->ID;
            $is_current = ($bid === $current_id);

            $pax_max  = (int) get_field('boat_pax_max', $bid);
            $repeater = get_field('boat_price', $bid) ?: [];

            // Construire les forfaits (label durée + prix)
            $forfaits = [];
            $min_rep  = 0;
            foreach ($repeater as $row) {
                $p = (float) ($row['boat_price_boat'] ?? 0);
                if (!$p) continue;
                $dur_id    = (int) ($row['boat_location_duration'] ?? 0);
                $dur_label = '';
                if ($dur_id) {
                    $dur_term = get_term($dur_id);
                    if ($dur_term && !is_wp_error($dur_term)) $dur_label = $dur_term->name;
                }
                $forfaits[] = ['price' => $p, 'label' => $dur_label];
                if (!$min_rep || $p < $min_rep) $min_rep = $p;
            }
            // Fallback si repeater vide
            if (empty($forfaits)) {
                $ph = (float) get_field('boat_price_half', $bid);
                $pf = (float) get_field('boat_price_full', $bid);
                if ($ph) $forfaits[] = ['price' => $ph, 'label' => __('Demi-journée', 'blacktenderscore')];
                if ($pf) $forfaits[] = ['price' => $pf, 'label' => __('Journée complète', 'blacktenderscore')];
                $min_rep = $ph ?: $pf;
            }

            $first_price = $forfaits[0]['price'] ?? 0;
            $pp_price    = ($first_price && $pax_max > 0) ? (int) ceil($first_price / $pax_max) : 0;

            // Subtitle : modèle · année
            $boat_year   = (int) get_field('boat_year', $bid);
            $model_name  = '';
            $model_terms = get_the_terms($bid, 'boat-model');
            if (!empty($model_terms) && !is_wp_error($model_terms)) {
                $model_name = $model_terms[0]->name;
            } elseif ($raw_model = get_field('boat_model_name', $bid)) {
                $model_id = is_array($raw_model) ? (int) reset($raw_model) : (int) $raw_model;
                $term     = $model_id ? get_term($model_id) : null;
                if ($term && !is_wp_error($term)) $model_name = $term->name;
            }
            $subtitle = trim($model_name . ($boat_year ? ' · ' . $boat_year : ''));

            $has_jockey = (bool) get_field('boat_has_jockey_sits', $bid);
            $thumb      = get_the_post_thumbnail_url($bid, 'medium');

            $card_cls  = 'bt-quote-boat-card';
            $card_cls .= $is_current ? ' bt-quote-boat-card--selected bt-forfait-card--active' : '';

            $data = 'data-boat-id="' . esc_attr($bid) . '"';
            if ($min_rep)  $data .= ' data-price-min="' . esc_attr($min_rep) . '"';
            if ($pax_max)  $data .= ' data-pax-max="'   . esc_attr($pax_max) . '"';

            $bg_attr = $thumb ? ' data-lazy-bg="' . esc_url($thumb) . '"' : '';

            echo '<div class="' . $card_cls . '" ' . $data
               . ' tabindex="0" role="option"'
               . ' aria-pressed="' . ($is_current ? 'true' : 'false') . '">';

            echo '<div class="bt-quote-boat-card__bg"' . $bg_attr . '></div>';

            echo '<div class="bt-quote-boat-card__right">';
            echo '<div class="bt-quote-boat-card__body">';

            echo '<p class="bt-quote-boat-card__title">' . esc_html($boat->post_title) . '</p>';
            if ($subtitle) {
                echo '<p class="bt-quote-boat-card__subtitle">' . esc_html($subtitle) . '</p>';
            }

            // Pills forfaits (label durée + prix)
            if (!empty($forfaits)) {
                echo '<div class="bt-quote-boat-card__forfaits">';
                foreach ($forfaits as $i => $f) {
                    $active_cls = ($i === 0) ? ' bt-quote-boat-card__forfait--active' : '';
                    echo '<button type="button"'
                       . ' class="bt-quote-boat-card__forfait' . $active_cls . '"'
                       . ' data-price="' . esc_attr($f['price']) . '"'
                       . ' data-pax="'  . esc_attr($pax_max)    . '"'
                       . ' tabindex="-1">';
                    if ($f['label']) {
                        echo '<span class="bt-quote-boat-card__forfait-label">' . esc_html($f['label']) . '</span>';
                    }
                    echo '<span class="bt-quote-boat-card__forfait-price">'
                       . esc_html(number_format($f['price'], 0, ',', ' ')) . ' €</span>';
                    echo '</button>';
                }
                echo '</div>'; // .bt-quote-boat-card__forfaits

                // Prix / pers. du forfait actif (mis à jour par JS)
                if ($pp_price) {
                    echo '<p class="bt-quote-boat-card__pp">' . esc_html($pp_price) . ' €'
                       . ' <span class="bt-quote-boat-card__per">/ pers.</span></p>';
                }
            }

            // Meta
            echo '<div class="bt-quote-boat-card__meta">';
            if ($pax_max) {
                echo '<span class="bt-quote-boat-card__meta-item">';
                echo '<svg class="bt-quote-boat-card__icon" viewBox="0 0 24 24">'
                   . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>'
                   . '<circle cx="9" cy="7" r="4"/>'
                   . '<path d="M23 21v-2a4 4 0 0 0-3-3.87"/>'
                   . '<path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
                   . '</svg>';
                echo esc_html($pax_max) . ' pers.';
                echo '</span>';
            }
            $jockey_label = $has_jockey
                ? __('Avec siège jockey', 'blacktenderscore')
                : __('Sans siège jockey', 'blacktenderscore');
            echo '<span class="bt-quote-boat-card__meta-item bt-quote-boat-card__meta-item--jockey'
               . ($has_jockey ? ' bt-quote-boat-card__meta-item--yes' : '') . '">'
               . esc_html($jockey_label) . '</span>';
            echo '</div>'; // .bt-quote-boat-card__meta

            echo '</div>'; // .bt-quote-boat-card__body

            // Bouton "Plus d'infos" — collé en bas de __right (optionnel)
            if (($s['show_boat_more_btn'] ?? '') === 'yes') {
                $popup_tpl = (int) ($s['boat_popup_tpl'] ?? 0);
                echo '<button type="button" class="bt-quote-boat-card__more"'
                   . ' data-boat-id="' . esc_attr($bid) . '"'
                   . ($popup_tpl ? ' data-popup-tpl="' . esc_attr($popup_tpl) . '"' : '')
                   . '>' . esc_html__('Plus d\'infos', 'blacktenderscore') . '</button>';
            }

            echo '</div>'; // .bt-quote-boat-card__right
            echo '</div>'; // .bt-quote-boat-card
        }

        echo '</div>'; // .bt-quote-boat-cards
    }

    /**
     * Card bateau par défaut pour les bateaux liés (mode excursion).
     * Image + titre + subtitle + prix total + pp + meta capacité.
     *
     * @param int      $bid  ID du post bateau
     * @param \WP_Post $boat Objet post bateau
     * @param array    $s    Settings Elementor
     */
    protected function render_default_boat_card(int $bid, \WP_Post $boat, array $s = []): void {
        $thumb     = get_the_post_thumbnail_url($bid, 'medium');
        $pax       = (int) get_field('boat_pax_max', $bid);
        $boat_year = (int) get_field('boat_year', $bid);

        // Subtitle : modèle · année
        $model_name  = '';
        $model_terms = get_the_terms($bid, 'boat-model');
        if (!empty($model_terms) && !is_wp_error($model_terms)) {
            $model_name = $model_terms[0]->name;
        } elseif ($raw_model = get_field('boat_model_name', $bid)) {
            $model_id = is_array($raw_model) ? (int) reset($raw_model) : (int) $raw_model;
            $term     = $model_id ? get_term($model_id) : null;
            if ($term && !is_wp_error($term)) $model_name = $term->name;
        }
        $subtitle = trim($model_name . ($boat_year ? ' · ' . $boat_year : ''));

        // Prix : premier row repeater, fallback half/full
        $repeater    = get_field('boat_price', $bid) ?: [];
        $first_price = 0;
        foreach ($repeater as $row) {
            $p = (float) ($row['boat_price_boat'] ?? 0);
            if ($p > 0) { $first_price = $p; break; }
        }
        if (!$first_price) {
            $first_price = (float) (get_field('boat_price_half', $bid) ?: get_field('boat_price_full', $bid));
        }
        $pp_price = ($first_price && $pax > 0) ? (int) ceil($first_price / $pax) : 0;

        $has_jockey = (bool) get_field('boat_has_jockey_sits', $bid);

        $bg_attr = $thumb ? ' data-lazy-bg="' . esc_url($thumb) . '"' : '';
        echo '<div class="bt-quote-boat-card__bg"' . $bg_attr . '></div>';

        echo '<div class="bt-quote-boat-card__right">';
        echo '<div class="bt-quote-boat-card__body">';

        echo '<p class="bt-quote-boat-card__title">' . esc_html($boat->post_title) . '</p>';

        if ($subtitle) {
            echo '<p class="bt-quote-boat-card__subtitle">' . esc_html($subtitle) . '</p>';
        }

        if ($first_price) {
            echo '<div class="bt-quote-boat-card__pricing">';
            echo '<span class="bt-quote-boat-card__price">' . esc_html(number_format($first_price, 0, ',', ' ')) . '</span>';
            echo '<span class="bt-quote-boat-card__currency">€</span>';
            echo '</div>';
            if ($pp_price) {
                echo '<p class="bt-quote-boat-card__pp">' . esc_html($pp_price) . ' €'
                   . ' <span class="bt-quote-boat-card__per">/ pers.</span></p>';
            }
        }

        echo '<div class="bt-quote-boat-card__meta">';
        if ($pax) {
            echo '<span class="bt-quote-boat-card__meta-item">';
            echo '<svg class="bt-quote-boat-card__icon" viewBox="0 0 24 24">'
               . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>'
               . '<circle cx="9" cy="7" r="4"/>'
               . '<path d="M23 21v-2a4 4 0 0 0-3-3.87"/>'
               . '<path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
               . '</svg>';
            echo esc_html($pax) . ' pers.';
            echo '</span>';
        }
        $jockey_label = $has_jockey
            ? __('Avec siège jockey', 'blacktenderscore')
            : __('Sans siège jockey', 'blacktenderscore');
        echo '<span class="bt-quote-boat-card__meta-item bt-quote-boat-card__meta-item--jockey'
           . ($has_jockey ? ' bt-quote-boat-card__meta-item--yes' : '') . '">'
           . esc_html($jockey_label) . '</span>';
        echo '</div>'; // .bt-quote-boat-card__meta

        echo '</div>'; // .bt-quote-boat-card__body

        if (($s['show_boat_more_btn'] ?? '') === 'yes') {
            $popup_tpl = (int) ($s['boat_popup_tpl'] ?? 0);
            echo '<button type="button" class="bt-quote-boat-card__more"'
               . ' data-boat-id="' . esc_attr($bid) . '"'
               . ($popup_tpl ? ' data-popup-tpl="' . esc_attr($popup_tpl) . '"' : '')
               . '>' . esc_html__('Plus d\'infos', 'blacktenderscore') . '</button>';
        }

        echo '</div>'; // .bt-quote-boat-card__right
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
