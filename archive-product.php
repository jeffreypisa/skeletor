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

// ✅ Stel de juiste titel in voor de shop pagina en categorieën
if (is_shop()) {
    $context['title'] = get_the_title(wc_get_page_id('shop')); // WooCommerce shop pagina titel
} elseif (is_product_category()) {
    $context['title'] = single_term_title('', false);
} else {
    $context['title'] = post_type_archive_title('', false);
}

// ✅ Haal producten op met Timber
$context['products'] = Timber::get_posts();

// ✅ Controleer of er een sidebar is ingesteld voor de shop
$widgets = Timber::get_widgets('shop-sidebar');
$context['sidebar'] = trim($widgets) ? $widgets : false;

// ✅ Render de WooCommerce template met Timber
Timber::render($templates, $context);