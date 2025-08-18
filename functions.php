<?php

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Include your theme-specific files.
require_once __DIR__ . '/src/ACFScripts.php';
require_once __DIR__ . '/src/CustomWooCommerce.php';
require_once __DIR__ . '/src/Gutenberg.php';
require_once __DIR__ . '/src/HideUsers.php';
require_once __DIR__ . '/src/Includes.php';
require_once __DIR__ . '/src/Menu.php';
require_once __DIR__ . '/src/PolylangStrings.php';
require_once __DIR__ . '/src/StarterSite.php';
require_once __DIR__ . '/src/ThemeSpecific.php';
require_once __DIR__ . '/src/WPAdmin.php';


require_once __DIR__ . '/components/Components.php';

Timber\Timber::init();


// Initialize your classes
new ACFScripts();
new CustomWooCommerce();
new Gutenberg();
new HideUsers();
new Includes();
new Menu();
new PolylangStrings();
new StarterSite();
new ThemeSpecific();
new WPAdmin();

remove_action('wp_head', 'wp_generator');

if (function_exists('acf_add_local_field_group')) {
    new ACF();
}

if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('init', function () {
        $polylang_strings = get_option('polylang_registered_strings', []);
        error_log('Polylang geregistreerde strings: ' . print_r($polylang_strings, true));
    });
}

