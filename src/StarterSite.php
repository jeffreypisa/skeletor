<?php

use Timber\Menu;
use Timber\Site;

/**
 * Class StarterSite
 */
class StarterSite extends Site {
        /**
         * Header instellingen op 1 plek.
         * `$header_menu_mode` accepteert: `normal`, `mega_dropdown`, `takeover`.
         */
        private bool $show_topbar = false;
        private string $header_menu_mode = 'takeover';
        private string $header_menu_breakpoint = 'lg';
        private string $takeover_button_variant = 'auto';
        private string $takeover_button_class_on_light_header = 'btn-dark';
        private string $takeover_button_class_on_dark_header = 'btn-dark';
        private string $takeover_button_active_class_on_light_header = 'btn-outline-dark';
        private string $takeover_button_active_class_on_dark_header = 'btn-outline-light';
        private int $footer_menu_count = 3;
        private int $takeover_secondary_menu_count = 3;

        public function __construct() {
            add_action('after_setup_theme', [$this, 'theme_supports']);
            add_filter('skeletor_show_topbar', [$this, 'default_show_topbar_state'], 5);
            add_filter('skeletor_header_menu_mode', [$this, 'default_header_menu_mode'], 5);
            add_filter('skeletor_header_menu_breakpoint', [$this, 'default_header_menu_breakpoint'], 5);
            add_filter('skeletor_takeover_button_variant', [$this, 'default_takeover_button_variant'], 5);
            add_filter('skeletor_takeover_button_class_on_light_header', [$this, 'default_takeover_button_class_on_light_header'], 5);
            add_filter('skeletor_takeover_button_class_on_dark_header', [$this, 'default_takeover_button_class_on_dark_header'], 5);
            add_filter('skeletor_takeover_button_active_class_on_light_header', [$this, 'default_takeover_button_active_class_on_light_header'], 5);
            add_filter('skeletor_takeover_button_active_class_on_dark_header', [$this, 'default_takeover_button_active_class_on_dark_header'], 5);
            add_filter('skeletor_footer_menu_count', [$this, 'default_footer_menu_count'], 5);
            add_filter('skeletor_takeover_secondary_menu_count', [$this, 'default_takeover_secondary_menu_count'], 5);
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

        public function default_show_topbar_state($enabled): bool {
                return $this->show_topbar;
        }

        public function default_header_menu_mode($mode): string {
                return $this->resolve_header_menu_mode($this->header_menu_mode);
        }

        public function default_header_menu_breakpoint($breakpoint): string {
                return $this->resolve_header_menu_breakpoint($this->header_menu_breakpoint);
        }

        public function default_footer_menu_count($count): int {
                return $this->sanitize_menu_count($this->footer_menu_count, 1, 8);
        }

        public function default_takeover_button_variant($variant): string {
                return $this->resolve_takeover_button_variant($this->takeover_button_variant);
        }

        public function default_takeover_button_class_on_light_header($class): string {
                return $this->sanitize_button_class_string(
                        $this->takeover_button_class_on_light_header,
                        'btn-outline-dark'
                );
        }

        public function default_takeover_button_class_on_dark_header($class): string {
                return $this->sanitize_button_class_string(
                        $this->takeover_button_class_on_dark_header,
                        'btn-outline-light'
                );
        }

        public function default_takeover_button_active_class_on_light_header($class): string {
                return $this->sanitize_button_class_string(
                        $this->takeover_button_active_class_on_light_header,
                        $this->takeover_button_class_on_light_header
                );
        }

        public function default_takeover_button_active_class_on_dark_header($class): string {
                return $this->sanitize_button_class_string(
                        $this->takeover_button_active_class_on_dark_header,
                        $this->takeover_button_class_on_dark_header
                );
        }

        public function default_takeover_secondary_menu_count($count): int {
                return $this->sanitize_menu_count($this->takeover_secondary_menu_count, 1, 8);
        }

        private function resolve_header_menu_mode($mode): string {
                $allowed_modes = ['normal', 'mega_dropdown', 'takeover'];
                $mode = is_string($mode) ? strtolower(trim($mode)) : '';

                if ($mode === 'dropdown') {
                        $mode = 'mega_dropdown';
                }

                return in_array($mode, $allowed_modes, true) ? $mode : 'normal';
        }

        private function resolve_takeover_button_variant($variant): string {
                $allowed_variants = ['auto', 'light', 'dark'];
                $variant = is_string($variant) ? strtolower(trim($variant)) : '';

                return in_array($variant, $allowed_variants, true) ? $variant : 'auto';
        }

        private function resolve_header_menu_breakpoint($breakpoint): string {
                $allowed_breakpoints = ['sm', 'md', 'lg', 'xl', 'xxl'];
                $breakpoint = is_string($breakpoint) ? strtolower(trim($breakpoint)) : '';

                return in_array($breakpoint, $allowed_breakpoints, true) ? $breakpoint : 'lg';
        }

        private function sanitize_button_class_string($class_string, string $fallback): string {
                $value = is_string($class_string) ? trim($class_string) : '';
                $value = preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $value);
                $value = preg_replace('/\s+/', ' ', (string) $value);
                $value = trim((string) $value);

                return $value !== '' ? $value : $fallback;
        }

        private function sanitize_menu_count($count, int $min = 1, int $max = 8): int {
                $count = (int) $count;

                if ($count < $min) {
                        return $min;
                }

                if ($count > $max) {
                        return $max;
                }

                return $count;
        }

        private function get_menu_if_has_items(string $location) {
                $menu = \Timber\Timber::get_menu($location);

                if (!$menu || empty($menu->get_items())) {
                        return null;
                }

                return $menu;
        }

        private function get_menus_by_prefix(string $prefix, int $count): array {
                $menus = [];

                for ($i = 1; $i <= $count; $i++) {
                        $menu = $this->get_menu_if_has_items($prefix . $i);
                        if ($menu) {
                                $menus[] = $menu;
                        }
                }

                return $menus;
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

        $show_topbar = (bool) apply_filters('skeletor_show_topbar', false);
        $header_menu_mode = $this->resolve_header_menu_mode(
                apply_filters('skeletor_header_menu_mode', 'normal')
        );
        $header_menu_breakpoint = $this->resolve_header_menu_breakpoint(
                apply_filters('skeletor_header_menu_breakpoint', 'lg')
        );
        $takeover_button_variant = $this->resolve_takeover_button_variant(
                apply_filters('skeletor_takeover_button_variant', 'auto')
        );
        $takeover_button_class_on_light_header = $this->sanitize_button_class_string(
                apply_filters('skeletor_takeover_button_class_on_light_header', $this->takeover_button_class_on_light_header),
                'btn-outline-dark'
        );
        $takeover_button_class_on_dark_header = $this->sanitize_button_class_string(
                apply_filters('skeletor_takeover_button_class_on_dark_header', $this->takeover_button_class_on_dark_header),
                'btn-outline-light'
        );
        $takeover_button_active_class_on_light_header = $this->sanitize_button_class_string(
                apply_filters('skeletor_takeover_button_active_class_on_light_header', $this->takeover_button_active_class_on_light_header),
                $takeover_button_class_on_light_header
        );
        $takeover_button_active_class_on_dark_header = $this->sanitize_button_class_string(
                apply_filters('skeletor_takeover_button_active_class_on_dark_header', $this->takeover_button_active_class_on_dark_header),
                $takeover_button_class_on_dark_header
        );
        $use_takeover_menu = $header_menu_mode === 'takeover';
        $footer_menu_count = $this->sanitize_menu_count(
                apply_filters('skeletor_footer_menu_count', $this->footer_menu_count),
                1,
                8
        );
        $takeover_secondary_menu_count = $this->sanitize_menu_count(
                apply_filters('skeletor_takeover_secondary_menu_count', $this->takeover_secondary_menu_count),
                1,
                8
        );

        $context['show_topbar'] = $show_topbar;
        $context['header_menu_mode'] = $header_menu_mode;
        $context['header_menu_breakpoint'] = $header_menu_breakpoint;
        $context['takeover_button_variant'] = $takeover_button_variant;
        $context['takeover_button_class_on_light_header'] = $takeover_button_class_on_light_header;
        $context['takeover_button_class_on_dark_header'] = $takeover_button_class_on_dark_header;
        $context['takeover_button_active_class_on_light_header'] = $takeover_button_active_class_on_light_header;
        $context['takeover_button_active_class_on_dark_header'] = $takeover_button_active_class_on_dark_header;
        $context['use_takeover_menu'] = $use_takeover_menu;
        $context['footer_menu_count'] = $footer_menu_count;
        $context['takeover_secondary_menu_count'] = $takeover_secondary_menu_count;

        // Haal menu's op
        $context['header_menu'] = \Timber\Timber::get_menu('header_menu');
        $context['service_menu'] = $show_topbar ? \Timber\Timber::get_menu('service_menu') : null;
        $context['mobile_menu'] = \Timber\Timber::get_menu('mobile_menu');
        $context['takeover_primary_menu'] = $use_takeover_menu ? \Timber\Timber::get_menu('takeover_primary_menu') : null;

        $footer_menus = $this->get_menus_by_prefix('footer_menu_', $footer_menu_count);
        $context['footer_menus'] = $footer_menus;

        $takeover_secondary_menus = [];
        if ($use_takeover_menu) {
                $takeover_secondary_menus = $this->get_menus_by_prefix('takeover_secondary_menu_', $takeover_secondary_menu_count);
        }
        $context['takeover_secondary_menus'] = $takeover_secondary_menus;

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
                if (apply_filters('skeletor_show_topbar', false)) {
                        $classes[] = 'has-topbar';
                }

                $menu_mode = $this->resolve_header_menu_mode(
                        apply_filters('skeletor_header_menu_mode', 'normal')
                );
                $classes[] = 'header-menu-' . $menu_mode;

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
