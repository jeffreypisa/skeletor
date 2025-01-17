<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components
 */
class Components extends Site {
	public function __construct() {
		// Voeg Timber-filters toe
		add_filter('timber/twig', [$this, 'add_to_twig']);

		// Zorg dat je altijd de ouderconstructor aanroept
		parent::__construct();
	}

	/**
	 * Voeg de `button`-functie toe aan Twig
	 *
	 * @param \Twig\Environment $twig
	 * @return \Twig\Environment
	 */
	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('button', [$this, 'render_button']));
		return $twig;
	}

	/**
	 * Render een knop via Timber
	 *
	 * @param array $button_field
	 * @return string
	 */
	public function render_button($button_field) {
		$button_data = $this->get_button_data($button_field);
		return Timber::compile('components/button.twig', $button_data);
	}

	/**
	 * Verkrijg knopdata
	 *
	 * @param array|null $button_field
	 * @return array|null
	 */
	private function get_button_data($button_field) {
		if (!$button_field) {
			return null;
		}
		return [
			'url' => $button_field['url'] ?? '#',
			'title' => $button_field['title'] ?? 'Klik hier',
			'style' => $button_field['style'] ?? 'primary',
		];
	}
}