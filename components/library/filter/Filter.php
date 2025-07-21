<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Filter extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
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
		$name = $data['name'] ?? $acf_field;
		$type = $data['type'] ?? 'select';

		$data['name'] = $name;

		// ⛏ Verwerk waarde vanuit $_GET
		if ($type === 'range') {
			$data['value'] = [
				'min' => $_GET['min_' . $acf_field] ?? null,
				'max' => $_GET['max_' . $acf_field] ?? null,
			];
		} else {
			$data['value'] = $_GET[$acf_field] ?? ($_GET[$acf_field . '[]'] ?? null);
		}

		// 🧮 Automatische range min/max ophalen als niet opgegeven
		if ($type === 'range' && (!isset($data['options']['min']) || !isset($data['options']['max']))) {
			$data['options'] = self::get_auto_min_max($acf_field);
		}

		// 🧾 Opties ophalen voor select/multiselect indien leeg
		if (in_array($type, ['select', 'multiselect']) && empty($data['options'])) {
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
			$type = $filter['type'] ?? null;

			// 🟡 RANGE FILTER
			if ($type === 'range' && is_array($value)) {
				$min = isset($value['min']) && $value['min'] !== '' ? (float) $value['min'] : null;
				$max = isset($value['max']) && $value['max'] !== '' ? (float) $value['max'] : null;
			
				if (!is_null($min) && !is_null($max)) {
					$meta_query[] = [
						'key'     => $filter['acf_field'],
						'type'    => 'NUMERIC',
						'compare' => 'BETWEEN',
						'value'   => [$min, $max],
					];
				} elseif (!is_null($min)) {
					$meta_query[] = [
						'key'     => $filter['acf_field'],
						'type'    => 'NUMERIC',
						'compare' => '>=',
						'value'   => $min,
					];
				} elseif (!is_null($max)) {
					$meta_query[] = [
						'key'     => $filter['acf_field'],
						'type'    => 'NUMERIC',
						'compare' => '<=',
						'value'   => $max,
					];
				}
			}

			// 🟡 TAXONOMY FILTERS
			elseif (isset($filter['options']) && !empty($filter['options']) && !isset($filter['acf_field'])) {
				$tax_query[] = [
					'taxonomy' => $key,
					'field'    => 'slug',
					'terms'    => is_array($value) ? $value : [$value],
				];
			}

			// 🟡 ACF FILTERS
			elseif (isset($filter['acf_field'])) {
				if (!empty($value)) {
					$meta_query[] = [
						'key'     => $filter['acf_field'],
						'value'   => is_array($value) ? $value : [$value],
						'compare' => 'IN',
					];
				}
			}
		}

		$args = [];
		if (!empty($meta_query)) $args['meta_query'] = $meta_query;
		if (!empty($tax_query))  $args['tax_query']  = $tax_query;

		return $args;
	}
}