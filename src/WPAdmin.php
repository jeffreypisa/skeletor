<?php

use Timber\Site;

/**
 * Class WPAdmin
 */
class WPAdmin extends Site {

	public function __construct() {
		parent::__construct();

		// Voeg acties en filters toe via de constructor
		add_action('admin_menu', [$this, 'post_remove']);
		add_filter('tiny_mce_before_init', [$this, 'remove_h1_from_heading']);
		add_action('admin_menu', [$this, 'remove_admin_menus']);
		add_action('init', [$this, 'remove_comment_support'], 100);
		add_action('wp_before_admin_bar_render', [$this, 'admin_bar_render']);
		add_action('admin_head', [$this, 'custom_admin_styles']);
		add_action('init', [$this, 'disable_emojis']); // Emoji's uitschakelen
	}

	/**
	 * Verwijder 'Berichten' uit het adminmenu.
	 */
	public function post_remove() {
		remove_menu_page('edit.php');
	}

	/**
	 * Verwijder de optie voor H1-koppen uit de TinyMCE-editor.
	 */
	public function remove_h1_from_heading($args) {
		$args['block_formats'] = 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4;Heading 5=h5;Heading 6=h6;Pre=pre';
		return $args;
	}

	/**
	 * Verwijder ongewenste items uit het adminmenu, zoals 'Reacties'.
	 */
	public function remove_admin_menus() {
		remove_menu_page('edit-comments.php');
	}

	/**
	 * Verwijder ondersteuning voor reacties van posts en pagina's.
	 */
	public function remove_comment_support() {
		remove_post_type_support('post', 'comments');
		remove_post_type_support('page', 'comments');
	}

	/**
	 * Verwijder het menu-item 'Reacties' uit de adminbalk.
	 */
	public function admin_bar_render() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('comments');
	}

	/**
	 * Voeg aangepaste admin-styles toe.
	 */
	public function custom_admin_styles() {
		echo '<style>
			#wp-admin-bar-new-post {
				display: none !important;
			}
			#wp-admin-bar-new-content > a {
				pointer-events: none;
			}    
		</style>';
	}

	/**
	 * Schakel emoji-functionaliteit uit in WordPress.
	 */
	public function disable_emojis() {
		// Verwijder emoji scripts en styles
		remove_action('wp_head', 'print_emoji_detection_script', 7);
		remove_action('wp_print_styles', 'print_emoji_styles');
		remove_action('admin_print_scripts', 'print_emoji_detection_script');
		remove_action('admin_print_styles', 'print_emoji_styles');

		// Verwijder emoji filters
		remove_filter('the_content_feed', 'wp_staticize_emoji');
		remove_filter('comment_text_rss', 'wp_staticize_emoji');
		remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
	}
}