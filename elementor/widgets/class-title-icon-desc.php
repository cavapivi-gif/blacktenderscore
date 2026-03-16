<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Titre + Icône + Descriptif.
 *
 * Titre de section, icône (champ ACF image ou SVG), descriptif.
 * Icône : champ ACF → <img> si image, <svg> inline si SVG (pour contrôle couleur).
 * Réutilise les traits : section_title, icon_style_section, collapsible.
 */
class TitleIconDesc extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-title-icon-desc',
            'title'    => 'BT — Titre + Icône + Descriptif',
            'icon'     => 'eicon-info-circle',
            'keywords' => ['titre', 'icône', 'descriptif', 'bloc', 'bt'],
            'css'      => ['bt-title-icon-desc'],
        ];
    }

    protected function register_controls(): void {

        $this->start_controls_section('section_content', [
            'label' => __('Champs', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls();
        $this->register_collapsible_section_control();

        $this->add_control('icon_heading', [
            'label'     => __('Icône', 'blacktenderscore'),
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $icon_opts = static::acf_field_options('icon', ['' => __('— Choisir un champ ACF (image / SVG) —', 'blacktenderscore')]);
        $this->add_control('icon_acf_field', [
            'label'   => __('Champ ACF icône', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => $icon_opts,
            'default' => array_key_first($icon_opts) ?: '',
        ]);

        $this->add_control('icon_position', [
            'label'   => __('Position de l\'icône', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'row'         => ['title' => __('Gauche', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'row-reverse' => ['title' => __('Droite', 'blacktenderscore'), 'icon' => 'eicon-h-align-right'],
                'column'      => ['title' => __('Au-dessus', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'row',
            'selectors' => ['{{WRAPPER}} .bt-tid__inner' => 'flex-direction: {{VALUE}}'],
        ]);

        $this->add_control('description', [
            'label'   => __('Descriptif', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXTAREA,
            'default' => '',
            'dynamic' => ['active' => true],
        ]);

        $this->end_controls_section();

        // Espacement bas sur le trigger en repliable (écart titre+icône / descriptif), sinon sur le titre
        $this->register_section_title_style('{{WRAPPER}} .bt-tid__section-title', [
            '{{WRAPPER}} .bt-tid--collapsible .bt-collapsible-block__trigger' => 'margin-bottom: {{SIZE}}{{UNIT}}',
            '{{WRAPPER}} .bt-tid:not(.bt-tid--collapsible) .bt-tid__section-title' => 'margin-bottom: {{SIZE}}{{UNIT}}',
        ]);
        $this->register_icon_style_section(
            'icon',
            __('Style — Icône', 'blacktenderscore'),
            '{{WRAPPER}} .bt-tid__icon',
            ['size' => 32]
        );

        $this->start_controls_section('style_desc', [
            'label' => __('Style — Descriptif', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'desc_typography',
            'selector' => '{{WRAPPER}} .bt-tid__desc',
        ]);
        $this->add_control('desc_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-tid__desc' => 'color: {{VALUE}}'],
        ]);
        $this->end_controls_section();
    }

    protected function render(): void {
        $s           = $this->get_settings_for_display();
        $collapsible = isset($s['collapsible_mode']) && $s['collapsible_mode'] !== '';
        $icon_key    = isset($s['icon_acf_field']) ? (string) $s['icon_acf_field'] : '';
        $post_id     = (int) get_the_ID();
        $icon_html   = $this->render_icon_from_acf($icon_key, $post_id);
        $has_icon    = $icon_html !== '';
        $desc        = $s['description'] ?? '';

        echo '<div class="bt-tid' . ($collapsible ? ' bt-tid--collapsible' : '') . '">';

        if ($collapsible) {
            $this->render_collapsible_section_open($s, 'bt-tid__section-title');
        } else {
            $this->render_section_title($s, 'bt-tid__section-title');
        }

        echo '<div class="bt-tid__inner">';
        if ($has_icon) {
            echo '<span class="bt-tid__icon" aria-hidden="true">' . $icon_html . '</span>';
        }
        if ($desc !== '') {
            echo '<div class="bt-tid__desc">' . wp_kses_post(nl2br($desc)) . '</div>';
        }
        echo '</div>'; // .bt-tid__inner

        if ($collapsible) {
            $this->render_collapsible_section_close();
        }

        echo '</div>'; // .bt-tid
    }

    /**
     * Icône depuis un champ ACF : image → <img>, SVG → <svg> inline (pour style couleur).
     *
     * @return string HTML sécurisé (img ou svg inline) ou chaîne vide
     */
    private function render_icon_from_acf(string $field_name, int $post_id): string {
        if ($field_name === '' || !function_exists('get_field')) {
            return '';
        }

        $raw = get_field($field_name, $post_id);
        if ($raw === null || $raw === false || $raw === '') {
            return '';
        }

        $url = '';
        if (is_array($raw)) {
            $url = $raw['url'] ?? (isset($raw['ID']) ? (wp_get_attachment_url((int) $raw['ID']) ?: '') : '');
        } elseif (is_numeric($raw)) {
            $url = wp_get_attachment_url((int) $raw) ?: '';
        } elseif (is_string($raw)) {
            $trimmed = trim($raw);
            if (strpos($trimmed, '<svg') === 0) {
                return $this->kses_svg($trimmed);
            }
            if (filter_var($raw, FILTER_VALIDATE_URL)) {
                $url = $raw;
            }
        }

        if ($url === '') {
            return '';
        }

        $ext = strtolower((string) pathinfo(parse_url($url, PHP_URL_PATH) ?: '', PATHINFO_EXTENSION));
        if ($ext === 'svg') {
            $svg_content = $this->fetch_svg_content($url);
            return $svg_content !== '' ? $this->kses_svg($svg_content) : '<img src="' . esc_url($url) . '" alt="" loading="lazy" />';
        }

        $alt = is_array($raw) && isset($raw['alt']) ? (string) $raw['alt'] : '';
        return '<img src="' . esc_url($url) . '" alt="' . esc_attr($alt) . '" loading="lazy" />';
    }

    private function fetch_svg_content(string $url): string {
        if (strpos($url, '://') === false) {
            $path = ABSPATH . ltrim(wp_parse_url($url, PHP_URL_PATH) ?: '', '/');
            if (is_readable($path)) {
                $c = file_get_contents($path);
                return is_string($c) ? $c : '';
            }
        }
        $r = wp_safe_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($r) || wp_remote_retrieve_response_code($r) !== 200) {
            return '';
        }
        $body = wp_remote_retrieve_body($r);
        return is_string($body) ? $body : '';
    }

    /** Autorise svg/path etc. pour affichage inline (couleur via CSS). */
    private function kses_svg(string $html): string {
        $allowed = [
            'svg'   => ['xmlns' => true, 'viewbox' => true, 'width' => true, 'height' => true, 'fill' => true, 'class' => true],
            'path'  => ['d' => true, 'fill' => true, 'stroke' => true, 'class' => true],
            'g'     => ['fill' => true, 'class' => true],
        ];
        return wp_kses($html, $allowed);
    }
}
