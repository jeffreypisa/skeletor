<?php

use Timber\Site;
use Timber\Timber;
use Twig\Markup;
use Twig\TwigFunction;

class Components_Breadcrumbs extends Site {
    public function __construct() {
        add_filter('timber/twig', [$this, 'add_to_twig']);
        add_filter('timber/context', [$this, 'add_to_context']);
        parent::__construct();
    }

    public function add_to_twig($twig) {
        $twig->addFunction(new TwigFunction('breadcrumbs', [$this, 'render'], ['is_safe' => ['html']]));
        return $twig;
    }

    public function add_to_context($context) {
        $context['breadcrumbs'] = $this->render();
        return $context;
    }

    public function render() {
        $crumbs = $this->build_crumbs();
        $html = Timber::compile('breadcrumbs/breadcrumbs.twig', [
            'crumbs' => $crumbs,
        ]);
        return new Markup($html, 'UTF-8');
    }

    protected function build_crumbs() {
        $crumbs = [];
        $crumbs[] = [
            'url' => home_url('/'),
            'title' => __('Home'),
        ];

        if (is_front_page() || is_home()) {
            return $crumbs;
        }

        if (is_search()) {
            $crumbs[] = [
                'title' => sprintf(__('Search results for %s'), get_search_query()),
            ];
        } elseif (is_archive()) {
            $crumbs[] = [
                'title' => get_the_archive_title(),
            ];
        } elseif (is_singular()) {
            $post = get_post();
            if ($post && $post->post_type !== 'post') {
                $post_type = get_post_type_object($post->post_type);
                if ($post_type && $post_type->has_archive) {
                    $crumbs[] = [
                        'url' => get_post_type_archive_link($post_type->name),
                        'title' => $post_type->labels->name,
                    ];
                }
            }

            $parents = get_post_ancestors($post);
            if ($parents) {
                $parents = array_reverse($parents);
                foreach ($parents as $parent_id) {
                    $crumbs[] = [
                        'url' => get_permalink($parent_id),
                        'title' => get_the_title($parent_id),
                    ];
                }
            }

            $crumbs[] = [
                'title' => get_the_title(),
            ];
        } else {
            $crumbs[] = [
                'title' => get_the_title(),
            ];
        }

        return $crumbs;
    }
}
