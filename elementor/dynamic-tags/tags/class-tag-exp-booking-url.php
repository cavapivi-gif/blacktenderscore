<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag URL — Lien de réservation Regiondo.
 *
 * Lit exp_booking_short_url ou exp_booking_long_url (UUID → URL Regiondo).
 * Catégorie URL : utilisable dans l'attribut href d'un Button, Link, etc.
 *
 * Options :
 *  - forfait   : court (short) ou long
 *  - base_url  : URL de base Regiondo (override si besoin)
 */
class Tag_Exp_Booking_Url extends \Elementor\Core\DynamicTags\Tag {

    public function get_name():       string { return 'bt-exp-booking-url'; }
    public function get_title():      string { return 'BT: URL réservation Regiondo'; }
    public function get_group():      string { return 'blacktenderscore'; }
    public function get_categories(): array  { return ['url']; }

    protected function register_controls(): void {

        $this->add_control('forfait', [
            'label'   => __('Forfait', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'exp_booking_short_url' => __('Trajet court (exp_booking_short_url)', 'blacktenderscore'),
                'exp_booking_long_url'  => __('Trajet long (exp_booking_long_url)', 'blacktenderscore'),
            ],
            'default' => 'exp_booking_short_url',
        ]);

        $this->add_control('open_target', [
            'label'   => __('Cible du lien', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                '_blank' => __('Nouvel onglet', 'blacktenderscore'),
                '_self'  => __('Même onglet', 'blacktenderscore'),
            ],
            'default' => '_blank',
        ]);
    }

    public function render(): void {
        if (!function_exists('get_field')) return;

        $field = $this->get_settings('forfait') ?: 'exp_booking_short_url';
        $uuid  = (string) get_field($field, get_the_ID());

        if (empty($uuid)) return;

        // UUID Regiondo → URL widget
        $url = 'https://www.regiondo.net/booking/widget/' . ltrim($uuid, '/');
        echo esc_url($url);
    }
}
