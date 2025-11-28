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
 *   'source'     => 'field',                  // 'field', 'meta', 'taxonomy', 'post_date', 'post_type', 'user', 'author'
 *   'value'      => $_GET['uren'] ?? null,    // huidige waarde (optioneel)
 *   'options'    => Components_Filter::get_options_from_meta('uren'), // array met key => value
 *   'date_format'=> 'd-m-Y',                 // formaat voor datumvelden (optioneel)
 *
 *   // Alleen data-logica in PHP (géén presentatie):
 *   'sort_options'       => 'asc',      // 'asc', 'desc', 'count_asc', 'count_desc', 'none'
 *   'hide_empty_options' => true,       // verberg opties zonder resultaten
 * ];
 *
 * // Datumfilter (publicatiedatum of veld)
 * $context['filters']['datum'] = [
 *   'name'   => 'event_date',           // veldnaam of 'post_date'
 *   'label'  => 'Datum',
 *   'type'   => 'date_range',           // 'date' voor enkel veld
 *   'source' => 'field',               // or 'meta' or 'post_date'
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
add_action('save_post', ['Components_Filter', 'clear_cache']);
parent::__construct();
}

       /**
        * Maakt een configuratie-array voor het zoekveld van het filtercomponent.
        *
        * @param string $value    De huidige zoekterm.
        * @param array  $settings Instellingen voor de zoekvelden.
        *
        * @return array
        */
       public static function create_search_filter($value = '', array $settings = []) {
               if (!is_string($value)) {
                       $value = '';
               }

               return [
                       'name'            => 's',
                       'type'            => 'search',
                       'source'          => 'search',
                       'value'           => sanitize_text_field(wp_unslash($value)),
                       'search_settings' => self::normalize_search_settings($settings),
               ];
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
                $source = $data['source'] ?? 'field';

                if (!$name || !in_array($source, ['field', 'meta', 'taxonomy', 'post_date', 'post_type', 'user', 'author'])) {
                        return "<pre>❌ Ongeldige filterconfiguratie\n" . print_r($data, true) . "</pre>";
                }

               $data['name'] = $name;

               if (isset($args['post_types'])) {
                       $data['post_types'] = $args['post_types'];
                       unset($args['post_types']);
               }

               $args['date_format'] = $data['date_format'] ?? $args['date_format'] ?? 'd-m-Y';
               $data['show_hierarchy'] = filter_var(
                       $data['show_hierarchy'] ?? $args['show_hierarchy'] ?? true,
                       FILTER_VALIDATE_BOOLEAN,
                       FILTER_NULL_ON_FAILURE
               ) ?? true;

                // ⛏ Verwerk waarde vanuit $_GET
               if ($type === 'range') {
                        $data['value'] = [
                                'min' => isset($_GET['min_' . $name]) ? floatval($_GET['min_' . $name]) : null,
                                'max' => isset($_GET['max_' . $name]) ? floatval($_GET['max_' . $name]) : null,
                        ];
               } elseif ($type === 'date_range') {
                       $from_dt = self::parse_date(sanitize_text_field($_GET['from_' . $name] ?? ''));
                       $to_dt   = self::parse_date(sanitize_text_field($_GET['to_' . $name] ?? ''));
                       $data['value'] = [
                               'from' => $from_dt ? $from_dt->format('d-m-Y') : '',
                               'to'   => $to_dt ? $to_dt->format('d-m-Y') : '',
                       ];
               } elseif ($type === 'date') {
                       $dt = self::parse_date(sanitize_text_field($_GET[$name] ?? ''));
                       $data['value'] = $dt ? $dt->format('d-m-Y') : '';
               } else {
                       if (in_array($type, ['checkbox', 'multiselect'])) {
                               $raw = $_GET[$name] ?? ($_GET[$name . '[]'] ?? null);
                               $data['value'] = is_array($raw) ? array_map('sanitize_text_field', (array) $raw) : sanitize_text_field($raw);
                       } else {
                               $data['value'] = isset($_GET[$name]) ? sanitize_text_field($_GET[$name]) : null;
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
                        if ($source === 'field' || $source === 'meta') {
                                $data['options'] = self::get_options_from_meta($name);
                        } elseif ($source === 'taxonomy') {
                                $data['options'] = self::get_options_from_taxonomy(
                                        $name,
                                        'name',
                                        $data['hide_empty_options'] ?? false
                                );
                        } elseif ($source === 'post_type') {
                                $data['options'] = self::get_post_type_options($data['post_types'] ?? []);
                        } elseif ($source === 'user') {
                                $data['options'] = self::get_user_options();
                        } elseif ($source === 'author') {
                                $data['options'] = self::get_author_options($data['post_types'] ?? null);
                        }
                }

               if ($source === 'taxonomy' && $data['show_hierarchy'] && !isset($data['options_tree'])) {
                       $data['options_tree'] = self::get_options_from_taxonomy(
                               $name,
                               'name',
                               $data['hide_empty_options'] ?? false,
                               true
                       );
               }
	
               $sort_options = strtolower((string) ($data['sort_options'] ?? 'none'));
               $needs_option_counts = ($args['show_option_counts'] ?? false)
                       || in_array($sort_options, ['count_asc', 'count_desc'], true);

               // 🧮 Tellingen ophalen als gewenst (of wanneer sorteren op aantal)
               if ($needs_option_counts && isset($data['name'])) {
                       $ctx_filters = Timber::context()['filters'] ?? [];

                       foreach ($ctx_filters as $key => &$filter) {
                               $fname = $filter['name'] ?? $filter['taxonomy'] ?? $filter['acf_field'] ?? $key;
                               $ftype = $filter['type'] ?? 'select';

                               if ($ftype === 'range') {
                                       $filter['value'] = [
                                               'min' => isset($_GET['min_' . $fname]) ? floatval($_GET['min_' . $fname]) : null,
                                               'max' => isset($_GET['max_' . $fname]) ? floatval($_GET['max_' . $fname]) : null,
                                       ];
                               } elseif ($ftype === 'date_range') {
                                       $from = sanitize_text_field($_GET['from_' . $fname] ?? '');
                                       $to   = sanitize_text_field($_GET['to_' . $fname] ?? '');
                                       $filter['value'] = [
                                               'from' => $from,
                                               'to'   => $to,
                                       ];
                               } elseif ($ftype === 'date') {
                                       $filter['value'] = sanitize_text_field($_GET[$fname] ?? '');
                               } else {
                                       if (in_array($ftype, ['checkbox', 'multiselect'])) {
                                               $raw = $_GET[$fname] ?? ($_GET[$fname . '[]'] ?? null);
                                               $filter['value'] = is_array($raw) ? array_map('sanitize_text_field', (array) $raw) : sanitize_text_field($raw);
                                       } else {
                                               $filter['value'] = isset($_GET[$fname]) ? sanitize_text_field($_GET[$fname]) : null;
                                       }
                               }
                       }
                       $ctx_filters[$data['name']] = $data;

                       $global_args = [];
                       if (!empty($_GET['s'])) {
                               $global_args['s'] = sanitize_text_field($_GET['s']);
                       }

                       $args['option_counts'] = self::get_option_counts($ctx_filters, $data['name'], $global_args);
               }

               // ↕️ Sorteren van opties
               if (isset($data['options']) && is_array($data['options'])) {
                       $data['options'] = self::sort_option_list(
                               $data['options'],
                               $sort_options,
                               $args['option_counts'] ?? []
                       );
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

       public static function get_options_from_taxonomy($taxonomy, $orderby = 'name', $hide_empty = false, $as_tree = false) {
               $terms = get_terms([
                       'taxonomy'   => $taxonomy,
                       'hide_empty' => filter_var(
                               $hide_empty,
                               FILTER_VALIDATE_BOOLEAN,
                               FILTER_NULL_ON_FAILURE
                       ) ?? false,
                       'orderby'    => $orderby,
               ]);

               if ($as_tree) {
                       return self::build_taxonomy_tree($terms);
               }

               $options = [];
               foreach ($terms as $term) {
                       $options[$term->name] = $term->slug;
               }
               return $options;
       }

       private static function build_taxonomy_tree(array $terms) {
               $children = [];
               foreach ($terms as $term) {
                       $parent_id = (int) $term->parent;
                       if (!isset($children[$parent_id])) {
                               $children[$parent_id] = [];
                       }
                       $children[$parent_id][] = $term;
               }

               $buildBranch = function ($parent_id) use (&$buildBranch, $children) {
                       $branch = [];
                       foreach ($children[$parent_id] ?? [] as $term) {
                               $branch[] = [
                                       'label'    => $term->name,
                                       'value'    => $term->slug,
                                       'children' => $buildBranch((int) $term->term_id),
                               ];
                       }
                       return $branch;
               };

               return $buildBranch(0);
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

                $transient_key = 'comp_filter_meta_' . $post_type . '_' . $meta_key;
                $cached = get_transient($transient_key);
                if ($cached !== false) {
                        return $cached;
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

                set_transient($transient_key, $options, DAY_IN_SECONDS);
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

		$transient_key = 'comp_filter_minmax_' . $post_type . '_' . $meta_key;
		$cached = get_transient($transient_key);
		if ($cached !== false) {
		return $cached;
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

		$result = [
'min' => $row->min ?? 0,
'max' => $row->max ?? 100,
];

		set_transient($transient_key, $result, DAY_IN_SECONDS);
		return $result;
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

       private static function normalize_search_settings($settings) {
               if (!is_array($settings)) {
                       $settings = [];
               }

               $default_columns = [];

               if (isset($settings['columns'])) {
                       $default_columns = is_array($settings['columns'])
                               ? $settings['columns']
                               : [$settings['columns']];
               }

               $toggle_columns = [];
               if (isset($settings['include']) && is_array($settings['include'])) {
                       foreach ($settings['include'] as $key => $enabled) {
                               if ($enabled) {
                                       $toggle_columns[] = $key;
                               }
                       }
               }

               foreach (['title', 'content', 'excerpt', 'slug'] as $column_key) {
                       $flag = 'include_' . $column_key;
                       if (array_key_exists($flag, $settings)) {
                               if ($settings[$flag]) {
                                       $toggle_columns[] = $column_key;
                               }
                       }
               }

               $columns = array_merge($default_columns, $toggle_columns);
               if (empty($columns)) {
                       $columns = ['title', 'content'];
               }

               $column_map = [
                       'title'       => 'post_title',
                       'post_title'  => 'post_title',
                       'content'     => 'post_content',
                       'post_content'=> 'post_content',
                       'excerpt'     => 'post_excerpt',
                       'post_excerpt'=> 'post_excerpt',
                       'slug'        => 'post_name',
                       'post_name'   => 'post_name',
               ];

               $normalized_columns = [];
               foreach ($columns as $column) {
                       $key = strtolower((string) $column);
                       if (isset($column_map[$key])) {
                               $normalized_columns[] = $column_map[$key];
                       }
               }
               $normalized_columns = array_values(array_unique($normalized_columns));

               $meta_keys_input = [];
               foreach (['meta_keys', 'acf_fields', 'fields'] as $meta_key_option) {
                       if (!empty($settings[$meta_key_option])) {
                               $meta_keys_input = $settings[$meta_key_option];
                               break;
                       }
               }

               if (!is_array($meta_keys_input)) {
                       $meta_keys_input = $meta_keys_input ? [$meta_keys_input] : [];
               }

               $meta_keys = [];
               foreach ($meta_keys_input as $meta_key) {
                       if (!is_string($meta_key)) {
                               continue;
                       }
                       $meta_key = wp_unslash($meta_key);
                       $meta_key = preg_replace('/[^A-Za-z0-9_:\-]/', '', $meta_key);
                       if ($meta_key !== '') {
                               $meta_keys[] = $meta_key;
                       }
               }
               $meta_keys = array_values(array_unique($meta_keys));

               $allowed_compares = ['LIKE', 'NOT LIKE', '=', '!=', '>', '>=', '<', '<='];
               $meta_compare = strtoupper($settings['meta_compare'] ?? 'LIKE');
               if (!in_array($meta_compare, $allowed_compares, true)) {
                       $meta_compare = 'LIKE';
               }

               $meta_relation = strtoupper($settings['meta_relation'] ?? 'OR');
               if (!in_array($meta_relation, ['AND', 'OR'], true)) {
                       $meta_relation = 'OR';
               }

               return [
                       'columns'       => $normalized_columns,
                       'meta_keys'     => $meta_keys,
                       'meta_compare'  => $meta_compare,
                       'meta_relation' => $meta_relation,
               ];
       }

       /**
        * Bepaalt automatisch de oudste en nieuwste datum voor een veld of publicatiedatum.
        *
        * @param string $field      Meta key or special value 'post_date'.
        * @param string $source     'field', 'meta' or 'post_date'.
        * @param string $post_type  Optioneel post type (default huidige query).
        * @return array ['min' => 'd-m-Y', 'max' => 'd-m-Y']
        */
       public static function get_auto_date_bounds($field, $source = 'field', $post_type = null) {
               global $wpdb;

	if (!$post_type) {
		$post_type = get_post_type() ?: get_query_var('post_type') ?: 'post';
}

		$transient_key = 'comp_filter_dates_' . $post_type . '_' . $field . '_' . $source;
		$cached = get_transient($transient_key);
		if ($cached !== false) {
		return $cached;
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

		$result = [
'min' => $row->min ? date('d-m-Y', strtotime($row->min)) : '',
'max' => $row->max ? date('d-m-Y', strtotime($row->max)) : '',
];

		set_transient($transient_key, $result, DAY_IN_SECONDS);
		return $result;
}

        public static function build_query_from_filters($filters) {
                $meta_query = [];
                $tax_query  = [];
                $date_query = [];
                $post_type  = null;
                $authors    = [];
               $search_term = null;
               $search_settings = [
                       'columns'       => [],
                       'meta_keys'     => [],
                       'meta_compare'  => 'LIKE',
                       'meta_relation' => 'OR',
               ];

                foreach ($filters as $key => $filter) {
                        $value  = $filter['value'] ?? null;
                        $type   = $filter['type'] ?? null;
                        $source = $filter['source'] ?? 'field';
                        $name   = $filter['name'] ?? $key;

                        $is_search_filter = ($name === 's') || !empty($filter['is_search']) || $type === 'search';

                        if ($is_search_filter) {
                                $search_term = is_array($value) ? '' : sanitize_text_field(wp_unslash((string) $value));
                                $search_settings = self::normalize_search_settings(
                                        $filter['search_settings'] ?? $filter['search'] ?? []
                                );
                                continue;
                        }

                        // 🟡 Post Type
                        if ($source === 'post_type' && !empty($value)) {
                                $post_type = $value;
                                continue;
                        }

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

                        // 🟡 Authors / Users
                        elseif (($source === 'author' || $source === 'user') && !empty($value)) {
                                $ids = is_array($value) ? array_map('intval', $value) : [intval($value)];
                                $authors = array_merge($authors, $ids);
                        }

                        // 🟡 Meta veld
                        elseif (($source === 'field' || $source === 'meta') && !empty($value)) {
                                $meta_query[] = [
                                        'key'     => $name,
                                        'value'   => is_array($value) ? $value : [$value],
                                        'compare' => 'IN',
                                ];
                        }
                }

                if ($search_term !== null && $search_term !== '') {
                        if (empty($search_settings['columns'])) {
                                $search_settings['columns'] = ['post_title', 'post_content'];
                        }

                        if (!empty($search_settings['meta_keys'])) {
                                $search_meta_group = ['relation' => $search_settings['meta_relation'] ?? 'OR'];

                                foreach ($search_settings['meta_keys'] as $meta_key) {
                                        $compare = $search_settings['meta_compare'] ?? 'LIKE';
                                        $search_meta_group[] = [
                                                'key'     => $meta_key,
                                                'value'   => $search_term,
                                                'compare' => $compare,
                                        ];
                                }

                                if (count($search_meta_group) > 1) {
                                        if (!empty($meta_query) && !isset($meta_query['relation'])) {
                                                $meta_query['relation'] = 'AND';
                                        }
                                        $meta_query[] = $search_meta_group;
                                }
                        }
                }

                $args = [];
                if ($post_type !== null) $args['post_type'] = $post_type;
                if (!empty($meta_query)) $args['meta_query'] = $meta_query;
                if (!empty($tax_query))  $args['tax_query']  = $tax_query;
                if (!empty($date_query)) $args['date_query'] = $date_query;
                if (!empty($authors))    $args['author__in'] = array_unique($authors);

                if ($search_term !== null && $search_term !== '') {
                        $args['s'] = $search_term;
                        if (!empty($search_settings['columns'])) {
                                $args['search_columns'] = $search_settings['columns'];
                        }
                }

return $args;
}

/**
 * Geeft beschikbare post types terug als [label => value].
         *
         * @param array $allowed Optionele lijst van toegestane post types.
         * @return array
         */
        public static function get_post_type_options($allowed = []) {
                $objs = get_post_types(['public' => true, 'exclude_from_search' => false], 'objects');

                $options = [];
                foreach ($objs as $slug => $obj) {
                        if (!empty($allowed) && !in_array($slug, $allowed, true)) {
                                continue;
                        }
                        $options[$obj->labels->name] = $slug;
                }

                ksort($options);
                return $options;
        }

        /**
         * Geeft een lijst van WordPress-gebruikers terug als [label => value].
         *
         * @param array $args Optionele argumenten voor get_users.
         * @return array
         */
        public static function get_user_options($args = []) {
                $users = get_users($args);
                $options = [];
                foreach ($users as $user) {
                        $options[$user->display_name] = (string) $user->ID;
                }
                ksort($options);
                return $options;
        }

        /**
         * Geeft een lijst van auteurs terug op basis van posts in opgegeven post types.
         *
         * @param string|array|null $post_types Post type(s) om op te zoeken.
         * @return array
         */
        public static function get_author_options($post_types = null) {
                global $wpdb;
                if (!$post_types) {
                        $post_types = get_post_type() ?: get_query_var('post_type') ?: 'post';
                }
                $post_types = (array) $post_types;
                $placeholders = implode(',', array_fill(0, count($post_types), '%s'));
                $sql = "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($placeholders)";
                $author_ids = $wpdb->get_col($wpdb->prepare($sql, ...$post_types));
                $options = [];
                foreach ($author_ids as $uid) {
                        $user = get_user_by('ID', $uid);
                        if ($user) {
                                $options[$user->display_name] = (string) $uid;
                        }
                }
                ksort($options);
                return $options;
        }

       /**
        * Sorteert een optielijst op label of aantal resultaten.
        *
        * @param array  $options        Assoc array van label => value
        * @param string $sort           'asc', 'desc', 'count_asc', 'count_desc' of 'none'
        * @param array  $option_counts  Waardes => aantal posts (voor counts-sorting)
        * @return array                 Gesorteerde opties
        */
       private static function sort_option_list(array $options, string $sort, array $option_counts = []) {
               $sort = strtolower($sort);

               if ($sort === '' || $sort === 'none') {
                       return $options;
               }

               $items = [];
               foreach ($options as $label => $value) {
                       $items[] = [
                               'label' => (string) $label,
                               'value' => $value,
                               'count' => $option_counts[$value] ?? 0,
                       ];
               }

               if (in_array($sort, ['asc', 'desc'], true)) {
                       usort($items, function ($a, $b) use ($sort) {
                               $cmp = strcasecmp($a['label'], $b['label']);
                               return $sort === 'asc' ? $cmp : -$cmp;
                       });
               } elseif (in_array($sort, ['count_asc', 'count_desc'], true)) {
                       usort($items, function ($a, $b) use ($sort) {
                               $cmp = ($a['count'] <=> $b['count']);
                               if ($cmp === 0) {
                                       $cmp = strcasecmp($a['label'], $b['label']);
                               }
                               return $sort === 'count_asc' ? $cmp : -$cmp;
                       });
               }

               $sorted = [];
               foreach ($items as $item) {
                       $sorted[$item['label']] = $item['value'];
               }

               return $sorted;
       }

       /**
        * Berekent per optie hoeveel posts eraan voldoen met de huidige filters actief (behalve zichzelf).
	 *
	 * @param array $filters Alle filters (inclusief de actieve waardes)
	 * @param string $filter_key De filter waarvoor je wil tellen
	 * @return array ['optiewaarde' => count]
	 */
       public static function get_option_counts($filters, $filter_key, $global_args = []) {
               $filter = $filters[$filter_key] ?? null;
               if (!$filter || empty($filter['options'])) return [];

               $counts  = [];
               $options = $filter['options'];
               $type    = $filter['type'] ?? 'select';
               $source  = $filter['source'] ?? 'field';
               $name    = $filter['name'] ?? $filter_key;

               // Verwijder dit filter zelf tijdelijk uit de actieve filters
               $other_filters = $filters;
               unset($other_filters[$filter_key]);

               foreach ($options as $label => $val) {
                       // Voeg deze specifieke optie tijdelijk toe
                       $test_filters = $other_filters;
                       $test_filters[$filter_key] = [
                               'type'   => $type,
                               'source' => $source,
                               'name'   => $name,
                               'value'  => $val,
                       ];

                       $count_args = array_merge(
                               self::build_query_from_filters($test_filters),
                               $global_args
                       );
                       $count_args['post_type'] = $global_args['post_type'] ?? (get_post_type() ?: 'post');
                       $count_args['posts_per_page'] = 1;
                       $count_args['fields'] = 'ids';

                       $query = new WP_Query($count_args);
                       $counts[$val] = $query->found_posts;
                       wp_reset_postdata();
               }

return $counts;
}

public static function clear_cache() {
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_comp_filter_%' OR option_name LIKE '_transient_timeout_comp_filter_%'");
}
}
