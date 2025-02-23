<?php
/**
 * Add functionality on Login Screen.
 */

declare( strict_types = 1 );

namespace BioAuth\Login;

use BioAuth;
use BioAuth\Helpers;

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

	// Enqueue runtime script for development.
	Helpers\enqueue_runtime();

	// Get the assets file detail.
	$asset_file = require_once BioAuth\BASE_DIR . '/assets/dist/login.asset.php';

	wp_enqueue_script(
		'wp-passkeys-login',
		trailingslashit( plugin_dir_url( BioAuth\BASE_FILE ) ) . 'assets/dist/login.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);
}
