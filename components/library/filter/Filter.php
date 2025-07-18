<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Filter extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public static function register() {
		new self();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('filter', [$this, 'render_filter'], ['is_safe' => ['html']]));
		return $twig;
	}

	public function render_filter($data) {
		if (!is_array($data) || !isset($data['acf_field'])) {
			return "<pre>❌ Ongeldige filterdata ontvangen\n" . print_r($data, true) . "</pre>";
		}

		$acf_field = $data['acf_field'];
		$value = $_GET[$acf_field] ?? ($_GET[$acf_field . '[]'] ?? null);

		$data['name']  = $acf_field;
		$data['value'] = $data['value'] ?? $value;

		// Automatisch min/max instellen bij range
		if ($data['type'] === 'range' && (!isset($data['min']) || !isset($data['max']))) {
			$data = array_merge($data, self::get_auto_min_max($acf_field));
		}

		// Automatisch opties instellen bij select/multiselect
		if (in_array($data['type'], ['select', 'multiselect']) && empty($data['options'])) {
			$data['options'] = self::get_options_from_meta($acf_field);
		}

		return Timber::compile('filter.twig', $data);
	}

	public static function get_options_from_taxonomy($taxonomy, $orderby = 'name') {
		$terms = get_terms([
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => $orderby
		]);

		$options = [];
		foreach ($terms as $term) {
			$options[$term->name] = $term->slug;
		}

		return $options;
	}

	public static function get_options_from_meta($meta_key, $post_type = null) {
		global $wpdb;

		if (!$post_type) {
			global $wp_query;
			$post_type = get_post_type() 
				?: get_query_var('post_type') 
				?: ($wp_query->query_vars['post_type'] ?? 'post');
		}

		$results = $wpdb->get_col($wpdb->prepare(
			"SELECT DISTINCT meta_value
			 FROM {$wpdb->postmeta}
			 WHERE meta_key = %s
			 AND post_id IN (
				 SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = %s AND post_status = 'publish'
			 )
			 ORDER BY meta_value ASC",
			$meta_key,
			$post_type
		));

		$options = [];
		foreach ($results as $val) {
			$options[$val] = $val;
		}

		return $options;
	}

	public static function get_auto_min_max($meta_key, $post_type = null) {
		global $wpdb;

		if (!$post_type) {
			$post_type = get_post_type() ?: get_query_var('post_type') ?: 'post';
		}

		$row = $wpdb->get_row($wpdb->prepare(
			"SELECT MIN(CAST(meta_value AS UNSIGNED)) AS min, MAX(CAST(meta_value AS UNSIGNED)) AS max
			 FROM {$wpdb->postmeta}
			 WHERE meta_key = %s
			 AND post_id IN (
				 SELECT ID FROM {$wpdb->posts}
				 WHERE post_type = %s AND post_status = 'publish'
			 )",
			$meta_key,
			$post_type
		));

		return [
			'min' => $row->min ?? 0,
			'max' => $row->max ?? 100,
		];
	}

	public static function build_query_from_filters($filters) {
		$meta_query = [];
		$tax_query  = [];

		foreach ($filters as $key => $filter) {
			$value = $filter['value'] ?? null;
			if (empty($value)) continue;

			if (isset($filter['options']) && !empty($filter['options']) && !isset($filter['acf_field'])) {
				$tax_query[] = [
					'taxonomy' => $key,
					'field'    => 'slug',
					'terms'    => $value,
				];
			}
			elseif ($filter['type'] === 'range' && is_array($value)) {
				$meta_query[] = [
					'key'     => $filter['acf_field'],
					'type'    => 'NUMERIC',
					'compare' => 'BETWEEN',
					'value'   => [
						$value['min'] ?? 0,
						$value['max'] ?? PHP_INT_MAX,
					],
				];
			}
			else {
				$meta_query[] = [
					'key'     => $filter['acf_field'],
					'value'   => $value,
					'compare' => is_array($value) ? 'IN' : '=',
				];
			}
		}

		$args = [];
		if (!empty($meta_query)) $args['meta_query'] = $meta_query;
		if (!empty($tax_query))  $args['tax_query']  = $tax_query;

		return $args;
	}
}
