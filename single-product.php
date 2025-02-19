<?php
/**
 * WooCommerce Single Product Page
 *
 * @package  WordPress
 * @subpackage  Timber
 */

use Timber\Timber;

$context = Timber::context();
$context['post'] = Timber::get_post();
$context['product'] = wc_get_product($context['post']->ID);

// ✅ Controleer of de WooCommerce sidebar widgets bevat
$widgets = Timber::get_widgets('shop-sidebar');
$context['sidebar'] = trim($widgets) ? $widgets : false;

// ✅ Gerelateerde producten ophalen
$related_limit = wc_get_loop_prop('columns') ?: 4; // Fallback naar 4 als er geen waarde is
$related_ids = wc_get_related_products($context['post']->ID, $related_limit);
$context['related_products'] = Timber::get_posts($related_ids);

wp_reset_postdata();

// ✅ Render de Timber WooCommerce single product template
Timber::render('views/woo/single-product.twig', $context);