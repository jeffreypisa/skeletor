<?php

use Timber\Site;

/**
 * Class HideUsers
 */
 
class HideUsers extends Site {

    public function __construct() {
        parent::__construct();

        // Voeg de filter toe via de constructor
        add_filter('rest_endpoints', [$this, 'disable_rest_endpoints']);
        add_action('template_redirect', [$this, 'block_author_requests']);
    }

    /**
     * Disable REST API endpoints for users.
     *
     * @param array $endpoints The list of REST API endpoints.
     * @return array Modified list of REST API endpoints.
     */
    public function disable_rest_endpoints($endpoints) {
        if (isset($endpoints['/wp/v2/users'])) {
            unset($endpoints['/wp/v2/users']);
        }
        if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
            unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
        }

        return $endpoints;
    }

    public function block_author_requests() {
        if (is_author() || isset($_GET['author'])) {
            wp_redirect(home_url());
            exit;
        }
    }
}
