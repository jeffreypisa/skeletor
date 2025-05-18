<?php

use Timber\Site;

/**
 * Class PolylangStrings
 */
class PolylangStrings extends Site {

	public function __construct() {
		add_action('after_setup_theme', [$this, 'register_polylang_strings']);
		parent::__construct();
	}

	/**
	 * Registreer opgeslagen Polylang strings
	 */
	public function register_polylang_strings() {
		if (function_exists('pll_register_string')) {
			// Haal de bestaande strings op
			$existing_strings = get_option('polylang_registered_strings', []);
			$new_strings = get_option('polylang_temp_strings', []);
	
			// Combineer de oude en nieuwe strings
			$polylang_strings = array_merge($existing_strings, $new_strings);
	
			if (!empty($polylang_strings)) {
				foreach ($polylang_strings as $key => $value) {
					pll_register_string($key, $value, 'Theme Strings');
				}
			}
	
			// **Sla de bijgewerkte lijst op**
			update_option('polylang_registered_strings', $polylang_strings);
	
			// **Leeg de tijdelijke lijst om duplicaten te voorkomen**
			delete_option('polylang_temp_strings');
		}
	}
}