<?php

use Timber\Site;
use Timber\Timber;
use Twig\TwigFunction;

class Components_Accordion extends Site {
	public function __construct() {
		add_filter('timber/twig', [$this, 'add_to_twig']);
		parent::__construct();
	}

	public function add_to_twig($twig) {
		$twig->addFunction(new TwigFunction('accordion', [$this, 'render_accordion']));
		return $twig;
	}

        public function render_accordion($accordion_items, $args = []) {
                if (!$accordion_items) {
                        return null;
                }

                if (!is_array($accordion_items)) {
                        if ($accordion_items instanceof \Timber\Post || $accordion_items instanceof \WP_Post) {
                                $accordion_items = [$accordion_items];
                        } else {
                                return null;
                        }
                }

                $accordion_items = array_map(function ($item) {
                        if (is_array($item) && (isset($item['titel']) || isset($item['tekst']))) {
                                return $item;
                        }

                        if ($item instanceof \Timber\Post || $item instanceof \WP_Post) {
                                return [
                                        'titel' => $item->post_title ?? '',
                                        'tekst' => apply_filters('the_content', $item->post_content ?? ''),
                                ];
                        }

                        if (is_array($item) && (isset($item['post_title']) || isset($item['post_content']))) {
                                return [
                                        'titel' => $item['post_title'] ?? '',
                                        'tekst' => apply_filters('the_content', $item['post_content'] ?? ''),
                                ];
                        }

                        return [
                                'titel' => '',
                                'tekst' => '',
                        ];
                }, $accordion_items);

                $defaults = [
                        'id_prefix' => 'accordion',
                        'icon' => 'chevron',
                        'icon_position' => 'after',
                        'heading_level' => 'h2',
			'heading_class' => '',
			'icon_weight' => 'solid',
			'first_item_open' => false,
		];

                $args = is_array($args) ? array_merge($defaults, $args) : $defaults;
                $args['accordion_id'] = $args['id_prefix'] . '-' . uniqid();
                $args['items'] = $accordion_items;

                return Timber::compile('accordion/accordion.twig', $args);
        }
}