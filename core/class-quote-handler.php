<?php
namespace BlackTenders\Core;

defined('ABSPATH') || exit;

/**
 * Gestion AJAX pour le formulaire de devis (bt-boat-pricing widget).
 *
 * Endpoints :
 *   - bt_get_boats_by_excursion : charge les bateaux liés à une excursion
 *   - bt_quote_request          : soumission du formulaire de devis
 *   - bt_render_boat_popup      : contenu popup (template Elementor ou fallback)
 */
class QuoteHandler {

    public function init(): void {
        add_action('wp_ajax_bt_get_boats_by_excursion',        [$this, 'get_boats']);
        add_action('wp_ajax_nopriv_bt_get_boats_by_excursion', [$this, 'get_boats']);
        add_action('wp_ajax_bt_get_excursions_by_boat',        [$this, 'get_excursions']);
        add_action('wp_ajax_nopriv_bt_get_excursions_by_boat', [$this, 'get_excursions']);
        add_action('wp_ajax_bt_quote_request',                 [$this, 'submit_quote']);
        add_action('wp_ajax_nopriv_bt_quote_request',          [$this, 'submit_quote']);
        add_action('wp_ajax_bt_render_boat_popup',             [$this, 'render_popup']);
        add_action('wp_ajax_nopriv_bt_render_boat_popup',      [$this, 'render_popup']);
    }

    /**
     * Retourne les bateaux liés à une excursion via AJAX.
     * Renvoie du HTML pré-rendu (cards).
     */
    public function get_boats(): void {
        check_ajax_referer('bt_quote_nonce', 'nonce');

        $exc_id = (int) ($_POST['excursion_id'] ?? 0);
        $config = json_decode(wp_unslash($_POST['config'] ?? '{}'), true);
        if (!is_array($config)) $config = [];

        if (!$exc_id || !function_exists('get_field')) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        // Relation bidirectionnelle : excursion → bateaux
        $boat_ids = [];
        $exp_boats = get_field('exp_boats', $exc_id);
        if (is_array($exp_boats)) {
            foreach ($exp_boats as $boat) {
                $boat_ids[] = $boat instanceof \WP_Post ? $boat->ID : (int) $boat;
            }
        }

        // Fallback : chercher via le champ relationship côté bateau
        if (empty($boat_ids)) {
            $boats_query = get_posts([
                'post_type'      => 'boat',
                'posts_per_page' => 50,
                'post_status'    => 'publish',
                'meta_query'     => [
                    [
                        'key'     => 'boat_relationship_withexp',
                        'value'   => '"' . $exc_id . '"',
                        'compare' => 'LIKE',
                    ],
                ],
            ]);
            foreach ($boats_query as $boat) {
                $boat_ids[] = $boat->ID;
            }
        }

        if (empty($boat_ids)) {
            wp_send_json_success(['html' => '<p class="bt-quote__empty">' . esc_html__('Aucun bateau disponible.', 'blacktenderscore') . '</p>']);
        }

        $boat_loop_tpl      = (int)  ($config['boat_loop_tpl']      ?? 0);
        $boat_popup_tpl     = (int)  ($config['boat_popup_tpl']     ?? 0);
        $show_boat_more_btn = (bool) ($config['show_boat_more_btn'] ?? false);

        ob_start();
        foreach ($boat_ids as $bid) {
            $boat = get_post($bid);
            if (!$boat || $boat->post_status !== 'publish') continue;

            echo '<div class="bt-quote-boat-card" data-boat-id="' . esc_attr($bid) . '">';
            if ($boat_loop_tpl) {
                echo $this->render_loop_item($boat_loop_tpl, $boat);
            } else {
                $this->render_default_boat_card($bid, $boat, $show_boat_more_btn ? $boat_popup_tpl : 0);
            }
            echo '</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Retourne les excursions liées à un bateau via AJAX.
     * Inverse de get_boats() : bateau → excursions.
     */
    public function get_excursions(): void {
        check_ajax_referer('bt_quote_nonce', 'nonce');

        $boat_id = (int) ($_POST['boat_id'] ?? 0);
        if (!$boat_id || !function_exists('get_field')) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        // Chercher les excursions qui référencent ce bateau via exp_boats
        $exc_ids = [];
        $excursions = get_posts([
            'post_type'      => 'excursion',
            'posts_per_page' => 50,
            'post_status'    => 'publish',
            'meta_query'     => [
                [
                    'key'     => 'exp_boats',
                    'value'   => '"' . $boat_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ]);
        foreach ($excursions as $exc) {
            $exc_ids[] = $exc->ID;
        }

        if (empty($exc_ids)) {
            wp_send_json_success(['html' => '<p class="bt-quote__empty">' . esc_html__('Aucune excursion disponible.', 'blacktenderscore') . '</p>']);
        }

        $config = json_decode(wp_unslash($_POST['config'] ?? '{}'), true);
        if (!is_array($config)) $config = [];

        $exc_loop_tpl = (int) ($config['exc_loop_tpl'] ?? 0);

        ob_start();
        foreach ($exc_ids as $eid) {
            $exc = get_post($eid);
            if (!$exc || $exc->post_status !== 'publish') continue;

            echo '<div class="bt-quote-exc-card" data-exc-id="' . esc_attr($eid) . '" tabindex="0" role="option" aria-selected="false">';
            echo $this->render_loop_item($exc_loop_tpl, $exc);
            echo '</div>';
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Soumission du formulaire de devis — sauvegarde en DB puis envoie un e-mail récapitulatif.
     *
     * Le formulaire est toujours considéré comme réussi côté utilisateur (données sauvegardées),
     * même si l'envoi d'e-mail échoue. Le statut e-mail est tracké en DB.
     */
    public function submit_quote(): void {
        check_ajax_referer('bt_quote_nonce', 'nonce');

        $to = sanitize_email($_POST['recipient'] ?? '');
        if (!$to) {
            $to = get_option('admin_email');
        }

        $excursion_id    = (int) ($_POST['excursion_id'] ?? 0);
        $boat_id         = (int) ($_POST['boat_id'] ?? 0);
        $duration_type   = sanitize_text_field($_POST['duration_type'] ?? '');
        $date_start      = sanitize_text_field($_POST['date_start'] ?? '');
        $date_end        = sanitize_text_field($_POST['date_end'] ?? '');
        $timeslot        = sanitize_text_field($_POST['timeslot'] ?? '');
        $name            = sanitize_text_field($_POST['client_name'] ?? '');
        $firstname       = sanitize_text_field($_POST['client_firstname'] ?? '');
        $email           = sanitize_email($_POST['client_email'] ?? '');
        $phone           = sanitize_text_field($_POST['client_phone'] ?? '');
        $exc_custom      = !empty($_POST['exc_custom']);
        $exc_custom_text = sanitize_textarea_field($_POST['exc_custom_text'] ?? '');
        $boat_options    = json_decode(wp_unslash($_POST['boat_options'] ?? '{}'), true);
        if (!is_array($boat_options)) $boat_options = [];
        $boat_forfait_label = sanitize_text_field($_POST['boat_forfait_label'] ?? '');
        $boat_forfait_price = sanitize_text_field($_POST['boat_forfait_price'] ?? '');

        // Acquisition data
        $utm_source   = sanitize_text_field($_POST['utm_source'] ?? '');
        $utm_medium   = sanitize_text_field($_POST['utm_medium'] ?? '');
        $utm_campaign = sanitize_text_field($_POST['utm_campaign'] ?? '');
        $referrer     = sanitize_url($_POST['referrer'] ?? '');
        $page_url     = sanitize_url($_POST['page_url'] ?? '');

        // Validation minimale
        if (!$email || !$name) {
            wp_send_json_error(['message' => __('Champs obligatoires manquants.', 'blacktenderscore')]);
        }

        $exc_title  = $exc_custom ? 'Expérience sur mesure' : ($excursion_id ? get_the_title($excursion_id) : '—');
        $boat_title = $boat_id ? get_the_title($boat_id) : '—';
        $full_name  = trim("{$firstname} {$name}");

        // ── Collect rich data from ACF for email ──────────────────────────────
        $exc_details  = [];
        $boat_details = [];

        if ($excursion_id && function_exists('get_fields')) {
            $exc_acf = get_fields($excursion_id) ?: [];

            if (!empty($exc_acf['exp_tagline']))        $exc_details[] = "Description : " . $exc_acf['exp_tagline'];
            if (!empty($exc_acf['exp_duration']))        $exc_details[] = "Durée : " . $exc_acf['exp_duration'];
            if (!empty($exc_acf['exp_departure_zone']))  $exc_details[] = "Départ : " . $exc_acf['exp_departure_zone'];
            if (!empty($exc_acf['exp_pax_min']) || !empty($exc_acf['exp_pax_max'])) {
                $pax_str = '';
                if (!empty($exc_acf['exp_pax_min'])) $pax_str .= $exc_acf['exp_pax_min'];
                if (!empty($exc_acf['exp_pax_max'])) $pax_str .= ($pax_str ? ' – ' : '') . $exc_acf['exp_pax_max'];
                $exc_details[] = "Passagers : " . $pax_str;
            }

            // Departure point taxonomy
            $dep_terms = $exc_acf['exp_departure_point'] ?? null;
            if (!empty($dep_terms)) {
                $exc_details[] = "Point de départ : " . $this->format_term_list($dep_terms);
            }

            // Langues
            if (!empty($exc_acf['exp_languages'])) {
                $exc_details[] = "Langues : " . $this->format_term_list($exc_acf['exp_languages']);
            }

            // Included / excluded taxonomies
            foreach ([
                'exp_included'    => 'Inclus',
                'exp_to_excluded' => 'Non inclus',
                'exp_to_bring'    => 'À apporter',
            ] as $field => $label) {
                $terms = $exc_acf[$field] ?? null;
                if (!empty($terms)) {
                    $exc_details[] = "{$label} : " . $this->format_term_list($terms);
                }
            }

            // Skipper info from post terms
            $skipper_terms = get_the_terms($excursion_id, 'skipper');
            if (!empty($skipper_terms) && !is_wp_error($skipper_terms)) {
                $exc_details[] = "Skipper : " . implode(', ', wp_list_pluck($skipper_terms, 'name'));
            }
        }

        if ($boat_id && function_exists('get_fields')) {
            $boat_acf = get_fields($boat_id) ?: [];

            // Type de bateau (taxonomy)
            $types = get_the_terms($boat_id, 'type-de-bateau');
            if (!empty($types) && !is_wp_error($types)) {
                $boat_details[] = "Type : " . implode(', ', wp_list_pluck($types, 'name'));
            }

            if (!empty($boat_acf['boat_tagline']))     $boat_details[] = "Description : " . $boat_acf['boat_tagline'];
            if (!empty($boat_acf['boat_pax_max']))      $boat_details[] = "Passagers max : " . $boat_acf['boat_pax_max'];
            if (!empty($boat_acf['boat_pax_comfort']))   $boat_details[] = "Passagers confort : " . $boat_acf['boat_pax_comfort'];
            if (!empty($boat_acf['boat_cabins']))        $boat_details[] = "Cabines : " . $boat_acf['boat_cabins'];
            if (!empty($boat_acf['boat_enginepower']))   $boat_details[] = "Motorisation : " . $boat_acf['boat_enginepower'] . " CV";
            if (!empty($boat_acf['boat_year']))          $boat_details[] = "Année : " . $boat_acf['boat_year'];

            // Boat taxonomies
            foreach ([
                'boat_equipment_included' => 'Équipements inclus',
                'boat_services_included'  => 'Services inclus',
                'boat_option_on_demand'   => 'Options sur demande',
            ] as $field => $label) {
                $terms = $boat_acf[$field] ?? null;
                if (!empty($terms)) {
                    $boat_details[] = "{$label} : " . $this->format_term_list($terms);
                }
            }
        }

        // ── Save to DB first (before email attempt) ──────────────────────────
        $db = new \BlackTenders\Admin\Backoffice\FormSubmissionsDb();
        $submission_id = $db->insert([
            'form_type'        => 'quote',
            'client_name'      => $name,
            'client_firstname' => $firstname,
            'client_email'     => $email,
            'client_phone'     => $phone,
            'excursion_id'     => $excursion_id,
            'excursion_name'   => $exc_title,
            'boat_id'          => $boat_id,
            'boat_name'        => $boat_title,
            'duration_type'    => $duration_type,
            'date_start'       => $date_start,
            'date_end'         => $date_end,
            'timeslot'         => $timeslot,
            'boat_forfait'     => trim($boat_forfait_label . ($boat_forfait_price ? " — {$boat_forfait_price} €" : '')),
            'boat_options'     => !empty($boat_options) ? wp_json_encode($boat_options) : null,
            'message'          => $exc_custom_text,
            'utm_source'       => $utm_source,
            'utm_medium'       => $utm_medium,
            'utm_campaign'     => $utm_campaign,
            'referrer'         => $referrer,
            'page_url'         => $page_url,
            'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        // ── Build and send email ─────────────────────────────────────────────
        $subject_parts = ['[Devis]', $full_name];
        if ($exc_title && $exc_title !== '—') $subject_parts[] = '— ' . $exc_title;
        if ($boat_title && $boat_title !== '—') $subject_parts[] = '(' . $boat_title . ')';
        $subject = implode(' ', $subject_parts);

        $duration_labels = [
            'half'   => 'Demi-journée',
            'full'   => 'Journée entière',
            'multi'  => 'Plusieurs jours',
            'custom' => 'Demande spécifique',
        ];
        $dur_label = $duration_labels[$duration_type] ?? $duration_type;

        // Parcours client : d'où vient-il ?
        $source_line = '';
        if ($utm_source) {
            $source_line = "Source : {$utm_source}";
            if ($utm_medium) $source_line .= " / {$utm_medium}";
            if ($utm_campaign) $source_line .= " ({$utm_campaign})";
        } elseif ($referrer) {
            $source_line = "Provenance : {$referrer}";
        }
        $page_line = $page_url ? "Page : {$page_url}" : '';

        // Options bateau (confort, activités, services, restauration)
        $options_lines = '';
        if (!empty($boat_options)) {
            $option_labels = [
                'boat_comfort'        => 'Confort',
                'activite'            => 'Activités',
                'boat_board_services' => 'Services à bord',
                'boat_onboard_food'   => 'Restauration à bord',
            ];
            foreach ($boat_options as $key => $opt) {
                $names = $opt['names'] ?? [];
                if (!empty($names)) {
                    $label = $option_labels[$key] ?? $key;
                    $options_lines .= "{$label} : " . implode(', ', array_map('sanitize_text_field', $names)) . "\n";
                }
            }
        }

        // Forfait sélectionné
        $forfait_line = '';
        if ($boat_forfait_label || $boat_forfait_price) {
            $forfait_line = "Forfait choisi : " . ($boat_forfait_label ?: '—') . ($boat_forfait_price ? " — {$boat_forfait_price} €" : '') . "\n";
        }

        $body = "Nouvelle demande de devis\n"
            . "══════════════════════════════════════\n\n"
            . "Client : {$full_name}\n"
            . "E-mail : {$email}\n"
            . "Téléphone : " . ($phone ?: '—') . "\n\n"
            . "── Demande ──────────────────────────\n"
            . "Excursion : {$exc_title}\n"
            . ($exc_custom_text ? "Demande sur mesure : {$exc_custom_text}\n" : '')
            . "Bateau : {$boat_title}\n"
            . $forfait_line
            . "Formule : {$dur_label}\n"
            . "Dates : " . ($date_start ?: '—') . ($timeslot ? " ({$timeslot})" : '') . ($date_end ? " → {$date_end}" : '') . "\n"
            . (!empty($exc_details) ? "\n── Détails excursion ────────────────\n" . implode("\n", $exc_details) . "\n" : '')
            . (!empty($boat_details) ? "\n── Détails bateau ──────────────────\n" . implode("\n", $boat_details) . "\n" : '')
            . ($options_lines ? "\n── Options sélectionnées ────────────\n" . $options_lines : '')
            . "\n── Parcours client ──────────────────\n"
            . ($source_line ? "{$source_line}\n" : '')
            . ($page_line ? "{$page_line}\n" : '')
            . "IP : " . ($_SERVER['REMOTE_ADDR'] ?? '—') . "\n"
            . "Date : " . current_time('d/m/Y H:i') . "\n";

        $headers = [
            'Content-Type: text/plain; charset=UTF-8',
            'Reply-To: ' . $full_name . ' <' . $email . '>',
        ];

        $sent = wp_mail($to, $subject, $body, $headers);
        $email_error = '';
        if (!$sent) {
            global $phpmailer;
            if (isset($phpmailer) && $phpmailer->ErrorInfo) {
                $email_error = $phpmailer->ErrorInfo;
            } else {
                $email_error = 'wp_mail returned false';
            }
        }

        // ── Update email status in DB ────────────────────────────────────────
        if ($submission_id) {
            $db->update_email_status($submission_id, $sent, $email_error);
        }

        // Always return success (form is saved regardless of email)
        wp_send_json_success([
            'message'    => 'ok',
            'email_sent' => $sent,
        ]);
    }

    /**
     * Rendu du contenu popup pour un bateau (template Elementor ou fallback tableau).
     */
    public function render_popup(): void {
        check_ajax_referer('bt_quote_nonce', 'nonce');

        $boat_id    = (int) ($_POST['boat_id'] ?? 0);
        $tpl_id     = (int) ($_POST['template_id'] ?? 0);
        $config     = json_decode(wp_unslash($_POST['config'] ?? '{}'), true);
        if (!is_array($config)) $config = [];

        if (!$boat_id || !function_exists('get_field')) {
            wp_send_json_error(['message' => 'Invalid request']);
        }

        ob_start();

        // Template Elementor sélectionné
        if ($tpl_id && class_exists('\Elementor\Plugin')) {
            // Set global post context for dynamic tags
            global $post;
            $original_post = $post;
            $post = get_post($boat_id);
            setup_postdata($post);

            echo \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($tpl_id);

            $post = $original_post;
            if ($original_post) wp_reset_postdata();
        } else {
            // Fallback : tableau du repeater
            $this->render_popup_fallback($boat_id, $config);
        }

        $html = ob_get_clean();
        wp_send_json_success(['html' => $html]);
    }

    /**
     * Tableau fallback pour le popup bateau (données du repeater).
     */
    private function render_popup_fallback(int $boat_id, array $config): void {
        $repeater_slug  = $this->sanitize_field_name($config['repeater_slug'] ?? 'boat_custom_price_by_departure');
        $rep_price_half = $this->sanitize_field_name($config['rep_price_half'] ?? 'boat_price_for_half_day');
        $rep_price_full = $this->sanitize_field_name($config['rep_price_full'] ?? 'boat_price_for_full_day');
        $rep_nav_zone   = $this->sanitize_field_name($config['rep_nav_zone'] ?? 'boat_navigation_zone');
        $rep_duration   = $this->sanitize_field_name($config['rep_duration'] ?? 'boat_duration_taxonomy');
        $rep_carburant  = $this->sanitize_field_name($config['rep_carburant'] ?? 'boat_carburant');
        $currency       = sanitize_text_field($config['currency'] ?? '€');

        $col_zone     = sanitize_text_field($config['popup_col_zone'] ?? __('Zone de navigation', 'blacktenderscore'));
        $col_half     = sanitize_text_field($config['popup_col_half'] ?? __('Demi-journée', 'blacktenderscore'));
        $col_full     = sanitize_text_field($config['popup_col_full'] ?? __('Journée', 'blacktenderscore'));
        $col_duration = sanitize_text_field($config['popup_col_duration'] ?? __('Durée', 'blacktenderscore'));
        $col_fuel     = sanitize_text_field($config['popup_col_fuel'] ?? __('Carburant', 'blacktenderscore'));

        $repeater = get_field($repeater_slug, $boat_id);

        echo '<h3 class="bt-quote-popup__title">' . esc_html(get_the_title($boat_id)) . '</h3>';

        if (!is_array($repeater) || empty($repeater)) {
            echo '<p>' . esc_html__('Aucun tarif disponible.', 'blacktenderscore') . '</p>';
            return;
        }

        echo '<div class="bt-quote-popup__table-wrap"><table class="bt-quote-popup__table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html($col_zone) . '</th>';
        echo '<th>' . esc_html($col_half) . '</th>';
        echo '<th>' . esc_html($col_full) . '</th>';
        echo '<th>' . esc_html($col_duration) . '</th>';
        echo '<th>' . esc_html($col_fuel) . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($repeater as $row) {
            echo '<tr>';

            // Zone de navigation
            echo '<td>' . esc_html($this->resolve_term_names($row[$rep_nav_zone] ?? null)) . '</td>';

            // Prix demi-journée
            $ph = isset($row[$rep_price_half]) ? (float) $row[$rep_price_half] : 0;
            echo '<td>' . ($ph ? esc_html(number_format($ph, 0, ',', ' ') . ' ' . $currency) : '—') . '</td>';

            // Prix journée
            $pf = isset($row[$rep_price_full]) ? (float) $row[$rep_price_full] : 0;
            echo '<td>' . ($pf ? esc_html(number_format($pf, 0, ',', ' ') . ' ' . $currency) : '—') . '</td>';

            // Durée
            echo '<td>';
            $dur_names = $this->resolve_term_names($row[$rep_duration] ?? null);
            if ($dur_names) {
                echo '<span class="bt-quote-popup__tag">' . esc_html($dur_names) . '</span>';
            } else {
                echo '—';
            }
            echo '</td>';

            // Carburant
            echo '<td>';
            $fuel_names = $this->resolve_term_names($row[$rep_carburant] ?? null);
            if ($fuel_names) {
                echo '<span class="bt-quote-popup__tag">' . esc_html($fuel_names) . '</span>';
            } else {
                echo '—';
            }
            echo '</td>';

            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }

    /**
     * Résout les noms de termes depuis un champ taxonomie ACF.
     */
    private function resolve_term_names($terms): string {
        if (empty($terms)) return '';
        if (!is_array($terms)) $terms = [$terms];

        $names = [];
        foreach ($terms as $t) {
            $term = is_numeric($t) ? get_term((int) $t) : ($t instanceof \WP_Term ? $t : null);
            if ($term && !is_wp_error($term)) {
                $names[] = $term->name;
            }
        }
        return implode(', ', $names);
    }

    /**
     * Card bateau par défaut : layout horizontal (30% img | 70% contenu).
     * Enrichie avec pax, prix/pers, carburant.
     */
    private function render_default_boat_card(int $bid, \WP_Post $boat, int $popup_tpl = 0): void {
        $thumb      = get_the_post_thumbnail_url($bid, 'medium');
        $pax        = (int) get_field('boat_pax_max', $bid);
        $price_full = (float) get_field('boat_price_full', $bid);
        $price_half = (float) get_field('boat_price_half', $bid);

        $type  = '';
        $types = get_the_terms($bid, 'type-de-bateau');
        if (!empty($types) && !is_wp_error($types)) {
            $type = $types[0]->name;
        }

        $base_price = $price_half ?: $price_full;
        $pp_price   = ($base_price && $pax > 0) ? ceil($base_price / $pax) : 0;
        $pp_label   = $price_full ? __('journée complète', 'blacktenderscore') : __('demi-journée', 'blacktenderscore');

        if ($thumb) {
            echo '<div class="bt-quote-boat-card__img">'
               . '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($boat->post_title) . '" loading="lazy">'
               . '</div>';
        }

        echo '<div class="bt-quote-boat-card__body">';
        echo '<div class="bt-quote-boat-card__header">';
        echo '<h4 class="bt-quote-boat-card__title">' . esc_html($boat->post_title) . '</h4>';
        if ($type) {
            echo '<span class="bt-quote-boat-card__type">' . esc_html($type) . '</span>';
        }
        echo '</div>';

        if ($pax) {
            echo '<p class="bt-quote-boat-card__pax">'
               . esc_html(sprintf(__('Jusqu\'à %d passagers', 'blacktenderscore'), $pax))
               . '</p>';
        }

        if ($pp_price) {
            echo '<p class="bt-quote-boat-card__price">'
               . '<span class="bt-quote-boat-card__price-amount">'
               . esc_html(sprintf(__('À partir de %d € / pers.', 'blacktenderscore'), $pp_price))
               . '</span>'
               . ' <span class="bt-quote-boat-card__price-suffix">' . esc_html($pp_label) . '</span>'
               . '</p>';
        }

        $boat_year = (int) get_field('boat_year', $bid);
        if ($boat_year) {
            echo '<span class="bt-quote-boat-card__year">' . esc_html($boat_year) . '</span>';
        }

        echo '</div>';

        if ($popup_tpl) {
            echo '<button type="button" class="bt-quote-boat-card__more"'
               . ' data-boat-id="' . esc_attr($bid) . '"'
               . ' data-popup-tpl="' . esc_attr($popup_tpl) . '">'
               . esc_html__('Plus d\'infos', 'blacktenderscore')
               . '</button>';
        }
    }

    /** Flag pour injecter le CSS du template une seule fois dans la réponse AJAX. */
    private static array $loop_css_injected = [];

    /**
     * Rendu d'un item via un template Elementor Loop.
     * Injecte le CSS inline (pour AJAX) + enqueue le fichier CSS.
     */
    private function render_loop_item(int $tpl_id, \WP_Post $item): string {
        if ($tpl_id && class_exists('\Elementor\Plugin')) {
            // Injecter le CSS du template inline (critique pour AJAX)
            $css_prefix = '';
            if (!isset(self::$loop_css_injected[$tpl_id])) {
                self::$loop_css_injected[$tpl_id] = true;
                $css_file = \Elementor\Core\Files\CSS\Post::create($tpl_id);
                $css_file->enqueue();
                $css_content = $css_file->get_content();
                if ($css_content) {
                    $css_prefix = '<style>' . $css_content . '</style>';
                }
            }

            global $post;
            $original_post = $post;
            $post = $item;
            setup_postdata($post);

            $html = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($tpl_id);

            $post = $original_post;
            if ($original_post) wp_reset_postdata();

            return $css_prefix . ($html ?: '<p>' . esc_html($item->post_title) . '</p>');
        }

        return '<div class="bt-quote-loop-fallback"><strong>' . esc_html($item->post_title) . '</strong></div>';
    }

    /**
     * Nettoie un nom de champ ACF (alphanumérique + underscores uniquement).
     */
    private function sanitize_field_name(string $name): string {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $name);
    }

    /**
     * Formate une liste de termes ACF (WP_Term[], int[], ou mixte) en string lisible.
     */
    private function format_term_list($terms): string {
        if (empty($terms)) return '';
        if (!is_array($terms)) $terms = [$terms];

        $names = [];
        foreach ($terms as $t) {
            if ($t instanceof \WP_Term) {
                $names[] = $t->name;
            } elseif (is_numeric($t)) {
                $term = get_term((int) $t);
                if ($term && !is_wp_error($term)) $names[] = $term->name;
            } elseif (is_string($t) && $t !== '') {
                $names[] = $t;
            }
        }
        return implode(', ', $names);
    }
}
