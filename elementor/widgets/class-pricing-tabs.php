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

        // ── Contenu général ───────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();

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

        $this->add_control('currency', [
            'label'   => __('Symbole devise', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => '€',
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

        // ══ STYLE ══════════════════════════════════════════════════════════════

        $this->register_box_style('container', 'Style — Conteneur', '{{WRAPPER}} .bt-pricing');

        $this->register_tabs_nav_style(
            'tab',
            'Style — Onglets',
            '{{WRAPPER}} .bt-pricing__tab',
            '{{WRAPPER}} .bt-pricing__tab--active',
            '{{WRAPPER}} .bt-pricing__tablist',
            [],
            ['with_hover' => true, 'with_radius' => true, 'with_indicator' => true]
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

        $this->register_section_title_style('{{WRAPPER}} .bt-pricing__section-title');

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

        $active_uuid = $global_uuid ?: ($tab_uuids[0] ?? '');
        $currency    = esc_html($s['currency'] ?: '€');
        $uid         = 'bt-pricing-' . $this->get_id();

        if ($layout === 'buttons') {
            $this->render_buttons_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id);
        } else {
            $this->render_tabs_layout($s, $rows, $uid, $currency, $tab_uuids, $active_uuid, $post_id);
        }
    }

    // ── Layout: Tabs ──────────────────────────────────────────────────────

    private function render_tabs_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id): void {
        $uuids_attr = '';
        if (!empty($tab_uuids)) {
            $uuids_attr = " data-tab-uuids='" . esc_attr(wp_json_encode($tab_uuids)) . "'";
        }

        echo "<div class=\"bt-pricing bt-pricing--tabs\" id=\"{$uid}\" data-bt-tabs data-bt-panel-class=\"bt-pricing__panel\"{$uuids_attr}>";

        $this->render_section_title($s, 'bt-pricing__section-title');

        // Tab bar
        echo '<div class="bt-pricing__tablist" role="tablist">';
        foreach ($rows as $i => $row) {
            $label  = $this->get_tab_label($row, $i);
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-panel-{$i}";
            $active = $i === 0 ? ' bt-pricing__tab--active' : '';
            $sel    = $i === 0 ? 'true' : 'false';
            $tabi   = $i === 0 ? '0' : '-1';
            echo "<button class=\"bt-pricing__tab{$active}\" id=\"{$tab_id}\" role=\"tab\" aria-selected=\"{$sel}\" aria-controls=\"{$pan_id}\" tabindex=\"{$tabi}\">";
            echo esc_html($label);
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
        $this->render_booking_section($s, $active_uuid, $post_id);

        echo '</div>'; // .bt-pricing
    }

    // ── Layout: Buttons (pills) ──────────────────────────────────────────

    private function render_buttons_layout(array $s, array $rows, string $uid, string $currency, array $tab_uuids, string $active_uuid, int $post_id): void {
        $uuids_json = !empty($tab_uuids) ? wp_json_encode($tab_uuids) : '';

        echo "<div class=\"bt-pricing bt-pricing--buttons\" id=\"{$uid}\" data-bt-pricing-buttons";
        if ($uuids_json) {
            echo " data-tab-uuids='" . esc_attr($uuids_json) . "'";
        }
        echo '>';

        $this->render_section_title($s, 'bt-pricing__section-title');

        // Title
        $title = $s['buttons_title'] ?? '';
        if ($title) {
            $tag = esc_attr($s['buttons_title_tag'] ?: 'h4');
            echo "<{$tag} class=\"bt-pricing__btn-title\">" . esc_html($title) . "</{$tag}>";
        }

        // Pill buttons — one per repeater row (same data as tabs)
        echo '<div class="bt-pricing__slots">';
        foreach ($rows as $i => $row) {
            $label = $this->get_tab_label($row, $i);
            $uuid  = $tab_uuids[$i] ?? $active_uuid;

            // Optionally append price to button label
            if (($s['buttons_show_price'] ?? '') === 'yes') {
                $price = $row['exp_price'] ?? '';
                if ($price !== '') {
                    $label .= ' — ' . number_format((float) $price, 0, ',', ' ') . ' ' . ($s['currency'] ?? '€');
                }
            }

            echo '<button type="button" class="bt-pricing__slot" data-slot-index="' . $i . '" data-uuid="' . esc_attr($uuid) . '">';
            echo esc_html($label);
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

        // Booking — shared, revealed on slot click
        if ($s['show_booking'] === 'yes') {
            echo '<div class="bt-pricing__booking-reveal">';
            if ($active_uuid) {
                echo $this->render_booking_widget($active_uuid, $post_id, 0);
            } elseif ($this->is_edit_mode()) {
                echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
            }
            echo '</div>';
        }

        echo '</div>'; // .bt-pricing
    }

    // ── Shared render helpers ─────────────────────────────────────────────

    private function render_price_block(array $s, array $row, string $currency): void {
        $price   = $row['exp_price']        ?? '';
        $note    = $row['exp_pricing_note'] ?? '';
        $deposit = $row['exp_deposit']      ?? '';

        if ($price !== '') {
            echo '<div class="bt-pricing__price-block">';
            if ($note && $s['show_note'] === 'yes') {
                echo '<span class="bt-pricing__note">' . esc_html($note) . ' </span>';
            }
            echo '<span class="bt-pricing__price">' . esc_html(number_format((float) $price, 0, ',', ' ')) . ' ' . $currency . '</span>';
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

    private function render_booking_section(array $s, string $active_uuid, int $post_id): void {
        if ($s['show_booking'] !== 'yes') return;

        if ($active_uuid) {
            echo $this->render_booking_widget($active_uuid, $post_id, 0);
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function get_tab_label(array $row, int $i): string {
        $term_val = $row['exp_time'] ?? null;

        if ($term_val) {
            if ($term_val instanceof \WP_Term) return $term_val->name;
            if (is_numeric($term_val)) {
                $t = get_term((int) $term_val);
                if ($t && !is_wp_error($t)) return $t->name;
            }
            if (is_array($term_val)) {
                $first = reset($term_val);
                if ($first instanceof \WP_Term) return $first->name;
                if (is_numeric($first)) {
                    $t = get_term((int) $first);
                    if ($t && !is_wp_error($t)) return $t->name;
                }
            }
            if (is_string($term_val) && $term_val !== '') return $term_val;
        }

        $price = $row['exp_price'] ?? '';
        return $price ? "Forfait " . ($i + 1) . " — {$price} €" : "Forfait " . ($i + 1);
    }

    private function render_booking_widget(string $uuid, int $post_id, int $index): string {
        $widget_id = esc_attr($uuid);
        $style_id  = "bt-booking-styles-{$post_id}-{$index}";

        $script = '';
        if (!self::$regiondo_script_printed) {
            $script = '<script src="https://widgets.regiondo.net/booking/v1/booking-widget.min.js" async></script>';
            self::$regiondo_script_printed = true;
        }

        ob_start(); ?>
        <div class="bt-pricing__booking">
            <template id="<?= $style_id ?>">
                <style>
                    .regiondo-booking-widget { max-width: 100% !important; }
                    .regiondo-widget .regiondo-button-addtocart,
                    .regiondo-widget .regiondo-button-checkout { border-radius: 40px; }
                </style>
            </template>
            <booking-widget
                styles-template-id="<?= esc_attr($style_id) ?>"
                widget-id="<?= $widget_id ?>"
                data-wid="1"
                tabindex="0">
            </booking-widget>
            <?= $script ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
