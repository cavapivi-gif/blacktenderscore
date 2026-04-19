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
            'body_template'      => $s['body_template'] ?? 'cards',
            // Passer tous les settings pour le template dropdown
            'qt_custom_trip_label' => $s['qt_custom_trip_label'] ?? __('Trajet sur mesure', 'blacktenderscore'),
            'qt_custom_trip_desc'  => $s['qt_custom_trip_desc'] ?? __('Créez votre propre itinéraire', 'blacktenderscore'),
            'qt_custom_trip_img'   => $s['qt_custom_trip_img'] ?? [],
            'qt_show_custom_trip'  => $s['qt_show_custom_trip'] ?? 'yes',
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

        $body_template = $qs['body_template'] ?? 'cards';

        if ($body_template === 'dropdown') {
            // Mode dropdown : select avec thumbnails
            $this->render_excursion_dropdown($qs, $is_excursion ? $post_id : 0);
        } elseif ($is_excursion) {
            // Mode cards avec boutons "Cette excursion / Sur mesure"
            echo '<div class="bt-quote-exc-auto" data-exc-id="' . esc_attr($post_id) . '">';
            echo '<p class="bt-quote-exc-auto__name">' . esc_html(get_the_title($post_id)) . '</p>';
            echo '<input type="hidden" name="excursion_id" value="' . esc_attr($post_id) . '">';
            echo '</div>';
            echo '<div class="bt-quote-exc-choice" data-bt-exc-choice>';
            echo '<button type="button" class="bt-quote-exc-choice__btn bt-quote-exc-choice__btn--selected" data-exc-choice="current" aria-selected="true">' . esc_html__('Cette excursion', 'blacktenderscore') . '</button>';
            echo '<button type="button" class="bt-quote-exc-choice__btn" data-exc-choice="custom" aria-selected="false">' . esc_html__('Expérience sur mesure', 'blacktenderscore') . '</button>';
            echo '</div>';
            echo '<div class="bt-quote-exc-custom" style="display:none">';
            $this->render_excursion_cards($qs, $post_id);
            echo '</div>';
        } else {
            // Mode cards sans excursion courante
            $this->render_excursion_cards($qs);
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

        if ($body_template === 'dropdown') {
            // Mode dropdown
            if ($is_boat) {
                $this->render_boat_dropdown($qs, [], $post_id);
            } elseif ($is_excursion) {
                $this->render_boat_dropdown($qs, [], 0, $post_id);
            } else {
                echo '<div class="bt-quote-boat-dd" data-bt-boat-dropdown></div>';
            }
        } elseif ($is_boat) {
            // Mode cards — page bateau
            echo '<div class="bt-quote-boat-auto" data-boat-id="' . esc_attr($post_id) . '" style="display:none">';
            echo '<span class="bt-quote-boat-auto__name">' . esc_html(get_the_title($post_id)) . '</span>';
            echo '</div>';
            $this->render_all_boat_cards($qs, $post_id);
        } elseif ($is_excursion) {
            // Mode cards — page excursion
            $this->render_linked_boat_cards($qs, $post_id);
        } else {
            // Mode cards — générique
            echo '<div class="bt-quote-boat-cards" data-bt-quote-boats></div>';
        }

        echo '<div class="bt-quote-step__actions"><button type="button" class="bt-quote-step__next" data-step-next>' . esc_html__('Suivant', 'blacktenderscore') . '</button></div>';
        echo '</div>'; // .bt-quote-step__content
        echo '</div>'; // step 2

        // Step 3 -- Options bateau via BtQuoteSubSteps (boat ET excursion)
        if ($is_boat || $is_excursion) {
            $step++;

            // Construire le JSON de config pour le composant JS
            // Étapes : Activités puis Services (Confort et Restauration supprimés)
            $taxo_defs = [
                'activite'            => ['label' => __('Vos activités ?', 'blacktenderscore'),    'multi' => true],
                'boat_board_services' => ['label' => __('Services à bord ?', 'blacktenderscore'), 'multi' => true],
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
        echo '<div class="bt-quote-timeslot" data-bt-timeslot style="display:none"><p class="bt-quote-timeslot__title">' . esc_html__('Choisissez votre créneau', 'blacktenderscore') . '</p><div class="bt-quote-timeslot__options"><button type="button" class="bt-quote-timeslot__btn" data-timeslot="matin" aria-selected="false">' . esc_html__('Matin', 'blacktenderscore') . '</button><button type="button" class="bt-quote-timeslot__btn" data-timeslot="apres-midi" aria-selected="false">' . esc_html__('Après-midi', 'blacktenderscore') . '</button><button type="button" class="bt-quote-timeslot__btn" data-timeslot="soiree" aria-selected="false">' . esc_html__('Soirée', 'blacktenderscore') . '</button></div><input type="hidden" name="timeslot" value=""></div>';
        echo '<div class="bt-quote-date-summary" data-bt-date-summary style="display:none"></div>';
        echo '</div>';
        echo '<div class="bt-quote-datepicker bt-quote-datepicker--range" data-bt-datepicker data-range="1" style="display:none"><input type="hidden" name="date_start"><input type="hidden" name="date_end"><div class="bt-quote-datepicker__calendar"></div><div class="bt-quote-date-summary" data-bt-date-summary style="display:none"></div></div>';
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
        echo '<div class="bt-quote-fields__group bt-quote-fields__group--full"><label class="bt-quote-fields__label">' . esc_html__('Ajouter une note', 'blacktenderscore') . ' <span class="bt-quote-fields__optional">(' . esc_html__('optionnel', 'blacktenderscore') . ')</span></label><textarea class="bt-quote-fields__input bt-quote-fields__textarea" name="client_note" placeholder="' . esc_attr__('Informations complémentaires, demandes particulières...', 'blacktenderscore') . '" rows="3"></textarea></div>';
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
    /** Placeholder par défaut pour images manquantes. */
    protected const DEFAULT_IMG_PLACEHOLDER = 'https://dev.studiojae.fr/wp-content/uploads/2026/02/images.png';

    /**
     * Liste des cards excursions avec option "Trajet sur mesure" en premier.
     *
     * @param array $s          Widget settings.
     * @param int   $exclude_id ID à exclure de la liste (ex: post courant en mode "sur mesure").
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

        $tpl_id   = (int) ($s['step_exc_loop_tpl'] ?? 0);
        $template = $s['exc_card_template'] ?? 't1';
        $tpl_cls  = $tpl_id ? '' : ' bt-quote-exc-card--' . esc_attr($template);
        $show_custom_trip = ($s['qt_show_custom_trip'] ?? 'yes') !== 'no';

        echo '<div class="bt-quote-exc-cards">';

        // ── Option "Trajet sur mesure" en premier ──
        if ($show_custom_trip) {
            $custom_label = $s['qt_custom_trip_label'] ?? __('Trajet sur mesure', 'blacktenderscore');
            $custom_desc  = $s['qt_custom_trip_desc'] ?? __('Créez votre propre itinéraire', 'blacktenderscore');
            $custom_img   = $s['qt_custom_trip_img']['url'] ?? self::DEFAULT_IMG_PLACEHOLDER;

            echo '<div class="bt-quote-exc-card bt-quote-exc-card--custom' . $tpl_cls . '" data-exc-id="0" data-custom-trip="1" tabindex="0" role="option" aria-selected="false">';
            echo '<div class="bt-quote-exc-card__img">'
               . '<img src="' . esc_url($custom_img) . '" alt="' . esc_attr($custom_label) . '" loading="lazy" decoding="async">'
               . '</div>';
            echo '<div class="bt-quote-exc-card__body">';
            echo '<div class="bt-quote-exc-card__title">' . esc_html($custom_label) . '</div>';
            echo '<p class="bt-quote-exc-card__desc">' . esc_html($custom_desc) . '</p>';
            echo '</div>';
            echo '</div>';
        }

        // ── Liste des excursions ──
        foreach ($excursions as $exc) {
            if ($exclude_id && $exc->ID === $exclude_id) continue;
            echo '<div class="bt-quote-exc-card' . $tpl_cls . '" data-exc-id="' . esc_attr($exc->ID) . '" tabindex="0" role="option" aria-selected="false">';
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
     * Card excursion par défaut : image + titre + badge skipper.
     *
     * @param int      $eid ID du CPT excursion
     * @param \WP_Post $exc Objet post excursion
     * @param array    $s   Settings Elementor
     */
    protected function render_default_exc_card(int $eid, \WP_Post $exc, array $s = []): void {
        $image_url = get_the_post_thumbnail_url($eid, 'medium') ?: self::DEFAULT_IMG_PLACEHOLDER;
        $image_alt = $exc->post_title;

        // Skipper mode : hide | custom (auto désactivé car boat_skipper est pour les bateaux)
        $skipper_mode = $s['exc_skipper_mode'] ?? 'hide';
        $skipper_text = '';

        if ($skipper_mode === 'custom') {
            // Message personnalisé (affiché sur toutes les cards)
            $skipper_text = $s['exc_skipper_text'] ?? '';
        }
        // Note: Le mode 'auto' vérifiait boat_skipper mais cette taxo est pour les bateaux,
        // pas les excursions. Le skipper n'a pas de sens sur les cards excursion.

        // Image
        echo '<div class="bt-quote-exc-card__img">'
           . '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($image_alt) . '" loading="lazy" decoding="async">'
           . '</div>';

        // Body
        echo '<div class="bt-quote-exc-card__body">';
        echo '<div class="bt-quote-exc-card__title">' . esc_html($exc->post_title) . '</div>';

        if ($skipper_text !== '') {
            echo '<span class="bt-quote-exc-card__skipper">'
               . esc_html($skipper_text)
               . '</span>';
        }

        echo '</div>';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TEMPLATE DROPDOWN — Excursions & Bateaux
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Rendu dropdown pour les excursions.
     *
     * Ordre d'affichage:
     * 1. Excursion courante (si on est sur une page excursion)
     * 2. "Trajet sur mesure" (option personnalisée)
     * 3. Autres excursions (sans l'excursion courante)
     *
     * @param array $s          Widget settings
     * @param int   $current_id ID de l'excursion courante (0 si mode boat)
     */
    protected function render_excursion_dropdown(array $s, int $current_id = 0): void {
        $excursions = get_posts([
            'post_type'      => 'excursion',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $show_custom_trip = ($s['qt_show_custom_trip'] ?? 'yes') !== 'no';
        $custom_label     = $s['qt_custom_trip_label'] ?? __('Trajet sur mesure', 'blacktenderscore');
        $custom_desc      = $s['qt_custom_trip_desc'] ?? __('Créez votre propre itinéraire', 'blacktenderscore');
        $custom_img       = $s['qt_custom_trip_img']['url'] ?? self::DEFAULT_IMG_PLACEHOLDER;

        // Sélection initiale : l'excursion courante OU première de la liste
        $sel_id    = $current_id ?: ($excursions[0]->ID ?? 0);
        $sel_name  = $current_id ? get_the_title($current_id) : ($excursions[0]->post_title ?? '');
        $sel_thumb = $current_id
            ? (get_the_post_thumbnail_url($current_id, 'thumbnail') ?: self::DEFAULT_IMG_PLACEHOLDER)
            : (get_the_post_thumbnail_url($excursions[0]->ID ?? 0, 'thumbnail') ?: self::DEFAULT_IMG_PLACEHOLDER);
        $sel_sub   = $current_id ? (get_field('exp_tagline', $current_id) ?: '') : (get_field('exp_tagline', $excursions[0]->ID ?? 0) ?: '');

        $arrow_svg = '<svg class="bt-quote-dd__arrow" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>';
        $check_svg = '<svg class="bt-quote-dd__check" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>';

        echo '<div class="bt-quote-dd" data-bt-exc-dropdown data-selected-id="' . esc_attr($sel_id) . '">';
        echo '<button type="button" class="bt-quote-dd__trigger" aria-expanded="false">';
        echo '<img class="bt-quote-dd__thumb" src="' . esc_url($sel_thumb) . '" alt="">';
        echo '<div class="bt-quote-dd__info">';
        echo '<div class="bt-quote-dd__name">' . esc_html($sel_name) . '</div>';
        if ($sel_sub) {
            echo '<div class="bt-quote-dd__sub">' . esc_html($sel_sub) . '</div>';
        }
        echo '</div>';
        echo $arrow_svg;
        echo '</button>';

        echo '<div class="bt-quote-dd__menu">';

        // 1. Excursion courante en premier (si définie)
        if ($current_id) {
            $thumb   = get_the_post_thumbnail_url($current_id, 'thumbnail') ?: self::DEFAULT_IMG_PLACEHOLDER;
            $tagline = get_field('exp_tagline', $current_id) ?: '';

            echo '<div class="bt-quote-dd__opt bt-quote-dd__opt--sel"'
               . ' data-exc-id="' . esc_attr($current_id) . '"'
               . ' data-thumb="' . esc_attr($thumb) . '"'
               . ' data-name="' . esc_attr(get_the_title($current_id)) . '"'
               . ' data-sub="' . esc_attr($tagline) . '">';
            echo '<img class="bt-quote-dd__opt-thumb" src="' . esc_url($thumb) . '" alt="">';
            echo '<div class="bt-quote-dd__opt-info">';
            echo '<div class="bt-quote-dd__opt-name">' . esc_html(get_the_title($current_id)) . '</div>';
            if ($tagline) {
                echo '<div class="bt-quote-dd__opt-sub">' . esc_html($tagline) . '</div>';
            }
            echo '</div>';
            echo $check_svg;
            echo '</div>';
        }

        // 2. Option "Trajet sur mesure"
        if ($show_custom_trip) {
            echo '<div class="bt-quote-dd__opt"'
               . ' data-exc-id="0" data-custom-trip="1"'
               . ' data-thumb="' . esc_attr($custom_img) . '"'
               . ' data-name="' . esc_attr($custom_label) . '"'
               . ' data-sub="' . esc_attr($custom_desc) . '">';
            echo '<img class="bt-quote-dd__opt-thumb" src="' . esc_url($custom_img) . '" alt="">';
            echo '<div class="bt-quote-dd__opt-info">';
            echo '<div class="bt-quote-dd__opt-name">' . esc_html($custom_label) . '</div>';
            echo '<div class="bt-quote-dd__opt-sub">' . esc_html($custom_desc) . '</div>';
            echo '</div>';
            echo $check_svg;
            echo '</div>';
        }

        // 3. Autres excursions (sans l'excursion courante)
        foreach ($excursions as $exc) {
            if ($current_id && $exc->ID === $current_id) {
                continue; // Déjà affichée en premier
            }

            $thumb   = get_the_post_thumbnail_url($exc->ID, 'thumbnail') ?: self::DEFAULT_IMG_PLACEHOLDER;
            $is_sel  = (!$current_id && $exc->ID === $sel_id);
            $tagline = get_field('exp_tagline', $exc->ID) ?: '';

            echo '<div class="bt-quote-dd__opt' . ($is_sel ? ' bt-quote-dd__opt--sel' : '') . '"'
               . ' data-exc-id="' . esc_attr($exc->ID) . '"'
               . ' data-thumb="' . esc_attr($thumb) . '"'
               . ' data-name="' . esc_attr($exc->post_title) . '"'
               . ' data-sub="' . esc_attr($tagline) . '">';
            echo '<img class="bt-quote-dd__opt-thumb" src="' . esc_url($thumb) . '" alt="">';
            echo '<div class="bt-quote-dd__opt-info">';
            echo '<div class="bt-quote-dd__opt-name">' . esc_html($exc->post_title) . '</div>';
            if ($tagline) {
                echo '<div class="bt-quote-dd__opt-sub">' . esc_html($tagline) . '</div>';
            }
            echo '</div>';
            echo $check_svg;
            echo '</div>';
        }

        echo '</div>'; // .bt-quote-dd__menu
        echo '<input type="hidden" name="excursion_id" value="' . esc_attr($sel_id) . '">';
        echo '</div>'; // .bt-quote-dd
    }

    /**
     * Rendu dropdown pour les bateaux.
     *
     * @param array $s          Widget settings
     * @param array $boat_ids   IDs des bateaux à afficher (vide = tous)
     * @param int   $current_id ID du bateau courant (page bateau)
     * @param int   $exc_id     ID de l'excursion pour charger les bateaux liés
     */
    protected function render_boat_dropdown(array $s, array $boat_ids = [], int $current_id = 0, int $exc_id = 0): void {
        // Récupérer les bateaux
        if (!empty($boat_ids)) {
            $boats = array_filter(array_map('get_post', $boat_ids));
        } elseif ($exc_id) {
            // Bateaux liés à l'excursion
            $exp_boats = get_field('exp_boats', $exc_id);
            $boats = [];
            if (is_array($exp_boats)) {
                foreach ($exp_boats as $boat) {
                    $boats[] = $boat instanceof \WP_Post ? $boat : get_post($boat);
                }
            }
        } else {
            // Tous les bateaux
            $boats = get_posts([
                'post_type'      => 'boat',
                'posts_per_page' => 50,
                'post_status'    => 'publish',
                'orderby'        => 'title',
                'order'          => 'ASC',
            ]);
        }

        if (empty($boats)) {
            echo '<p class="bt-quote__empty">' . esc_html__('Aucun bateau disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        // Sélection initiale
        $first_boat = $current_id ? get_post($current_id) : $boats[0];
        $sel_id     = $first_boat->ID;
        $sel_name   = $first_boat->post_title;
        $sel_thumb  = get_the_post_thumbnail_url($sel_id, 'thumbnail') ?: self::DEFAULT_IMG_PLACEHOLDER;
        $sel_pax    = (int) get_field('boat_pax_max', $sel_id);
        $sel_half   = (float) get_field('boat_price_half', $sel_id);
        $sel_full   = (float) get_field('boat_price_full', $sel_id);
        $sel_sub    = '';
        if ($sel_half || $sel_full) {
            $price = $sel_half ?: $sel_full;
            $sel_sub = sprintf(__('À partir de %s € · %d pers.', 'blacktenderscore'), number_format($price, 0, ',', ' '), $sel_pax);
        }

        $arrow_svg = '<svg class="bt-quote-dd__arrow" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>';
        $check_svg = '<svg class="bt-quote-dd__check" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>';

        echo '<div class="bt-quote-dd bt-quote-dd--boat" data-bt-boat-dropdown data-selected-id="' . esc_attr($sel_id) . '">';
        echo '<button type="button" class="bt-quote-dd__trigger" aria-expanded="false">';
        echo '<img class="bt-quote-dd__thumb" src="' . esc_url($sel_thumb) . '" alt="">';
        echo '<div class="bt-quote-dd__info">';
        echo '<div class="bt-quote-dd__name">' . esc_html($sel_name) . '</div>';
        echo '<div class="bt-quote-dd__sub">' . esc_html($sel_sub) . '</div>';
        echo '</div>';
        echo $arrow_svg;
        echo '</button>';

        echo '<div class="bt-quote-dd__menu">';

        // Helper pour rendre une option bateau
        $render_boat_opt = function ($boat, bool $is_selected) use ($check_svg) {
            if (!$boat) return;
            $bid     = $boat->ID;
            $thumb   = get_the_post_thumbnail_url($bid, 'thumbnail') ?: self::DEFAULT_IMG_PLACEHOLDER;
            $pax     = (int) get_field('boat_pax_max', $bid);
            $half    = (float) get_field('boat_price_half', $bid);
            $full    = (float) get_field('boat_price_full', $bid);
            $jockey  = has_term('jockey', 'boat_skipper', $bid);
            $price_from = $half ?: $full;
            $sub_text   = $price_from ? sprintf(__('À partir de %s € · %d pers.', 'blacktenderscore'), number_format($price_from, 0, ',', ' '), $pax) : '';

            echo '<div class="bt-quote-dd__opt' . ($is_selected ? ' bt-quote-dd__opt--sel' : '') . '"'
               . ' data-boat-id="' . esc_attr($bid) . '"'
               . ' data-thumb="' . esc_attr($thumb) . '"'
               . ' data-name="' . esc_attr($boat->post_title) . '"'
               . ' data-sub="' . esc_attr($sub_text) . '"'
               . ' data-half="' . esc_attr($half) . '"'
               . ' data-full="' . esc_attr($full) . '">';
            echo '<img class="bt-quote-dd__opt-thumb" src="' . esc_url($thumb) . '" alt="">';
            echo '<div class="bt-quote-dd__opt-info">';
            echo '<div class="bt-quote-dd__opt-name">' . esc_html($boat->post_title) . '</div>';
            echo '<div class="bt-quote-dd__opt-sub">' . esc_html($pax . ' pers.' . ($jockey ? ' · Siège jockey' : '')) . '</div>';
            if ($half || $full) {
                echo '<div class="bt-quote-dd__opt-prices">';
                if ($half) echo '<span class="bt-quote-dd__opt-price">Demi-j. <strong>' . esc_html(number_format($half, 0, ',', ' ') . ' €') . '</strong></span>';
                if ($full) echo '<span class="bt-quote-dd__opt-price">Journée <strong>' . esc_html(number_format($full, 0, ',', ' ') . ' €') . '</strong></span>';
                echo '</div>';
            }
            echo '</div>';
            echo $check_svg;
            echo '</div>';
        };

        // 1. Bateau courant en premier (si défini)
        if ($current_id) {
            $render_boat_opt(get_post($current_id), true);
        }

        // 2. Autres bateaux
        foreach ($boats as $boat) {
            if (!$boat) continue;
            if ($current_id && $boat->ID === $current_id) continue;
            $render_boat_opt($boat, !$current_id && $boat->ID === $sel_id);
        }

        echo '</div>'; // .bt-quote-dd__menu
        echo '<input type="hidden" name="boat_id" value="' . esc_attr($sel_id) . '">';
        echo '</div>'; // .bt-quote-dd
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

            // Template class
            $tpl = $s['boat_card_template'] ?? 'template-1';
            $card_cls = 'bt-quote-boat-card bt-quote-boat-card--' . esc_attr($tpl);

            echo '<div class="' . $card_cls . '" '
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

            // Subtitle : modèle (optionnel) · année (optionnel)
            $show_model = ($s['show_boat_model'] ?? '') === 'yes';
            $show_year  = ($s['show_boat_year']  ?? '') === 'yes';

            $model_name = '';
            if ($show_model) {
                $model_terms = get_the_terms($bid, 'boat-model');
                if (!empty($model_terms) && !is_wp_error($model_terms)) {
                    $model_name = $model_terms[0]->name;
                } elseif ($raw_model = get_field('boat_model_name', $bid)) {
                    $model_id = is_array($raw_model) ? (int) reset($raw_model) : (int) $raw_model;
                    $term     = $model_id ? get_term($model_id) : null;
                    if ($term && !is_wp_error($term)) $model_name = $term->name;
                }
            }

            $boat_year = $show_year ? (int) get_field('boat_year', $bid) : 0;

            // Subtitle = modèle uniquement (année va après le titre)
            $subtitle = $model_name;

            $has_jockey = (bool) get_field('boat_has_jockey_sits', $bid);
            $thumb      = get_the_post_thumbnail_url($bid, 'medium');

            // Template class (template-1 = Panoramique, template-2 = Vitrine)
            $tpl = $s['boat_card_template'] ?? 'template-1';
            $card_cls  = 'bt-quote-boat-card bt-quote-boat-card--' . esc_attr($tpl);
            $card_cls .= $is_current ? ' bt-quote-boat-card--selected bt-forfait-card--active' : '';

            $data = 'data-boat-id="' . esc_attr($bid) . '"';
            if ($min_rep)  $data .= ' data-price-min="' . esc_attr($min_rep) . '"';
            if ($pax_max)  $data .= ' data-pax-max="'   . esc_attr($pax_max) . '"';

            $pax_suffix = $s['boat_pax_suffix'] ?? __('pers.', 'blacktenderscore');
            $bg_attr = $thumb ? ' data-lazy-bg="' . esc_url($thumb) . '"' : '';

            echo '<div class="' . $card_cls . '" ' . $data
               . ' tabindex="0" role="option"'
               . ' aria-pressed="' . ($is_current ? 'true' : 'false') . '">';

            if ($tpl === 'template-2') {
                // ══════════════════════════════════════════════════════════════
                // TEMPLATE 2 — Vitrine (image full-width en haut, corps en bas)
                // ══════════════════════════════════════════════════════════════
                echo '<div class="bt-quote-boat-card__img"' . $bg_attr . '></div>';
                echo '<div class="bt-quote-boat-card__body">';
                echo '<p class="bt-quote-boat-card__title">' . esc_html($boat->post_title);
                if ($boat_year) {
                    echo ' <span class="bt-exp-price__suffix">' . esc_html($boat_year) . '</span>';
                }
                echo '</p>';

                // Forfaits en tabs segmentés — affichage seul, non-cliquable
                if (!empty($forfaits)) {
                    echo '<div class="bt-quote-boat-card__forfait-tabs">';
                    foreach ($forfaits as $f) {
                        echo '<div class="bt-quote-boat-card__forfait-tab">';
                        if ($f['label']) {
                            echo '<span class="bt-quote-boat-card__forfait-label">' . esc_html($f['label']) . '</span>';
                        }
                        echo '<span class="bt-quote-boat-card__forfait-price">'
                           . esc_html(number_format($f['price'], 0, ',', ' ')) . ' €</span>';
                        echo '</div>';
                    }
                    echo '</div>';
                }

                // Bottom row: pp | sep | pax + jockey
                echo '<div class="bt-quote-boat-card__bottom">';
                if ($pp_price) {
                    echo '<div class="bt-quote-boat-card__pp">'
                       . '<span class="bt-quote-boat-card__pp-val">' . esc_html($pp_price) . ' €</span>'
                       . '<span class="bt-quote-boat-card__pp-label">/ ' . esc_html($pax_suffix) . '</span>'
                       . '</div>';
                }
                echo '<span class="bt-quote-boat-card__sep"></span>';
                echo '<div class="bt-quote-boat-card__meta">';
                if ($pax_max) {
                    echo '<span class="bt-quote-boat-card__pax">' . esc_html($pax_max) . ' ' . esc_html($pax_suffix) . '</span>';
                }
                if ($has_jockey) {
                    echo '<span class="bt-quote-boat-card__jockey">' . esc_html__('Siège jockey', 'blacktenderscore') . '</span>';
                }
                echo '</div>';
                echo '</div>'; // .bt-quote-boat-card__bottom
                echo '</div>'; // .bt-quote-boat-card__body

            } else {
                // ══════════════════════════════════════════════════════════════
                // TEMPLATE 1 — Panoramique (image à gauche, corps à droite)
                // ══════════════════════════════════════════════════════════════
                echo '<div class="bt-quote-boat-card__bg"' . $bg_attr . '></div>';
                echo '<div class="bt-quote-boat-card__right">';
                echo '<div class="bt-quote-boat-card__body">';

                echo '<p class="bt-quote-boat-card__title">' . esc_html($boat->post_title);
                if ($boat_year) {
                    echo ' <span class="bt-exp-price__suffix">' . esc_html($boat_year) . '</span>';
                }
                echo '</p>';
                if ($subtitle) {
                    echo '<p class="bt-quote-boat-card__subtitle">' . esc_html($subtitle) . '</p>';
                }

                // Pills forfaits (label durée + prix) — affichage seul, non-cliquable
                if (!empty($forfaits)) {
                    echo '<div class="bt-quote-boat-card__forfaits">';
                    foreach ($forfaits as $f) {
                        echo '<div class="bt-quote-boat-card__forfait">';
                        if ($f['label']) {
                            echo '<span class="bt-quote-boat-card__forfait-label">' . esc_html($f['label']) . '</span>';
                        }
                        echo '<span class="bt-quote-boat-card__forfait-price">'
                           . esc_html(number_format($f['price'], 0, ',', ' ')) . ' €</span>';
                        echo '</div>';
                    }
                    echo '</div>'; // .bt-quote-boat-card__forfaits

                    // Prix / pers. basé sur le prix minimum
                    if ($pp_price) {
                        echo '<p class="bt-quote-boat-card__pp">' . esc_html__('À partir de', 'blacktenderscore') . ' ' . esc_html($pp_price) . ' €'
                           . ' <span class="bt-quote-boat-card__per">/ ' . esc_html($pax_suffix) . '</span></p>';
                    }
                }

                // Meta row
                echo '<div class="bt-quote-boat-card__meta">';
                if ($pax_max) {
                    echo '<span class="bt-quote-boat-card__meta-item">';
                    echo '<svg class="bt-quote-boat-card__icon" viewBox="0 0 24 24">'
                       . '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>'
                       . '<circle cx="9" cy="7" r="4"/>'
                       . '<path d="M23 21v-2a4 4 0 0 0-3-3.87"/>'
                       . '<path d="M16 3.13a4 4 0 0 1 0 7.75"/>'
                       . '</svg>';
                    echo esc_html($pax_max) . ' ' . esc_html($pax_suffix);
                    echo '</span>';
                }
                // Jockey badge : afficher UNIQUEMENT si true
                if ($has_jockey) {
                    echo '<span class="bt-quote-boat-card__meta-item bt-quote-boat-card__meta-item--jockey bt-quote-boat-card__meta-item--yes">'
                       . esc_html__('Siège jockey', 'blacktenderscore') . '</span>';
                }
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
            }

            echo '</div>'; // .bt-quote-boat-card
        }

        echo '</div>'; // .bt-quote-boat-cards
    }

    /**
     * Card bateau par défaut — structure identique à render_all_boat_cards.
     * Image + titre + subtitle + pills forfaits + pp + meta capacité + "Plus d'infos".
     * Utilisée par render_linked_boat_cards (mode devis excursion).
     *
     * @param int      $bid  ID du post bateau
     * @param \WP_Post $boat Objet post bateau
     * @param array    $s    Settings Elementor (show_boat_more_btn, boat_popup_tpl)
     */
    protected function render_default_boat_card(int $bid, \WP_Post $boat, array $s = []): void {
        $thumb = get_the_post_thumbnail_url($bid, 'medium');
        $pax   = (int) get_field('boat_pax_max', $bid);

        // Settings avec fallbacks
        $pax_suffix    = $s['boat_pax_suffix']    ?? __('pers.', 'blacktenderscore');
        $current_label = $s['boat_current_label'] ?? __('Actuel', 'blacktenderscore');
        $is_current    = !empty($s['is_current']);

        // Subtitle : modèle (optionnel) · année (optionnel)
        $show_model = ($s['show_boat_model'] ?? '') === 'yes';
        $show_year  = ($s['show_boat_year']  ?? '') === 'yes';

        $model_name = '';
        if ($show_model) {
            $model_terms = get_the_terms($bid, 'boat-model');
            if (!empty($model_terms) && !is_wp_error($model_terms)) {
                $model_name = $model_terms[0]->name;
            } elseif ($raw_model = get_field('boat_model_name', $bid)) {
                $model_id = is_array($raw_model) ? (int) reset($raw_model) : (int) $raw_model;
                $term     = $model_id ? get_term($model_id) : null;
                if ($term && !is_wp_error($term)) $model_name = $term->name;
            }
        }

        $boat_year = $show_year ? (int) get_field('boat_year', $bid) : 0;

        // Subtitle = modèle uniquement (année va après le titre)
        $subtitle = $model_name;

        // Forfaits : repeater → pills, fallback half/full
        $repeater  = get_field('boat_price', $bid) ?: [];
        $forfaits  = [];
        $min_rep   = 0;
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
        if (empty($forfaits)) {
            $ph = (float) get_field('boat_price_half', $bid);
            $pf = (float) get_field('boat_price_full', $bid);
            if ($ph) $forfaits[] = ['price' => $ph, 'label' => __('Demi-journée', 'blacktenderscore')];
            if ($pf) $forfaits[] = ['price' => $pf, 'label' => __('Journée complète', 'blacktenderscore')];
            $min_rep = $ph ?: $pf;
        }
        $first_price = $forfaits[0]['price'] ?? 0;
        $pp_price    = ($first_price && $pax > 0) ? (int) ceil($first_price / $pax) : 0;

        $has_jockey = (bool) get_field('boat_has_jockey_sits', $bid);
        $tpl = $s['boat_card_template'] ?? 'template-1';
        $bg_attr = $thumb ? ' data-lazy-bg="' . esc_url($thumb) . '"' : '';

        if ($tpl === 'template-2') {
            // ══════════════════════════════════════════════════════════════
            // TEMPLATE 2 — Vitrine (image full-width en haut, corps en bas)
            // ══════════════════════════════════════════════════════════════
            echo '<div class="bt-quote-boat-card__img"' . $bg_attr . '></div>';
            echo '<div class="bt-quote-boat-card__body">';

            echo '<p class="bt-quote-boat-card__title">' . esc_html($boat->post_title);
            if ($boat_year) {
                echo ' <span class="bt-exp-price__suffix">' . esc_html($boat_year) . '</span>';
            }
            if ($is_current && $current_label) {
                echo ' <span class="bt-quote-boat-card__badge">(' . esc_html($current_label) . ')</span>';
            }
            echo '</p>';

            // Forfaits en tabs segmentés — affichage seul, non-cliquable
            if (!empty($forfaits)) {
                echo '<div class="bt-quote-boat-card__forfait-tabs">';
                foreach ($forfaits as $f) {
                    echo '<div class="bt-quote-boat-card__forfait-tab">';
                    if ($f['label']) {
                        echo '<span class="bt-quote-boat-card__forfait-label">' . esc_html($f['label']) . '</span>';
                    }
                    echo '<span class="bt-quote-boat-card__forfait-price">'
                       . esc_html(number_format($f['price'], 0, ',', ' ')) . ' €</span>';
                    echo '</div>';
                }
                echo '</div>';
            }

            // Bottom row: pp | sep | pax + jockey
            echo '<div class="bt-quote-boat-card__bottom">';
            if ($pp_price) {
                echo '<div class="bt-quote-boat-card__pp">'
                   . '<span class="bt-quote-boat-card__pp-val">' . esc_html($pp_price) . ' €</span>'
                   . '<span class="bt-quote-boat-card__pp-label">/ ' . esc_html($pax_suffix) . '</span>'
                   . '</div>';
            }
            echo '<span class="bt-quote-boat-card__sep"></span>';
            echo '<div class="bt-quote-boat-card__meta">';
            if ($pax) {
                echo '<span class="bt-quote-boat-card__pax">' . esc_html($pax) . ' ' . esc_html($pax_suffix) . '</span>';
            }
            if ($has_jockey) {
                echo '<span class="bt-quote-boat-card__jockey">' . esc_html__('Siège jockey', 'blacktenderscore') . '</span>';
            }
            echo '</div>';
            echo '</div>'; // .bt-quote-boat-card__bottom
            echo '</div>'; // .bt-quote-boat-card__body

        } else {
            // ══════════════════════════════════════════════════════════════
            // TEMPLATE 1 — Panoramique (image à gauche, corps à droite)
            // ══════════════════════════════════════════════════════════════
            echo '<div class="bt-quote-boat-card__bg"' . $bg_attr . '></div>';

            echo '<div class="bt-quote-boat-card__right">';
            echo '<div class="bt-quote-boat-card__body">';

            // Titre + année + badge "(Actuel)" si premier bateau
            echo '<p class="bt-quote-boat-card__title">' . esc_html($boat->post_title);
            if ($boat_year) {
                echo ' <span class="bt-exp-price__suffix">' . esc_html($boat_year) . '</span>';
            }
            if ($is_current && $current_label) {
                echo ' <span class="bt-quote-boat-card__badge">(' . esc_html($current_label) . ')</span>';
            }
            echo '</p>';

            if ($subtitle) {
                echo '<p class="bt-quote-boat-card__subtitle">' . esc_html($subtitle) . '</p>';
            }

            // Pills forfaits (label durée + prix) — affichage seul, non-cliquable
            if (!empty($forfaits)) {
                echo '<div class="bt-quote-boat-card__forfaits">';
                foreach ($forfaits as $f) {
                    echo '<div class="bt-quote-boat-card__forfait">';
                    if ($f['label']) {
                        echo '<span class="bt-quote-boat-card__forfait-label">' . esc_html($f['label']) . '</span>';
                    }
                    echo '<span class="bt-quote-boat-card__forfait-price">'
                       . esc_html(number_format($f['price'], 0, ',', ' ')) . ' €</span>';
                    echo '</div>';
                }
                echo '</div>';
                if ($pp_price) {
                    echo '<p class="bt-quote-boat-card__pp">' . esc_html__('À partir de', 'blacktenderscore') . ' ' . esc_html($pp_price) . ' €'
                       . ' <span class="bt-quote-boat-card__per">/ ' . esc_html($pax_suffix) . '</span></p>';
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
                echo esc_html($pax) . ' ' . esc_html($pax_suffix);
                echo '</span>';
            }
            // Jockey badge : afficher UNIQUEMENT si true
            if ($has_jockey) {
                echo '<span class="bt-quote-boat-card__meta-item bt-quote-boat-card__meta-item--jockey bt-quote-boat-card__meta-item--yes">'
                   . esc_html__('Avec siège jockey', 'blacktenderscore') . '</span>';
            }
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
