<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Swiper extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('swiper', [$this, 'render_swiper']));
		return $twig;
	}

	public function render_swiper($slides, $settings = [], $template = 'tease.twig') {
		if (empty($slides)) {
			return null;
		}
	
		$unique_id = uniqid('swiper_');
	
		$defaults = [
			'direction' => 'horizontal',
			'loop' => false,
			'slidesPerView' => 1,
			'spaceBetween' => 0,
			'loopAdditionalSlides' => 0,
			'centeredSlides' => false,
			'speed' => 500,
			'autoplay' => false,
			'arrows' => true,
			'dots' => false,
			'arrowPrevIcon' => 'fa-chevron-left',
			'arrowNextIcon' => 'fa-chevron-right',
			'arrowIconStyle' => 'light',
			'class' => '',
			'swiper_id' => $unique_id,
			'navigation' => [
				'nextEl' => ".swiper-button-next-{$unique_id}",
				'prevEl' => ".swiper-button-prev-{$unique_id}",
				'disabledClass' => 'disabled-swiper-button',
			],
		];
		$finalSettings = array_merge($defaults, $settings);
	
		if ($finalSettings['loop']) {
			unset($finalSettings['navigation']['disabledClass']);
			$slides = array_merge($slides, $slides);
		}
	
		$processedSlides = array_map(function ($post) use ($template) {
			return Timber::compile($template, ['item' => $post]);
		}, $slides);
	
		return Timber::compile('components/swiper.twig', [
			'slides' => $processedSlides,
			'settings' => $finalSettings,
			'swiper_id' => $unique_id,
		]);
	}
}