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

               // 🧩 Haal originele filterdefinities op
               $filter_definitions = get_transient('components_ajax_filters_' . $post_type);
               if (!is_array($filter_definitions) || empty($filter_definitions)) {
                       $filter_definitions = Timber::context()['ajax_filters'] ?? [];
               }

               // ✏️ Vul de huidige waardes in en sla standaardwaarden over
               foreach ($filter_definitions as $fname => &$def) {
                       $type   = $def['type'] ?? 'select';
                       $source = $def['source'] ?? 'acf';
                       $name   = $def['name'] ?? $fname;

                       if ($type === 'range') {
                               $min = $filters['min_' . $name] ?? '';
                               $max = $filters['max_' . $name] ?? '';

                               $defaults = Components_Filter::get_auto_min_max($name, $post_type);
                               $def_min  = (float)($defaults['min'] ?? 0);
                               $def_max  = (float)($defaults['max'] ?? 0);

                               $min_val = $min !== '' && floatval($min) > $def_min ? floatval($min) : null;
                               $max_val = $max !== '' && floatval($max) < $def_max ? floatval($max) : null;
                               $def['value'] = ['min' => $min_val, 'max' => $max_val];
                       } elseif ($type === 'date_range') {
                               $fromRaw = $filters['from_' . $name] ?? '';
                               $toRaw   = $filters['to_' . $name] ?? '';

                               $defaults = Components_Filter::get_auto_date_bounds(
                                       $name,
                                       $source === 'post_date' ? 'post_date' : 'meta',
                                       $post_type
                               );

                               $from = ($fromRaw !== '' && $fromRaw !== ($defaults['min'] ?? '')) ? $fromRaw : '';
                               $to   = ($toRaw !== '' && $toRaw !== ($defaults['max'] ?? '')) ? $toRaw : '';
                               $def['value'] = ['from' => $from, 'to' => $to];
                       } elseif ($type === 'date') {
                               $valRaw  = $filters[$name] ?? '';
                               $defaults = Components_Filter::get_auto_date_bounds(
                                       $name,
                                       $source === 'post_date' ? 'post_date' : 'meta',
                                       $post_type
                               );
                               $def['value'] = ($valRaw !== '' && $valRaw !== ($defaults['min'] ?? '')) ? $valRaw : '';
                       } else {
                               if (in_array($type, ['checkbox', 'multiselect'])) {
                                       $def['value'] = $filters[$name] ?? ($filters[$name . '[]'] ?? null);
                               } else {
                                       $def['value'] = $filters[$name] ?? null;
                               }
                       }
               }
               unset($def);

               // 🔧 Bouw query-args op basis van filters
               $custom_args = Components_Filter::build_query_from_filters($filter_definitions);

               // 🔀 Sorteeropties bepalen
               $sort     = sanitize_text_field($filters['sort'] ?? '');
               $orderby  = 'date';
               $order    = 'DESC';
               $meta_key = '';

               if (!empty($filters['orderby'])) {
                       $woo_orderby = $filters['orderby'];
                       if (is_array($woo_orderby)) {
                               $woo_orderby = reset($woo_orderby);
                       }
                       $woo_orderby = sanitize_text_field($woo_orderby);

                       $woo_order = $filters['order'] ?? '';
                       if (is_array($woo_order)) {
                               $woo_order = reset($woo_order);
                       }
                       $woo_order = sanitize_text_field($woo_order);

                       if (function_exists('wc_get_catalog_ordering_args')) {
                               $ordering_args = wc_get_catalog_ordering_args($woo_orderby, $woo_order);
                               $orderby       = $ordering_args['orderby'] ?? $orderby;
                               $order         = $ordering_args['order'] ?? $order;
                               if (!empty($ordering_args['meta_key'])) {
                                       $meta_key = $ordering_args['meta_key'];
                               }
                       }
               } else {
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
               }

               // 🔍 WP_Query args
               $args = array_merge([
                       'post_type'      => $post_type,
                       'post_status'    => 'publish',
                       'posts_per_page' => $posts_per_page,
                       'paged'          => $paged,
                       'orderby'        => $orderby,
                       'order'          => $order,
               ], $custom_args);

               if ($meta_key !== '') {
                       $args['meta_key'] = $meta_key;
               }

               if (!empty($filters['s'])) {
                       $args['s'] = sanitize_text_field($filters['s']);
               }

		$query = new WP_Query($args);
		
		$context['total'] = $query->found_posts;
		
		$posts = Timber::get_posts($query);
		
                $context = [
                        'items'     => $posts,
                        'posts'     => $posts,
                        'max_pages' => $query->max_num_pages,
                        'filters'   => $filters,
                        'total'     => $query->found_posts,
                ];

               // 🧮 Dynamically calculate option counts for all defined filters
               $filter_defs_for_counts = $filter_definitions;

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
