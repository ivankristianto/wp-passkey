<?php
// phpcs:ignoreFile WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * Public Key Credential Source Repositoy.
 */

declare( strict_types = 1 );

namespace BioAuth;

use Exception;
use ParagonIE\ConstantTime\Base64UrlSafe;
use stdClass;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_User;

/**
 * Public Key Credential Source Repository.
 */
class Source_Repository implements PublicKeyCredentialSourceRepository {

	/**
	 * Meta key prefix.
	 *
	 * @var string
	 */
	private $meta_key = 'wp_passkey_';

	/**
	 * Find a credential source by its credential ID.
	 *
	 * @param string $public_key_credential_id The credential ID to find.
	 * @return null|PublicKeyCredentialSource The credential source, if found.
	 * @throws InvalidDataException If the credential source is invalid.
	 */
	public function findOneByCredentialId( string $public_key_credential_id ): ?PublicKeyCredentialSource {
		global $wpdb;

		$meta_key   = $this->meta_key . $public_key_credential_id;
		$public_key = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key ) );

		if ( ! $public_key instanceof stdClass || ! $public_key->meta_value ) {
			return null;
		}

		$public_key = (array) json_decode( $public_key->meta_value, true );

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
				"SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
				'wp_passkey_%%',
				$user->ID
			)
		);

		if ( ! $public_keys ) {
			return array();
		}

		$public_keys = array_map(
			function ( $public_key ) {
				return json_decode( $public_key->meta_value, true );
			},
			$public_keys
		);

		// Removes null values.
		$public_keys = array_filter( $public_keys );

		return array_map(
			function ( $public_key ) {
				return PublicKeyCredentialSource::createFromArray( $public_key );
			},
			$public_keys
		);
	}

	/**
	 * Save a new credential source.
	 *
	 * @param PublicKeyCredentialSource $public_key_credential_source The credential source to save.
	 * @param string[] $extra_data Extra data to store.
	 * @return void
	 * @throws Exception If the user is not found.
	 */
	public function saveCredentialSource( PublicKeyCredentialSource $public_key_credential_source, array $extra_data = array() ): void {
		$public_key = $public_key_credential_source->jsonSerialize();

		$user_handle = Base64UrlSafe::decodeNoPadding( $public_key['userHandle'] );
		$user        = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			throw new Exception( 'User not found.', 400 );
		}

		// Extra data to store.
		foreach ( $extra_data as $key => $value ) {
			$public_key['extra'][ $key ] = $value;
		}

		// Store the public key credential source. And need to add extra slashes to escape the slashes in the JSON.
		$public_key_json = addcslashes( wp_json_encode( $public_key, JSON_UNESCAPED_SLASHES ), '\\' );

		$meta_key = $this->meta_key . $public_key['publicKeyCredentialId'];
		update_user_meta( $user->ID, $meta_key, $public_key_json );
	}

	/**
	 * Delete a credential source.
	 *
	 * @param PublicKeyCredentialSource $public_key_credential_source The credential source to delete.
	 * @return void
	 * @throws Exception If the user is not found.
	 */
	public function deleteCredentialSource( PublicKeyCredentialSource $public_key_credential_source ): void {
		$public_key_credential_id = Base64UrlSafe::encodeUnpadded( $public_key_credential_source->getPublicKeyCredentialId() );

		$user_handle = $public_key_credential_source->getUserHandle();
		$user        = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			throw new Exception( 'User not found.', 404 );
		}

		$meta_key   = $this->meta_key . $public_key_credential_id;
		$is_success = delete_user_meta( $user->ID, $meta_key );

		if ( ! $is_success ) {
			throw new Exception( 'Unable to delete credential source.', 500 );
		}
	}

	/**
	 * Get extra data for a credential source.
	 *
	 * @param PublicKeyCredentialSource $public_key_credential_source The credential source to get extra data for.
	 * @return string[] The extra data.
	 * @throws Exception If the user is not found.
	 */
	public function get_extra_data( PublicKeyCredentialSource $public_key_credential_source ): array {
		$meta_key = $this->meta_key . Base64UrlSafe::encodeUnpadded( $public_key_credential_source->getPublicKeyCredentialId() );

		$user_handle = $public_key_credential_source->getUserHandle();

		$user = get_user_by( 'login', $user_handle );

		if ( ! $user instanceof WP_User ) {
			throw new Exception( 'User not found.', 404 );
		}

		$public_key = get_user_meta( $user->ID, $meta_key, true );

		if ( ! $public_key ) {
			throw new Exception( 'Credential source not found.', 404 );
		}

		$public_key = json_decode( $public_key, true );

		return $public_key['extra'] ?? [];
	}
}
