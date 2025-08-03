<?php

$context = Timber::context();
$post_type = get_post_type();
$context['post_type'] = $post_type;
$context['filters'] = $context['filters'] ?? [];

// 🧩 Filter: 'uren' – ACF radio
$context['filters']['uren'] = [
  'name'   => 'uren',
  'label'  => 'Uren',
  'type'   => 'checkbox',
  'source' => 'acf'
];

// 🧩 Filter: 'prijs' – ACF range slider
$context['filters']['prijs'] = [
  'name'   => 'prijs',
  'label'  => 'Prijs',
  'type'   => 'range',
  'source' => 'acf',
];

// 🧩 Filter: 'vakgebied' – Taxonomy select
$context['filters']['vakgebied'] = [
  'name'   => 'vakgebied',
  'label'  => 'Vakgebied',
  'type'   => 'buttons',
  'source' => 'taxonomy',
];

// 🔎 Query build
$query_args = [
  'post_type'      => $post_type,
  'posts_per_page' => 12,
  'paged'          => get_query_var('paged') ?: 1,
];

$query_args = array_merge(
  $query_args,
  Components_Filter::build_query_from_filters($context['filters'])
);

$query = new WP_Query($query_args);

$context['total'] = $query->found_posts;

$context['posts'] = Timber::get_posts($query);

$context['current_page'] = get_query_var('paged') ?: 1;
$context['max_num_pages'] = $query->max_num_pages;

$context['title'] = post_type_archive_title('', false);

$context['ajax_filters'] = $context['filters'];

Timber::render('archive-filter.twig', $context);