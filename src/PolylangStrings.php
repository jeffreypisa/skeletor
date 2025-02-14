<?php

use Timber\Site;

/**
 * Class PolylangStrings
 */
class PolylangStrings extends Site {

	public function __construct() {
		add_action('init', [$this, 'register_polylang_strings']);
		parent::__construct();
	}

	/**
	 * Registreer opgeslagen Polylang strings
	 */
	public function register_polylang_strings() {
		if (function_exists('pll_register_string')) {
			$polylang_strings = get_option('polylang_temp_strings', []);

			if (!empty($polylang_strings)) {
				foreach ($polylang_strings as $key => $value) {
					pll_register_string($key, $value, 'Theme Strings');
				}
				delete_option('polylang_temp_strings'); // Buffer opschonen
			}
		}
	}
}