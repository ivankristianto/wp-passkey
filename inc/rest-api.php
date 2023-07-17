<?php
/**
 * Rest API Endpoints
 */

declare(strict_types=1);

namespace WP\Passkey\Rest_API;

use Exception;
use WP_Error;
use WP_Passkey\Webauthn_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

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
			'permission_callback' => function () {
				// Only allow users who logged in with minimum capability.
				return current_user_can( 'read' );
			},
		]
	);

	// Register rest endpoint for registerResponse.
	register_rest_route(
		'wp-passkey/v1',
		'/register-response',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\register_response',
			'permission_callback' => function () {
				// Only allow users who logged in with minimum capability.
				return current_user_can( 'read' );
			},
		]
	);

	// Register rest endpoint for signinRequest.
	register_rest_route(
		'wp-passkey/v1',
		'/signin-request',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\signin_request',
			'permission_callback' => '__return_true',
		]
	);

	// Register rest endpoint for signinResponse.
	register_rest_route(
		'wp-passkey/v1',
		'/signin-response',
		[
			'methods'  => 'POST',
			'callback' => __NAMESPACE__ . '\\signin_response',
			'permission_callback' => '__return_true',
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
	$webauthn_server = new Webauthn_Server();

	try {
		$public_key_credential_creation_options = $webauthn_server->create_attestation_request( wp_get_current_user() );
	} catch ( Exception $error ) {
		return new WP_Error( 'invalid_request', 'Invalid request.', [ 'status' => 400 ] );
	}

	return rest_ensure_response( $public_key_credential_creation_options );
}

/**
 * Function to register response.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function register_response( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$data = $request->get_body();

	if ( ! $data ) {
		return new WP_Error( 'invalid_request', 'Invalid request.', [ 'status' => 400 ] );
	}

	$webauthn_server = new Webauthn_Server();

	try {
		$user = wp_get_current_user();
		$public_key_credential_source = $webauthn_server->validate_attestation_response( $data, $user );
	} catch ( Exception $error ) {
		return new WP_Error( 'public_key_validation_failed', $error->getMessage(), [ 'status' => 400 ] );
	}

	return rest_ensure_response( [
		'status' => 'verified',
		'message' => 'Successfully registered.',
	] );
}

/**
 * Function to signin request.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function signin_request( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$webauthn_server = new Webauthn_Server();
	$request_id = wp_generate_uuid4();

	try {
		$public_key_credential_request_options = $webauthn_server->create_assertion_request();
	} catch ( Exception $error ) {
		return new WP_Error( 'invalid_request', 'Invalid request.', [ 'status' => 400 ] );
	}

	$challenge = $public_key_credential_request_options->getChallenge();

	// Store the challenge in transient for 60 seconds.
	// For some hosting transient set to persistent object cache like Redis/Memcache. By default it stored in options table.
	set_transient( 'wp_passkey_' . $request_id, $challenge, 60 );

	$response = [
		'options' => $public_key_credential_request_options,
		'request_id' => $request_id,
	];

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
	$data = $request->get_json_params();

	if ( ! $data ) {
		return new WP_Error( 'invalid_request', 'Invalid request.', [ 'status' => 400 ] );
	}

	$webauthn_server = new Webauthn_Server();

	// Destruct request_id and asseResp from $data.
	$request_id = $data['request_id'];
	$assertion_response = json_encode( $data['asseResp'] );

	// Get challenge from cache.
	$challenge = get_transient( 'wp_passkey_' . $request_id );

	// If $challenge not exists, return WP_Error.
	if ( ! $challenge ) {
		return new WP_Error( 'invalid_challenge', 'Invalid Challenge.', [ 'status' => 400 ] );
	}

	// Delete challenge from cache.
	delete_transient( 'wp_passkey_' . $request_id );

	try {
		$public_key_credential_source = $webauthn_server->validate_assertion_response( $assertion_response, $challenge );

		$user_handle = $public_key_credential_source->getUserHandle();
		$user = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			return new WP_Error( 'user_not_found', 'User not found.', [ 'status' => 404 ] );
		}

		wp_set_auth_cookie( $user->ID, true, is_ssl() );
	} catch ( Exception $error ) {
		return new WP_Error( 'public_key_validation_failed', $error->getMessage(), [ 'status' => 400 ] );
	}

	return rest_ensure_response( [
		'status' => 'verified',
		'message' => 'Successfully signin with Passkey.',
	] );
}
