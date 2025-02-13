<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components Button
 */
class Components_Button extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('button', [$this, 'render_button']));
		return $twig;
	}

	public function render_button($button_field, $params = []) {
		if (!$button_field || empty($button_field['url'])) {
			return null;
		}
	
		// Controleer of $params een string is (zoals 'primary')
		if (is_string($params)) {
			$params = ['style' => $params];
		}
	
		// Standaardwaarden
		$defaults = [
			'title' => $button_field['title'] ?? 'Klik hier',
			'style' => 'primary',
			'size' => '',
			'target' => $button_field['target'] ?? '_self',
			'icon' => null,
			'icon_position' => 'before',
			'icon_style' => 'light',
			'class' => '', // Nieuw toegevoegd voor extra CSS-klassen
		];
	
		// Combineer de standaardwaarden met de opgegeven parameters
		$button = array_merge($defaults, $params);
	
		// Bouw de volledige Bootstrap-klasse
		$button['style_class'] = 'btn btn-' . $button['style'];
		if (!empty($button['size'])) {
			$button['style_class'] .= ' btn-' . $button['size'];
		}
	
		// Voeg extra CSS-klassen toe als deze zijn opgegeven
		if (!empty($button['class'])) {
			$button['style_class'] .= ' ' . $button['class'];
		}
	
		// Voeg de URL toe
		$button['url'] = $button_field['url'];
	
		// Render de knop
		return Timber::compile('button/button.twig', $button);
	}

	private function get_button_data($button_field) {
		if (!$button_field) {
			return null;
		}

		return [
			'url' => $button_field['url'] ?? '#',
			'title' => $button_field['title'] ?? 'Klik hier',
			'style_class' => $button_field['style_class'] ?? 'btn-primary',
			'target' => $button_field['target'] ?? '_self',
			'icon' => $button_field['icon'] ?? null,
			'icon_position' => $button_field['icon_position'] ?? 'before',
			'icon_style' => $button_field['icon_style'] ?? 'light', // Standaardstijl is 'light'
		];
	}
}