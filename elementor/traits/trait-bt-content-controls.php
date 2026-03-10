<?php
namespace BlackTenders\Elementor\Traits;

use Elementor\Controls_Manager;

defined('ABSPATH') || exit;

/**
 * BtContentControls — Controls de contenu inline réutilisables.
 *
 * Méthodes :
 *   register_cta_button_controls($prefix, $label, $defaults) — inline, dans une section ouverte
 */
trait BtContentControls {

    /**
     * Ajoute les controls bouton/CTA dans la section courante.
     * À appeler ENTRE start_controls_section() et end_controls_section().
     *
     * Controls générés (prefixés par $prefix) :
     *   {prefix}_text   TEXT
     *   {prefix}_link   URL (avec dynamic tags)
     *
     * @param string $prefix   Préfixe IDs
     * @param string $label    Titre du séparateur visuel dans l'éditeur
     * @param array  $defaults 'text' (string), 'url' (string)
     */
    protected function register_cta_button_controls(string $prefix, string $label, array $defaults = []): void {
        $this->add_control("{$prefix}_heading", [
            'label'     => $label,
            'type'      => Controls_Manager::HEADING,
            'separator' => 'before',
        ]);

        $this->add_control("{$prefix}_text", [
            'label'   => __('Texte du bouton', 'blacktenderscore'),
            'type'    => Controls_Manager::TEXT,
            'default' => $defaults['text'] ?? '',
            'dynamic' => ['active' => true],
        ]);

        $this->add_control("{$prefix}_link", [
            'label'       => __('Lien', 'blacktenderscore'),
            'type'        => Controls_Manager::URL,
            'dynamic'     => ['active' => true],
            'placeholder' => 'https://',
            'default'     => ['url' => $defaults['url'] ?? '', 'is_external' => false, 'nofollow' => false],
        ]);
    }
}
