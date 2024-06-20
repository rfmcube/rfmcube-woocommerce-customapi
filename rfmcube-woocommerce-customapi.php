<?php
/*
Plugin Name: Custom WooCommerce Filters
Description: Adds custom filters to the WooCommerce REST API for customer creation date.
Version: 1.0
Author: Rfmcube Team
*/


add_filter('rest_customer_query', function($args, $request) {
    if (!empty($request['created_since'])) {
        $args['date_query'] = [
            'column' => 'user_registered',
            'after'  => $request['created_since'],
        ];
    }
    return $args;
}, 10, 2);


add_action('rest_api_init', function () {
    register_rest_route('wc/v3', '/customers', array(
        'methods' => 'GET',
        'callback' => 'handle_custom_filter',
        'args' => array(
            'created_since' => array(
                'validate_callback' => function($param, $request, $key) {
                    return strtotime($param) !== false;
                }
            ),
        ),
    ));
});
