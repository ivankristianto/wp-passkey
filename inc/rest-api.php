<?php
/**
 * Bootstrap the plugin.
 */

declare(strict_types=1);

namespace WP\Passkey\Rest_API;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap(): void {
	add_action( 'rest_api_init', __NAMESPACE__ . '\\register_rest_api_endpoints' );
}

/**
 * Function to register rest api endpoints.
 *
 * @return void
 */
function register_rest_api_endpoints() {
	// Register rest endpoint for registerRequest.
	register_rest_route(
		'wp-passkey/v1',
		'/register-request',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\register_request',
		]
	);

	// Register rest endpoint for registerResponse.
	register_rest_route(
		'wp-passkey/v1',
		'/register-response',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\register_response',
		]
	);

	// Register rest endpoint for signinRequest.
	register_rest_route(
		'wp-passkey/v1',
		'/signin-request',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\signin_request',
		]
	);

	// Register rest endpoint for signinResponse.
	register_rest_route(
		'wp-passkey/v1',
		'/signin-response',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\signin_response',
		]
	);
}

/**
 * Function to register request.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function register_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$response = [];

	return rest_ensure_response( $response );
}

/**
 * Function to register response.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function register_response( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$response = [];

	return rest_ensure_response( $response );
}

/**
 * Function to signin request.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function signin_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$response = [];

	return rest_ensure_response( $response );
}

/**
 * Function to signin response.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function signin_response( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$response = [];

	return rest_ensure_response( $response );
}
