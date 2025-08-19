<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components_Icon
 */
class Components_Icon extends Site {
    public function __construct() {
        add_filter('timber/twig', [$this, 'add_to_twig']);
        parent::__construct();
    }

    public function add_to_twig($twig) {
        $twig->addFunction(new TwigFunction('icon', [$this, 'render_icon']));
        return $twig;
    }

    public function render_icon($icon, $params = []) {
        if (!is_string($icon) || $icon === '') {
            return null;
        }

        $defaults = [
            'title' => '',
            'url' => '',
            'position' => 'right',
            'icon_style' => 'light',
            'class' => ''
        ];

        $settings = array_merge($defaults, $params);
        $settings['icon'] = $icon;

        return Timber::render('icon/icon.twig', $settings);
    }
}
