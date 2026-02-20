<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Image extends Site {
	use Components_InviewAnimationOptions;

	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('image', [$this, 'render_image']));
		return $twig;
	}

	public function render_image($image_field, $options = []) {
		if (!$image_field || empty($image_field['url'])) {
			return null;
		}
	
		$defaults = [
			'ratio' => null,
			'figure_class' => '', // Extra CSS-klassen voor de <figure>
			'img_class' => 'w-100', // Extra CSS-klassen voor de <img>
			'object_fit' => 'cover',
                        'lazyload' => true,
			'style' => null,
			'show_caption' => false,
			'caption_position' => 'on-left', // Standaard: linksonder op de afbeelding
			'inview_animation' => '', // Animatie-optie
			'inview_animation_speed' => 1,
			'overlay_direction' => 'left' // Richting van de overlay-animatie
		];
		$settings = array_merge($defaults, $options);
		$animation = $this->normalize_inview_animation($settings['inview_animation']);
		$speed = $this->normalize_inview_animation_speed($settings['inview_animation_speed']);
	
		$image_data = [
			'url' => $image_field['url'],
			'alt' => $image_field['alt'] ?? '',
			'caption' => $image_field['caption'] ?? null,
			'ratio' => $settings['ratio'],
			'figure_class' => trim($settings['figure_class'] . ($animation ? ' animate-' . $animation : '')),
			'img_class' => $settings['img_class'],
			'object_fit' => $settings['object_fit'],
			'lazyload' => $settings['lazyload'],
			'style' => $settings['style'],
			'show_caption' => $settings['show_caption'],
			'caption_position' => $settings['caption_position'],
			'inview_animation' => $animation,
			'inview_animation_speed' => $speed,
			'overlay_direction' => $settings['overlay_direction'],
		];
	
		return Timber::compile('image/image.twig', $image_data);
	}
}
