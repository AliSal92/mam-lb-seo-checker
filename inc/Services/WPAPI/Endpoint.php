<?php

namespace MAM\SEOChecker\Services\WPAPI;


use Exception;
use MAM\SEOChecker\Config;

class Endpoint {

    /**
     * @var array
     */
    public $slugs = [];

    /**
     * @var array
     */
    public $endpoints = [];

    /**
     * Used to make sure you call add_endpoint before with_teamplate
     * @var bool
     */
    public $endpoint_added = false;

    /**
     * Add page.
     *
     * @param string $slug
     *
     * @return Endpoint this object for chaining
     */
    public function add_endpoint( $slug ) {
        $this->slugs[] = $slug;
        $this->endpoints[$slug] = '';

        $this->endpoint_added = true;

        return $this;
    }

    /**
     * Add Template.
     *
     * @param string $template_file
     *
     * @return Endpoint this object for chaining
     * @throws Exception
     */
    public function with_template( $template_file ) {
        if(!$this->endpoint_added){
            throw new Exception("You must call add_endpoint before with_teamplate!");
        }
        $this->endpoint_added = false;

        $this->endpoints[end($this->slugs)] = $template_file;
        return $this;
    }

    /**
     * Register all Endpoints.
     *
     * @wp-hook add_rewrite_endpoint
     */
    public function register_endpoints() {
        add_action('init', [$this, 'add_rewrite_endpoints']);

        add_action('template_redirect', [ $this, 'render_template' ]);

        return $this;
    }

    /**
     * Rewrite endpoints in WP add_rewrite_endpoint.
     *
     * @wp-hook add_rewrite_endpoint
     */
    public function add_rewrite_endpoints(){
        foreach ( $this->endpoints as $slug => $endpoint ) {
            /**
             * @param string        $slug
             */
            add_rewrite_endpoint( $slug, EP_ROOT );
        }

        // flush rules when the rewrites are done
        flush_rewrite_rules();
    }

    /**
     * Render all endpoints templates on request.
     *
     * @hook template_include
     */
    public function render_template() {
        global $wp_query;
        foreach ( $this->endpoints as $slug => $template ) {
            if (isset( $wp_query->query_vars[$slug] ) ) {

                /** @noinspection PhpIncludeInspection */
                include  Config::getInstance()->plugin_path . '/templates/' . $template;
                exit();

            }
        }
    }
}
