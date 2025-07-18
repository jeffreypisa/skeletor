<?php

$context = Timber::context();
$post_type = get_post_type();
$context['post_type'] = $post_type; // nodig voor JS/ajax

// 🧩 Filter: 'uren' – ACF select field, opties automatisch
$context['filters']['uren'] = [
  'acf_field' => 'uren',
  'label'     => 'Uren',
  'type'      => 'select',
  'value'     => $_GET['uren'] ?? null,
  'options'   => Components_Filter::get_options_from_meta('uren'),
];

// 🧩 Filter: 'prijs' – ACF range field, min/max automatisch
$context['filters']['prijs'] = [
  'acf_field' => 'prijs',
  'label'     => 'Prijs',
  'type'      => 'range',
  'value'     => [
    'min' => $_GET['min_prijs'] ?? null,
    'max' => $_GET['max_prijs'] ?? null,
  ],
];

// 🧩 Filter: 'vakgebied' – Taxonomie
$context['filters']['vakgebied'] = [
  'acf_field' => 'vakgebied',
  'label'     => 'Vakgebied',
  'type'      => 'select',
  'options'   => Components_Filter::get_options_from_taxonomy('vakgebied'),
  'value'     => $_GET['vakgebied'] ?? null,
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

$context['posts'] = Timber::get_posts($query_args);
$context['title'] = post_type_archive_title('', false);

Timber::render('archive-filter.twig', $context);