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
            'icon_wrapper_height' => 20,
            'icon_wrapper_width' => 20,
            'icon_wrapper_class' => '',
            'title' => '',
            'url' => '',
            'title_position' => 'right',
            'title_level' => 'span',
            'title_class' => '',
            'icon_class' => '',
            'gap' => 10,
            'target' => 'self',
            'color_primary' => '',
            'color_secondary' => ''
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

        $icon_wrapper_height = $settings['icon_wrapper_height'];
        if (is_numeric($icon_wrapper_height)) {
            $icon_wrapper_height .= 'px';
        }

        $icon_wrapper_width = $settings['icon_wrapper_width'];
        if ($icon_wrapper_width === 'auto') {
            $icon_wrapper_width = $icon_wrapper_height;
        } elseif (is_numeric($icon_wrapper_width)) {
            $icon_wrapper_width .= 'px';
        }

        $icon_wrapper_class = sanitize_text_field($settings['icon_wrapper_class']);

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
        $title_position = in_array($settings['title_position'], $allowed_positions, true) ? $settings['title_position'] : 'right';
        $allowed_levels = ['span', 'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];
        $title_level = in_array($settings['title_level'], $allowed_levels, true) ? $settings['title_level'] : 'span';
        $title_class = sanitize_text_field($settings['title_class']);

        $context = [
            'svg' => $svg,
            'class' => trim($settings['class']),
            'icon_wrapper_width' => $icon_wrapper_width,
            'icon_wrapper_height' => $icon_wrapper_height,
            'icon_wrapper_class' => $icon_wrapper_class,
            'title' => $title,
            'url' => $url,
            'title_position' => $title_position,
            'title_level' => $title_level,
            'title_class' => $title_class,
            'gap' => $gap,
            'target' => $target
        ];

        return Timber::compile('icon/icon.twig', $context);
    }
}
