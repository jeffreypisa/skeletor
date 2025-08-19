<?php
use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Icon extends Site {
    public function __construct() {
        add_filter('timber/twig', [$this, 'add_to_twig']);
        parent::__construct();
    }

    public function add_to_twig($twig) {
        $twig->addFunction(new TwigFunction('icon', [$this, 'render_icon']));
        return $twig;
    }

    public function render_icon($args = []) {
        if (is_string($args)) {
            $args = ['icon' => $args];
        }

        $defaults = [
            'icon' => null,
            'style' => 'solid',
            'library' => 'fontawesome',
            'class' => '',
            'width' => 'auto',
            'height' => 30,
            'container_width' => 'auto'
        ];

        $settings = array_merge($defaults, $args);

        if (empty($settings['icon'])) {
            return null;
        }

        $icon = sanitize_file_name($settings['icon']);
        $style = sanitize_file_name($settings['style']);
        $library = sanitize_file_name($settings['library']);

        $file_path = __DIR__ . "/library/{$library}/{$style}/{$icon}.svg";

        if (!file_exists($file_path)) {
            return null;
        }

        $svg = file_get_contents($file_path);

        // Remove existing width and height attributes
        $svg = preg_replace('/\s(width|height)="[^"]*"/', '', $svg);

        // Build new SVG attribute string
        $attr = ' height="' . esc_attr($settings['height']) . '"';
        if ($settings['width'] !== 'auto') {
            $attr .= ' width="' . esc_attr($settings['width']) . '"';
        }

        $svg = preg_replace('/<svg\b([^>]*)>/', '<svg$1' . $attr . '>', $svg, 1);

        $container_width = $settings['container_width'];
        if ($container_width !== 'auto' && is_numeric($container_width)) {
            $container_width .= 'px';
        }

        $context = [
            'svg' => $svg,
            'class' => trim('icon ' . $settings['class']),
            'container_width' => $container_width
        ];

        return Timber::compile('icon/icon.twig', $context);
    }
}
