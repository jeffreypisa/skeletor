<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;
use Twig\TwigFilter;

/**
 * Class Components_Text
 */
class Components_Text extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('text', [$this, 'render_text']));
		$twig->addFilter(new TwigFilter('apply', [$this, 'apply_filter']));
		return $twig;
	}
	
	public function apply_filter($text, $filter_name) {
		if ($filter_name === 'remove_empty_paragraphs') {
			return $this->remove_empty_paragraphs($text);
		}
		return $text;
	}

	private function remove_empty_paragraphs($text) {
		return preg_replace('/<p[^>]*>\s*(<br\s*\/?>)?\s*<\/p>/i', '', $text);
	}

	public function render_text($text, $options = []) {
		if (!is_string($text) || trim($text) === '') {
			return null;
		}

		$defaults = [
			'class' => '',
			'tag' => 'p',
			'style' => '',
			'max_length' => null,
			'inview_animation' => ''
		];
		$settings = array_merge($defaults, $options);

		// **Beperk tekstlengte indien ingesteld (pas NA vertaling toe)**
		if (!empty($settings['max_length']) && mb_strlen($text) > $settings['max_length']) {
			$text = mb_substr($text, 0, $settings['max_length']) . '...';
		}

		// **Reinig de inhoud**
		$text = $this->apply_filter($text, 'remove_empty_paragraphs');

		$text_data = [
			'text' => $text,
			'class' => trim($settings['class'] . ($settings['inview_animation'] ? ' animate-' . $settings['inview_animation'] : '')),
			'tag' => $settings['tag'],
			'style' => $settings['style'],
			'is_html' => preg_match('/<[^>]+>/', $text), // Controleer of de inhoud al HTML is
		];

		return Timber::render('text/text.twig', $text_data);
	}
}