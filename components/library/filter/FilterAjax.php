<?php

class Components_FilterAjax {
	public static function handle() {
               $filters = $_POST;
               $filter_defs = json_decode(wp_unslash($_POST['filter_defs'] ?? ''), true);

                if (!is_array($filters)) {
                        echo '❌ Ongeldige filterdata ontvangen';
                        wp_die();
                }

                if (!is_array($filter_defs)) {
                        $filter_defs = [];
                }

                $post_type = sanitize_text_field($filters['post_type'] ?? 'post');
                $paged     = (int)($filters['paged'] ?? 1);
	
		// 🔍 Meta filters
		$meta_query = [];

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
			'posts_per_page' => 12,
			'paged'          => $paged,
			'orderby'        => $orderby,
			'order'          => $order,
			'meta_query'     => $meta_query,
		];
		
               if (!empty($filters['s'])) {
                       $args['s'] = sanitize_text_field(wp_unslash($filters['s']));
               }
		
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}

                // 🧠 Combineer custom filter-output met bestaande query args
                $custom_args = Components_Filter::build_query_from_filters($filter_defs);

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

               // 🧮 Option counts for checkbox filters
               $context['option_counts'] = [];
               $search = sanitize_text_field(wp_unslash($filters['s'] ?? ''));
               foreach ($filter_defs as $key => $def) {
                       $context['option_counts'][$key] = Components_Filter::get_option_counts(
                               $filter_defs,
                               $key,
                               [
                                       's'         => $search,
                                       'post_type' => $post_type,
                               ]
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