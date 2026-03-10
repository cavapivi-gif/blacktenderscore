<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Tarification par forfait avec tabs.
 *
 * Lit le repeater ACF `tarification_par_forfait` du post courant.
 * Chaque ligne = un tab dont le titre est le nom du terme ACF exp_time.
 * Format: "1h30 — 45 €" (durée + prix).
 * Optionnellement affiche le calendrier de réservation Regiondo (UUID ACF).
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

        // ── Content ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'tarification_par_forfait' => __('Tarification par forfait (tarification_par_forfait)', 'blacktenderscore'),
            ],
            'default' => 'tarification_par_forfait',
        ]);

        $this->add_control('show_deposit', [
            'label'        => __('Afficher l\'acompte', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('show_note', [
            'label'        => __('Afficher la note tarifaire', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('per_label', [
            'label'   => __('Libellé "par pers."', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => __('/ pers.', 'blacktenderscore'),
        ]);

        $this->add_control('deposit_label', [
            'label'     => __('Libellé "Acompte"', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => __('Acompte :', 'blacktenderscore'),
            'condition' => ['show_deposit' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Réservation ───────────────────────────────────────────────────
        $this->start_controls_section('section_booking', [
            'label' => __('Réservation Regiondo', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_booking', [
            'label'        => __('Afficher le widget de réservation', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'description'  => __('Intègre le calendrier Regiondo via le UUID stocké dans le champ ACF.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('booking_field', [
            'label'     => __('Champ UUID Regiondo', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::SELECT,
            'options'   => [
                'exp_booking_short_url' => __('Forfait court (exp_booking_short_url)', 'blacktenderscore'),
                'exp_booking_long_url'  => __('Forfait long (exp_booking_long_url)', 'blacktenderscore'),
            ],
            'default'   => 'exp_booking_short_url',
            'condition' => ['show_booking' => 'yes'],
        ]);

        $this->add_control('booking_per_tab', [
            'label'       => __('UUID par tab (champ sous-repeater)', 'blacktenderscore'),
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'description' => __('Si chaque forfait a son propre UUID dans le repeater (champ exp_booking_uuid), activez cette option.', 'blacktenderscore'),
            'return_value'=> 'yes',
            'default'     => '',
            'condition'   => ['show_booking' => 'yes'],
        ]);

        $this->add_control('booking_uuid_subfield', [
            'label'     => __('Nom du sous-champ UUID', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::TEXT,
            'default'   => 'exp_booking_uuid',
            'condition' => ['show_booking' => 'yes', 'booking_per_tab' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Style — Tabs ──────────────────────────────────────────────────
        $this->start_controls_section('style_tabs', [
            'label' => __('Style — Tabs', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'tab_typography',
            'selector' => '{{WRAPPER}} .bt-pricing__tab',
        ]);

        $this->add_control('tab_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-pricing__tab' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('tab_bg', [
            'label'     => __('Fond', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-pricing__tab' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_control('tab_active_color', [
            'label'     => __('Couleur (actif)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-pricing__tab--active' => 'color: {{VALUE}}'],
        ]);

        $this->add_control('tab_active_bg', [
            'label'     => __('Fond (actif)', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-pricing__tab--active' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('tab_padding', [
            'label'      => __('Padding tab', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-pricing__tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Border::get_type(), [
            'name'     => 'tab_border',
            'selector' => '{{WRAPPER}} .bt-pricing__tab',
        ]);

        $this->end_controls_section();

        // ── Style — Contenu ───────────────────────────────────────────────
        $this->start_controls_section('style_panel', [
            'label' => __('Style — Panneau', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'price_typography',
            'label'    => __('Typographie prix', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-pricing__price',
        ]);

        $this->add_control('price_color', [
            'label'     => __('Couleur prix', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-pricing__price' => 'color: {{VALUE}}'],
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'note_typography',
            'label'    => __('Typographie note', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-pricing__note, {{WRAPPER}} .bt-pricing__deposit',
        ]);

        $this->add_control('panel_bg', [
            'label'     => __('Fond du panneau', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-pricing__panel' => 'background-color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('panel_padding', [
            'label'      => __('Padding panneau', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::DIMENSIONS,
            'size_units' => ['px', 'em'],
            'selectors'  => ['{{WRAPPER}} .bt-pricing__panel' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}'],
        ]);

        $this->end_controls_section();
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

        // UUID global (fallback si pas per-tab)
        $global_uuid = '';
        if ($s['show_booking'] === 'yes' && $s['booking_per_tab'] !== 'yes') {
            $global_uuid = (string) get_field($s['booking_field'], $post_id);
        }

        $uid = 'bt-pricing-' . $this->get_id();

        echo "<div class=\"bt-pricing\" id=\"{$uid}\" data-bt-tabs data-bt-panel-class=\"bt-pricing__panel\">";

        // ── Tab bar ───────────────────────────────────────────────────────
        echo '<div class="bt-pricing__tablist" role="tablist">';
        foreach ($rows as $i => $row) {
            $label  = $this->get_tab_label($row, $i);
            $tab_id = "{$uid}-tab-{$i}";
            $pan_id = "{$uid}-panel-{$i}";
            $active = $i === 0 ? ' bt-pricing__tab--active' : '';
            echo "<button class=\"bt-pricing__tab{$active}\" id=\"{$tab_id}\" role=\"tab\" aria-selected=\"" . ($i === 0 ? 'true' : 'false') . "\" aria-controls=\"{$pan_id}\" tabindex=\"" . ($i === 0 ? '0' : '-1') . "\">";
            echo esc_html($label);
            echo '</button>';
        }
        echo '</div>';

        // ── Panels ────────────────────────────────────────────────────────
        foreach ($rows as $i => $row) {
            $tab_id  = "{$uid}-tab-{$i}";
            $pan_id  = "{$uid}-panel-{$i}";
            $active_panel = $i === 0 ? ' bt-pricing__panel--active' : '';
            $price   = $row['exp_price']        ?? '';
            $note    = $row['exp_pricing_note'] ?? '';
            $deposit = $row['exp_deposit']      ?? '';

            // UUID per-tab
            $uuid = $global_uuid;
            if ($s['show_booking'] === 'yes' && $s['booking_per_tab'] === 'yes') {
                $uuid = (string) ($row[$s['booking_uuid_subfield']] ?? '');
            }

            echo "<div class=\"bt-pricing__panel{$active_panel}\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\">";

            // Prix principal
            if ($price !== '') {
                echo '<div class="bt-pricing__price-block">';
                if ($note && $s['show_note'] === 'yes') {
                    echo '<span class="bt-pricing__note">' . esc_html($note) . ' </span>';
                }
                echo '<span class="bt-pricing__price">' . esc_html(number_format((float) $price, 0, ',', ' ')) . ' €</span>';
                $per_lbl = esc_html($s['per_label'] ?: __('/ pers.', 'blacktenderscore'));
                echo '<span class="bt-pricing__per"> ' . $per_lbl . '</span>';
                echo '</div>';
            }

            // Acompte
            if ($deposit && $s['show_deposit'] === 'yes') {
                $dep_lbl = esc_html($s['deposit_label'] ?: __('Acompte :', 'blacktenderscore'));
                echo '<p class="bt-pricing__deposit">' . $dep_lbl . ' <strong>' . esc_html($deposit) . ' €</strong></p>';
            }

            // Booking widget Regiondo
            if ($s['show_booking'] === 'yes' && $uuid) {
                echo $this->render_booking_widget($uuid, $post_id, $i);
            } elseif ($s['show_booking'] === 'yes' && $this->is_edit_mode()) {
                echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
            }

            echo '</div>'; // .bt-pricing__panel
        }

        echo '</div>'; // .bt-pricing
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
            // exp_time est un champ texte libre (ex : "1h30") — l'utiliser directement
            if (is_string($term_val) && $term_val !== '') return $term_val;
        }

        // Fallback : numérotation + prix
        $price = $row['exp_price'] ?? '';
        return $price ? "Forfait " . ($i + 1) . " — {$price} €" : "Forfait " . ($i + 1);
    }

    private function render_booking_widget(string $uuid, int $post_id, int $index): string {
        $widget_id = esc_attr($uuid);
        $style_id  = "bt-booking-styles-{$post_id}-{$index}";

        // Script chargé une seule fois par page, peu importe le nombre de forfaits
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
