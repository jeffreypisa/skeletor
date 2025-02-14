<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components_Heading
 */
class Components_Heading extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('heading', [$this, 'render_heading']));
		return $twig;
	}

	public function render_heading($text, $options = []) {
		if (empty($text)) {
			return null;
		}
	
		global $polylang_strings;
	
		$defaults = [
			'level' => 'h2',
			'class' => '',
			'inview_animation' => '',
			'translatable' => false,
		];
		$settings = array_merge($defaults, $options);
	
		if (!empty($settings['translatable']) && function_exists('pll__')) {
			// Maak een veilige sleutel voor Polylang
			$key = strtolower(trim(strip_tags($text)));
			$key = preg_replace('/[^a-z0-9]+/i', '_', $key); // Vervang niet-alfanumerieke tekens door _
			$key = trim($key, '_'); // Verwijder extra underscores aan begin/einde
	
			if (!isset($polylang_strings[$key])) {
				$polylang_strings[$key] = $text;
				// Sla buffer tijdelijk op als optie in de database
				update_option('polylang_temp_strings', $polylang_strings);
			}
	
			$text = pll__($text);
		}
	
		return Timber::compile('heading/heading.twig', [
			'text' => $text,
			'level' => $settings['level'],
			'class' => $settings['class'] . ($settings['inview_animation'] ? ' animate-' . $settings['inview_animation'] : ''),
			'inview_animation' => $settings['inview_animation'],
		]);
	}
}
