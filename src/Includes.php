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
       wp_enqueue_style(
           'styles',
           get_template_directory_uri() . '/dist/main-min.css',
           [],
           filemtime(get_template_directory() . '/dist/main-min.css')
       );
       
       wp_enqueue_script(
           'script',
           get_template_directory_uri() . '/dist/main-min.js',
           ['jquery'],
           filemtime(get_template_directory() . '/dist/main-min.js'),
           true
       );
    }
}