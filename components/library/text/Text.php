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
		if (!is_string($text) || trim($text) === '') {
			return null;
		}

		// **Zorg ervoor dat we niet per ongeluk de globale Polylang-buffer verwijderen**
		global $polylang_strings;
		if (!isset($polylang_strings)) {
			$polylang_strings = get_option('polylang_temp_strings', []);
		}

		$defaults = [
			'class' => '',
			'tag' => 'p',
			'style' => '',
			'max_length' => null,
			'inview_animation' => '',
			'translatable' => false,
		];
		$settings = array_merge($defaults, $options);

		// **Polylang vertaling toepassen indien nodig**
		if (!empty($settings['translatable']) && function_exists('pll__') && function_exists('pll_register_string')) {
			$key = strtolower(trim(strip_tags($text)));
			$key = preg_replace('/[^a-z0-9]+/i', '_', $key);
			$key = trim($key, '_');

			// **Check of de string al geregistreerd is**
			if (!isset($polylang_strings[$key])) {
				$polylang_strings[$key] = $text;
				update_option('polylang_temp_strings', $polylang_strings);
				pll_register_string($key, $text, 'Theme Strings');
			}

			// **Haal de vertaalde string op**
			$text = pll__($text);
		}

		// **Beperk tekstlengte indien ingesteld**
		if (!empty($settings['max_length']) && mb_strlen($text) > $settings['max_length']) {
			$text = mb_substr($text, 0, $settings['max_length']) . '...';
		}

		// **Reinig de inhoud**
		$text = $this->apply_filter($text, 'remove_empty_paragraphs');

		$text_data = [
			'content' => $text,
			'class' => trim($settings['class'] . ($settings['inview_animation'] ? ' animate-' . $settings['inview_animation'] : '')),
			'tag' => $settings['tag'],
			'style' => $settings['style'],
			'is_html' => preg_match('/<[^>]+>/', $text), // Controleer of de inhoud al HTML is
		];

		return Timber::render('text/text.twig', $text_data);
	}
}