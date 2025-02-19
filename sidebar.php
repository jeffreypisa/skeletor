<?php
/**
 * The Template for the sidebar containing the main widget area
 *
 * @package  WordPress
 * @subpackage  Timber
 */

$context = Timber::context(); // Haal de standaard Timber-context op
$context['sidebar'] = Timber::get_widgets('sidebar-1'); // Voeg widgets toe (optioneel)

Timber::render('sidebar.twig', $context);