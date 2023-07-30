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
			'dependencies' => [ 'wp-api-fetch', 'wp-dom-ready', 'wp-i18n' ],
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
		<table class="wp-list-table wp-passkey-list-table widefat fixed striped table-view-list">
			<thead>
				<tr>
					<th class="manage-column column-name column-primary" scope="col"><?php esc_html_e( 'Name', 'wp-passkey' ); ?></th>
					<th class="manage-column column-created-date" scope="col"><?php esc_html_e( 'Created Date', 'wp-passkey' ); ?></th>
					<th class="manage-column column-type" scope="col"><?php esc_html_e( 'Type', 'wp-passkey' ); ?></th>
					<th class="manage-column column-action" scope="col"><?php esc_html_e( 'Action', 'wp-passkey' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( empty( $public_key_credentials ) ) :
					?>
					<tr>
						<td colspan="4">
							<?php esc_html_e( 'No passkeys found.', 'wp-passkey' ); ?>
						</td>
					</tr>
					<?php
				endif;

				foreach ( $public_key_credentials as $public_key_credential ) :
					$name = $public_key_credential->name;
					$fingerprint = Base64UrlSafe::encodeUnpadded( $public_key_credential->getPublicKeyCredentialId() );
					?>
				<tr>
					<td>
						<?php echo esc_html( $name ); ?>
					</td>
					<td>
						<?php
							echo date_i18n( __( 'F j, Y' ), $public_key_credential->created );
						?>
					</td>
					<td>
						<?php echo esc_html( $public_key_credential->getPublicKeyCredentialDescriptor()->getType() ); ?>
					</td>
					<td>
						<?php
							printf(
								'<button type="button" data-id="%1$s" name="%2$s" id="%1$s" class="button delete" aria-label="%3$s">%4$s</button>',
								esc_attr( $fingerprint ),
								esc_attr( $name ),
								/* translators: %s: the application password's given name. */
								esc_attr( sprintf( __( 'Revoke "%s"' ), $name ) ),
								__( 'Revoke' )
							);
						?>
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
