<?php

class Components_FilterAjax {
  public static function register() {
	add_action('wp_ajax_ajax_filter', [self::class, 'handle']);
	add_action('wp_ajax_nopriv_ajax_filter', [self::class, 'handle']);
  }

  public static function handle() {
	$context = Timber::context();
	$post_type = sanitize_key($_POST['post_type'] ?? 'post');

	if (!function_exists('get_filters_context')) {
	  require_once get_template_directory() . '/inc/Filter/get-filters-context.php';
	}

	$context['filters'] = get_filters_context($_POST, $post_type);

	$query_args = [
	  'post_type'      => $post_type,
	  'posts_per_page' => 12,
	  'paged'          => intval($_POST['paged'] ?? 1),
	];

	$query_args = array_merge(
	  $query_args,
	  Components_Filter::build_query_from_filters($context['filters'])
	);

	$context['posts'] = Timber::get_posts($query_args);

	Timber::render('partials/list.twig', $context);
	wp_die();
  }
}
