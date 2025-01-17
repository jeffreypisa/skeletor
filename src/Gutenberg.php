<?php

use Timber\Site;

/**
 * Class Gutenberg
 */
 
class Gutenberg extends Site {
    
    public function __construct() {
        parent::__construct();

        // Voeg filters en acties toe via de constructor
        add_filter('use_block_editor_for_post_type', [$this, 'disable_gutenberg'], 10, 2);
        add_action('after_setup_theme', [$this, 'add_gutenberg_css']);
    }

    /**
     * Disable the Gutenberg editor for all post types except posts.
     *
     * @param bool   $is_enabled Whether to use the Gutenberg editor.
     * @param string $post_type  Name of WordPress post type.
     * @return bool
     */
    public function disable_gutenberg($is_enabled, $post_type) {
        // Disable for all post types except 'post'
        if ($post_type === 'post') {
            return true;
        }

        return false;
    }

    /**
     * Add custom editor styles for Gutenberg.
     */
    public function add_gutenberg_css() {
        add_theme_support('editor-styles'); // Enable editor styles
        add_editor_style('assets/css/style-editor.css'); // Load custom editor styles
    }
}