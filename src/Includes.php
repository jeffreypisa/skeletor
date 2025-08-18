<?php

use Timber\Site;

/**
 * Class Includes
 */
 
class Includes extends Site {

    public function __construct() {
        parent::__construct();

        // Voeg de actie toe via de constructor
        add_action('wp_enqueue_scripts', [$this, 'add_theme_scripts']);
    }

    /**
     * Enqueue theme styles and scripts.
     */
    public function add_theme_scripts() {
        // Voeg de hoofdstijl toe
       $css_file = get_template_directory() . '/dist/main-min.css';
       wp_enqueue_style(
           'styles',
           get_template_directory_uri() . '/dist/main-min.css',
           [],
           file_exists($css_file) ? filemtime($css_file) : null
       );

       $js_file = get_template_directory() . '/dist/main-min.js';
       wp_enqueue_script(
           'script',
           get_template_directory_uri() . '/dist/main-min.js',
           ['jquery'],
           file_exists($js_file) ? filemtime($js_file) : null,
           true
       );

       wp_localize_script(
           'script',
           'ajaxurl',
           [
               'url' => admin_url('admin-ajax.php'),
               'nonce' => wp_create_nonce('skeletor_filter_nonce'),
           ]
       );

    }
}
