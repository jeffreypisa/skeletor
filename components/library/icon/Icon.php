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
<<<<<<< HEAD
            'position' => 'right',
            'gap' => 10,
            'target' => 'self'
=======
            'title_position' => 'right',
            'title_level' => 'span',
            'title_class' => '',
            'icon_class' => '',
            'gap' => 10,
            'target' => 'self',
            'color_primary' => '',
            'color_secondary' => ''
>>>>>>> add-link-support-to-icon-component-nthuuj
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
        $icon_class = trim($settings['icon_class']);
        $color_primary = sanitize_text_field($settings['color_primary']);
        $color_secondary = sanitize_text_field($settings['color_secondary']);
        $style_attr = '';
        if ($color_primary !== '') {
            $style_attr .= '--fa-primary-color:' . $color_primary . ';';
        }
        if ($color_secondary !== '') {
            $style_attr .= '--fa-secondary-color:' . $color_secondary . ';';
        }
        $class_attr = $icon_class !== '' ? ' class="' . esc_attr($icon_class) . '"' : '';
        $style_attr = $style_attr !== '' ? ' style="' . esc_attr($style_attr) . '"' : '';
        $svg = preg_replace('/<svg\b([^>]*)>/', '<svg$1' . $class_attr . $style_attr . ' width="100%" height="100%">', $svg, 1);

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
<<<<<<< HEAD
        $position = in_array($settings['position'], $allowed_positions, true) ? $settings['position'] : 'right';
=======
        $title_position = in_array($settings['title_position'], $allowed_positions, true) ? $settings['title_position'] : 'right';
        $allowed_levels = ['span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $title_level = in_array($settings['title_level'], $allowed_levels, true) ? $settings['title_level'] : 'span';
        $title_class = sanitize_text_field($settings['title_class']);
>>>>>>> add-link-support-to-icon-component-nthuuj

        $context = [
            'svg' => $svg,
            'class' => trim($settings['class']),
            'container_width' => $container_width,
            'container_height' => $container_height,
            'title' => $title,
            'url' => $url,
<<<<<<< HEAD
            'position' => $position,
=======
            'title_position' => $title_position,
            'title_level' => $title_level,
            'title_class' => $title_class,
>>>>>>> add-link-support-to-icon-component-nthuuj
            'gap' => $gap,
            'target' => $target
        ];

        return Timber::compile('icon/icon.twig', $context);
    }
}
