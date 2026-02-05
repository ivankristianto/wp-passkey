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
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;
use WP_User;

/**
 * Public Key Credential Source Repository.
 *
 * Note: In webauthn-lib 5.x, the PublicKeyCredentialSourceRepository interface was removed.
 * This class no longer implements an interface but provides the same credential storage functionality.
 */
class Source_Repository {

	/**
	 * Meta key prefix.
	 *
	 * @var string
	 */
	private $meta_key = 'wp_passkey_';

	/**
	 * Find a credential source by its credential ID.
	 *
	 * @param string $public_key_credential_id The credential ID (raw binary) to find.
	 * @return null|CredentialRecord The credential record, if found.
	 * @throws InvalidDataException If the credential source is invalid.
	 */
	public function findOneByCredentialId( string $public_key_credential_id ): ?CredentialRecord {
		global $wpdb;

		// Encode credential ID to match the format used when saving.
		$meta_key   = $this->meta_key . Base64UrlSafe::encodeUnpadded( $public_key_credential_id );
		$public_key = $wpdb->get_row( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $meta_key ) );

		if ( ! $public_key instanceof stdClass || ! $public_key->meta_value ) {
			return null;
		}

		$public_key = (array) json_decode( $public_key->meta_value, true );

		return $this->array_to_credential( $public_key );
	}

	/**
	 * Find all credential sources for a given user entity.
	 *
	 * @param PublicKeyCredentialUserEntity $public_key_credential_user_entity The user entity to find credential sources for.
	 * @return CredentialRecord[] The credential records, if found.
	 * @throws Exception If the user is not found.
	 */
	public function findAllForUserEntity( PublicKeyCredentialUserEntity $public_key_credential_user_entity ): array {
		$user_handle = $public_key_credential_user_entity->id;

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
			return [];
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
				return $this->array_to_credential( $public_key );
			},
			$public_keys
		);
	}

	/**
	 * Save a new credential source.
	 *
	 * @param CredentialRecord $credential The credential record to save.
	 * @param string[] $extra_data Extra data to store.
	 * @return void
	 * @throws Exception If the user is not found.
	 */
	public function saveCredentialSource( CredentialRecord $credential, array $extra_data = array() ): void {
		$public_key = $this->credential_to_array( $credential );

		// Decode userHandle to get the plain username for lookup.
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

		// Use base64url-encoded credential ID as meta key (already encoded in array).
		$meta_key = $this->meta_key . $public_key['publicKeyCredentialId'];
		update_user_meta( $user->ID, $meta_key, $public_key_json );
	}

	/**
	 * Delete a credential source.
	 *
	 * @param CredentialRecord $credential The credential record to delete.
	 * @return void
	 * @throws Exception If the user is not found.
	 */
	public function deleteCredentialSource( CredentialRecord $credential ): void {
		$public_key_credential_id = Base64UrlSafe::encodeUnpadded( $credential->publicKeyCredentialId );

		$user_handle = $credential->userHandle;
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
	 * @param CredentialRecord $credential The credential record to get extra data for.
	 * @return string[] The extra data.
	 * @throws Exception If the user is not found.
	 */
	public function get_extra_data( CredentialRecord $credential ): array {
		$meta_key = $this->meta_key . Base64UrlSafe::encodeUnpadded( $credential->publicKeyCredentialId );

		$user_handle = $credential->userHandle;

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

	/**
	 * Convert CredentialRecord to array for storage.
	 *
	 * @param CredentialRecord $credential The credential to serialize.
	 * @return array<string, mixed> The serialized credential.
	 */
	private function credential_to_array( CredentialRecord $credential ): array {
		return array(
			'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded( $credential->publicKeyCredentialId ),
			'type'                  => $credential->type,
			'transports'            => $credential->transports,
			'attestationType'       => $credential->attestationType,
			'trustPath'             => $this->serialize_trust_path( $credential->trustPath ),
			'aaguid'                => $credential->aaguid->toRfc4122(),
			'credentialPublicKey'   => Base64UrlSafe::encodeUnpadded( $credential->credentialPublicKey ),
			'userHandle'            => Base64UrlSafe::encodeUnpadded( $credential->userHandle ),
			'counter'               => $credential->counter,
			'otherUI'               => $credential->otherUI,
			'backupEligible'        => $credential->backupEligible,
			'backupStatus'          => $credential->backupStatus,
			'uvInitialized'         => $credential->uvInitialized,
		);
	}

	/**
	 * Convert array to CredentialRecord.
	 *
	 * @param array<string, mixed> $data The serialized credential data.
	 * @return CredentialRecord The credential record.
	 */
	private function array_to_credential( array $data ): CredentialRecord {
		return CredentialRecord::create(
			Base64UrlSafe::decodeNoPadding( $data['publicKeyCredentialId'] ),
			$data['type'],
			$data['transports'] ?? [],
			$data['attestationType'],
			$this->deserialize_trust_path( $data['trustPath'] ?? [] ),
			Uuid::fromString( $data['aaguid'] ?? Uuid::v4()->toRfc4122() ),
			Base64UrlSafe::decodeNoPadding( $data['credentialPublicKey'] ),
			Base64UrlSafe::decodeNoPadding( $data['userHandle'] ),
			$data['counter'] ?? 0,
			$data['otherUI'] ?? null,
			$data['backupEligible'] ?? null,
			$data['backupStatus'] ?? null,
			$data['uvInitialized'] ?? null
		);
	}

	/**
	 * Serialize TrustPath to array.
	 *
	 * @param \Webauthn\TrustPath\TrustPath $trust_path The trust path to serialize.
	 * @return array<string, mixed> The serialized trust path.
	 */
	private function serialize_trust_path( $trust_path ): array {
		if ( $trust_path instanceof EmptyTrustPath ) {
			return array( 'type' => 'empty' );
		}

		// For other TrustPath types, store the class name.
		return array(
			'type'  => 'certificate',
			'class' => get_class( $trust_path ),
		);
	}

	/**
	 * Deserialize TrustPath from array.
	 *
	 * @param array<string, mixed> $data The serialized trust path data.
	 * @return \Webauthn\TrustPath\TrustPath The trust path.
	 */
	private function deserialize_trust_path( array $data ) {
		$type = $data['type'] ?? 'empty';

		if ( 'empty' === $type ) {
			return EmptyTrustPath::create();
		}

		// For certificate trust paths, return EmptyTrustPath as fallback.
		// Full certificate chain reconstruction would require storing certificate data.
		return EmptyTrustPath::create();
	}
}
