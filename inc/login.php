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
	add_action( 'login_form', __NAMESPACE__ . '\\output_passkey_buttons', 15 );
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

	wp_enqueue_style(
		'wp-passkeys-login',
		trailingslashit( plugin_dir_url( BioAuth\BASE_FILE ) ) . 'assets/dist/login.css',
		array(),
		$asset_file['version']
	);
}

/**
 * Output Passkey Buttons.
 *
 * @return void
 */
function output_passkey_buttons() {
	printf(
		'<div class="wp-passkeys-options">
			<div class="wp-passkeys-sep">%s</div>
			<p class="wp-passkeys"><a class="button button-hero" id="login-via-passkeys">%s</a></p>
		</div>',
		// translators: Separator text between login options.
		esc_html( apply_filters( 'wp_passkeys_login_separator_text', __( 'or', 'biometric-authentication' ) ) ),
		/**
		 * Filters the SSO login button text
		 *
		 * @param string $login_button_text Text to be used for the login button.
		 */
		esc_html( apply_filters( 'wp_passkeys_log_in_text', __( 'Log in with Passkeys', 'biometric-authentication' ) ) )
	);
}
