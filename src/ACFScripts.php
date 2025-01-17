<?php

use Timber\Site;

/**
 * Class ACFScripts
 */
 
class ACFScripts extends Site {
 
	public function __construct() {
		parent::__construct();

		// Voeg filters toe via de constructor
		add_filter('acf/settings/save_json', [$this, 'my_acf_json_save_point']);
		add_filter('acf/fields/flexible_content/layout_title/name=stroken', [$this, 'my_acf_fields_flexible_content_layout_title'], 10, 4);
		add_filter('acf/load_field/name=select_post_type', [$this, 'yourprefix_acf_load_post_types']);
	}

	// Stel het pad in voor het opslaan van ACF JSON-bestanden
	public function my_acf_json_save_point($path) {
		// Stel het pad in naar de map 'acf-json' in het child theme
		$path = get_stylesheet_directory() . '/acf-json';

		return $path;
	}

	// Pas de layouttitel aan voor een flexibel inhoudsveld
	public function my_acf_fields_flexible_content_layout_title($title, $field, $layout, $i) {
		$oldtitle = $title;

		// Laad het subveld 'titel' of 'lead' en voeg het toe aan de layouttitel
		if ($text = get_sub_field('titel')) {
			$title = '<strong>' . $oldtitle . ' </strong> | ' . $text;
		} elseif ($text = get_sub_field('lead')) {
			$title = '<strong>' . $oldtitle . ' </strong> | ' . $text;
		} else {
			$title = '<strong>' . $oldtitle . ' </strong>';
		}

		return $title;
	}

	// Vul een selecteerbaar veld met waarden en labels van alle openbare post types
	public function yourprefix_acf_load_post_types($field) {
		$choices = get_post_types(['show_in_nav_menus' => true], 'objects');

		foreach ($choices as $post_type) {
			$field['choices'][$post_type->name] = $post_type->labels->name;
		}

		return $field;
	}
}