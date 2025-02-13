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
		register_nav_menu('footermenu', __('Footer menu'));
		register_nav_menu('mobielmenu', __('Mobiel menu'));
	}
}