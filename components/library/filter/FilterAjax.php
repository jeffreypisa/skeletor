<?php

class Components_FilterAjax {
	public static function handle() {
		$filters = $_POST;
	
		if (!is_array($filters)) {
			echo '❌ Ongeldige filterdata ontvangen';
			wp_die();
		}
	
		$post_type = sanitize_text_field($filters['post_type'] ?? 'post');
		$paged     = (int)($filters['paged'] ?? 1);
	
		// Filter-definities ophalen, maar taxonomy keys overslaan
		$taxonomy_keys = ['vakgebied']; // voeg hier je taxonomy filter keys toe
		
		$filter_definitions = [];
		foreach ($filters as $key => $val) {
			if (in_array($key, ['action', 'paged', 'post_type'])) continue;
		
			// ⛔️ Als de key een bestaande taxonomy is, dan niet in meta_query opnemen
			if (taxonomy_exists($key)) continue;
		
			$filter_definitions[$key] = [
				'acf_field' => $key,
				'value'     => $filters[$key]
			];
		}
	
		// 🔍 Meta filters
		$meta_query = [];
	
		$min = isset($_POST['min_prijs']) && $_POST['min_prijs'] !== '' ? floatval($_POST['min_prijs']) : null;
		$max = isset($_POST['max_prijs']) && $_POST['max_prijs'] !== '' ? floatval($_POST['max_prijs']) : null;
		
		if ($min !== null && $max !== null) {
			$meta_query[] = [
				'key'     => 'prijs',
				'value'   => [$min, $max],
				'type'    => 'NUMERIC',
				'compare' => 'BETWEEN',
			];
		}
	
		// 🔍 Taxonomy filters
		$tax_query = [];
	
		if (!empty($_POST['vakgebied'])) {
			$vakgebied = $_POST['vakgebied'];
			if (!is_array($vakgebied)) {
				$vakgebied = [$vakgebied];
			}
	
			$tax_query[] = [
				'taxonomy' => 'vakgebied',
				'field'    => 'slug',
				'terms'    => $vakgebied,
			];
		}
	
		// 🔍 WP_Query args
		$args = [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => $meta_query,
		];
	
		if (!empty($tax_query)) {
			$args['tax_query'] = $tax_query;
		}
	
		// ➕ Extra query-opbouw op basis van filters
		$args = array_merge(
			$args,
			Components_Filter::build_query_from_filters($filter_definitions)
		);
	
		$query = new WP_Query($args);
		$posts = Timber::get_posts($query);
	
		$context = [
			'items'     => $posts,
			'posts'     => $posts,
			'max_pages' => $query->max_num_pages,
		];
	
		// ✅ DEBUG
		echo '<div style="background:#f8f8f8;padding:1rem;margin-bottom:1rem;border:1px solid #ccc">';
		echo '<strong>🔍 FILTER DEBUG:</strong><br>';
		echo '<pre>' . print_r($filters, true) . '</pre>';
		echo '<strong>🔍 WP_Query ARGS:</strong><br>';
		echo '<pre>' . print_r($args, true) . '</pre>';
		echo '<strong>🔍 Aantal resultaten:</strong> ' . count($posts) . '<br>';
		echo '</div>';
	
		Timber::render('partials/list.twig', $context);
		wp_die();
	}
}