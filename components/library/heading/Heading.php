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

		$defaults = [
			'level' => 'h2',
			'class' => '',
			'inview_animation' => ''
		];
		$settings = array_merge($defaults, $options);

		return Timber::compile('heading/heading.twig', [
			'text' => $text,
			'level' => $settings['level'],
			'class' => $settings['class'] . ($settings['inview_animation'] ? ' animate-' . $settings['inview_animation'] : ''),
			'inview_animation' => $settings['inview_animation'],
		]);
	}
}