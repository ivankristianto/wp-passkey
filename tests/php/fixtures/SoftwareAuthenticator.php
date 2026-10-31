<?php
/**
 * Minimal ES256 software authenticator for end-to-end WebAuthn tests.
 */

namespace BioAuth\Tests\Fixtures;

use OpenSSLAsymmetricKey;
use ParagonIE\ConstantTime\Base64UrlSafe;

/**
 * Produces real, signed WebAuthn registration and authentication responses.
 */
class SoftwareAuthenticator {

	/**
	 * Private key.
	 *
	 * @var OpenSSLAsymmetricKey
	 */
	private $key;

	/**
	 * Raw credential ID.
	 *
	 * @var string
	 */
	public $credential_id;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->key           = openssl_pkey_new(
			array(
				'private_key_type' => OPENSSL_KEYTYPE_EC,
				'curve_name'       => 'prime256v1',
			)
		);
		$this->credential_id = random_bytes( 32 );
	}

	/**
	 * Origin of the test site (scheme://host[:port]).
	 *
	 * @return string
	 */
	public static function origin(): string {
		$parsed = wp_parse_url( get_site_url() );
		$origin = $parsed['scheme'] . '://' . $parsed['host'];

		if ( ! empty( $parsed['port'] ) ) {
			$origin .= ':' . $parsed['port'];
		}

		return $origin;
	}

	/**
	 * Build a registration (attestation) response.
	 *
	 * @param string $challenge Base64url challenge from the creation options.
	 * @return array<string, mixed>
	 */
	public function create( string $challenge ): array {
		$client_data = wp_json_encode(
			array(
				'type'      => 'webauthn.create',
				'challenge' => $challenge,
				'origin'    => self::origin(),
			),
			JSON_UNESCAPED_SLASHES
		);

		$auth_data = $this->rp_id_hash()
			. chr( 0x45 ) // UP | UV | AT.
			. pack( 'N', 0 )
			. str_repeat( "\0", 16 ) // AAGUID.
			. pack( 'n', strlen( $this->credential_id ) )
			. $this->credential_id
			. self::cbor( $this->cose_public_key() );

		$attestation_object = self::cbor(
			array(
				'fmt'      => 'none',
				'attStmt'  => array(),
				'authData' => self::bytes( $auth_data ),
			)
		);

		return array(
			'id'       => Base64UrlSafe::encodeUnpadded( $this->credential_id ),
			'rawId'    => Base64UrlSafe::encodeUnpadded( $this->credential_id ),
			'type'     => 'public-key',
			'response' => array(
				'clientDataJSON'    => Base64UrlSafe::encodeUnpadded( $client_data ),
				'attestationObject' => Base64UrlSafe::encodeUnpadded( $attestation_object ),
				'transports'        => array( 'internal' ),
			),
		);
	}

	/**
	 * Build an authentication (assertion) response.
	 *
	 * @param string $challenge   Base64url challenge from the request options.
	 * @param string $user_handle User handle (user_login).
	 * @param int    $sign_count  Signature counter to report.
	 * @return array<string, mixed>
	 */
	public function get( string $challenge, string $user_handle, int $sign_count ): array {
		$client_data = wp_json_encode(
			array(
				'type'      => 'webauthn.get',
				'challenge' => $challenge,
				'origin'    => self::origin(),
			),
			JSON_UNESCAPED_SLASHES
		);

		$auth_data = $this->rp_id_hash() . chr( 0x05 ) . pack( 'N', $sign_count ); // UP | UV.

		openssl_sign( $auth_data . hash( 'sha256', $client_data, true ), $signature, $this->key, OPENSSL_ALGO_SHA256 );

		return array(
			'id'       => Base64UrlSafe::encodeUnpadded( $this->credential_id ),
			'rawId'    => Base64UrlSafe::encodeUnpadded( $this->credential_id ),
			'type'     => 'public-key',
			'response' => array(
				'clientDataJSON'    => Base64UrlSafe::encodeUnpadded( $client_data ),
				'authenticatorData' => Base64UrlSafe::encodeUnpadded( $auth_data ),
				'signature'         => Base64UrlSafe::encodeUnpadded( $signature ),
				'userHandle'        => Base64UrlSafe::encodeUnpadded( $user_handle ),
			),
		);
	}

	/**
	 * SHA-256 of the RP ID (site host).
	 *
	 * @return string
	 */
	private function rp_id_hash(): string {
		return hash( 'sha256', wp_parse_url( get_site_url(), PHP_URL_HOST ), true );
	}

	/**
	 * COSE EC2 public key map.
	 *
	 * @return array<int, mixed>
	 */
	private function cose_public_key(): array {
		$details = openssl_pkey_get_details( $this->key );

		return array(
			1  => 2,  // kty: EC2.
			3  => -7, // alg: ES256.
			-1 => 1,  // crv: P-256.
			-2 => self::bytes( str_pad( $details['ec']['x'], 32, "\0", STR_PAD_LEFT ) ),
			-3 => self::bytes( str_pad( $details['ec']['y'], 32, "\0", STR_PAD_LEFT ) ),
		);
	}

	/**
	 * Mark a string as a CBOR byte string.
	 *
	 * @param string $raw Raw bytes.
	 * @return object
	 */
	private static function bytes( string $raw ): object {
		return (object) array( 'bytes' => $raw );
	}

	/**
	 * Minimal CBOR encoder: ints, text strings, byte strings and maps.
	 *
	 * @param mixed $value Value to encode.
	 * @return string
	 */
	private static function cbor( $value ): string {
		if ( is_int( $value ) ) {
			return $value >= 0 ? self::cbor_head( 0, $value ) : self::cbor_head( 1, -1 - $value );
		}

		if ( is_object( $value ) ) {
			return self::cbor_head( 2, strlen( $value->bytes ) ) . $value->bytes;
		}

		if ( is_string( $value ) ) {
			return self::cbor_head( 3, strlen( $value ) ) . $value;
		}

		$out = self::cbor_head( 5, count( $value ) );
		foreach ( $value as $key => $item ) {
			$out .= self::cbor( $key ) . self::cbor( $item );
		}

		return $out;
	}

	/**
	 * CBOR item head.
	 *
	 * @param int $major  Major type.
	 * @param int $length Argument.
	 * @return string
	 */
	private static function cbor_head( int $major, int $length ): string {
		if ( $length < 24 ) {
			return chr( ( $major << 5 ) | $length );
		}

		if ( $length < 256 ) {
			return chr( ( $major << 5 ) | 24 ) . chr( $length );
		}

		return chr( ( $major << 5 ) | 25 ) . pack( 'n', $length );
	}
}
