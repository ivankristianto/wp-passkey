<?php
/**
 * Bootstrap the plugin.
 */

declare( strict_types = 1 );

namespace BioAuth;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap(): void {
	Rest_API\bootstrap();
	User_Profile\bootstrap();
	Login\bootstrap();
}
