<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

/**
 * Class Components_Button
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

		// **Zorg ervoor dat een string (zoals 'primary') automatisch in een array wordt omgezet**
		if (is_string($params)) {
			$params = ['style' => $params];
		}

		// **Standaardwaarden**
		$defaults = [
			'title' => $button_field['title'] ?? 'Klik hier',
			'url' => $button_field['url'] ?? '#',
			'style' => 'primary',
			'size' => '',
			'target' => $button_field['target'] ?? '_self',
			'icon' => null,
			'icon_position' => 'before',
			'icon_style' => 'light',
			'class' => ''
		];

		// **Combineer standaardwaarden met opgegeven parameters**
		$button = array_merge($defaults, $params);

		// **Bouw de volledige Bootstrap-klasse**
		$button['style_class'] = 'btn btn-' . $button['style'];
		if (!empty($button['size'])) {
			$button['style_class'] .= ' btn-' . $button['size'];
		}

		// **Extra CSS-klassen toevoegen**
		if (!empty($button['class'])) {
			$button['style_class'] .= ' ' . $button['class'];
		}

		// **Render de knop via Timber**
		return Timber::render('button/button.twig', $button);
	}
}