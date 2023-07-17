<?php
/**
 * Add functionality on Login Screen.
 */

declare( strict_types = 1 );

namespace WP\Passkey\Login;

use Kucrut\Vite;
use WP_Error;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap(): void {
	add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );

	add_filter( 'wp_login_errors', __NAMESPACE__ . '\\maybe_display_failed_authentication', 10, 2 );
}

/**
 * Enqueue Admin Scripts.
 *
 * @return void
 */
function enqueue_scripts() {
	if ( is_user_logged_in() ) {
		return;
	}

	Vite\enqueue_asset(
		WP_PASSKEY_DIR . '/assets/dist',
		'assets/src/js/login.js',
		[
			'handle' => 'wp-passkeys-login',
			'dependencies' => [ 'wp-api-fetch', 'wp-dom-ready' ],
			'in-footer' => true,
		]
	);
}

/**
 * Maybe display failed authentication.
 *
 * @param WP_Error $errors WP Error object.
 * @return WP_Error WP Error object.
 */
function maybe_display_failed_authentication( WP_Error $errors ) : WP_Error {
	if ( ! isset( $_GET['wp_passkey_error'] ) ) {
		return $errors;
	}

	$errors->add( 'wp_passkey_error', sanitize_text_field( wp_unslash( $_GET['wp_passkey_error'] ) ) );

	return $errors;
}
