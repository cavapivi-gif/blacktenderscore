<?php
namespace BT_Regiondo\Frontend\Widget;

defined('ABSPATH') || exit;

class Renderer {

    public function render(array $tickets): string {
        if (empty($tickets)) return '';

        ob_start();

        if (count($tickets) === 1) {
            $template = BT_REGIONDO_DIR . 'frontend/widget/templates/single.php';
            $ticket   = $tickets[0];
            require $template;
        } else {
            $template = BT_REGIONDO_DIR . 'frontend/widget/templates/tabs.php';
            require $template;
        }

        return ob_get_clean();
    }
}