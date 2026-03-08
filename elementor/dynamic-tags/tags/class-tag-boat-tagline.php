<?php
namespace BlackTenders\Elementor\DynamicTags;

defined('ABSPATH') || exit;

/**
 * Dynamic Tag — Accroche courte du bateau (boat_tagline).
 */
class Tag_Boat_Tagline extends Abstract_BT_Tag {

    public function get_name():       string { return 'bt-boat-tagline'; }
    public function get_title():      string { return 'BT: Accroche bateau'; }
    public function get_categories(): array  { return ['text']; }

    public function render(): void {
        $this->print_value((string) $this->acf('boat_tagline'));
    }
}
