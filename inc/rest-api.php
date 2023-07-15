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

		// Store the public key credential source.
		$public_key = $public_key_credential_source->jsonSerialize();
		update_user_meta( $user->ID, 'wp_passkey_' . $public_key['publicKeyCredentialId'], $public_key );
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

	try {
		$public_key_credential_request_options = $webauthn_server->create_assertion_request();
	} catch ( Exception $error ) {
		return new WP_Error( 'invalid_request', 'Invalid request.', [ 'status' => 400 ] );
	}

	return rest_ensure_response( $public_key_credential_request_options );
}

/**
 * Function to signin response.
 *
 * @param WP_REST_Request $request The request object.
 *
 * @return WP_REST_Response|WP_Error
 */
function signin_response( WP_REST_Request $request ): WP_REST_Response|WP_Error {
	$data = $request->get_body();

	if ( ! $data ) {
		return new WP_Error( 'invalid_request', 'Invalid request.', [ 'status' => 400 ] );
	}

	$webauthn_server = new Webauthn_Server();

	try {
		$public_key_credential_source = $webauthn_server->validate_assertion_response( $data );
	} catch ( Exception $error ) {
		return new WP_Error( 'public_key_validation_failed', $error->getMessage(), [ 'status' => 400 ] );
	}

	return rest_ensure_response( [
		'status' => 'verified',
		'message' => 'Successfully registered.',
	] );
}
