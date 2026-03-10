<?php
namespace BlackTenders\Elementor;

defined('ABSPATH') || exit;

/**
 * Classe de base pour tous les widgets Elementor BlackTenders.
 * Inspirée de SjWidgetBase (studiojaereview — cavapivi-gif).
 *
 * Chaque widget implémente get_bt_config() et obtient gratuitement :
 *   get_name(), get_title(), get_icon(), get_categories(), get_keywords(),
 *   get_script_depends(), get_style_depends()
 *
 * Plus des helpers de render : acf_required(), get_acf_rows(),
 * render_placeholder(), render_section_title(), acf_field_options().
 *
 * Exemple minimal :
 *
 *   class MonWidget extends AbstractBtWidget {
 *       use BtSharedControls;
 *
 *       protected static function get_bt_config(): array {
 *           return [
 *               'id'       => 'bt-mon-widget',
 *               'title'    => 'BT — Mon Widget',
 *               'icon'     => 'eicon-code',
 *               'keywords' => ['mon', 'widget', 'bt'],
 *               'js'       => ['bt-elementor'],
 *           ];
 *       }
 *
 *       protected function register_controls(): void {
 *           $this->start_controls_section('section_content', [...]);
 *           $this->register_section_title_controls();
 *           $this->end_controls_section();
 *           $this->register_section_title_style('{{WRAPPER}} .bt-mon-widget__title');
 *       }
 *
 *       protected function render(): void {
 *           if (!$this->acf_required()) return;
 *           $s    = $this->get_settings_for_display();
 *           $rows = $this->get_acf_rows('mon_champ_acf');
 *           if (!$rows) return;
 *           $this->render_section_title($s, 'bt-mon-widget__title');
 *           // ...
 *       }
 *   }
 */
abstract class AbstractBtWidget extends \Elementor\Widget_Base {

    // ── Config (à surcharger dans chaque widget) ──────────────────────────────

    /**
     * Retourne la configuration statique du widget.
     *
     * @return array{
     *   id:        string,     — ex: 'bt-faq-accordion'
     *   title:     string,     — ex: 'BT — FAQ'
     *   icon:      string,     — ex: 'eicon-toggle'
     *   keywords?: string[],
     *   js?:       string[],   — handles de scripts  (get_script_depends)
     *   css?:      string[],   — handles de styles   (get_style_depends)
     * }
     */
    protected static function get_bt_config(): array {
        return [];
    }

    // ── Elementor identity — auto-dérivées depuis get_bt_config ───────────────

    public function get_name(): string {
        return static::get_bt_config()['id'] ?? parent::get_name();
    }

    public function get_title(): string {
        return static::get_bt_config()['title'] ?? parent::get_title();
    }

    public function get_icon(): string {
        return static::get_bt_config()['icon'] ?? 'eicon-code';
    }

    /** Toujours 'blacktenderscore' — pas besoin de le redéfinir dans chaque widget. */
    public function get_categories(): array {
        return ['blacktenderscore'];
    }

    public function get_keywords(): array {
        return static::get_bt_config()['keywords'] ?? [];
    }

    public function get_script_depends(): array {
        return static::get_bt_config()['js'] ?? [];
    }

    public function get_style_depends(): array {
        return static::get_bt_config()['css'] ?? [];
    }

    // ── Selector dictionary ───────────────────────────────────────────────────

    /**
     * Dictionnaire de sélecteurs CSS pour ce widget.
     * Populer dans le constructeur de chaque widget.
     *
     *   $this->selectors['title']     = '{{WRAPPER}} .bt-faq__section-title';
     *   $this->selectors['container'] = '{{WRAPPER}} .bt-faq';
     */
    protected array $selectors = [];

    /**
     * Résout un sélecteur depuis le dictionnaire.
     * Retourne une chaîne vide si la clé n'existe pas (pas d'erreur silencieuse).
     */
    protected function sel(string $key): string {
        return $this->selectors[$key] ?? '';
    }

    // ── Render helpers ────────────────────────────────────────────────────────

    /**
     * Affiche un placeholder dans l'éditeur Elementor.
     * Utilise la classe CSS .bt-widget-placeholder définie dans bt-elementor.css.
     */
    protected function render_placeholder(string $message): void {
        echo '<p class="bt-widget-placeholder">' . esc_html($message) . '</p>';
    }

    /**
     * Vérifie qu'ACF Pro est actif.
     * Affiche un placeholder et retourne false si ACF manque.
     */
    protected function acf_required(): bool {
        if (function_exists('get_field')) return true;
        $this->render_placeholder(__('ACF Pro requis.', 'blacktenderscore'));
        return false;
    }

    /**
     * Récupère les lignes d'un champ ACF (repeater ou relationship).
     * Affiche un placeholder dans l'éditeur si le champ est vide ou introuvable.
     *
     * @param string      $field_name  Nom du champ ACF
     * @param string|null $empty_label Message placeholder si vide (optionnel)
     * @return array|null  Rows si trouvés, null sinon
     */
    protected function get_acf_rows(string $field_name, ?string $empty_label = null): ?array {
        $rows = get_field($field_name, get_the_ID());
        if (!empty($rows)) return (array) $rows;

        if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $label = $empty_label ?? sprintf(
                /* translators: %s = nom du champ ACF */
                __('Champ « %s » vide ou introuvable.', 'blacktenderscore'),
                $field_name
            );
            $this->render_placeholder($label);
        }
        return null;
    }

    /**
     * Affiche le titre de section si le setting est défini.
     *
     * @param array  $s       Résultat de get_settings_for_display()
     * @param string $class   Classe CSS à appliquer (ex: 'bt-faq__section-title')
     * @param string $key     Clé du setting titre  (défaut: 'section_title')
     * @param string $tag_key Clé du setting balise (défaut: 'section_title_tag')
     */
    protected function render_section_title(
        array  $s,
        string $class,
        string $key     = 'section_title',
        string $tag_key = 'section_title_tag'
    ): void {
        if (empty($s[$key])) return;
        $tag = esc_attr($s[$tag_key] ?: 'h3');
        echo "<{$tag} class=\"{$class}\">" . esc_html($s[$key]) . "</{$tag}>";
    }

    /**
     * Retourne true si le contexte est l'éditeur Elementor.
     */
    protected function is_edit_mode(): bool {
        return \Elementor\Plugin::$instance->editor->is_edit_mode();
    }

    /**
     * Capture le HTML généré par Icons_Manager::render_icon() et le retourne sous forme de chaîne.
     * Retourne '' si l'icône n'a pas de valeur (évite d'appeler ob_start inutilement).
     *
     * Usage :
     *   $icon_html = $this->capture_icon($s['my_icon']);
     *   $icon_html = $this->capture_icon($s['my_icon'], ['aria-hidden' => 'true', 'class' => 'my-class']);
     *
     * @param array $icon_settings  Valeur d'un contrôle ICONS Elementor (['value' => ..., 'library' => ...])
     * @param array $attributes     Attributs HTML supplémentaires passés à render_icon()
     */
    protected function capture_icon(array $icon_settings, array $attributes = ['aria-hidden' => 'true']): string {
        if (empty($icon_settings['value'])) return '';
        ob_start();
        \Elementor\Icons_Manager::render_icon($icon_settings, $attributes);
        return (string) ob_get_clean();
    }

    // ── ACF field discovery ───────────────────────────────────────────────────

    /**
     * Retourne tous les champs ACF dont le nom contient $keyword (insensible à la casse).
     * Utile pour générer des options SELECT dynamiques dans register_controls().
     *
     * @param string $keyword  Mot-clé (ex: 'faq', 'itinerary')
     * @param array  $fallback Options si ACF absent ou aucun résultat
     */
    protected static function acf_field_options(string $keyword, array $fallback = []): array {
        if (!function_exists('acf_get_field_groups')) return $fallback;

        $options = [];
        foreach (acf_get_field_groups() as $group) {
            foreach (acf_get_fields($group['key']) ?: [] as $field) {
                if (stripos($field['name'], $keyword) === false) continue;
                $options[$field['name']] = sprintf(
                    '%s (%s) [%s]',
                    $field['label'],
                    $field['name'],
                    $field['type']
                );
            }
        }
        return $options ?: $fallback;
    }
}
