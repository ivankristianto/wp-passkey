<?php
/**
 * Public Key Credential Source Repositoy.
 */

declare( strict_types = 1 );

namespace WP_Passkey;

use Exception;
use ParagonIE\ConstantTime\Base64UrlSafe;
use stdClass;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_User;

/**
 * Public Key Credential Source Repository.
 */
class Source_Repository implements PublicKeyCredentialSourceRepository {

	/**
	 * Find a credential source by its credential ID.
	 *
	 * @param string $public_key_credential_id The credential ID to find.
	 * @return null|PublicKeyCredentialSource The credential source, if found.
	 * @throws InvalidDataException If the credential source is invalid.
	 */
	public function findOneByCredentialId( string $public_key_credential_id ): ?PublicKeyCredentialSource {
		global $wpdb;

		$public_key = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", 'wp_passkey_' . $public_key_credential_id ) );

		if ( ! $public_key instanceof stdClass || ! $public_key->meta_value ) {
			return null;
		}

		$public_key = json_decode( $public_key->meta_value, true );

		return PublicKeyCredentialSource::createFromArray( $public_key );
	}

	/**
	 * Find all credential sources for a given user entity.
	 *
	 * @param PublicKeyCredentialUserEntity $public_key_credential_user_entity The user entity to find credential sources for.
	 * @return PublicKeyCredentialSource[] The credential sources, if found.
	 * @throws Exception If the user is not found.
	 */
	public function findAllForUserEntity( PublicKeyCredentialUserEntity $public_key_credential_user_entity ): array {
		$user_handle = $public_key_credential_user_entity->getId();

		$user = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			throw new Exception( 'User not found.', 400 );
		}

		global $wpdb;

		$public_keys = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d", 'wp_passkey_%%', $user->ID
			)
		);

		if ( ! $public_keys ) {
			return [];
		}

		$public_keys = array_map(
			function( $public_key ) {
				return json_decode( $public_key->meta_value, true );
			},
			$public_keys
		);

		// Removes null values.
		$public_keys = array_filter( $public_keys );

		return array_map(
			function( $public_key ) {
				$public_key_credential_source = PublicKeyCredentialSource::createFromArray( $public_key );
				$public_key_credential_source->name = $public_key['name'] ?? '';
				$public_key_credential_source->created = $public_key['created'] ?? '';
				return $public_key_credential_source;
			},
			$public_keys
		);
	}

	/**
	 * Save a new credential source.
	 *
	 * @param PublicKeyCredentialSource $public_key_credential_source The credential source to save.
	 * @return void
	 * @throws Exception If the user is not found.
	 */
	public function saveCredentialSource( PublicKeyCredentialSource $public_key_credential_source ): void {
		$public_key = $public_key_credential_source->jsonSerialize();

		$user_handle = Base64UrlSafe::decodeNoPadding( $public_key['userHandle'] );
		$user = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			throw new Exception( 'User not found.', 400 );
		}

		// Custom data to store.
		$public_key['name'] = $public_key['userHandle'];
		$public_key['created'] = time();

		// Store the public key credential source. And need to add extra slashes to escape the slashes in the JSON.
		$public_key_json = addcslashes( wp_json_encode( $public_key, JSON_UNESCAPED_SLASHES ), '\\' );
		update_user_meta( $user->ID, 'wp_passkey_' . $public_key['publicKeyCredentialId'], $public_key_json );
	}

	/**
	 * Delete a credential source.
	 *
	 * @param PublicKeyCredentialSource $public_key_credential_source The credential source to delete.
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws RangeException
	 * @throws TypeError
	 * @throws Exception
	 */
	public function deleteCredentialSource( PublicKeyCredentialSource $public_key_credential_source ): void {
		$public_key_credential_id = Base64UrlSafe::encodeUnpadded( $public_key_credential_source->getPublicKeyCredentialId() );

		$user_handle = $public_key_credential_source->getUserHandle();
		$user = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			throw new Exception( 'User not found.', 404 );
		}

		$is_success = delete_user_meta( $user->ID, 'wp_passkey_' . $public_key_credential_id );

		if ( ! $is_success ) {
			throw new Exception( 'Unable to delete credential source.', 500 );
		}
	}

}
