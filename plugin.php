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

require_once __DIR__ . '/inc/rest-api.php';
require_once __DIR__ . '/inc/namespace.php';

// Kickstart the plugin.
bootstrap();
