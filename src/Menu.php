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
                register_nav_menu('header_menu', __('Header menu'));
                register_nav_menu('mobile_menu', __('Mobiel menu'));

                $footer_menu_count = (int) apply_filters('skeletor_footer_menu_count', 1);
                $footer_menu_count = max(1, min(8, $footer_menu_count));
                for ($i = 1; $i <= $footer_menu_count; $i++) {
                        register_nav_menu('footer_menu_' . $i, sprintf(__('Footer menu %d'), $i));
                }

                if ((bool) apply_filters('skeletor_show_topbar', false)) {
                        register_nav_menu('service_menu', __('Service menu'));
                }

                $menu_mode = apply_filters('skeletor_header_menu_mode', 'normal');
                $menu_mode = is_string($menu_mode) ? strtolower(trim($menu_mode)) : 'normal';
                if ($menu_mode === 'dropdown') {
                        $menu_mode = 'mega_dropdown';
                }

                if ($menu_mode === 'takeover') {
                        register_nav_menu('takeover_primary_menu', __('Take-over primair menu'));
                        $secondary_count = (int) apply_filters('skeletor_takeover_secondary_menu_count', 1);
                        $secondary_count = max(1, min(8, $secondary_count));

                        for ($i = 1; $i <= $secondary_count; $i++) {
                                register_nav_menu('takeover_secondary_menu_' . $i, sprintf(__('Take-over secundair menu %d'), $i));
                        }
                }
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
