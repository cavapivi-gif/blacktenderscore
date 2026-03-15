<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Tarification par forfait.
 *
 * Lit le repeater ACF `tarification_par_forfait` du post courant.
 * Chaque ligne = un forfait (durée via exp_time + prix).
 *
 * Deux layouts mutuellement exclusifs :
 *   - `tabs`    : onglets classiques, un panneau par forfait
 *   - `buttons` : boutons pill (durée), clic révèle prix + booking
 *
 * Les deux lisent la MÊME source : le repeater tarification_par_forfait.
 */
class PricingTabs extends AbstractBtWidget {
    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-pricing-tabs',
            'title'    => 'BT — Tarification',
            'icon'     => 'eicon-price-table',
            'keywords' => ['tarif', 'prix', 'forfait', 'réservation', 'booking', 'bt'],
            'js'       => ['bt-elementor'],
        ];
    }

    /** Évite d'injecter le script Regiondo plusieurs fois sur la même page. */
    private static bool $regiondo_script_printed = false;

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Titre & description ───────────────────────────────────────────
        $this->start_controls_section('section_heading', [
            'label' => __('Titre & description', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();

        $this->add_control('section_description', [
            'label'   => __('Description', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => '',
            'rows'    => 3,
            'dynamic' => ['active' => true],
        ]);

        $this->end_controls_section();

        // ── Affichage ─────────────────────────────────────────────────────
        $this->start_controls_section('section_display', [
            'label' => __('Affichage', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('layout', [
            'label'   => __('Format d\'affichage', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'tabs'    => __('Onglets (tabs)', 'blacktenderscore'),
                'buttons' => __('Boutons pill', 'blacktenderscore'),
            ],
            'default' => 'tabs',
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'tarification_par_forfait' => __('Tarification par forfait', 'blacktenderscore'),
            ],
            'default' => 'tarification_par_forfait',
        ]);

        $this->end_controls_section();

        // ── Prix ─────────────────────────────────────────────────────────
        $this->start_controls_section('section_price', [
            'label' => __('Prix', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('currency', [
            'label'   => __('Symbole devise', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => '€',
        ]);

        $this->add_control('show_price', [
            'label'        => __('Afficher le prix', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_per_label', [
            'label'        => __('Afficher "/ pers."', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('per_label', [
            'label'     => __('Libellé "par pers."', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('/ pers.', 'blacktenderscore'),
            'condition' => ['show_per_label' => 'yes'],
        ]);

        $this->add_control('show_deposit', [
            'label'        => __('Afficher l\'acompte', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('deposit_label', [
            'label'     => __('Libellé "Acompte"', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Acompte :', 'blacktenderscore'),
            'condition' => ['show_deposit' => 'yes'],
        ]);

        $this->add_control('show_note', [
            'label'        => __('Afficher la note tarifaire', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->end_controls_section();

        // ── Onglets ──────────────────────────────────────────────────────
        $this->start_controls_section('section_tabs', [
            'label' => __('Onglets', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('tab_title_mode', [
            'label'   => __('Titre des onglets', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'forfait_et_prix' => __('Forfait + prix', 'blacktenderscore'),
                'prix_seul'       => __('Prix seul', 'blacktenderscore'),
            ],
            'default'     => 'forfait_et_prix',
            'description' => __('Contenu affiché dans chaque onglet (et dans chaque bouton pill en layout boutons).', 'blacktenderscore'),
        ]);

        $this->add_control('discount_subfield', [
            'label'       => __('Champ ACF remise (repeater)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'is_a_discount',
            'description' => __('Nom du sous-champ nombre (%). Si > 0, affiche "-X%" à côté du prix (onglet + corps).', 'blacktenderscore'),
        ]);

        $this->end_controls_section();

        // ── Buttons layout — configuration ──────────────────────────────
        $this->start_controls_section('section_buttons_config', [
            'label'     => __('Boutons — Configuration', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['layout' => 'buttons'],
        ]);

        $this->add_control('buttons_title', [
            'label'   => __('Titre au-dessus des boutons', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Choisissez votre forfait', 'blacktenderscore'),
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('buttons_title_tag', [
            'label'     => __('Balise titre', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => ['h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'p' => 'p', 'span' => 'span'],
            'default'   => 'h4',
            'condition' => ['buttons_title!' => ''],
        ]);

        $this->add_control('buttons_show_price', [
            'label'        => __('Afficher le prix dans le bouton', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Ajoute le prix à côté de la durée dans chaque bouton pill.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->end_controls_section();

        // ── Réservation Regiondo ──────────────────────────────────────────
        $this->start_controls_section('section_booking', [
            'label' => __('Réservation Regiondo', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_booking', [
            'label'        => __('Afficher le widget de réservation', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Intègre le calendrier Regiondo via le UUID stocké dans le champ ACF.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('booking_per_tab', [
            'label'        => __('UUID différent par forfait (sous-champ repeater)', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Si chaque forfait a son propre UUID dans le repeater (exp_booking_uuid), activez.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
            'condition'    => ['show_booking' => 'yes'],
        ]);

        $this->add_control('booking_field', [
            'label'     => __('Champ UUID Regiondo (global)', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'exp_booking_short_url' => __('Forfait court (exp_booking_short_url)', 'blacktenderscore'),
                'exp_booking_long_url'  => __('Forfait long (exp_booking_long_url)', 'blacktenderscore'),
            ],
            'default'   => 'exp_booking_short_url',
            'condition' => ['show_booking' => 'yes', 'booking_per_tab!' => 'yes'],
        ]);

        $this->add_control('booking_uuid_subfield', [
            'label'     => __('Nom du sous-champ UUID', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'exp_booking_uuid',
            'condition' => ['show_booking' => 'yes', 'booking_per_tab' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Bouton « Réserver » et emplacement du contenu ──────────────────────
        $this->start_controls_section('section_trigger', [
            'label' => __('Bouton « Réserver » et emplacement', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('trigger_mode', [
            'label'       => __('Mode d\'affichage', 'blacktenderscore'),
            'type'        => Controls_Manager::SELECT,
            'options'     => [
                'none'   => __('Désactivé — forfaits + résa visibles directement', 'blacktenderscore'),
                'reveal' => __('Bouton « Réserver » — clic révèle forfaits + résa (sous le bouton ou dans un conteneur)', 'blacktenderscore'),
                'modal'  => __('Bouton « Réserver » — ouvre en modal / popup', 'blacktenderscore'),
            ],
            'default'     => 'none',
            'description' => __('Choisir comment afficher les forfaits et le widget de réservation.', 'blacktenderscore'),
        ]);

        $this->add_control('trigger_label', [
            'label'     => __('Texte du bouton', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Réserver', 'blacktenderscore'),
            'dynamic'   => ['active' => true],
            'condition' => ['trigger_mode!' => 'none'],
        ]);

        $this->add_control('reveal_target_id', [
            'label'       => __('ID du conteneur cible', 'blacktenderscore'),
            'description' => __('Renseigner l\'ID du conteneur Elementor où afficher forfaits + réservation (ex: booking-exc). Donnez le même ID à votre colonne/conteneur dans les paramètres Avancé. Vide = le contenu s\'ouvre sous le bouton.', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => '',
            'placeholder' => 'booking-exc',
            'condition'   => ['trigger_mode' => 'reveal'],
        ]);

        $this->end_controls_section();

        // ══ STYLE ══════════════════════════════════════════════════════════════

        $this->register_box_style('container', 'Style — Conteneur', '{{WRAPPER}} .bt-pricing');

        $this->register_tabs_nav_style(
            'tab',
            'Style — Onglets',
            '{{WRAPPER}} .bt-pricing__tab',
            '{{WRAPPER}} .bt-pricing__tab--active',
            '{{WRAPPER}} .bt-pricing__tablist',
            [],
            [
                'with_hover'      => true,
                'with_radius'     => true,
                'with_indicator'  => true,
                'with_direction'  => true,
                'with_justify'    => true,
                'with_scroll'     => true,
                'with_breakpoint' => true,
                'with_panel'      => true,
                'panel_sel'       => '{{WRAPPER}} .bt-pricing__panel--active',
            ]
        );

        $this->register_panel_style('panel', 'Style — Panneau', '{{WRAPPER}} .bt-pricing__panel--active');

        $this->register_typography_section(
            'price',
            'Style — Prix',
            '{{WRAPPER}} .bt-pricing__price'
        );

        $this->register_typography_section(
            'note',
            'Style — Note / Acompte',
            '{{WRAPPER}} .bt-pricing__note, {{WRAPPER}} .bt-pricing__deposit'
        );

        $this->register_button_style(
            'discount',
            'Style — Remise (-X%)',
            '{{WRAPPER}} .bt-pricing__discount',
            [],
            []
        );

        $this->register_section_title_style('{{WRAPPER}} .bt-pricing__section-title');

        $this->register_typography_section(
            'section_desc',
            'Style — Description',
            '{{WRAPPER}} .bt-pricing__section-desc',
            [],
            [],
            ['section_description!' => '']
        );

        $this->register_typography_section(
            'btn_title',
            'Style — Titre boutons',
            '{{WRAPPER}} .bt-pricing__btn-title',
            [],
            [],
            ['layout' => 'buttons']
        );

        $this->register_button_style(
            'slot',
            'Style — Boutons pill',
            '{{WRAPPER}} .bt-pricing__slot',
            [],
            ['layout' => 'buttons']
        );

        $this->register_button_style(
            'trigger_btn',
            'Style — Bouton déclencheur',
            '{{WRAPPER}} .bt-pricing__trigger',
            [],
            ['trigger_mode!' => 'none']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $rows = $this->get_acf_rows(
            $s['acf_field'],
            sprintf(__('Aucun forfait trouvé pour le champ « %s ».', 'blacktenderscore'), $s['acf_field'])
        );
        if (!$rows) return;

        $layout = $s['layout'] ?? 'tabs';

        // ── Résolution UUID (une seule fois) ─────────────────────────────
        $global_uuid = '';
        $tab_uuids   = [];

        if ($s['show_booking'] === 'yes') {
            if ($s['booking_per_tab'] === 'yes') {
                $subfield = $s['booking_uuid_subfield'] ?: 'exp_booking_uuid';
                foreach ($rows as $row) {
                    $tab_uuids[] = (string) ($row[$subfield] ?? '');
                }
            } else {
                $global_uuid = (string) get_field($s['booking_field'], $post_id);
            }
        }

        $active_uuid  = $global_uuid ?: ($tab_uuids[0] ?? '');
        $currency     = esc_html($s['currency'] ?: '€');
        $uid          = 'bt-pricing-' . $this->get_id();
        $trigger_mode = $s['trigger_mode'] ?? 'none';

        if ($trigger_mode !== 'none') {
            $trigger_label   = esc_html($s['trigger_label'] ?: __('Réserver', 'blacktenderscore'));
            $reveal_target   = $trigger_mode === 'reveal' ? trim((string) ($s['reveal_target_id'] ?? '')) : '';
            $wrap_id         = 'bt-pricing-trigger-' . $this->get_id();
            $data_reveal_tgt = $reveal_target !== '' ? ' data-bt-reveal-target="' . esc_attr($reveal_target) . '"' : '';

            echo '<div class="bt-pricing-trigger-wrap" id="' . esc_attr($wrap_id) . '" data-bt-trigger="' . esc_attr($trigger_mode) . '"' . $data_reveal_tgt . '>';
            echo "<button type=\"button\" class=\"bt-pricing__trigger\" aria-expanded=\"false\">{$trigger_label}</button>";

            if ($trigger_mode === 'reveal') {
                echo '<div class="bt-pricing__reveal-content">';
                echo '<div>'; // inner block for grid-template-rows animation
                $this->render_inner_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $layout, true);
                echo '</div></div>';
            } elseif ($trigger_mode === 'modal') {
                echo '<dialog class="bt-pricing-modal">';
                echo '<div class="bt-pricing-modal__inner">';
                echo '<button type="button" class="bt-pricing-modal__close" aria-label="' . esc_attr__('Fermer', 'blacktenderscore') . '">&times;</button>';
                $this->render_inner_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $layout, true);
                echo '</div></dialog>';
            }

            echo '</div>'; // .bt-pricing-trigger-wrap
        } else {
            $this->render_inner_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $layout, false);
        }
    }

    /**
     * Dispatche vers le bon layout (tabs ou buttons).
     * @param bool $lazy Charge le booking-widget via <template> (chargement différé).
     */
    private function render_inner_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, string $layout, bool $lazy): void {
        if ($layout === 'buttons') {
            $this->render_buttons_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $lazy);
        } else {
            $this->render_tabs_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id, $lazy);
        }
    }

    // ── Layout: Tabs ──────────────────────────────────────────────────────

    /**
     * @param bool $lazy Charge le booking via <template> (chargement différé).
     */
    private function render_tabs_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy = false): void {
        $uuids_attr = '';
        if (!empty($tab_uuids)) {
            $uuids_attr = " data-tab-uuids='" . esc_attr(wp_json_encode($tab_uuids)) . "'";
        }

        echo "<div class=\"bt-pricing bt-pricing--tabs\" id=\"{$uid}\" data-bt-tabs data-bt-panel-class=\"bt-pricing__panel\"{$uuids_attr}>";

        $this->render_heading_block($s);

        // Tab bar
        $discount_key = isset($s['discount_subfield']) && $s['discount_subfield'] !== '' ? $s['discount_subfield'] : 'is_a_discount';
        echo '<div class="bt-pricing__tablist" role="tablist">';
        foreach ($rows as $i => $row) {
            $label   = $this->get_tab_label($row, $i, $s, $currency);
            $discount = $this->get_discount_value($row, $discount_key);
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
            $this->render_price_block($s, $row, $currency);
            echo '</div>';
        }

        // Booking — shared, outside panels
        if ($lazy) {
            $this->render_booking_section_lazy($s, $active_uuid, $post_id);
        } else {
            $this->render_booking_section($s, $active_uuid, $post_id);
        }

        echo '</div>'; // .bt-pricing
    }

    // ── Layout: Buttons (pills) ──────────────────────────────────────────

    /**
     * @param bool $lazy Charge le booking via <template> (chargement différé).
     */
    private function render_buttons_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id, bool $lazy = false): void {
        $uuids_json = !empty($tab_uuids) ? wp_json_encode($tab_uuids) : '';

        echo "<div class=\"bt-pricing bt-pricing--buttons\" id=\"{$uid}\" data-bt-pricing-buttons";
        if ($uuids_json) {
            echo " data-tab-uuids='" . esc_attr($uuids_json) . "'";
        }
        echo '>';

        $this->render_heading_block($s);

        // Title
        $title = $s['buttons_title'] ?? '';
        if ($title) {
            $tag = esc_attr($s['buttons_title_tag'] ?: 'h4');
            echo "<{$tag} class=\"bt-pricing__btn-title\">" . esc_html($title) . "</{$tag}>";
        }

        $discount_key = isset($s['discount_subfield']) && $s['discount_subfield'] !== '' ? $s['discount_subfield'] : 'is_a_discount';
        // Pill buttons — one per repeater row (same data as tabs)
        echo '<div class="bt-pricing__slots">';
        foreach ($rows as $i => $row) {
            $label    = $this->get_tab_label($row, $i, $s, $currency);
            $discount = $this->get_discount_value($row, $discount_key);
            $uuid     = $tab_uuids[$i] ?? $active_uuid;

            echo '<button type="button" class="bt-pricing__slot" data-slot-index="' . $i . '" data-uuid="' . esc_attr($uuid) . '">';
            echo esc_html($label);
            if ($discount > 0) {
                echo ' <span class="bt-pricing__discount">-' . (int) $discount . '%</span>';
            }
            echo '</button>';
        }
        echo '</div>'; // .bt-pricing__slots

        // Price panels — hidden by default, revealed on slot click
        foreach ($rows as $i => $row) {
            $panel_id = "{$uid}-panel-{$i}";
            echo "<div class=\"bt-pricing__panel\" id=\"{$panel_id}\" data-slot-panel=\"{$i}\">";
            $this->render_price_block($s, $row, $currency);
            echo '</div>';
        }

        // Booking — shared, revealed on slot click (lazy si trigger actif)
        if ($s['show_booking'] === 'yes') {
            echo '<div class="bt-pricing__booking-reveal">';
            if ($active_uuid) {
                if ($lazy) {
                    $this->render_booking_section_lazy($s, $active_uuid, $post_id);
                } else {
                    echo $this->render_booking_widget($active_uuid, $post_id, 0);
                }
            } elseif ($this->is_edit_mode()) {
                echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
            }
            echo '</div>';
        }

        echo '</div>'; // .bt-pricing
    }

    // ── Shared render helpers ─────────────────────────────────────────────

    /** Affiche le titre de section + description optionnelle. */
    private function render_heading_block(array $s): void {
        $this->render_section_title($s, 'bt-pricing__section-title');
        $desc = trim((string) ($s['section_description'] ?? ''));
        if ($desc !== '') {
            echo '<div class="bt-pricing__section-desc">' . wp_kses_post($desc) . '</div>';
        }
    }

    private function render_price_block(array $s, array $row, string $currency): void {
        $price    = $row['exp_price']        ?? '';
        $note     = $row['exp_pricing_note'] ?? '';
        $deposit  = $row['exp_deposit']      ?? '';
        $discount_key = isset($s['discount_subfield']) && $s['discount_subfield'] !== '' ? $s['discount_subfield'] : 'is_a_discount';
        $discount = $this->get_discount_value($row, $discount_key);

        if ($price !== '' && ($s['show_price'] ?? 'yes') === 'yes') {
            echo '<div class="bt-pricing__price-block">';
            if ($note && $s['show_note'] === 'yes') {
                echo '<span class="bt-pricing__note">' . esc_html($note) . ' </span>';
            }
            echo '<span class="bt-pricing__price">' . esc_html(number_format((float) $price, 0, ',', ' ')) . ' ' . $currency . '</span>';
            if ($discount > 0) {
                echo ' <span class="bt-pricing__discount">-' . (int) $discount . '%</span>';
            }
            if ($s['show_per_label'] === 'yes') {
                $per_lbl = esc_html($s['per_label'] ?: __('/ pers.', 'blacktenderscore'));
                echo '<span class="bt-pricing__per"> ' . $per_lbl . '</span>';
            }
            echo '</div>';
        }

        if ($deposit && $s['show_deposit'] === 'yes') {
            $dep_lbl = esc_html($s['deposit_label'] ?: __('Acompte :', 'blacktenderscore'));
            echo '<p class="bt-pricing__deposit">' . $dep_lbl . ' <strong>' . esc_html($deposit) . ' ' . $currency . '</strong></p>';
        }
    }

    /**
     * Rend le booking via <template> : le script Regiondo sera injecté par JS au clic.
     * Utilisé uniquement quand trigger_mode est actif (lazy load).
     */
    private function render_booking_section_lazy(array $s, string $active_uuid, int $post_id): void {
        if ($s['show_booking'] !== 'yes') return;

        if ($active_uuid) {
            echo '<div class="bt-pricing__booking-lazy">';
            echo '<template class="bt-booking-tpl">';
            echo $this->render_booking_widget_lazy($active_uuid, $post_id);
            echo '</template>';
            echo '</div>';
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
        }
    }

    /**
     * Génère le HTML du booking-widget sans le <script> Regiondo (injecté par JS).
     */
    private function render_booking_widget_lazy(string $uuid, int $post_id): string {
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

    private function render_booking_section(array $s, string $active_uuid, int $post_id): void {
        if ($s['show_booking'] !== 'yes') return;

        if ($active_uuid) {
            echo $this->render_booking_widget($active_uuid, $post_id, 0);
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Libellé d’onglet / bouton : forfait (durée) et/ou prix selon tab_title_mode.
     */
    private function get_tab_label(array $row, int $i, array $s, string $currency): string {
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

        $mode = $s['tab_title_mode'] ?? 'forfait_et_prix';
        if ($mode === 'prix_seul') {
            return $price_str !== '' ? $price_str : $base;
        }
        return $price_str !== '' ? $base . ' — ' . $price_str : $base;
    }

    /** Valeur du champ remise (%). 0 si absent ou ≤ 0. */
    private function get_discount_value(array $row, string $subfield): int {
        $v = $row[$subfield] ?? null;
        if ($v === null || $v === '') return 0;
        $n = (int) $v;
        return $n > 0 ? $n : 0;
    }

    private function render_booking_widget(string $uuid, int $post_id, int $index): string {
        $widget_id = esc_attr($uuid);
        $style_id  = "bt-booking-styles-{$post_id}-{$index}";

        $script = '';
        if (!self::$regiondo_script_printed) {
            $script = '<script src="https://widgets.regiondo.net/booking/v1/booking-widget.min.js" async></script>';
            self::$regiondo_script_printed = true;
        }

        // Load global custom CSS for all booking widgets
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
}
