<?php

use Timber\Menu;
use Timber\Site;

/**
 * Class StarterSite
 */
class StarterSite extends Site {
	public function __construct() {
		add_action('after_setup_theme', [$this, 'theme_supports']);
		add_filter('timber/context', [$this, 'add_to_context']);
		add_filter('timber/twig', [$this, 'add_to_twig']);
		add_filter('timber/twig/environment/options', [$this, 'update_twig_environment_options']);

		parent::__construct();
	}

	/**
	 * Voeg context toe aan Timber
	 *
	 * @param array $context Context-array voor Twig.
	 * @return array
	 */
	public function add_to_context($context) {
		$context['site'] = $this;

		// Haal menu's op
		$context['headermenu'] = \Timber\Timber::get_menu('headermenu');
		$context['footermenu'] = \Timber\Timber::get_menu('footermenu');
		$context['mobielmenu'] = \Timber\Timber::get_menu('mobielmenu');
		
		// Voeg optiespagina velden toe
		$context['options'] = get_fields('options');

		// Voeg een helperfunctie toe voor thumbnails
		$context['get_thumbnail'] = function ($post_id) {
			$thumbnail_url = get_the_post_thumbnail_url($post_id);
			if ($thumbnail_url) {
				return [
					'url' => $thumbnail_url,
					'alt' => get_post_meta(get_post_thumbnail_id($post_id), '_wp_attachment_image_alt', true) ?: '',
				];
			}
			return null;
		};

		// Voeg een extra waarde toe voor front-page controle
		$context['is_front_page'] = is_front_page();

		return $context;
	}

	/**
	 * Schakel thema-ondersteuning in
	 */
	public function theme_supports() {
		add_theme_support('menus');
		add_theme_support('automatic-feed-links');
		add_theme_support('title-tag');
		add_theme_support('post-thumbnails');
		add_theme_support(
			'html5',
			['comment-form', 'comment-list', 'gallery', 'caption']
		);
		add_theme_support(
			'post-formats',
			['aside', 'image', 'video', 'quote', 'link', 'gallery', 'audio']
		);
	}

	/**
	 * Voeg aangepaste functies en filters toe aan Twig
	 *
	 * @param Twig\Environment $twig Twig-object.
	 * @return Twig\Environment
	 */
	public function add_to_twig($twig) {
		// Voeg een custom filter toe om HTML te strippen
		$twig->addFilter(new \Twig\TwigFilter('strip_html', function ($content) {
			return wp_strip_all_tags($content);
		}));
		
		// Voeg custom Twig-functie `post_thumbnail_url` toe
		$twig->addFunction(new \Twig\TwigFunction('post_thumbnail_url', function ($post_id) {
			return get_the_post_thumbnail_url($post_id);
		}));

		// Voeg custom Twig-functie `get_thumbnail` toe
		$twig->addFunction(new \Twig\TwigFunction('get_thumbnail', function ($post_id) {
			$thumbnail_url = get_the_post_thumbnail_url($post_id);
			if ($thumbnail_url) {
				return [
					'url' => $thumbnail_url,
					'alt' => get_post_meta(get_post_thumbnail_id($post_id), '_wp_attachment_image_alt', true) ?: '',
				];
			}
			return null;
		}));

		return $twig;
	}

	/**
	 * Pas Twig-omgeving opties aan
	 *
	 * @param array $options Array met Twig-opties.
	 * @return array Aangepaste Twig-opties.
	 */
	public function update_twig_environment_options($options) {
		// $options['autoescape'] = true; // Indien nodig inschakelen
		return $options;
	}
}