<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;
use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Profil capitaine.
 *
 * Photo, nom, "depuis", biographie depuis champs ACF.
 * Layout horizontal ou vertical, styles via méthodes partagées.
 */
class Captain extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-captain',
            'title'    => 'BT — Profil capitaine',
            'icon'     => 'eicon-person',
            'keywords' => ['capitaine', 'skipper', 'profil', 'équipage', 'bt'],
            'css'      => ['bt-captain'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls(['title' => __('Votre capitaine', 'blacktenderscore')]);

        $this->add_control('name_acf_field', [
            'label'     => __('Champ ACF nom', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_captain_name',
            'separator' => 'before',
        ]);

        $this->add_control('show_since', [
            'label'        => __('Afficher «depuis»', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('since_acf_field', [
            'label'     => __('Champ ACF (année de début)', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_captain_since',
            'condition' => ['show_since' => 'yes'],
        ]);

        $this->add_control('since_prefix', [
            'label'     => __('Libellé «depuis»', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => __('Skipper depuis', 'blacktenderscore'),
            'condition' => ['show_since' => 'yes'],
        ]);

        $this->add_control('show_bio', [
            'label'        => __('Afficher la bio', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('bio_acf_field', [
            'label'     => __('Champ ACF bio', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_captain_bio',
            'condition' => ['show_bio' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Photo ─────────────────────────────────────────────────────────
        $this->start_controls_section('section_photo', [
            'label' => __('Photo', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_photo', [
            'label'        => __('Afficher la photo', 'blacktenderscore'),
            'type'         => Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('photo_acf_field', [
            'label'     => __('Champ ACF photo (image)', 'blacktenderscore'),
            'type'      => Controls_Manager::TEXT,
            'default'   => 'boat_captain_photo',
            'condition' => ['show_photo' => 'yes'],
        ]);

        $this->add_control('photo_size', [
            'label'     => __('Taille de l\'image', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'thumbnail' => __('Miniature', 'blacktenderscore'),
                'medium'    => __('Moyenne', 'blacktenderscore'),
                'large'     => __('Grande', 'blacktenderscore'),
                'full'      => __('Originale', 'blacktenderscore'),
            ],
            'default'   => 'medium',
            'condition' => ['show_photo' => 'yes'],
        ]);

        $this->end_controls_section();

        // ── Mise en page ──────────────────────────────────────────────────
        $this->start_controls_section('section_layout', [
            'label' => __('Mise en page', 'blacktenderscore'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('card_direction', [
            'label'   => __('Disposition de la carte', 'blacktenderscore'),
            'type'    => Controls_Manager::CHOOSE,
            'options' => [
                'row'    => ['title' => __('Horizontal (photo + infos côte à côte)', 'blacktenderscore'), 'icon' => 'eicon-h-align-left'],
                'column' => ['title' => __('Vertical (photo au-dessus)', 'blacktenderscore'), 'icon' => 'eicon-v-align-top'],
            ],
            'default'   => 'row',
            'selectors' => ['{{WRAPPER}} .bt-captain__card' => 'flex-direction: {{VALUE}}'],
        ]);

        $this->add_responsive_control('card_align_items', [
            'label'   => __('Alignement vertical (mode horizontal)', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'flex-start' => __('Haut', 'blacktenderscore'),
                'center'     => __('Centre', 'blacktenderscore'),
                'flex-end'   => __('Bas', 'blacktenderscore'),
            ],
            'default'   => 'flex-start',
            'condition' => ['card_direction' => 'row'],
            'selectors' => ['{{WRAPPER}} .bt-captain__card' => 'align-items: {{VALUE}}'],
        ]);

        $this->add_responsive_control('card_gap', [
            'label'      => __('Espacement photo / infos', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'default'    => ['size' => 24, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-captain__card' => 'gap: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // ── Styles ────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-captain__section-title');

        $this->register_box_style('card', __('Style — Carte', 'blacktenderscore'), '{{WRAPPER}} .bt-captain__card', ['padding' => 24]);

        // Style — Photo
        $this->start_controls_section('style_photo', [
            'label'     => __('Style — Photo', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['show_photo' => 'yes'],
        ]);

        $this->add_responsive_control('photo_size_px', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 40, 'max' => 300]],
            'default'    => ['size' => 100, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-captain__photo-wrap' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; flex-shrink: 0'],
        ]);

        $this->add_responsive_control('photo_radius', [
            'label'      => __('Border radius', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 50, 'unit' => '%'],
            'selectors'  => [
                '{{WRAPPER}} .bt-captain__photo-wrap' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden',
                '{{WRAPPER}} .bt-captain__photo'      => 'border-radius: {{SIZE}}{{UNIT}}',
            ],
        ]);

        $this->add_control('photo_object_position', [
            'label'     => __('Point focal', 'blacktenderscore'),
            'type'      => Controls_Manager::SELECT,
            'options'   => [
                'center center' => __('Centre', 'blacktenderscore'),
                'center top'    => __('Haut', 'blacktenderscore'),
                'center bottom' => __('Bas', 'blacktenderscore'),
                'left center'   => __('Gauche', 'blacktenderscore'),
                'right center'  => __('Droite', 'blacktenderscore'),
            ],
            'default'   => 'center center',
            'selectors' => ['{{WRAPPER}} .bt-captain__photo' => 'object-position: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // Typographies
        $this->register_typography_section(
            'name',
            __('Style — Nom', 'blacktenderscore'),
            '{{WRAPPER}} .bt-captain__name'
        );

        $this->register_typography_section(
            'since',
            __('Style — «Depuis»', 'blacktenderscore'),
            '{{WRAPPER}} .bt-captain__since',
            [],
            [],
            ['show_since' => 'yes']
        );

        $this->register_typography_section(
            'bio',
            __('Style — Bio', 'blacktenderscore'),
            '{{WRAPPER}} .bt-captain__bio',
            [],
            [],
            ['show_bio' => 'yes']
        );
    }

    // ── Render ───────────────────────────────────────────────────────────────

    protected function render(): void {
        $s       = $this->get_settings_for_display();
        $post_id = get_the_ID();

        if (!$this->acf_required()) return;

        $name  = get_field(sanitize_text_field($s['name_acf_field']  ?: 'boat_captain_name'),  $post_id);
        $photo = get_field(sanitize_text_field($s['photo_acf_field'] ?: 'boat_captain_photo'), $post_id);
        $since = get_field(sanitize_text_field($s['since_acf_field'] ?: 'boat_captain_since'), $post_id);
        $bio   = get_field(sanitize_text_field($s['bio_acf_field']   ?: 'boat_captain_bio'),   $post_id);

        if (!$name && !$bio && !$photo) {
            if ($this->is_edit_mode()) {
                $this->render_placeholder(__('Aucune donnée capitaine trouvée (nom, bio, photo).', 'blacktenderscore'));
            }
            return;
        }

        echo '<div class="bt-captain">';

        $this->render_section_title($s, 'bt-captain__section-title');

        echo '<div class="bt-captain__card">';

        // Photo
        if ($s['show_photo'] === 'yes' && $photo) {
            $photo_size = sanitize_text_field($s['photo_size'] ?: 'medium');
            $img_url    = is_array($photo) ? ($photo['sizes'][$photo_size] ?? $photo['url'] ?? '') : '';
            $img_alt    = is_array($photo) ? ($photo['alt'] ?? '') : '';

            if ($img_url) {
                echo '<div class="bt-captain__photo-wrap">';
                echo '<img class="bt-captain__photo" src="' . esc_url($img_url) . '" alt="' . esc_attr($img_alt) . '" loading="lazy">';
                echo '</div>';
            }
        }

        echo '<div class="bt-captain__info">';

        if ($name) {
            echo '<div class="bt-captain__name">' . esc_html($name) . '</div>';
        }

        if ($s['show_since'] === 'yes' && $since) {
            $prefix = esc_html($s['since_prefix'] ?: __('Skipper depuis', 'blacktenderscore'));
            echo '<p class="bt-captain__since">' . $prefix . ' <strong>' . esc_html($since) . '</strong></p>';
        }

        if ($s['show_bio'] === 'yes' && $bio) {
            // wp_kses_post pour préserver le formatage HTML (p, strong, em...) de l'ACF
            echo '<div class="bt-captain__bio">' . wp_kses_post($bio) . '</div>';
        }

        echo '</div>'; // .bt-captain__info
        echo '</div>'; // .bt-captain__card
        echo '</div>'; // .bt-captain
    }
}
