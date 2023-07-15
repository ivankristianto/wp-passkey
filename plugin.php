<?php
/**
 * Plugin Name: WordPress Passkey
 * Description: Provides passwordless authentication on WordPress
 * Author: Ivan Kristianto
 * Author URI: https://www.ivankristianto.com
 * Version: 0.0.1
 */

declare( strict_types=1 );

namespace WP\Passkey;

/**
 * Shortcut constant to the path of this file.
 */
define( 'WP_PASSKEY_DIR', plugin_dir_path( __FILE__ ) );

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/inc/class-webauthn-server.php';
require_once __DIR__ . '/inc/rest-api.php';
require_once __DIR__ . '/inc/login.php';
require_once __DIR__ . '/inc/user-profile.php';
require_once __DIR__ . '/inc/namespace.php';

// Kickstart the plugin.
bootstrap();
