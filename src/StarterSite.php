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
            add_filter('body_class', [$this, 'add_body_class']);
            add_filter('wp_nav_menu_objects', [$this, 'set_post_type_archive_active']);

                // Extra template-locaties toevoegen
                Timber::$locations = [
                        get_template_directory() . '/views',
			get_template_directory() . '/components/library', // Extra locatie voor componenten
		];
		
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
			
		// Voeg Polylang functies toe aan Twig (indien actief)
		if (function_exists('pll__')) {
			$context['pll__'] = function ($string) {
				return pll__($string);
			};
		}

        $show_topbar = apply_filters('skeletor_show_topbar', true);
        $context['show_topbar'] = $show_topbar;

        // Haal menu's op
        $context['headermenu'] = \Timber\Timber::get_menu('headermenu');
        $context['servicemenu'] = \Timber\Timber::get_menu('servicemenu');
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

        public function add_body_class($classes) {
                if (apply_filters('skeletor_show_topbar', true)) {
                        $classes[] = 'has-topbar';
                }

                if ($this->should_use_transparent_header()) {
                        $classes[] = 'header-transparent';
                }

                return $classes;
        }

        private function should_use_transparent_header(): bool {
                if (!is_singular()) {
                        return false;
                }

                if (!get_field('hero_tonen')) {
                        return false;
                }

                $hero_background = get_field('hero_achtergrond');
                $hero_image = get_field('hero_afbeelding');

                if ($hero_background !== 'afbeelding' || empty($hero_image)) {
                        return false;
                }

                $preference = get_field('hero_transparante_header');

                if ($preference === 'transparent') {
                        return true;
                }

                if ($preference === 'solid') {
                        return false;
                }

                $options = get_fields('options');
                $default_post_types = $options['header_transparante_post_types'] ?? [];

                return in_array(get_post_type(), $default_post_types, true);
        }

        /**
         * Markeer het archief-menu item als actief op enkelvoudige pagina's van het post type.
         *
         * @param array $items De huidige menu-items.
         * @return array
         */
        public function set_post_type_archive_active($items) {
                if (!is_singular()) {
                        return $items;
                }

                $post_type = get_post_type();

                foreach ($items as $item) {
                        if (($item->type ?? '') !== 'post_type_archive') {
                                continue;
                        }

                        if (($item->object ?? '') !== $post_type) {
                                continue;
                        }

                        $item->classes = array_unique(array_merge(
                                $item->classes,
                                ['current-menu-ancestor', 'current-menu-parent']
                        ));

                        $item->current = true;
                }

                return $items;
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
