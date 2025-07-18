<?php

class Components_FilterAjax {
	public static function handle() {
		$filters = $_POST;
	
		if (!is_array($filters)) {
			echo '❌ Ongeldige filterdata ontvangen';
			wp_die();
		}
	
		$post_type = sanitize_text_field($filters['post_type'] ?? 'post');
	
		// Filter-definities ophalen op basis van opgegeven filters
		$filter_definitions = [];
		foreach ($filters as $key => $val) {
			if (in_array($key, ['action', 'paged', 'post_type'])) continue;
	
			$filter_definitions[$key] = [
				'acf_field' => $key,
				'value'     => $filters[$key]
			];
		}
	
		$args = [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 12,
			'paged'          => (int)($filters['paged'] ?? 1),
			'orderby'        => 'date',
			'order'          => 'DESC',
		];
	
		$args = array_merge(
			$args,
			Components_Filter::build_query_from_filters($filter_definitions)
		);
	
		$query = new WP_Query($args);
		$posts = Timber::get_posts($query);
	
		$context = [
			'items'      => $posts,
			'posts'      => $posts,
			'max_pages'  => $query->max_num_pages,
		];
		
		$context['posts'] = $context['items']; // Zorgt dat de lijst werkt met list.twig
	
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