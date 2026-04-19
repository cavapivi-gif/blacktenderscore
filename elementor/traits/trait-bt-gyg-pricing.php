<?php
/**
 * Trait BtGygPricing — Rendu des tarifs GYG dans le widget PricingBody.
 *
 * Lit les disponibilités depuis le cache transient bt_gyg_avail_{productId}
 * (mis à jour lors du sync Regiondo ou d'un appel GYG get-availabilities).
 *
 * @package BlackTenders\Elementor\Traits
 */

namespace BlackTenders\Elementor\Traits;

defined('ABSPATH') || exit;

trait BtGygPricing {

    /**
     * Rendu des tarifs en mode GYG.
     *
     * Lit les disponibilités depuis le cache transient bt_gyg_avail_{productId}_{date}
     * (date du jour par défaut). Regroupe par date et affiche ADULT/CHILD avec prix.
     * Prix en centimes → divisés par 100 pour l'affichage EUR.
     */
    protected function render_gyg_pricing(): void {
        $product_id = $this->get_settings_for_display('gyg_product_id');

        if (empty($product_id)) {
            echo '<p class="bt-notice bt-notice--info">'
               . esc_html__('Aucun produit GYG configuré.', 'blacktenderscore')
               . '</p>';
            return;
        }

        $safe_id  = sanitize_key($product_id);
        $today    = gmdate('Y-m-d');

        // Chercher dans les 7 prochains jours en cas de cache absent pour aujourd'hui
        $availabilities = false;
        for ($i = 0; $i <= 7 && $availabilities === false; $i++) {
            $date      = gmdate('Y-m-d', strtotime("+{$i} days"));
            $cache_key = 'bt_gyg_avail_' . $safe_id . '_' . $date;
            $cached    = get_transient($cache_key);
            if (is_array($cached) && !empty($cached)) {
                $availabilities = $cached;
                break;
            }
        }

        if (empty($availabilities)) {
            echo '<p class="bt-notice bt-notice--info">'
               . esc_html__('Disponibilités non disponibles pour le moment.', 'blacktenderscore')
               . '</p>';
            return;
        }

        // Regrouper les créneaux par date
        $by_date = [];
        foreach ($availabilities as $slot) {
            $dt   = $slot['dateTime'] ?? '';
            if (empty($dt)) continue;

            try {
                $datetime_obj = new \DateTime($dt);
            } catch (\Throwable $e) {
                continue;
            }

            $day_key  = $datetime_obj->format('Y-m-d');
            $time_str = $datetime_obj->format('H:i');

            if (!isset($by_date[$day_key])) {
                $by_date[$day_key] = [];
            }

            $prices    = $slot['pricesByCategory']['retailPrices'] ?? [];
            $vacancies = (int) ($slot['vacancies'] ?? 0);
            $currency  = $slot['currency'] ?? 'EUR';

            $by_date[$day_key][] = [
                'time'      => $time_str,
                'vacancies' => $vacancies,
                'currency'  => $currency,
                'prices'    => $prices,
            ];
        }

        if (empty($by_date)) {
            echo '<p class="bt-notice bt-notice--info">'
               . esc_html__('Aucun créneau disponible.', 'blacktenderscore')
               . '</p>';
            return;
        }

        echo '<div class="bt-gyg-pricing">';

        foreach ($by_date as $day => $slots) {
            $day_fmt = (new \DateTime($day))->format('d/m/Y');
            echo '<div class="bt-gyg-pricing__day">';
            echo '<div class="bt-gyg-pricing__date">' . esc_html($day_fmt) . '</div>';

            foreach ($slots as $slot) {
                echo '<div class="bt-gyg-pricing__slot">';
                echo '<span class="bt-gyg-pricing__time">' . esc_html($slot['time']) . '</span>';

                if ($slot['vacancies'] > 0) {
                    echo '<span class="bt-gyg-pricing__vacancies">'
                       . sprintf(
                           esc_html__('%d places disponibles', 'blacktenderscore'),
                           $slot['vacancies']
                         )
                       . '</span>';
                }

                if (!empty($slot['prices'])) {
                    echo '<ul class="bt-gyg-pricing__prices">';

                    foreach ($slot['prices'] as $price_entry) {
                        $category    = sanitize_text_field($price_entry['category'] ?? 'ADULT');
                        $amount_cts  = (int) ($price_entry['price'] ?? 0);
                        $amount_eur  = number_format($amount_cts / 100, 2, ',', ' ');
                        $symbol      = $slot['currency'] === 'EUR' ? '€' : esc_html($slot['currency']);

                        // Libellés catégories traduits
                        $labels = [
                            'ADULT'  => __('Adulte', 'blacktenderscore'),
                            'CHILD'  => __('Enfant', 'blacktenderscore'),
                            'SENIOR' => __('Senior', 'blacktenderscore'),
                            'YOUTH'  => __('Jeune', 'blacktenderscore'),
                        ];
                        $label = $labels[$category] ?? $category;

                        echo '<li class="bt-gyg-pricing__price-item">';
                        echo '<span class="bt-gyg-pricing__category">' . esc_html($label) . '</span>';
                        echo '<span class="bt-gyg-pricing__amount">'
                           . esc_html($amount_eur) . '&nbsp;' . esc_html($symbol)
                           . '</span>';
                        echo '</li>';
                    }

                    echo '</ul>';
                }

                echo '</div>'; // .bt-gyg-pricing__slot
            }

            echo '</div>'; // .bt-gyg-pricing__day
        }

        echo '</div>'; // .bt-gyg-pricing
    }
}
