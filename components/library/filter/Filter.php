<?php

/**
 * 🧩 Filter Component – Filter.php
 *
 * Hiermee definieer je de datasets (filters) die in Twig worden gerenderd met {{ filter(filterdata) }}.
 *
 * ➤ Definitie van een filter in PHP (voorbeeld):
 *
 * $context['filters']['uren'] = [
 *   'name'       => 'uren',                   // input name, ook gebruikt in GET
 *   'label'      => 'Uren',                   // veldlabel
 *   'type'       => 'checkbox',               // 'select', 'checkbox', 'radio', 'range'
 *   'source'     => 'acf',                    // 'acf' of 'taxonomy'
 *   'value'      => $_GET['uren'] ?? null,    // huidige waarde (optioneel)
 *   'options'    => Components_Filter::get_options_from_meta('uren'), // array met key => value
 *
 *   // Alleen data-logica in PHP (géén presentatie):
 *   'sort_options'       => 'asc',      // 'asc', 'desc', 'none'
 *   'hide_empty_options' => true,       // verberg opties zonder resultaten
 * ];
 */
 
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

	public function render_filter($data, $args = []) {
		if (!is_array($data)) {
			return "<pre>❌ Ongeldige filterdata ontvangen\n" . print_r($data, true) . "</pre>";
		}
	
		$name   = $data['name'] ?? $data['taxonomy'] ?? $data['acf_field'] ?? null;
		$type   = $data['type'] ?? 'select';
		$source = $data['source'] ?? 'acf';
	
		if (!$name || !in_array($source, ['acf', 'taxonomy'])) {
			return "<pre>❌ Ongeldige filterconfiguratie\n" . print_r($data, true) . "</pre>";
		}
	
		$data['name'] = $name;
	
		// ⛏ Verwerk waarde vanuit $_GET
		if ($type === 'range') {
			$data['value'] = [
				'min' => $_GET['min_' . $name] ?? null,
				'max' => $_GET['max_' . $name] ?? null,
			];
		} else {
			if (in_array($type, ['checkbox', 'multiselect'])) {
				$data['value'] = $_GET[$name] ?? ($_GET[$name . '[]'] ?? null);
			} else {
				$data['value'] = $_GET[$name] ?? null;
			}
		}
	
		// 🧮 Automatische range min/max ophalen als niet opgegeven
		if ($type === 'range' && (!isset($data['options']['min']) || !isset($data['options']['max']))) {
			$data['options'] = self::get_auto_min_max($name);
		}
	
		// 🧾 Opties ophalen indien leeg
		if (!isset($data['options']) || empty($data['options'])) {
			if ($source === 'acf') {
				$data['options'] = self::get_options_from_meta($name);
			} elseif ($source === 'taxonomy') {
				$data['options'] = self::get_options_from_taxonomy($name);
			}
		}
	
		// 🧮 Tellingen ophalen als gewenst
		if (($args['show_option_counts'] ?? false) && isset($data['name'])) {
			$args['option_counts'] = self::get_option_counts(['self' => $data], 'self');
		}
	
		return Timber::compile('filter.twig', array_merge($data, $args));
	}

	public static function get_options_from_taxonomy($taxonomy, $orderby = 'name') {
		$terms = get_terms([
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'orderby'    => $orderby,
		]);

		$options = [];
		foreach ($terms as $term) {
			$options[$term->name] = $term->slug;
		}
		return $options;
	}

	/**
	 * Haalt unieke waarden op uit de meta van gepubliceerde posts voor een gegeven ACF-veld.
	 *
	 * @param string $meta_key De naam van het ACF-veld in de meta.
	 * @param string $post_type Het post type (optioneel, default: huidige query).
	 * @return array Unieke opties als [label => value], gesorteerd op label.
	 */
	 
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

	/**
	 * Bepaalt automatisch het minimum en maximum numerieke waarde voor een ACF-veld.
	 *
	 * Wordt gebruikt bij range filters om het bereik te bepalen op basis van bestaande postwaarden.
	 *
	 * @param string $meta_key De naam van het ACF-veld.
	 * @param string $post_type Het post type (optioneel, default: huidige query).
	 * @return array ['min' => int|float, 'max' => int|float]
	 */
	 
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
			$value  = $filter['value'] ?? null;
			$type   = $filter['type'] ?? null;
			$source = $filter['source'] ?? 'acf';
			$name   = $filter['name'] ?? $key;

			// 🟡 RANGE
			if ($type === 'range' && is_array($value)) {
				$min = isset($value['min']) && $value['min'] !== '' ? (float) $value['min'] : null;
				$max = isset($value['max']) && $value['max'] !== '' ? (float) $value['max'] : null;

				if (!is_null($min) && !is_null($max)) {
					$meta_query[] = [
						'key'     => $name,
						'type'    => 'NUMERIC',
						'compare' => 'BETWEEN',
						'value'   => [$min, $max],
					];
				} elseif (!is_null($min)) {
					$meta_query[] = [
						'key'     => $name,
						'type'    => 'NUMERIC',
						'compare' => '>=',
						'value'   => $min,
					];
				} elseif (!is_null($max)) {
					$meta_query[] = [
						'key'     => $name,
						'type'    => 'NUMERIC',
						'compare' => '<=',
						'value'   => $max,
					];
				}
			}

			// 🟡 TAXONOMY
			elseif ($source === 'taxonomy' && !empty($value)) {
				$tax_query[] = [
					'taxonomy' => $name,
					'field'    => 'slug',
					'terms'    => is_array($value) ? $value : [$value],
				];
			}

			// 🟡 ACF (meta)
			elseif ($source === 'acf' && !empty($value)) {
				$meta_query[] = [
					'key'     => $name,
					'value'   => is_array($value) ? $value : [$value],
					'compare' => 'IN',
				];
			}
		}

		$args = [];
		if (!empty($meta_query)) $args['meta_query'] = $meta_query;
		if (!empty($tax_query))  $args['tax_query']  = $tax_query;

		return $args;
	}
	
	/**
	 * Berekent per optie hoeveel posts eraan voldoen met de huidige filters actief (behalve zichzelf).
	 *
	 * @param array $filters Alle filters (inclusief de actieve waardes)
	 * @param string $filter_key De filter waarvoor je wil tellen
	 * @return array ['optiewaarde' => count]
	 */
	public static function get_option_counts($filters, $filter_key) {
		$filter = $filters[$filter_key] ?? null;
		if (!$filter || empty($filter['options'])) return [];
	
		$counts = [];
		$options = $filter['options'];
		$type    = $filter['type'] ?? 'select';
		$source  = $filter['source'] ?? 'acf';
		$name    = $filter['name'] ?? $filter_key;
	
		// Verwijder dit filter zelf tijdelijk uit de actieve filters
		$other_filters = $filters;
		unset($other_filters[$filter_key]);
		$args = self::build_query_from_filters($other_filters);
	
		foreach ($options as $label => $val) {
			// Voeg deze specifieke optie tijdelijk toe
			$test_filters = $other_filters;
			$test_filters[$filter_key] = [
				'type'   => $type,
				'source' => $source,
				'name'   => $name,
				'value'  => $val,
			];
	
			$count_args = self::build_query_from_filters($test_filters);
			$count_args['post_type'] = get_post_type() ?: 'post';
			$count_args['posts_per_page'] = 1;
			$count_args['fields'] = 'ids';
	
			$query = new WP_Query($count_args);
			$counts[$val] = $query->found_posts;
			wp_reset_postdata();
		}
	
		return $counts;
	}
}