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
 *   'type'       => 'checkbox',               // 'select', 'checkbox', 'radio', 'buttons', 'range', 'date', 'date_range'
 *   'source'     => 'acf',                    // 'acf', 'taxonomy' of 'post_date'
 *   'value'      => $_GET['uren'] ?? null,    // huidige waarde (optioneel)
 *   'options'    => Components_Filter::get_options_from_meta('uren'), // array met key => value
 *   'date_format'=> 'd-m-Y',                 // formaat voor datumvelden (optioneel)
 *
 *   // Alleen data-logica in PHP (géén presentatie):
 *   'sort_options'       => 'asc',      // 'asc', 'desc', 'none'
 *   'hide_empty_options' => true,       // verberg opties zonder resultaten
 * ];
 *
 * // Datumfilter (publicatiedatum of ACF-datumveld)
 * $context['filters']['datum'] = [
 *   'name'   => 'event_date',           // ACF-veldnaam of 'post_date'
 *   'label'  => 'Datum',
 *   'type'   => 'date_range',           // 'date' voor enkel veld
 *   'source' => 'acf',                 // of 'post_date'
 *   // Waarden worden automatisch uit $_GET['event_date'] of
 *   // $_GET['from_event_date']/$_GET['to_event_date'] gelezen.
 *   // Wanneer geen waardes worden meegegeven vult het component
 *   // automatisch de oudste en nieuwste beschikbare datum in.
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
		$twig->addFunction(new TwigFunction('sort_select', [$this, 'render_sort_select'], ['is_safe' => ['html']]));
		return $twig;
	}

       public function render_filter($data, $args = []) {
               if (!is_array($data)) {
                       if (is_object($data)) {
                               $data = (array) $data;
                       } else {
                               $ctx = Timber::context();
                               if (is_string($data) && isset($ctx['filters'][$data])) {
                                       $data = $ctx['filters'][$data];
                               } else {
                                       return "<pre>❌ Ongeldige filterdata ontvangen\n" . print_r($data, true) . "</pre>";
                               }
                       }
               }
	
		$name   = $data['name'] ?? $data['taxonomy'] ?? $data['acf_field'] ?? null;
		$type   = $data['type'] ?? 'select';
                $source = $data['source'] ?? 'acf';

                if (!$name || !in_array($source, ['acf', 'taxonomy', 'post_date'])) {
                        return "<pre>❌ Ongeldige filterconfiguratie\n" . print_r($data, true) . "</pre>";
                }
	
               $data['name'] = $name;

               $args['date_format'] = $data['date_format'] ?? $args['date_format'] ?? 'd-m-Y';

                // ⛏ Verwerk waarde vanuit $_GET
                if ($type === 'range') {
                        $data['value'] = [
                                'min' => $_GET['min_' . $name] ?? null,
                                'max' => $_GET['max_' . $name] ?? null,
                        ];
               } elseif ($type === 'date_range') {
                       $from_dt = self::parse_date($_GET['from_' . $name] ?? '');
                       $to_dt   = self::parse_date($_GET['to_' . $name] ?? '');
                       $data['value'] = [
                               'from' => $from_dt ? $from_dt->format('d-m-Y') : '',
                               'to'   => $to_dt ? $to_dt->format('d-m-Y') : '',
                       ];
               } elseif ($type === 'date') {
                       $dt = self::parse_date($_GET[$name] ?? '');
                       $data['value'] = $dt ? $dt->format('d-m-Y') : '';
               } else {
                       if (in_array($type, ['checkbox', 'multiselect'])) {
                               $data['value'] = $_GET[$name] ?? ($_GET[$name . '[]'] ?? null);
                       } else {
                               $data['value'] = $_GET[$name] ?? null;
                       }
               }

               // 🗓 Vullen met oudste/nieuwste datum indien leeg
               if ($type === 'date') {
                       if (empty($data['value'])) {
                               $bounds = self::get_auto_date_bounds($name, $source);
                               $data['value'] = $bounds['min'];
                       }
               } elseif ($type === 'date_range') {
                       $needsFrom = empty($data['value']['from']);
                       $needsTo   = empty($data['value']['to']);
                       if ($needsFrom || $needsTo) {
                               $bounds = self::get_auto_date_bounds($name, $source);
                               if ($needsFrom) $data['value']['from'] = $bounds['min'];
                               if ($needsTo)   $data['value']['to']   = $bounds['max'];
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
				$data['options'] = self::get_options_from_taxonomy(
					$name,
					'name',
					$data['hide_empty_options'] ?? false
				);
			}
		}
	
		// 🧮 Tellingen ophalen als gewenst
		if (($args['show_option_counts'] ?? false) && isset($data['name'])) {
			$args['option_counts'] = self::get_option_counts(['self' => $data], 'self');
		}
	
		return Timber::compile('filter.twig', array_merge($data, $args));
	}
	
	public function render_sort_select($value = '', $args = []) {
		$defaults = [
			'id'    => 'sort',
			'name'  => 'sort',
			'label' => 'Sorteer op:',
			'value' => $value,
		];
	
		$data = array_merge($defaults, $args);
	
		return Timber::compile('sortselect.twig', $data);
	}

	public static function get_options_from_taxonomy($taxonomy, $orderby = 'name', $hide_empty = false) {
		$terms = get_terms([
			'taxonomy'   => $taxonomy,
			'hide_empty' => filter_var(
				$hide_empty,
				FILTER_VALIDATE_BOOLEAN,
				FILTER_NULL_ON_FAILURE
			) ?? false,
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

       private static function parse_date($date) {
               if (empty($date)) return null;
               $d = \DateTime::createFromFormat('d-m-Y', $date);
               if (!$d) {
                       $d = \DateTime::createFromFormat('Y-m-d', $date);
               }
               return $d ?: null;
       }

       private static function normalize_date($date) {
               $d = self::parse_date($date);
               return $d ? $d->format('Y-m-d') : '';
       }

       private static function date_parts($date) {
               $d = self::parse_date($date);
               return $d ? [
                       'year'  => (int) $d->format('Y'),
                       'month' => (int) $d->format('m'),
                       'day'   => (int) $d->format('d'),
               ] : [];
       }

       /**
        * Bepaalt automatisch de oudste en nieuwste datum voor een veld of publicatiedatum.
        *
        * @param string $field      Meta key of special value 'post_date'.
        * @param string $source     'acf' of 'post_date'.
        * @param string $post_type  Optioneel post type (default huidige query).
        * @return array ['min' => 'd-m-Y', 'max' => 'd-m-Y']
        */
       public static function get_auto_date_bounds($field, $source = 'acf', $post_type = null) {
               global $wpdb;

               if (!$post_type) {
                       $post_type = get_post_type() ?: get_query_var('post_type') ?: 'post';
               }

               if ($source === 'post_date') {
                       $row = $wpdb->get_row($wpdb->prepare(
                               "SELECT MIN(post_date) AS min, MAX(post_date) AS max
                                FROM {$wpdb->posts}
                                WHERE post_type = %s AND post_status = 'publish'",
                               $post_type
                       ));
               } else {
                       $row = $wpdb->get_row($wpdb->prepare(
                               "SELECT MIN(meta_value) AS min, MAX(meta_value) AS max
                                FROM {$wpdb->postmeta}
                                WHERE meta_key = %s
                                AND post_id IN (
                                        SELECT ID FROM {$wpdb->posts}
                                        WHERE post_type = %s AND post_status = 'publish'
                                )",
                               $field,
                               $post_type
                       ));
               }

               return [
                       'min' => $row->min ? date('d-m-Y', strtotime($row->min)) : '',
                       'max' => $row->max ? date('d-m-Y', strtotime($row->max)) : '',
               ];
       }

        public static function build_query_from_filters($filters) {
                $meta_query = [];
                $tax_query  = [];
                $date_query = [];

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

                        // 🟡 Date range
                        elseif ($type === 'date_range' && is_array($value)) {
                                $fromRaw = $value['from'] ?? '';
                                $toRaw   = $value['to'] ?? '';
                                $from = self::normalize_date($fromRaw);
                                $to   = self::normalize_date($toRaw);
                                $fromParts = self::date_parts($fromRaw);
                                $toParts   = self::date_parts($toRaw);

                                if ($source === 'post_date') {
                                        if ($from || $to) {
                                                $dq = ['inclusive' => true, 'column' => 'post_date'];
                                                if ($from) $dq['after'] = $fromParts;
                                                if ($to)   $dq['before'] = $toParts;
                                                $date_query[] = $dq;
                                        }
                                } else {
                                        if ($from && $to) {
                                                $meta_query[] = [
                                                        'key'     => $name,
                                                        'type'    => 'DATE',
                                                        'compare' => 'BETWEEN',
                                                        'value'   => [$from, $to],
                                                ];
                                        } elseif ($from) {
                                                $meta_query[] = [
                                                        'key'     => $name,
                                                        'type'    => 'DATE',
                                                        'compare' => '>=',
                                                        'value'   => $from,
                                                ];
                                        } elseif ($to) {
                                                $meta_query[] = [
                                                        'key'     => $name,
                                                        'type'    => 'DATE',
                                                        'compare' => '<=',
                                                        'value'   => $to,
                                                ];
                                        }
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

                        // 🟡 Date (single)
                        elseif ($type === 'date' && !empty($value)) {
                                $valNorm  = self::normalize_date($value);
                                $valParts = self::date_parts($value);
                                if ($source === 'post_date') {
                                        if ($valNorm) {
                                                $date_query[] = [
                                                        'inclusive' => true,
                                                        'after'  => $valParts,
                                                        'before' => $valParts,
                                                        'column' => 'post_date',
                                                ];
                                        }
                                } else {
                                        if ($valNorm) {
                                                $meta_query[] = [
                                                        'key'     => $name,
                                                        'value'   => $valNorm,
                                                        'compare' => '=',
                                                        'type'    => 'DATE',
                                                ];
                                        }
                                }
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
                if (!empty($date_query)) $args['date_query'] = $date_query;

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