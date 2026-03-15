<?php
namespace BlackTenders\Elementor\Traits;

defined('ABSPATH') || exit;

// Les sous-traits sont nommés trait-bt-*.php (pas class-bt-*) donc l'autoloader
// ne peut pas les résoudre automatiquement — on les charge explicitement ici.
require_once __DIR__ . '/trait-bt-heading-controls.php';
require_once __DIR__ . '/trait-bt-layout-controls.php';
require_once __DIR__ . '/trait-bt-nav-controls.php';
require_once __DIR__ . '/trait-bt-typography-controls.php';
require_once __DIR__ . '/trait-bt-content-controls.php';

/**
 * BtSharedControls — Agrège tous les sous-traits de controls Elementor.
 *
 * Chaque sous-trait est spécialisé par domaine fonctionnel.
 * Utiliser ce trait dans les widgets évite d'importer plusieurs traits.
 *
 * ─── Sous-traits disponibles ──────────────────────────────────────────────
 *
 * BtHeadingControls (trait-bt-heading-controls.php)
 *   register_section_title_controls($defaults)      — inline
 *   register_section_title_style($selector)         — section complète
 *
 * BtLayoutControls (trait-bt-layout-controls.php)
 *   register_grid_layout_controls($sel, $defaults, $label)              — section complète
 *   register_box_style($prefix, $label, $sel, $defaults, $condition)
 *   register_separator_controls($prefix, $label, $sel, $defaults, $condition)
 *     → inclut l’option « Saut de ligne » ; si activée, le widget doit ajouter .bt-sep--line-break sur l’élément
 *   register_stars_style($prefix, $label, $sel, $defaults, $condition)
 *   register_icon_style_section($prefix, $label, $sel, $defaults, $condition) — section complète icône
 *   register_gap_control($control_id, $label, $selectors[], $default)   — inline (section ouverte)
 *   register_badge_colors($prefix, $label, $sel, $defaults, $condition) — inline (section ouverte)
 *
 * BtNavControls (trait-bt-nav-controls.php)
 *   register_tabs_nav_style($prefix, $label, $tab_sel, $active_sel, $tablist_sel, $condition, $options)
 *     $options : 'with_hover', 'with_radius', 'with_indicator', 'with_panel' + 'panel_sel'
 *   register_panel_style($prefix, $label, $selector, $condition)
 *   register_item_3state_style($prefix, $label, $item_sel, ...)
 *   register_button_style($prefix, $label, $selector, $defaults, $condition)
 *
 * BtTypographyControls (trait-bt-typography-controls.php)
 *   register_typography_section($prefix, $label, $selector, $options, ...)
 *
 * BtContentControls (trait-bt-content-controls.php)
 *   register_cta_button_controls($prefix, $label, $defaults) — inline
 *   register_collapsible_section_control() — Rendre repliable (mobile / PC / les deux)
 *
 * ─── Exemples d'appel depuis register_controls() ──────────────────────────
 *
 *   $this->register_section_title_controls(['title' => 'Nos avis']);
 *   $this->register_section_title_style('{{WRAPPER}} .bt-widget__title');
 *   $this->register_grid_layout_controls('{{WRAPPER}} .bt-widget__grid', ['columns' => 3]);
 *   $this->register_box_style('card', 'Style — Cartes', '{{WRAPPER}} .bt-widget__card');
 *   $this->register_typography_section('title', 'Style — Titre', '{{WRAPPER}} .bt-widget__title');
 *   $this->register_tabs_nav_style('tab', 'Style — Onglets', '{{WRAPPER}} .bt-widget__tab', '{{WRAPPER}} .bt-widget__tab--active');
 *   $this->register_panel_style('panel', 'Style — Panneau', '{{WRAPPER}} .bt-widget__panel');
 *   $this->register_item_3state_style('item', 'Style — Items', '{{WRAPPER}} .bt-widget__item');
 *   $this->register_cta_button_controls('cta', 'Bouton CTA');
 */
trait BtSharedControls {
    use BtHeadingControls;
    use BtLayoutControls;
    use BtNavControls;
    use BtTypographyControls;
    use BtContentControls;
}
