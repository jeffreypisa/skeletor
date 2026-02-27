<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_InpageNav extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('inpage_nav', [$this, 'render_inpage_nav']));
		return $twig;
	}

	public function render_inpage_nav($args = []) {
		$default_id = function_exists('get_the_ID') ? get_the_ID() : null;
		if (!$default_id) {
			$default_id = uniqid('inpage-nav-');
		}

		$defaults = [
			'id' => $default_id,
			'title' => 'Inhoud',
			'mode' => 'all',
			'parent_links' => [],
			'current_parent_id' => '',
			'parent_links_category' => '',
			'parent_icon' => '',
			'parent_icon_size' => 12,
		];

		$args = is_array($args) ? array_merge($defaults, $args) : $defaults;

		$taxonomy = isset($args['parent_links_category']) ? (string) $args['parent_links_category'] : '';
		$current_post_id = function_exists('get_the_ID') ? (int) get_the_ID() : 0;

		if ($taxonomy && empty($args['parent_links']) && $current_post_id > 0 && taxonomy_exists($taxonomy)) {
			$term_ids = wp_get_post_terms($current_post_id, $taxonomy, ['fields' => 'ids']);

			if (!is_wp_error($term_ids) && !empty($term_ids)) {
				$term_id = (int) $term_ids[0];
				$current_post_type = get_post_type($current_post_id) ?: 'post';

				$posts = get_posts([
					'post_type' => $current_post_type,
					'post_status' => 'publish',
					'posts_per_page' => -1,
					'orderby' => [
						'menu_order' => 'ASC',
						'date' => 'ASC',
					],
					'tax_query' => [
						[
							'taxonomy' => $taxonomy,
							'field' => 'term_id',
							'terms' => [$term_id],
						],
					],
				]);

				$args['parent_links'] = array_map(
					static function ($post) use ($current_post_id) {
						return [
							'id' => (string) $post->ID,
							'title' => get_the_title($post->ID),
							'url' => get_permalink($post->ID),
							'is_current' => (int) $post->ID === (int) $current_post_id,
						];
					},
					$posts
				);
			}
		}

		if (empty($args['current_parent_id']) && $current_post_id > 0) {
			$args['current_parent_id'] = (string) $current_post_id;
		}

		return Timber::compile('inpage-nav/inpage-nav.twig', $args);
	}
}
