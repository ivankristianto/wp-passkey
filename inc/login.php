<?php
/**
 * Add functionality on Login Screen.
 */

declare( strict_types = 1 );

namespace WP\Passkey\Login;

use Kucrut\Vite;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap(): void {
	add_action( 'login_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );
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
