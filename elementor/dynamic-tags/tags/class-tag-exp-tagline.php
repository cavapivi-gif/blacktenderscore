<?php
namespace BT_Regiondo\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Accroche courte de l'excursion (exp_tagline).
 */
class Tag_Exp_Tagline extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-exp-tagline'; }
    public function get_title():      string { return 'BT: Accroche excursion'; }
    public function get_categories(): array  { return ['text']; }

    public function render(): void {
        $this->print_value((string) $this->acf('exp_tagline'));
    }
}
