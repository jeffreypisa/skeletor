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
            'icon' => 'check',
            'style' => 'light',
            'library' => 'fontawesome',
            'class' => '',
            'container_height' => 20,
            'container_width' => 20,
            'title' => '',
            'url' => '',
            'position' => 'right',
            'gap' => 10,
            'target' => 'self'
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

        // Remove existing width and height attributes and force 100% scaling
        $svg = preg_replace('/\s(width|height)="[^"]*"/', '', $svg);
        $svg = preg_replace('/<svg\b([^>]*)>/', '<svg$1 width="100%" height="100%">', $svg, 1);

        $container_height = $settings['container_height'];
        if (is_numeric($container_height)) {
            $container_height .= 'px';
        }

        $container_width = $settings['container_width'];
        if ($container_width === 'auto') {
            $container_width = $container_height;
        } elseif (is_numeric($container_width)) {
            $container_width .= 'px';
        }

        $gap = $settings['gap'];
        if (is_numeric($gap)) {
            $gap .= 'px';
        } else {
            $gap = sanitize_text_field($gap);
        }

        $allowed_targets = ['self', 'blank'];
        $target = in_array($settings['target'], $allowed_targets, true) ? '_' . $settings['target'] : '_self';

        $title = sanitize_text_field($settings['title']);
        $url = esc_url($settings['url']);
        $allowed_positions = ['left', 'right', 'top', 'bottom'];
        $position = in_array($settings['position'], $allowed_positions, true) ? $settings['position'] : 'right';

        $context = [
            'svg' => $svg,
            'class' => trim($settings['class']),
            'container_width' => $container_width,
            'container_height' => $container_height,
            'title' => $title,
            'url' => $url,
            'position' => $position,
            'gap' => $gap,
            'target' => $target
        ];

        return Timber::compile('icon/icon.twig', $context);
    }
}
