<?php
/**
 * Add functionality on User Profile.
 */

declare( strict_types = 1 );

namespace WP\Passkey\User_Profile;

use Kucrut\Vite;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_Passkey\Source_Repository;
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
	$public_key_credential_source_repository = new Source_Repository();

	$user_entity = PublicKeyCredentialUserEntity::create(
		$user->user_login,   // Name.
		$user->user_login,   // Use user login as the ID.
		$user->display_name, // Display name.
	);

	$public_key_credentials = $public_key_credential_source_repository->findAllForUserEntity( $user_entity );
	?>
	<div class="wp-passkey-admin">
		<h2 class="wp-passkey-admin--heading"><?php esc_html_e( 'Passkeys', 'wp-passkey' ) ?></h2>
		<p class="description">
			<?php esc_html_e( 'Passkeys are used to authenticate you when you log in to your account.', 'wp-passkey' ); ?>
		</p>
		<?php
		// @TODO: Change to WP_List_Table.
		?>
		<table class="wp-list-table wp-passkey-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th class="col-name" scope="col"><?php esc_html_e( 'Fingerprint', 'wp-passkey' ); ?></th>
					<th class="col-name" scope="col"><?php esc_html_e( 'Type', 'wp-passkey' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $public_key_credentials as $public_key_credential ) :
					?>
					<tr>
						<td>
							<?php echo esc_html( Base64UrlSafe::encode( $public_key_credential->getPublicKeyCredentialDescriptor()->getId() ) ); ?>
						</td>
						<td>
							<?php echo esc_html( $public_key_credential->getPublicKeyCredentialDescriptor()->getType() ); ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<button type="button" class="button button-secondary wp-register-new-passkey hide-if-no-js" aria-expanded="false"><?php esc_html_e( 'Register New Passkey', 'wp-passkey' ); ?></button>
		<div class="wp-register-passkey--message"></div>
	</div>

	<?php
}
