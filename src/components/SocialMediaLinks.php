<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_SocialMediaLinks extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('social_media_links', [$this, 'render_links']));
		return $twig;
	}

	/**
	 * Render sociale media links via Timber
	 *
	 * @param array $links Associatieve array met sociale media naam, URL en optionele suffix.
	 * @param array $options Opties zoals het tonen van iconen.
	 * @return string HTML-output.
	 */
	public function render_links($links, $options = []) {
		// Controleer of er links zijn
		if (empty($links)) {
			return null;
		}

		// Standaardopties
		$defaults = [
			'show_icons' => true,       // Toon standaard iconen
			'class' => 'social-links',  // Standaardklasse voor de container
		];
		$settings = array_merge($defaults, $options);

		// Zet gegevens klaar voor Twig
		$data = [
			'links' => $links,
			'show_icons' => $settings['show_icons'],
			'class' => $settings['class'],
		];

		// Render Twig-template
		return Timber::compile('components/social-media-links.twig', $data);
	}
}