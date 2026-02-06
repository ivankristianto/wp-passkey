<?php
/**
 * WebAuthn Test Helper
 */

namespace BioAuth\Tests\Fixtures;

use Mockery;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Helper class for WebAuthn testing
 */
class WebauthnTestHelper {

	/**
	 * Create a mock CredentialRecord for testing
	 *
	 * @param string $user_handle User handle (username).
	 * @return CredentialRecord
	 */
	public static function mock_credential_record( string $user_handle = 'testuser' ): CredentialRecord {
		return CredentialRecord::create(
			random_bytes( 32 ), // publicKeyCredentialId
			'public-key',
			[],
			'none',
			EmptyTrustPath::create(),
			Uuid::v4(),
			random_bytes( 77 ), // credentialPublicKey (COSE format)
			$user_handle,
			0
		);
	}

	/**
	 * Create a mock PublicKeyCredentialUserEntity
	 *
	 * @param string $username Username.
	 * @param string $display_name Display name.
	 * @return PublicKeyCredentialUserEntity
	 */
	public static function mock_user_entity( string $username = 'testuser', string $display_name = 'Test User' ): PublicKeyCredentialUserEntity {
		return PublicKeyCredentialUserEntity::create(
			$username,
			$username,
			$display_name
		);
	}

	/**
	 * Mock AuthenticatorAttestationResponseValidator
	 *
	 * @param CredentialRecord $return_credential Credential to return.
	 * @return Mockery\MockInterface
	 */
	public static function mock_attestation_validator( CredentialRecord $return_credential ): \Mockery\MockInterface {
		$mock = Mockery::mock( AuthenticatorAttestationResponseValidator::class );
		$mock->shouldReceive( 'check' )->andReturn( $return_credential );
		return $mock;
	}

	/**
	 * Mock AuthenticatorAssertionResponseValidator
	 *
	 * @param CredentialRecord $return_credential Credential to return.
	 * @return Mockery\MockInterface
	 */
	public static function mock_assertion_validator( CredentialRecord $return_credential ): \Mockery\MockInterface {
		$mock = Mockery::mock( AuthenticatorAssertionResponseValidator::class );
		$mock->shouldReceive( 'check' )->andReturn( $return_credential );
		return $mock;
	}

	/**
	 * Create credential array data for testing
	 *
	 * @return array
	 */
	public static function create_credential_array_data(): array {
		$credential_id = random_bytes( 32 );
		$public_key    = random_bytes( 77 );
		$user_handle   = 'testuser';

		return array(
			'publicKeyCredentialId' => Base64UrlSafe::encodeUnpadded( $credential_id ),
			'type'                  => 'public-key',
			'transports'            => array(),
			'attestationType'       => 'none',
			'trustPath'             => array( 'type' => 'empty' ),
			'aaguid'                => Uuid::v4()->toRfc4122(),
			'credentialPublicKey'   => Base64UrlSafe::encodeUnpadded( $public_key ),
			'userHandle'            => Base64UrlSafe::encodeUnpadded( $user_handle ),
			'counter'               => 0,
			'otherUI'               => null,
			'backupEligible'        => null,
			'backupStatus'          => null,
			'uvInitialized'         => null,
		);
	}
}
