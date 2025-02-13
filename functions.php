<?php

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Include your theme-specific files.
require_once __DIR__ . '/src/ACFScripts.php';
require_once __DIR__ . '/src/Gutenberg.php';
require_once __DIR__ . '/src/HideUsers.php';
require_once __DIR__ . '/src/Includes.php';
require_once __DIR__ . '/src/Menu.php';
require_once __DIR__ . '/src/StarterSite.php';
// require_once __DIR__ . '/src/ThemeSpecific.php';
require_once __DIR__ . '/src/WPAdmin.php';

require_once __DIR__ . '/components/Components.php';

Timber\Timber::init();

// Sets the directories (inside your theme) to find .twig files.
Timber::$dirname = [ 'templates', 'views' ];

// Initialize your classes
new ACFScripts();
new Gutenberg();
new HideUsers();
new Includes();
new Menu();
new StarterSite();
// new ThemeSpecific();
new WPAdmin();

new Components_Accordion();
new Components_Button();
new Components_Heading();
new Components_Image();
new Components_SocialMediaLinks();
new Components_Swiper();
new Components_Text();

if (function_exists('acf_add_local_field_group')) {
    new ACF();
}