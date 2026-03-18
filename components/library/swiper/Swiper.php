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
			'arrowPrevIcon' => 'arrow-left',
			'arrowNextIcon' => 'arrow-right',
			'arrowIconStyle' => 'light',
			'mobileListGap' => null,
			'class' => '',
			'swiper_id' => $unique_id,
			'navigation' => [
				'nextEl' => ".swiper-button-next-{$unique_id}",
				'prevEl' => ".swiper-button-prev-{$unique_id}",
				'disabledClass' => 'disabled-swiper-button',
			],
		];
		$finalSettings = array_merge($defaults, $settings);
		$mobileListGap = null;

		if (!empty($finalSettings['mobileListGap'])) {
			$mobileListGap = $finalSettings['mobileListGap'];

			if (is_numeric($mobileListGap)) {
				$mobileListGap .= 'px';
			} else {
				$mobileListGap = sanitize_text_field((string) $mobileListGap);
			}
		}

		unset($finalSettings['mobileListGap']);
	
		if ($finalSettings['loop']) {
			unset($finalSettings['navigation']['disabledClass']);
			$slides = array_merge($slides, $slides);
		}
	
		$processedSlides = array_map(function ($post) use ($template) {
			$item = $post;

			// ACF relationship fields often return WP_Post objects.
			// Normalize to Timber\Post so Twig methods like item.terms(...) are available.
			if (!$post instanceof \Timber\Post) {
				if ($post instanceof \WP_Post || is_numeric($post)) {
					$item = Timber::get_post($post);
				} elseif (is_array($post) && isset($post['ID'])) {
					$item = Timber::get_post((int) $post['ID']);
				}
			}

			return Timber::compile($template, ['item' => $item]);
		}, $slides);
	
		return Timber::compile('swiper/swiper.twig', [
			'slides' => $processedSlides,
			'settings' => $finalSettings,
			'swiper_id' => $unique_id,
			'mobile_list_gap' => $mobileListGap,
		]);
	}
}
