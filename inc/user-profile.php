<?php
/**
 * Add functionality on User Profile.
 */

declare( strict_types = 1 );

namespace WP\Passkey\User_Profile;

use Kucrut\Vite;
use WP_Screen;
use WP_User;

/**
 * Connect namespace methods to hooks and filters.
 *
 * @return void
 */
function bootstrap(): void {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_scripts' );
	add_action( 'show_user_profile', __NAMESPACE__ . '\\display_user_passkeys' );
}

/**
 * Enqueue Admin Scripts.
 *
 * @return void
 */
function enqueue_scripts() {
	if ( ! is_admin() && ! is_user_logged_in() ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen instanceof WP_Screen || $screen->id !== 'profile' ) {
		return;
	}

	Vite\enqueue_asset(
		WP_PASSKEY_DIR . '/assets/dist',
		'assets/src/js/user-profile.js',
		[
			'handle' => 'wp-passkeys-user-profile',
			'dependencies' => [ 'wp-api-fetch', 'wp-dom-ready' ],
			'in-footer' => true,
		]
	);
}

/**
 * Display User Passkeys List
 *
 * @param WP_User $user Current User
 * @return void
 */
function display_user_passkeys( WP_User $user ) {
	?>
	<table class="form-table">
		<tr>
			<th class="two-factor-main-label">
				<?php esc_html_e( 'Passkeys', 'wp-passkey' ); ?>
			</th>
			<td>
				<table class="passkeys-list-table">
					<thead>
						<tr>
							<th class="col-name" scope="col"><?php esc_html_e( 'Name', 'two-factor' ); ?></th>
							<th class="col-name" scope="col"><?php esc_html_e( 'Fingerprint', 'two-factor' ); ?></th>
						</tr>
					</thead>
					<tbody>
				</table>
			</td>
		</tr>
	</table>
	<button type="button" class="button wp-register-passkey hide-if-no-js" aria-expanded="false"><?php esc_html_e( 'Register New Passkey', 'wp-passkey' ); ?></button>
	<p class="wp-register-passkey--message error"></p>
	<?php
}
