<?php
/**
 * Template voor het tonen van archiefpagina's.
 *
 * Wordt gebruikt wanneer er geen specifiekere template (zoals date.php) bestaat.
 * Dit bestand bouwt de context op voor Timber en voegt filters toe die worden
 * gebruikt in combinatie met AJAX filtering.
 *
 * @package    WordPress
 * @subpackage Timber
 * @since      Timber 0.2
 */

$context = Timber::context(); // Basiscontext vanuit Timber, bevat globale data (site, user, etc.)

$post_type = get_post_type();
$context['post_type'] = $post_type; // Nodig voor AJAX (JS moet weten welk post_type gefilterd wordt)
$context['filters'] = [];

// ⚙️ Basisinstellingen voor filters
$posts_per_page = 12;
$context['posts_per_page'] = $posts_per_page;
$context['col_class'] = 'col-12 mb-4';

/**
 * 🧩 Filters
 *
 * Hiermee definieer je de datasets (filters) die in Twig worden gerenderd met {{ filter(filterdata) }}.
 *
 * ➤ Definitie van een filter in PHP (voorbeeld):
 *
 * $context['filters']['uren'] = [
 *   'name'       => 'uren',                   // input name, ook gebruikt in GET
 *   'label'      => 'Uren',                   // veldlabel
 *   'type'       => 'checkbox',               // 'select', 'checkbox', 'radio', 'range'
 *   'source'     => 'field',                  // 'field' of 'taxonomy'
 *   'value'      => $_GET['uren'] ?? null,    // huidige waarde (optioneel)
 *   'options'    => Components_Filter::get_options_from_meta('uren'), // array met key => value
 *
 *   // Alleen data-logica in PHP (géén presentatie):
 *   'sort_options'       => 'asc',      // 'asc', 'desc', 'count_asc', 'count_desc', 'none'
 *   'hide_empty_options' => true,       // verberg opties zonder resultaten
 * ];
 */

$context['filters']['s'] = Components_Filter::create_search_filter(
        $_GET['s'] ?? '',
        [
                'include' => [
                        'title'   => true,
                        'content' => true,
                        'excerpt' => true,
                ],
        ]
);

// 🧩 Filter: 'provincies'

$context['filters']['rating'] = [
  'name'   => 'rating',
  'label'  => 'Rating',
  'type'   => 'range',
  'source' => 'field'
];

$context['filters']['provincies'] = [
  'name'   => 'provincies',
  'label'  => 'Provincies',
  'type'   => 'checkbox',
  'source' => 'taxonomy',
  'hide_empty_options' => true
];

$context['filters']['genres'] = [
  'name'   => 'genre',
  'label'  => 'Genres',
  'type'   => 'checkbox',
  'source' => 'taxonomy',
  'hide_empty_options' => true,
  'sort_options' => 'count_asc'
];

// 🧩 Filter: publicatiedatum (van–tot)
$context['filters']['published'] = [
  'name'   => 'post_date',
  'label'  => 'Periode',
  'type'   => 'date_range',
  'source' => 'post_date',
];

$context['filters']['author'] = [
  'name'   => 'author',
  'label'  => 'Auteur',
  'type'   => 'checkbox',
  'source' => 'author',
];
  
/**
 * 🔎 Query bouwen
 * We beginnen met een standaard WP_Query voor het huidige post_type.
 */
$query_args = [
  'post_type'      => $post_type,
  'posts_per_page' => $posts_per_page,        // Resultaten per pagina
  'paged'          => get_query_var('paged') ?: 1, // Huidige paginanummer (voor paginatie)
];

/**
 * Voeg filter-voorwaarden toe aan de query
 * Components_Filter::build_query_from_filters() converteert de gedefinieerde filters
 * naar geldige WP_Query arguments (zoals meta_query, tax_query, etc.)
 */
$query_args = array_merge(
  $query_args,
  Components_Filter::build_query_from_filters($context['filters'])
);

$query = new WP_Query($query_args); // Voer de query uit

// Zet queryresultaten in de context voor Timber/Twig
$context['total']         = $query->found_posts;         // Totaal aantal gevonden posts
$context['posts']         = Timber::get_posts($query);   // Lijst met Timber\Post objecten
$context['current_page']  = get_query_var('paged') ?: 1; // Huidige paginanummer
$context['max_num_pages'] = $query->max_num_pages;       // Aantal pagina's voor paginatie

// Titel van de archiefpagina (bv. post type naam)
$context['title'] = post_type_archive_title('', false);

// Zet de filters ook apart in de context voor AJAX
$context['ajax_filters'] = $context['filters'];
set_transient('components_ajax_filters_' . $post_type, $context['filters'], DAY_IN_SECONDS);

// Render de Twig-template voor de archiefpagina
Timber::render([
    'archive-' . $post_type . '.twig', // Specifiek voor post type (bv. archive-filter.twig)
    'archive.twig',                    // Fallback als bovenstaande niet bestaat
], $context);