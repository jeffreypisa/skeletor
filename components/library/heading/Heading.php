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
		$animation = trim((string) $settings['inview_animation']);

		// Sta zowel "word-rise" als "animate-word-rise" en underscore-varianten toe.
		if ($animation !== '') {
			$animation = str_replace('_', '-', $animation);
			$animation = preg_replace('/^animate-/', '', $animation);
		}

		return Timber::compile('heading/heading.twig', [
			'text' => $text,
			'level' => $settings['level'],
			'class' => trim($settings['class'] . ($animation ? ' animate-' . $animation : '')),
			'inview_animation' => $animation,
		]);
	}
}
