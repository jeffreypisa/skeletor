<?php

use Timber\Site;

/**
 * Class Menu
 */
 
class Menu extends Site {

	public function __construct() {
		parent::__construct();

		// Voeg de actie toe via de constructor
		add_action('after_setup_theme', [$this, 'register_nav_menus']);
	}

	/**
	 * Registreer navigatiemenu's
	 */
        public function register_nav_menus() {
                register_nav_menu('headermenu', __('Header menu'));
                register_nav_menu('servicemenu', __('Service menu'));
                register_nav_menu('footermenu', __('Footer menu'));
                register_nav_menu('mobielmenu', __('Mobiel menu'));
        }
}

/**
 * Verplaats Menu's uit "Weergave" naar hoofdmenu
 * en maak menu-beheer beschikbaar voor Editor (Redacteur),
 * zonder toegang tot andere theme-instellingen.
 */
 
 /**
  * Admin menu aanpassen (alleen voor Editor)
  */
 add_action('admin_menu', function () {
 
         // Alleen voor Editors
         if (!current_user_can('editor') || current_user_can('administrator')) {
                 return;
         }
 
         // Menu's onder Weergave weg
         remove_submenu_page('themes.php', 'nav-menus.php');
 
         // Weergave volledig verbergen
         remove_menu_page('themes.php');
 
         // Menu's op hoofdniveau
         add_menu_page(
                 "Menu's",
                 "Menu's",
                 'edit_theme_options',
                 'nav-menus.php',
                 '',
                 'dashicons-menu',
                 58
         );
 
 }, 999);
 
 /**
  * Geef Editors toegang tot menu-beheer
  */
 add_action('init', function () {
 
         $role = get_role('editor');
 
         if ($role && !$role->has_cap('edit_theme_options')) {
                 $role->add_cap('edit_theme_options');
         }
 
 });
 
 /**
  * Beperk Editors tot alleen Menu's binnen theme-gebied
  */
 add_action('admin_init', function () {
 
         // Alleen Editors
         if (!current_user_can('editor') || current_user_can('administrator')) {
                 return;
         }
 
         $screen = function_exists('get_current_screen') ? get_current_screen() : null;
 
         if (!$screen) {
                 return;
         }
 
         // Alleen Menu's toegestaan
         if ($screen->id !== 'nav-menus' && str_starts_with($screen->base, 'appearance')) {
                 wp_safe_redirect(admin_url('nav-menus.php'));
                 exit;
         }
 
 });