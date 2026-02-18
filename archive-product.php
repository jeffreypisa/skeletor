<?php
/**
 * WooCommerce Archive (Shop) Page
 *
 * @package  WordPress
 * @subpackage  Timber
 */

use Timber\Timber;

$context = Timber::context();
$templates = ['woo/archive.twig']; // Gebruik de WooCommerce specifieke Twig-template

$context['post_type'] = 'product';
$context['filters'] = [];

// ⚙️ Basisinstellingen voor filters
$posts_per_page = 12;
$context['posts_per_page'] = $posts_per_page;
$context['col_class'] = 'col-12 col-md-6 col-xl-4 mb-4';

// ✅ Stel de juiste titel in voor de shop pagina en categorieën
if (is_shop()) {
    $context['title'] = get_the_title(wc_get_page_id('shop')); // WooCommerce shop pagina titel
} elseif (is_product_category()) {
    $context['title'] = single_term_title('', false);
} else {
    $context['title'] = post_type_archive_title('', false);
}

$context['filters']['s'] = Components_Filter::create_search_filter(
    $_GET['s'] ?? '',
    [
        'include' => [
            'title'   => true,
            'content' => true,
        ],
        'meta_keys' => ['_sku'],
    ]
);
$context['search_query'] = $context['filters']['s']['value'];

// 🧩 Filters voor WooCommerce producten
$context['filters']['price'] = [
    'name'   => '_price',
    'label'  => 'Prijs',
    'type'   => 'range',
    'source' => 'meta',
];

$context['filters']['stock'] = [
    'name'   => '_stock',
    'label'  => 'Voorraad',
    'type'   => 'range',
    'source' => 'meta',
];

$context['filters']['stock_status'] = [
    'name'    => '_stock_status',
    'label'   => 'Beschikbaarheid',
    'type'    => 'select',
    'source'  => 'meta',
    'options' => [
        'Op voorraad' => 'instock',
        'Uitverkocht' => 'outofstock',
    ],
];

// 🔎 Query bouwen op basis van filters
$query_args = [
    'post_type'      => 'product',
    'posts_per_page' => $posts_per_page,
    'paged'          => get_query_var('paged') ?: 1,
];
$current_taxonomy_filter = null;

// ✅ Behoud de huidige WooCommerce taxonomy-context (bv. productcategorie)
if (is_tax('product_cat') || is_tax('product_tag')) {
    $queried_term = get_queried_object();

    if ($queried_term instanceof WP_Term) {
        $taxonomy_filter = [
            'taxonomy' => $queried_term->taxonomy,
            'field'    => 'term_id',
            'terms'    => [$queried_term->term_id],
        ];

        if ('product_cat' === $queried_term->taxonomy) {
            $taxonomy_filter['include_children'] = true;
        }
        $current_taxonomy_filter = $taxonomy_filter;
    }
}

$filter_query_args = Components_Filter::build_query_from_filters($context['filters']);

if ($current_taxonomy_filter) {
    $existing_tax_query = $filter_query_args['tax_query'] ?? [];

    if (!is_array($existing_tax_query)) {
        $existing_tax_query = [];
    }

    if (empty($existing_tax_query)) {
        $filter_query_args['tax_query'] = [$current_taxonomy_filter];
    } else {
        if (!isset($existing_tax_query['relation'])) {
            $existing_tax_query['relation'] = 'AND';
        }
        $existing_tax_query[] = $current_taxonomy_filter;
        $filter_query_args['tax_query'] = $existing_tax_query;
    }
}

$query_args = array_merge($query_args, $filter_query_args);

$orderby = sanitize_text_field($_GET['orderby'] ?? '');
$order   = sanitize_text_field($_GET['order'] ?? '');
$ordering_args = [];

if ($orderby !== '') {
    if (function_exists('wc_get_catalog_ordering_args')) {
        $ordering_args = wc_get_catalog_ordering_args($orderby, $order);
    }

    if (empty($ordering_args)) {
        switch ($orderby) {
            case 'price':
                $ordering_args = ['orderby' => 'meta_value_num', 'order' => 'ASC', 'meta_key' => '_price'];
                break;
            case 'price-desc':
                $ordering_args = ['orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_price'];
                break;
            case 'popularity':
                $ordering_args = ['orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => 'total_sales'];
                break;
            case 'rating':
                $ordering_args = ['orderby' => 'meta_value_num', 'order' => 'DESC', 'meta_key' => '_wc_average_rating'];
                break;
            case 'menu_order':
                $ordering_args = ['orderby' => 'menu_order title', 'order' => 'ASC'];
                break;
            case 'date':
                $ordering_args = ['orderby' => 'date', 'order' => 'DESC'];
                break;
            default:
                $ordering_args = ['orderby' => 'date', 'order' => 'DESC'];
                break;
        }
    }

    $query_args = array_merge($query_args, $ordering_args);
}

$context['orderby'] = $orderby ?: 'menu_order';

$query = new WP_Query($query_args);

// ✅ Haal producten op met Timber
$context['products']       = Timber::get_posts($query);
$context['total']          = $query->found_posts;
$context['current_page']   = get_query_var('paged') ?: 1;
$context['max_num_pages']  = $query->max_num_pages;
$context['ajax_filters']   = $context['filters'];
set_transient('components_ajax_filters_product', $context['filters'], DAY_IN_SECONDS);

// ✅ Controleer of er een sidebar is ingesteld voor de shop
$widgets = Timber::get_widgets('shop-sidebar');
$context['sidebar'] = trim($widgets) ? $widgets : false;

// ✅ Render de WooCommerce template met Timber
Timber::render($templates, $context);
