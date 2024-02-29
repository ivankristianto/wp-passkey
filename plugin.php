<?php
/**
 * Provides passwordless authentication for WordPress
 *
 * @package   passworldless-authentication
 * @author    Ivan Kristianto <ivan@ivankristianto.com>
 * @copyright 2023 Ivan Kristianto
 * @license   GPL v2 or later
 *
 * Plugin Name:  Passwordless Authentication
 * Description:  Provides passwordless authentication for WordPress
 * Version:      0.2.2
 * Author:       Ivan Kristianto
 * Author URI:   https://github.com/ivankristianto/
 * Text Domain:  wp-passkey
 * Requires PHP: 8.1
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
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

require_once __DIR__ . '/inc/class-source-repository.php';
require_once __DIR__ . '/inc/class-webauthn-server.php';
require_once __DIR__ . '/inc/rest-api.php';
require_once __DIR__ . '/inc/login.php';
require_once __DIR__ . '/inc/user-profile.php';
require_once __DIR__ . '/inc/namespace.php';

// Kickstart the plugin.
bootstrap();
