<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Quicklinks extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('quicklinks', [$this, 'render_quicklinks']));
		return $twig;
	}

	public function render_quicklinks($args = []) {
		$default_id = function_exists('get_the_ID') ? get_the_ID() : null;
		if (!$default_id) {
			$default_id = uniqid('quicklinks-');
		}

		$defaults = [
			'id' => $default_id,
			'title' => 'Inhoud',
			'mode' => 'all',
		];

		$args = is_array($args) ? array_merge($defaults, $args) : $defaults;

		return Timber::compile('quicklinks/quicklinks.twig', $args);
	}
}
