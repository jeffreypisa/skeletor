<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components_Accordion
 */
class Components_Accordion extends Site {
	public function __construct() {
		// Voeg Timber-filters toe
		add_filter('timber/twig', [$this, 'add_to_twig']);

		// Zorg dat je altijd de ouderconstructor aanroept
		parent::__construct();
	}

	/**
	 * Voeg de `accordion`-functie toe aan Twig
	 *
	 * @param \Twig\Environment $twig
	 * @return \Twig\Environment
	 */
	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('accordion', [$this, 'render_accordion']));
		return $twig;
	}

	/**
	 * Render een accordion via Timber
	 *
	 * @param array $accordion_items
	 * @param string $id_prefix
	 * @return string
	 */
	public function render_accordion($accordion_items, $id_prefix = 'accordion') {
		// Controleer of er items zijn; zo niet, retourneer niets
		if (!$accordion_items || !is_array($accordion_items)) {
			return null;
		}

		// Voeg een unieke ID toe aan de accordion
		$accordion_id = $id_prefix . '-' . uniqid();

		// Render de accordion met de items
		return Timber::compile('components/accordion.twig', [
			'accordion_id' => $accordion_id,
			'items' => $accordion_items,
		]);
	}
}