<?php
/**
 * Search results page with post type selector.
 *
 * @package WordPress
 * @subpackage Timber
 */

$templates = ['search.twig', 'archive.twig', 'index.twig'];

$context = Timber::context();

$posts_per_page = 12;
$context['posts_per_page'] = $posts_per_page;

$allowed_post_types = $_GET['post_types'] ?? ['post', 'product'];
if (!is_array($allowed_post_types)) {
    $allowed_post_types = explode(',', $allowed_post_types);
}
$allowed_post_types = array_values(array_filter(array_map('sanitize_key', (array) $allowed_post_types)));
if (empty($allowed_post_types)) {
    $allowed_post_types = ['post'];
}
$context['allowed_post_types'] = $allowed_post_types;

$selected_post_type = sanitize_key($_GET['post_type'] ?? '');
if ($selected_post_type && in_array($selected_post_type, $allowed_post_types, true)) {
    $query_post_type = $selected_post_type;
} else {
    $selected_post_type = '';
    $query_post_type    = $allowed_post_types;
}
$context['post_type'] = $selected_post_type;

$post_type_options = [];
foreach ($allowed_post_types as $type) {
    $obj   = get_post_type_object($type);
    $label = $obj ? $obj->labels->name : ucfirst($type);
    $post_type_options[$label] = $type;
}

$context['filters'] = [
    'post_type' => [
        'name'   => 'post_type',
        'label'  => 'Type',
        'type'   => 'select',
        'source' => 'acf',
        'options'=> $post_type_options,
        'value'  => $selected_post_type,
    ],
];
$context['ajax_filters'] = $context['filters'];

$context['search_query'] = get_search_query();

$query_args = [
    'post_type'      => $query_post_type,
    'posts_per_page' => $posts_per_page,
    'paged'          => get_query_var('paged') ?: 1,
    's'              => $context['search_query'],
];

$query = new WP_Query($query_args);

$context['posts']         = Timber::get_posts($query);
$context['total']         = $query->found_posts;
$context['current_page']  = get_query_var('paged') ?: 1;
$context['max_num_pages'] = $query->max_num_pages;
$context['title']         = 'Zoekresultaten voor ' . get_search_query();

Timber::render($templates, $context);
