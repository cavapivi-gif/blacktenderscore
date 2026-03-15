<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

defined('ABSPATH') || exit;

/**
 * BtHeadingControls — Titre de section + style associé.
 *
 * Méthodes :
 *   register_section_title_controls($defaults) — inline, dans une section ouverte
 *   register_section_title_style($selector)    — ouvre sa propre section Style
 */
trait BtHeadingControls {

    /**
     * Ajoute les controls "Titre de section" + "Balise" dans la section courante.
     * À appeler ENTRE start_controls_section() et end_controls_section().
     *
     * Controls générés :
     *   section_title     (TEXT  — supporte les dynamic tags)
     *   section_title_tag (SELECT — h2/h3/h4/p/span)
     *
     * @param array $defaults  'title' (string), 'tag' (string, défaut 'h3')
     */
    protected function register_section_title_controls(array $defaults = []): void {
        $this->add_control('section_title', [
            'label'   => __('Titre de section', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['title'] ?? '',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control('section_title_tag', [
            'label'   => __('Balise', 'blacktenderscore'),
            'type'    => Controls_Manager::SELECT,
            'options' => [
                'h2'   => 'H2',
                'h3'   => 'H3',
                'h4'   => 'H4',
                'p'    => 'p',
                'span' => 'span',
            ],
            'default' => $defaults['tag'] ?? 'h3',
        ]);
    }

    /**
     * Enregistre une section Style complète pour le titre de section.
     * Conditionnée à section_title non vide.
     *
     * Controls générés (prefixés "heading_") :
     *   heading_typography, heading_color, heading_align, heading_spacing
     *
     * @param string       $selector         Sélecteur CSS — ex: '{{WRAPPER}} .bt-faq__section-title'
     * @param array|string $spacing_selector Optionnel. Si fourni, l'espacement bas est appliqué à ce(s) sélecteur(s)
     *                                       au lieu du titre (ex: trigger repliable pour garder l'icône en haut).
     *                                       Array de [ selector => 'margin-bottom: {{SIZE}}{{UNIT}}' ] ou string.
     */
    protected function register_section_title_style(string $selector, $spacing_selector = null): void {
        $this->start_controls_section('style_heading', [
            'label'     => __('Style — Titre section', 'blacktenderscore'),
            'tab'       => Controls_Manager::TAB_STYLE,
            'condition' => ['section_title!' => ''],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'heading_typography',
            'selector' => $selector,
        ]);

        $this->add_control('heading_color', [
            'label'     => __('Couleur', 'blacktenderscore'),
            'type'      => Controls_Manager::COLOR,
            'selectors' => [$selector => 'color: {{VALUE}}'],
        ]);

        $this->add_responsive_control('heading_align', [
            'label'     => __('Alignement', 'blacktenderscore'),
            'type'      => Controls_Manager::CHOOSE,
            'options'   => [
                'left'   => ['title' => __('Gauche',  'blacktenderscore'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centre', 'blacktenderscore'), 'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Droite',  'blacktenderscore'), 'icon' => 'eicon-text-align-right'],
            ],
            'selectors' => [$selector => 'text-align: {{VALUE}}'],
        ]);

        $spacing_selectors = $spacing_selector !== null
            ? (is_array($spacing_selector) ? $spacing_selector : [$spacing_selector => 'margin-bottom: {{SIZE}}{{UNIT}}'])
            : [$selector => 'margin-bottom: {{SIZE}}{{UNIT}}'];
        $this->add_responsive_control('heading_spacing', [
            'label'      => __('Espacement bas', 'blacktenderscore'),
            'type'       => Controls_Manager::SLIDER,
            'size_units' => ['px', 'em'],
            'selectors'  => $spacing_selectors,
        ]);

        $this->end_controls_section();
    }
}
