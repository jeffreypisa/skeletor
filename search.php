<?php
/**
 * Enhanced search results page with filter components and load more.
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

$post_type = sanitize_key($_GET['post_type'] ?? $allowed_post_types[0]);
if (!in_array($post_type, $allowed_post_types, true)) {
    $post_type = $allowed_post_types[0];
}
$context['post_type'] = $post_type;

$filters = [];

if ($post_type === 'product') {
    $filters['price'] = [
        'name'   => '_price',
        'label'  => 'Prijs',
        'type'   => 'range',
        'source' => 'meta',
    ];
    $filters['stock_status'] = [
        'name'    => '_stock_status',
        'label'   => 'Beschikbaarheid',
        'type'    => 'select',
        'source'  => 'meta',
        'options' => [
            'Op voorraad' => 'instock',
            'Uitverkocht' => 'outofstock',
        ],
    ];
} else {
    $filters['provincies'] = [
        'name'   => 'provincies',
        'label'  => 'Provincies',
        'type'   => 'checkbox',
        'source' => 'taxonomy',
        'hide_empty_options' => true,
    ];
    $filters['rating'] = [
        'name'   => 'rating',
        'label'  => 'Rating',
        'type'   => 'range',
        'source' => 'acf',
    ];
    $filters['published'] = [
        'name'   => 'post_date',
        'label'  => 'Periode',
        'type'   => 'date_range',
        'source' => 'post_date',
    ];
}

$context['filters'] = $filters;

$query_args = [
    'post_type'      => $post_type,
    'posts_per_page' => $posts_per_page,
    'paged'          => get_query_var('paged') ?: 1,
    's'              => get_search_query(),
];

$query_args = array_merge(
    $query_args,
    Components_Filter::build_query_from_filters($filters)
);

$query = new WP_Query($query_args);

$context['posts']         = Timber::get_posts($query);
$context['total']         = $query->found_posts;
$context['current_page']  = get_query_var('paged') ?: 1;
$context['max_num_pages'] = $query->max_num_pages;
$context['title']         = 'Zoekresultaten voor ' . get_search_query();

$context['ajax_filters'] = $filters;
set_transient('components_ajax_filters_' . $post_type, $filters, DAY_IN_SECONDS);

$post_type_options = [];
foreach ($allowed_post_types as $type) {
    $obj   = get_post_type_object($type);
    $label = $obj ? $obj->labels->name : ucfirst($type);
    $post_type_options[$label] = $type;
}

$context['filters']['post_type'] = [
    'name'   => 'post_type',
    'label'  => 'Type',
    'type'   => 'select',
    'source' => 'acf',
    'options'=> $post_type_options,
    'value'  => $post_type,
];

Timber::render($templates, $context);

