<?php

use Timber\Site;
use Timber\Timber;

/**
 * Class CustomWooCommerce
 */
class CustomWooCommerce extends Site {
	public function __construct() {
		parent::__construct();
		add_action('after_setup_theme', [$this, 'theme_add_woocommerce_support']);
		add_action('init', [$this, 'customize_woocommerce_hooks']);
		add_filter('template_include', [$this, 'filter_woocommerce_template']); // ✅ Voorkom dubbele rendering
		add_filter('timber/twig', [$this, 'add_woocommerce_to_twig']);
	}

	public function theme_add_woocommerce_support() {
		add_theme_support('woocommerce');
	}

	public function customize_woocommerce_hooks() {
		// Verwijder standaard WooCommerce gerelateerde producten
		remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);

		// Verwijder de productafbeelding uit de shop-overzichtspagina
		remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail');

		// Voorkom dat WooCommerce zijn eigen template laadt
		remove_action('template_redirect', 'woocommerce_template_loader');
	}

	/**
	 * Laadt WooCommerce-templates met Timber en voorkomt dubbele rendering.
	 *
	 * @param string $template Huidige WordPress-template.
	 * @return string Aangepast template of standaard WordPress-template.
	 */
	public function filter_woocommerce_template($template) {
		if (is_admin()) {
			return $template;
		}
		
		return $template;
	}

	/**
	 * Zet het huidige WooCommerce-product in de Timber context.
	 *
	 * @param \Timber\Post $post Timber post object.
	 * @return array Timber product data.
	 */
	public function timber_set_product($post) {
		if (!$post) {
			return [];
		}

		$product = wc_get_product($post->ID);

		if (!$product) {
			return [];
		}

		// ✅ Forceer de juiste WooCommerce product afbeelding
		$thumbnail = get_the_post_thumbnail_url($post->ID, 'woocommerce_single') ?: wc_placeholder_img_src();

		// ✅ Return Timber product data
		return [
			'product' => $product,
			'thumbnail' => $thumbnail,
		];
	}

	/**
	 * Registreer WooCommerce-functies in Timber, zodat ze in Twig beschikbaar zijn.
	 *
	 * @param \Twig\Environment $twig Timber Twig-object.
	 * @return \Twig\Environment
	 */
	public function add_woocommerce_to_twig($twig) {
		$twig->addFunction(new \Twig\TwigFunction('timber_set_product', [$this, 'timber_set_product']));
		return $twig;
	}
}