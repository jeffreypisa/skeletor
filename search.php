<?php
/**
 * Search results page with post type selector.
 *
 * @package WordPress
 * @subpackage Timber
 */

$templates = ['search.twig', 'archive.twig', 'index.twig'];

$context = Timber::context();

// ⚙️ Basisinstellingen voor filters
$posts_per_page = 12;
$context['posts_per_page'] = $posts_per_page;
$context['col_class'] = 'col-12';
$allowed_post_types = [
    'page',
    'locaties',
    'product'
];

$allowed_post_types = array_values(array_filter(array_map('sanitize_key', $allowed_post_types)));
if (empty($allowed_post_types)) {
    $allowed_post_types = array_values(
        get_post_types(['public' => true, 'exclude_from_search' => false], 'names')
    );
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

$context['filters'] = [
    'post_type' => [
        'name'   => 'post_type',
        'label'  => 'Type',
        'type'   => 'select',
        'source' => 'post_type',
        'post_types' => $allowed_post_types,
        'value'  => $selected_post_type,
    ],
];

$context['filters']['s'] = Components_Filter::create_search_filter(
    $_GET['s'] ?? get_search_query(),
    [
        'include' => [
            'title'   => true,
            'content' => true,
            'excerpt' => true,
        ],
    ]
);
$context['search_query'] = $context['filters']['s']['value'];
$context['ajax_filters'] = $context['filters'];

if ($context['search_query'] === '') {
    $context['posts']            = [];
    $context['current_page']     = 0;
    $context['max_num_pages']    = 0;
    $context['title']            = 'Zoeken';
    $context['show_search_prompt'] = true;
} else {
    $query_args = [
        'post_type'      => $query_post_type,
        'posts_per_page' => $posts_per_page,
        'paged'          => get_query_var('paged') ?: 1,
    ];

    $query_args = array_merge(
        $query_args,
        Components_Filter::build_query_from_filters($context['filters'])
    );

    $query = new WP_Query($query_args);

    $context['posts']         = Timber::get_posts($query);
    $context['total']         = $query->found_posts;
    $context['current_page']  = get_query_var('paged') ?: 1;
    $context['max_num_pages'] = $query->max_num_pages;
    $context['title']         = 'Zoekresultaten voor ' . $context['search_query'];
}

Timber::render($templates, $context);
