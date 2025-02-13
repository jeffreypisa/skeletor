<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Text extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new \Twig\TwigFunction('text', [$this, 'render_text']));
		$twig->addFilter(new \Twig\TwigFilter('apply', [$this, 'apply_filter']));
		return $twig;
	}
	
	public function apply_filter($content, $filter_name) {
		if ($filter_name === 'remove_empty_paragraphs') {
			return $this->remove_empty_paragraphs($content);
		}
		return $content;
	}

	private function remove_empty_paragraphs($content) {
		return preg_replace('/<p[^>]*>\s*(<br\s*\/?>)?\s*<\/p>/i', '', $content);
	}

	public function render_text($text, $options = []) {
		if (!trim((string) $text)) {
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
	
		if (!empty($settings['max_length']) && mb_strlen($text) > $settings['max_length']) {
			$text = mb_substr($text, 0, $settings['max_length']) . '...';
		}
	
		// Reinig de inhoud
		$text = $this->apply_filter($text, 'remove_empty_paragraphs');
	
		$text_data = [
			'content' => $text,
			'class' => trim($settings['class'] . ($settings['inview_animation'] ? ' animate-' . $settings['inview_animation'] : '')),
			'tag' => $settings['tag'],
			'style' => $settings['style'],
			'is_html' => preg_match('/<[^>]+>/', $text), // Controleer of de inhoud al HTML is
		];
	
		return Timber::compile('components/text.twig', $text_data);
	}
}