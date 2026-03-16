<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Tarifs Body (ancre réceptrice).
 *
 * Widget léger à placer là où le contenu de BT — Tarifs doit apparaître.
 * Quand BT — Tarifs est en mode "reveal", le JS déplace le contenu ici
 * et copie la classe elementor-element-XXX du widget source pour que
 * les sélecteurs {{WRAPPER}} continuent de matcher.
 *
 * Usage Elementor :
 *   1. Placer ce widget dans la colonne / section cible
 *   2. Dans BT — Tarifs, activer le mode reveal → cible "BT Tarifs Body"
 *   3. Le contenu apparaîtra automatiquement dans ce body au clic
 */
class PricingBody extends AbstractBtWidget {

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-pricing-body',
            'title'    => 'BT — Tarifs Body',
            'icon'     => 'eicon-download-kit',
            'keywords' => ['tarif', 'body', 'loader', 'ancre', 'prix', 'bt'],
            'css'      => ['bt-boat-pricing', 'bt-pricing-tabs', 'bt-quote-form'],
        ];
    }

    protected function register_controls(): void {
        // Aucun contrôle : ce widget est un récepteur passif.
    }

    protected function render(): void {
        $is_edit = $this->is_edit_mode();

        echo '<div class="bt-pricing-body" data-bt-pricing-body>';

        if ($is_edit) {
            echo '<div class="bt-widget-placeholder" style="padding:24px;text-align:center;opacity:.6">';
            echo esc_html__('BT — Tarifs Body', 'blacktenderscore');
            echo '<br><small>' . esc_html__('Le contenu du widget BT — Tarifs apparaîtra ici.', 'blacktenderscore') . '</small>';
            echo '</div>';
        }

        echo '</div>';
    }
}
