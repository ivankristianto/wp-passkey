<?php
/**
 * Plugin Helpers.
 */

declare( strict_types = 1 );

namespace BioAuth\Helpers;

use BioAuth;

/**
 * Enqueue runtime script for development.
 *
 * @return void
 */
function enqueue_runtime() {

	if ( ! file_exists( BioAuth\BASE_DIR . '/assets/dist/runtime.asset.php' ) ) {
		return;
	}

	// Get the assets file detail.
	$asset_file = require_once BioAuth\BASE_DIR . '/assets/dist/runtime.asset.php';

	wp_enqueue_script(
		'wp-passkeys-runtime',
		trailingslashit( plugin_dir_url( BioAuth\BASE_FILE ) ) . 'assets/dist/runtime.js',
		$asset_file['dependencies'],
		$asset_file['version'],
		true
	);
}
