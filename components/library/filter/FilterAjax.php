<?php

/**
 * AJAX handler voor het filtercomponent.
 *
 * Ondersteunt het doorgeven van:
 * - numerieke ranges via `min_{veld}` / `max_{veld}`
 * - datumvelden via `veld` (enkel) of `from_{veld}` / `to_{veld}` voor ranges
 * - publicatiedatum filteren met de special key `post_date`
 */
class Components_FilterAjax {
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
        public static function handle() {
                $filters = $_POST;
	
		if (!is_array($filters)) {
			echo '❌ Ongeldige filterdata ontvangen';
			wp_die();
		}
	
               $post_type      = sanitize_text_field($filters['post_type'] ?? 'post');
               $paged          = (int)($filters['paged'] ?? 1);
               $posts_per_page = isset($filters['posts_per_page']) ? (int) $filters['posts_per_page'] : 12;
	
                // 🔍 Meta filters
                $meta_query = [];
                $date_query = [];

                // ➕ Dynamische range filters (min_xxx / max_xxx)
                foreach ($_POST as $key => $value) {
                        if (strpos($key, 'min_') === 0) {
                                $base = substr($key, 4);
                                $min = $_POST['min_' . $base] ?? '';
                                $max = $_POST['max_' . $base] ?? '';

                                $min_filled = $min !== '';
                                $max_filled = $max !== '';

                                if ($min_filled && $max_filled) {
                                        $meta_query[] = [
                                                'key'     => $base,
                                                'value'   => [floatval($min), floatval($max)],
                                                'type'    => 'NUMERIC',
                                                'compare' => 'BETWEEN',
                                        ];
                                } elseif ($min_filled) {
                                        $meta_query[] = [
                                                'key'     => $base,
                                                'value'   => floatval($min),
                                                'type'    => 'NUMERIC',
                                                'compare' => '>=',
                                        ];
                                } elseif ($max_filled) {
                                        $meta_query[] = [
                                                'key'     => $base,
                                                'value'   => floatval($max),
                                                'type'    => 'NUMERIC',
                                                'compare' => '<=',
                                        ];
                                }
                        }
                       if (strpos($key, 'from_') === 0) {
                               $base = substr($key, 5);
                               $fromRaw = $_POST['from_' . $base] ?? '';
                               $toRaw   = $_POST['to_' . $base] ?? '';
                               $from = self::normalize_date($fromRaw);
                               $to   = self::normalize_date($toRaw);
                               $fromParts = self::date_parts($fromRaw);
                               $toParts   = self::date_parts($toRaw);

                               $from_filled = $from !== '';
                               $to_filled   = $to !== '';

                               if ($base === 'post_date') {
                                       if ($from_filled || $to_filled) {
                                               $dq = ['inclusive' => true, 'column' => 'post_date'];
                                               if ($from_filled) $dq['after'] = $fromParts;
                                               if ($to_filled)   $dq['before'] = $toParts;
                                               $date_query[] = $dq;
                                       }
                               } else {
                                       if ($from_filled && $to_filled) {
                                               $meta_query[] = [
                                                       'key'     => $base,
                                                       'value'   => [$from, $to],
                                                       'type'    => 'DATE',
                                                       'compare' => 'BETWEEN',
                                               ];
                                       } elseif ($from_filled) {
                                               $meta_query[] = [
                                                       'key'     => $base,
                                                       'value'   => $from,
                                                       'type'    => 'DATE',
                                                       'compare' => '>=',
                                               ];
                                       } elseif ($to_filled) {
                                               $meta_query[] = [
                                                       'key'     => $base,
                                                       'value'   => $to,
                                                       'type'    => 'DATE',
                                                       'compare' => '<=',
                                               ];
                                       }
                               }
                       }
                }

		// 🔍 Taxonomy filters
		$tax_query = [];

		foreach ($_POST as $key => $value) {
			if (taxonomy_exists($key) && !empty($value)) {
				$terms = is_array($value) ? $value : [$value];

				$tax_query[] = [
					'taxonomy' => $key,
					'field'    => 'slug',
					'terms'    => $terms,
				];
			}
		}

		// 🔀 Sorteeropties bepalen
		$sort = sanitize_text_field($filters['sort'] ?? '');
		$orderby = 'date';
		$order   = 'DESC';
		
		switch ($sort) {
			case 'date_asc':
				$orderby = 'date';
				$order   = 'ASC';
				break;
			case 'date_desc':
				$orderby = 'date';
				$order   = 'DESC';
				break;
			case 'title_asc':
				$orderby = 'title';
				$order   = 'ASC';
				break;
			case 'title_desc':
				$orderby = 'title';
				$order   = 'DESC';
				break;
			default:
				// default is al 'date' DESC
				break;
		}
		
		// 🔍 WP_Query args
               $args = [
                       'post_type'      => $post_type,
                       'post_status'    => 'publish',
                       'posts_per_page' => $posts_per_page,
                       'paged'          => $paged,
                       'orderby'        => $orderby,
                       'order'          => $order,
                       'meta_query'     => $meta_query,
               ];
                if (!empty($date_query)) {
                        $args['date_query'] = $date_query;
                }
		
		if (!empty($filters['s'])) {
			$args['s'] = sanitize_text_field($filters['s']);
		}
		
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}

		// 🔧 Filters doorgeven aan build_query_from_filters, excl. technische of al verwerkte keys
               $exclude_keys = ['action', 'paged', 'post_type', 's', 'sort', 'posts_per_page'];
		$filter_definitions = [];

		foreach ($filters as $key => $val) {
			if (in_array($key, $exclude_keys, true)) continue;

			if (taxonomy_exists($key)) continue;

                        if (str_starts_with($key, 'min_') || str_starts_with($key, 'max_') || str_starts_with($key, 'from_') || str_starts_with($key, 'to_')) continue;

                        $filter_definitions[$key] = [
                                'name'   => $key,
                                'source' => 'meta',
                                'value'  => $filters[$key]
                        ];
		}

		// 🧠 Combineer custom filter-output met bestaande query args
		$custom_args = Components_Filter::build_query_from_filters($filter_definitions);

		if (!empty($custom_args['meta_query'])) {
			$args['meta_query'] = array_merge($args['meta_query'] ?? [], $custom_args['meta_query']);
		}

		if (!empty($custom_args['tax_query'])) {
			$args['tax_query'] = array_merge($args['tax_query'] ?? [], $custom_args['tax_query']);
		}

		foreach ($custom_args as $key => $val) {
			if (!in_array($key, ['meta_query', 'tax_query'])) {
				$args[$key] = $val;
			}
		}

		$query = new WP_Query($args);
		
		$context['total'] = $query->found_posts;
		
		$posts = Timber::get_posts($query);
		
                $context = [
                        'items'     => $posts,
                        'posts'     => $posts,
                        'max_pages' => $query->max_num_pages,
                        'filters'   => $filters,
                        'total'     => $query->found_posts, // <== voeg dit hier toe
                ];

               // 🧮 Dynamically calculate option counts for all defined filters
               $filter_defs_for_counts = get_transient('components_ajax_filters_' . $post_type);
               if (!is_array($filter_defs_for_counts) || empty($filter_defs_for_counts)) {
                       $filter_defs_for_counts = Timber::context()['ajax_filters'] ?? [];
               }

               foreach ($filter_defs_for_counts as $fname => &$def) {
                       $ftype = $def['type'] ?? 'select';
                       $fsrc  = $def['source'] ?? 'meta';
                       $key   = $def['name'] ?? $fname;

                       if ($ftype === 'range') {
                               $def['value'] = [
                                       'min' => $filters['min_' . $key] ?? null,
                                       'max' => $filters['max_' . $key] ?? null,
                               ];
                       } elseif ($ftype === 'date_range') {
                               $def['value'] = [
                                       'from' => $filters['from_' . $key] ?? '',
                                       'to'   => $filters['to_' . $key] ?? '',
                               ];
                       } elseif ($ftype === 'date') {
                               $def['value'] = $filters[$key] ?? '';
                       } else {
                               if (in_array($ftype, ['checkbox', 'multiselect'])) {
                                       $def['value'] = $filters[$key] ?? ($filters[$key . '[]'] ?? null);
                               } else {
                                       $def['value'] = $filters[$key] ?? null;
                               }
                       }

                       if (empty($def['options'])) {
                               if ($fsrc === 'acf' || $fsrc === 'meta') {
                                       $def['options'] = Components_Filter::get_options_from_meta($key);
                               } elseif ($fsrc === 'taxonomy') {
                                       $def['options'] = Components_Filter::get_options_from_taxonomy($key);
                               }
                       }
               }
               unset($def);

               $global_count_args = ['post_type' => $post_type];
               if (!empty($filters['s'])) {
                       $global_count_args['s'] = sanitize_text_field($filters['s']);
               }

               $context['option_counts'] = [];
               foreach ($filter_defs_for_counts as $fname => $def) {
                       $context['option_counts'][$fname] = Components_Filter::get_option_counts(
                               $filter_defs_for_counts,
                               $fname,
                               $global_count_args
                       );
               }

		// ✅ DEBUG
		// echo '<div style="background:#f8f8f8;padding:1rem;margin-bottom:1rem;border:1px solid #ccc">';
		// echo '<strong>🔍 FILTER DEBUG:</strong><br>';
		// echo '<pre>' . print_r($filters, true) . '</pre>';
		// echo '<strong>🔍 WP_Query ARGS:</strong><br>';
		// echo '<pre>' . print_r($args, true) . '</pre>';
		// echo '<strong>🔍 Aantal resultaten:</strong> ' . count($posts) . '<br>';
		// echo '</div>';

		Timber::render('partials/list.twig', $context);
		wp_die();
	}
}
