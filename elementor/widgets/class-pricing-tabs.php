<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Tarification par forfait avec tabs.
 *
 * Lit le repeater ACF `tarification_par_forfait` du post courant.
 * Chaque ligne = un tab dont le titre est le nom du terme ACF exp_time.
 * Format: "1h30 — 45 €" (durée + prix).
 *
 * Mode Onboarding (optionnel) :
 *   Affiche un titre "Choisissez vos dispo" + les créneaux horaires disponibles
 *   (champ ACF configurable) avant de révéler le widget de réservation Regiondo.
 *   Le widget Regiondo se charge au clic sur un créneau (lazy-reveal).
 */
class PricingTabs extends AbstractBtWidget {
    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-pricing-tabs',
            'title'    => 'BT — Tarification',
            'icon'     => 'eicon-price-table',
            'keywords' => ['tarif', 'prix', 'forfait', 'réservation', 'booking', 'créneau', 'horaire', 'bt'],
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

        $this->add_control('acf_field', [
            'label'   => __('Champ ACF repeater', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'tarification_par_forfait' => __('Tarification par forfait (tarification_par_forfait)', 'blacktenderscore'),
            ],
            'default' => 'tarification_par_forfait',
        ]);

        $this->add_control('currency', [
            'label'   => __('Symbole devise', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => '€',
        ]);

        $this->add_control('show_note', [
            'label'        => __('Note tarifaire', 'blacktenderscore'),
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
            'label'        => __('Acompte', 'blacktenderscore'),
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

        // ── Onboarding — Choisissez vos dispo ────────────────────────────
        $this->start_controls_section('section_onboarding', [
            'label'     => __('Onboarding — Sélection créneaux', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_CONTENT,
            'condition' => ['show_booking' => 'yes'],
        ]);

        $this->add_control('show_onboarding', [
            'label'        => __('Afficher les créneaux avant réservation', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'description'  => __('Montre les horaires disponibles. Le widget Regiondo se révèle au clic sur un créneau.', 'blacktenderscore'),
            'return_value' => 'yes',
            'default'      => '',
        ]);

        $this->add_control('onboarding_title', [
            'label'     => __('Titre "Choisissez vos dispo"', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Choisissez vos dispo', 'blacktenderscore'),
            'dynamic'   => ['active' => true],
            'condition' => ['show_onboarding' => 'yes'],
        ]);

        $this->add_control('onboarding_title_tag', [
            'label'     => __('Balise titre', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => ['h3' => 'H3', 'h4' => 'H4', 'h5' => 'H5', 'p' => 'p', 'span' => 'span'],
            'default'   => 'h4',
            'condition' => ['show_onboarding' => 'yes', 'onboarding_title!' => ''],
        ]);

        $this->add_control('slot_layout', [
            'label'     => __('Disposition créneaux', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'flex'   => __('Ligne (flex wrap)', 'blacktenderscore'),
                'grid-2' => __('Grille 2 colonnes', 'blacktenderscore'),
                'grid-3' => __('Grille 3 colonnes', 'blacktenderscore'),
            ],
            'default'   => 'flex',
            'condition' => ['show_onboarding' => 'yes'],
        ]);

        $this->add_control('onboarding_slots_field', [
            'label'       => __('Champ ACF créneaux (repeater)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => 'exp_departure_times',
            'description' => __('Repeater ACF avec les horaires. Défaut : exp_departure_times.', 'blacktenderscore'),
            'condition'   => ['show_onboarding' => 'yes'],
        ]);

        $this->add_control('onboarding_slot_subfield', [
            'label'     => __('Sous-champ heure', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'time',
            'condition' => ['show_onboarding' => 'yes'],
        ]);

        $this->add_control('onboarding_fallback_label', [
            'label'       => __('Bouton de repli (si aucun créneau)', 'blacktenderscore'),
            'type'        => Controls_Manager::TEXT,
            'default'     => __('Voir les disponibilités', 'blacktenderscore'),
            'description' => __('Affiché si le champ ACF est vide ou introuvable.', 'blacktenderscore'),
            'condition'   => ['show_onboarding' => 'yes'],
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
            'ob_title',
            'Style — Titre onboarding',
            '{{WRAPPER}} .bt-pricing__ob-title',
            [],
            [],
            ['show_onboarding' => 'yes']
        );

        $this->register_button_style(
            'slot',
            'Style — Créneaux',
            '{{WRAPPER}} .bt-pricing__slot',
            [],
            ['show_onboarding' => 'yes']
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

        // UUID global (fallback si pas per-tab)
        $global_uuid = '';
        if ($s['show_booking'] === 'yes' && $s['booking_per_tab'] !== 'yes') {
            $global_uuid = (string) get_field($s['booking_field'], $post_id);
        }

        // Créneaux onboarding (lus une seule fois — partagés entre tous les tabs)
        $slots = [];
        if ($s['show_onboarding'] === 'yes') {
            $slots = $this->resolve_onboarding_slots($s, $post_id);
        }

        $currency = esc_html($s['currency'] ?: '€');
        $uid      = 'bt-pricing-' . $this->get_id();

        echo "<div class=\"bt-pricing\" id=\"{$uid}\" data-bt-tabs data-bt-panel-class=\"bt-pricing__panel\">";

        $this->render_section_title($s, 'bt-pricing__section-title');

        // ── Tab bar ───────────────────────────────────────────────────────
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

        // ── Panels ────────────────────────────────────────────────────────
        foreach ($rows as $i => $row) {
            $tab_id      = "{$uid}-tab-{$i}";
            $pan_id      = "{$uid}-panel-{$i}";
            $active_cls  = $i === 0 ? ' bt-pricing__panel--active' : '';
            $price       = $row['exp_price']        ?? '';
            $note        = $row['exp_pricing_note'] ?? '';
            $deposit     = $row['exp_deposit']      ?? '';

            // UUID per-tab ou global
            $uuid = $global_uuid;
            if ($s['show_booking'] === 'yes' && $s['booking_per_tab'] === 'yes') {
                $uuid = (string) ($row[$s['booking_uuid_subfield']] ?? '');
            }

            echo "<div class=\"bt-pricing__panel{$active_cls}\" id=\"{$pan_id}\" role=\"tabpanel\" aria-labelledby=\"{$tab_id}\">";

            // Prix principal
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

            // Acompte
            if ($deposit && $s['show_deposit'] === 'yes') {
                $dep_lbl = esc_html($s['deposit_label'] ?: __('Acompte :', 'blacktenderscore'));
                echo '<p class="bt-pricing__deposit">' . $dep_lbl . ' <strong>' . esc_html($deposit) . ' ' . $currency . '</strong></p>';
            }

            // Booking
            if ($s['show_booking'] === 'yes') {
                if ($s['show_onboarding'] === 'yes') {
                    $this->render_onboarding_block($s, $slots, $uuid, $post_id, $i);
                } elseif ($uuid) {
                    echo $this->render_booking_widget($uuid, $post_id, $i);
                } elseif ($this->is_edit_mode()) {
                    echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
                }
            }

            echo '</div>'; // .bt-pricing__panel
        }

        echo '</div>'; // .bt-pricing
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Lit les créneaux depuis le champ ACF configurable.
     * Retourne un tableau de labels (strings).
     */
    private function resolve_onboarding_slots(array $s, int $post_id): array {
        $field_name = sanitize_text_field($s['onboarding_slots_field'] ?: 'exp_departure_times');
        $subfield   = sanitize_key($s['onboarding_slot_subfield'] ?: 'time');
        $rows       = get_field($field_name, $post_id);

        if (empty($rows) || !is_array($rows)) return [];

        $labels = [];
        foreach ($rows as $row) {
            $t = is_array($row) ? ($row[$subfield] ?? '') : (string) $row;
            if ($t !== '') $labels[] = (string) $t;
        }
        return $labels;
    }

    /**
     * Rend le bloc onboarding (titre + créneaux + booking révélé au clic).
     */
    private function render_onboarding_block(array $s, array $slots, string $uuid, int $post_id, int $index): void {
        echo '<div class="bt-pricing__onboarding" data-bt-onboarding>';

        // Titre "Choisissez vos dispo"
        $ob_title = $s['onboarding_title'] ?? '';
        if ($ob_title) {
            $tag = esc_attr($s['onboarding_title_tag'] ?: 'h4');
            echo "<{$tag} class=\"bt-pricing__ob-title\">" . esc_html($ob_title) . "</{$tag}>";
        }

        // Créneaux
        $slot_layout_cls = match ($s['slot_layout'] ?? 'flex') {
            'grid-2' => ' bt-pricing__slots--grid-2',
            'grid-3' => ' bt-pricing__slots--grid-3',
            default  => '',
        };
        echo '<div class="bt-pricing__slots' . $slot_layout_cls . '">';
        if (!empty($slots)) {
            foreach ($slots as $slot_label) {
                echo '<button type="button" class="bt-pricing__slot" data-uuid="' . esc_attr($uuid) . '">';
                echo esc_html($slot_label);
                echo '</button>';
            }
        } else {
            // Fallback CTA si aucun créneau trouvé
            $fallback = esc_html($s['onboarding_fallback_label'] ?: __('Voir les disponibilités', 'blacktenderscore'));
            echo '<button type="button" class="bt-pricing__slot bt-pricing__slot--cta" data-uuid="' . esc_attr($uuid) . '">' . $fallback . '</button>';
        }
        echo '</div>'; // .bt-pricing__slots

        echo '</div>'; // .bt-pricing__onboarding

        // Widget Regiondo — révélé avec animation au clic sur un créneau
        if ($uuid) {
            echo '<div class="bt-pricing__booking-reveal">';
            echo $this->render_booking_widget($uuid, $post_id, $index);
            echo '</div>';
        } elseif ($this->is_edit_mode()) {
            echo '<div class="bt-widget-placeholder">Widget de réservation Regiondo (UUID requis)</div>';
        }
    }

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
