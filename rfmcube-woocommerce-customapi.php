<?php
/*
Plugin Name: Rfmcube REST API
Description: Extend the Woocommerce REST API v3 for Rfmcube. Compatible with WC 3.5.x or later	and WP 4.4 or later.
Version: 1.0
Author: Rfmcube Team
*/

add_action( 'rest_api_init', function() {
    register_rest_route( 'wc/v3', '/rfmcustom-customers', [
        'methods'  => 'GET',
        'callback' => 'custom_get_customers',
        'permission_callback' => function () {
            return current_user_can( 'manage_woocommerce' );
        },
    ] );
});
/**
 * Basically it find the ids of the customers beetween the requested dates and ad the include parameter to a standard request to cutomer endpoint
 * We do it because is faster for modified date that rely on usermeta table that is huge
 * and beacause is safer to have a custom endpoint to not modify the standard api call used by other softwares
 */
function custom_get_customers( $request ) {
    $params = $request->get_params();
    
    $before = $request->get_param('before');
    $after = $request->get_param('after');
    $modified_before = $request->get_param('modified_before');
    $modified_after = $request->get_param('modified_after');

    if($before and $after) {
        global $wpdb;
		
        $vsSql = $wpdb->prepare(
            "SELECT id FROM {$wpdb->users} WHERE user_registered BETWEEN %s AND %s",
            date("Y-m-d H:i:s", strtotime($after)),
            date("Y-m-d H:i:s", strtotime($before))
        );

	$vaUsers = $wpdb->get_col($vsSql);

	if($vaUsers){
		$params['include'] = (array) $vaUsers;
	} else{
		$params['include'] = ['0']; // Set 0 that do not exists, if you set empty array is interpretaed as all entities
	}

    } else if($modified_before and $modified_after) {
		global $wpdb;
		
        $vsSql = $wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'last_update' AND meta_value BETWEEN %s AND %s",
            strtotime($modified_after),
            strtotime($modified_before)
        );

	$vaUsers = $wpdb->get_col($vsSql);

	if($vaUsers){
		$params['include'] = (array) $vaUsers;
	} else{
		$params['include'] = ['0']; // Set 0 that do not exists, if you set empty array is interpretaed as all entities
	}
    }

    $internal_request = new WP_REST_Request( 'GET', '/wc/v3/customers' );
    $internal_request->set_query_params( $params );

    $response = rest_do_request( $internal_request );

    if ( $response->is_error() ) {
        return $response;
    }

    $data = $response->get_data();

    // Add customer fields on response
    // foreach ( $data as &$customer ) {
    //     $customer['custom_field'] = 'Valore personalizzato';
    // $customer["fe_gift_points"] = 0;
	// if(class_exists( 'WC_Points_Rewards_Manager' ))
	// {
	// 	$customer["fe_gift_points"] = WC_Points_Rewards_Manager::get_users_points($user_data->ID);
	// }
    // }

    return rest_ensure_response( $data );
}


/*
add_filter('woocommerce_rest_prepare_customer', 'rfmcube_woocommerce_rest_prepare_customer', 10, 3);
function rfmcube_woocommerce_rest_prepare_customer($response, $user_data, $request)
{
	$response->data["fe_gift_points"] = 0;
	if(class_exists( 'WC_Points_Rewards_Manager' ))
	{
		$response->data["fe_gift_points"] = WC_Points_Rewards_Manager::get_users_points($user_data->ID);
	}
	return $response;
}
*/
