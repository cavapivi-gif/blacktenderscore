<?php
namespace BlackTenders\Elementor\Widgets;

use BlackTenders\Elementor\AbstractBtWidget;
use BlackTenders\Elementor\Traits\BtSharedControls;

defined('ABSPATH') || exit;

/**
 * Widget Elementor — Profil capitaine.
 *
 * Affiche la fiche profil du capitaine/skipper du bateau :
 * photo, nom, année de début, biographie.
 */
class Captain extends AbstractBtWidget {

    use BtSharedControls;

    protected static function get_bt_config(): array {
        return [
            'id'       => 'bt-captain',
            'title'    => 'BT — Profil capitaine',
            'icon'     => 'eicon-person',
            'keywords' => ['capitaine', 'skipper', 'profil', 'équipage', 'bt'],
        ];
    }

    // ── Controls ─────────────────────────────────────────────────────────────

    protected function register_controls(): void {

        // ── Contenu ───────────────────────────────────────────────────────
        $this->start_controls_section('section_content', [
            'label' => __('Contenu', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $this->register_section_title_controls(['title' => __('Votre capitaine', 'blacktenderscore')]);

        $this->add_control('show_photo', [
            'label'        => __('Afficher la photo', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('photo_acf_field', [
            'label'   => __('Champ ACF photo (image)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'boat_captain_photo',
        ]);

        $this->add_control('photo_size', [
            'label'   => __('Taille de l\'image', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'options' => [
                'thumbnail' => __('Miniature', 'blacktenderscore'),
                'medium'    => __('Moyenne', 'blacktenderscore'),
                'large'     => __('Grande', 'blacktenderscore'),
                'full'      => __('Originale', 'blacktenderscore'),
            ],
            'default' => 'medium',
        ]);

        $this->add_control('name_acf_field', [
            'label'   => __('Champ ACF nom', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'boat_captain_name',
        ]);

        $this->add_control('show_since', [
            'label'        => __('Afficher \'depuis\'', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('since_acf_field', [
            'label'   => __('Champ ACF (année de début)', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'boat_captain_since',
        ]);

        $this->add_control('since_prefix', [
            'label'   => __('Libellé \'depuis\'', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Skipper depuis',
        ]);

        $this->add_control('show_bio', [
            'label'        => __('Afficher la bio', 'blacktenderscore'),
            'type'         => \Elementor\Controls_Manager::SWITCHER,
            'return_value' => 'yes',
            'default'      => 'yes',
        ]);

        $this->add_control('bio_acf_field', [
            'label'   => __('Champ ACF bio', 'blacktenderscore'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'boat_captain_bio',
        ]);

        $this->end_controls_section();

        // ── Style ─────────────────────────────────────────────────────────
        $this->register_section_title_style('{{WRAPPER}} .bt-captain__section-title');

        $this->register_box_style('card', 'Style — Carte', '{{WRAPPER}} .bt-captain__card', ['padding' => 24]);

        // Style — Photo
        $this->start_controls_section('style_photo', [
            'label' => __('Style — Photo', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_responsive_control('photo_radius', [
            'label'      => __('Rayon de bordure', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px', '%'],
            'default'    => ['size' => 50, 'unit' => '%'],
            'selectors'  => ['{{WRAPPER}} .bt-captain__photo' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);

        $this->add_responsive_control('photo_size_px', [
            'label'      => __('Taille', 'blacktenderscore'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'size_units' => ['px'],
            'range'      => ['px' => ['min' => 40, 'max' => 200]],
            'default'    => ['size' => 100, 'unit' => 'px'],
            'selectors'  => ['{{WRAPPER}} .bt-captain__photo-wrap' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}'],
        ]);

        $this->end_controls_section();

        // Style — Nom
        $this->start_controls_section('style_name', [
            'label' => __('Style — Nom', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'name_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-captain__name',
        ]);

        $this->add_control('name_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-captain__name' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();

        // Style — Bio
        $this->start_controls_section('style_bio', [
            'label' => __('Style — Bio', 'blacktenderscore'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_group_control(\Elementor\Group_Control_Typography::get_type(), [
            'name'     => 'bio_typography',
            'label'    => __('Typographie', 'blacktenderscore'),
            'selector' => '{{WRAPPER}} .bt-captain__bio',
        ]);

        $this->add_control('bio_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'selectors' => ['{{WRAPPER}} .bt-captain__bio' => 'color: {{VALUE}}'],
        ]);

        $this->end_controls_section();
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

        // Rien à afficher
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
            $prefix = esc_html($s['since_prefix'] ?: 'Skipper depuis');
            echo '<p class="bt-captain__since">' . $prefix . ' ' . esc_html($since) . '</p>';
        }

        if ($s['show_bio'] === 'yes' && $bio) {
            echo '<p class="bt-captain__bio">' . esc_html($bio) . '</p>';
        }

        echo '</div>'; // .bt-captain__info
        echo '</div>'; // .bt-captain__card
        echo '</div>'; // .bt-captain
    }
}
