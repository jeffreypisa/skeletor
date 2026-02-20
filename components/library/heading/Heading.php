<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components_Heading
 */
class Components_Heading extends Site {
	use Components_InviewAnimationOptions;

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
			'inview_animation' => '',
			'inview_animation_speed' => 1
		];
		$settings = array_merge($defaults, $options);
		$animation = $this->normalize_inview_animation($settings['inview_animation']);
		$speed = $this->normalize_inview_animation_speed($settings['inview_animation_speed']);

		return Timber::compile('heading/heading.twig', [
			'text' => $text,
			'level' => $settings['level'],
			'class' => trim($settings['class'] . ($animation ? ' animate-' . $animation : '')),
			'inview_animation' => $animation,
			'inview_animation_speed' => $speed,
		]);
	}
}
