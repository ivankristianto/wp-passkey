<?php
// phpcs:ignoreFile WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * Webauthn Server.
 */

declare( strict_types = 1 );

namespace BioAuth;

use Cose\Algorithms;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\ECDSA\ES256K;
use Cose\Algorithm\Signature\ECDSA\ES384;
use Cose\Algorithm\Signature\ECDSA\ES512;
use Cose\Algorithm\Signature\EdDSA\Ed256;
use Cose\Algorithm\Signature\EdDSA\Ed512;
use Cose\Algorithm\Signature\RSA\PS256;
use Cose\Algorithm\Signature\RSA\PS384;
use Cose\Algorithm\Signature\RSA\PS512;
use Cose\Algorithm\Signature\RSA\RS256;
use Cose\Algorithm\Signature\RSA\RS384;
use Cose\Algorithm\Signature\RSA\RS512;
use Exception;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\CredentialRecord;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_User;

/**
 * Webauthn Server.
 *
 * @package BioAuth
 */
class Webauthn_Server {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Get the relying party.
	 *
	 * @return PublicKeyCredentialRpEntity
	 */
	public function get_relying_party(): PublicKeyCredentialRpEntity {
		// RP Entity i.e. the application.
		$rp_entity = PublicKeyCredentialRpEntity::create(
			get_bloginfo( 'name' ),      // Name.
			$this->get_current_domain(), // ID.
		);

		return $rp_entity;
	}

	/**
	 * Get the public key credential parameters list.
	 *
	 * @return PublicKeyCredentialParameters[]
	 */
	public function get_public_key_credential_parameters_list(): array {
		return array(
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES256 ),  // ECDSA w/ SHA-256.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES256K ), // ECDSA w/ SHA-256K.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES384 ),  // ECDSA w/ SHA-384.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES512 ),  // ECDSA w/ SHA-512.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_RS256 ),  // RSASSA-PKCS1-v1_5 w/ SHA-256.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_RS384 ),  // RSASSA-PKCS1-v1_5 w/ SHA-384.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_RS512 ),  // RSASSA-PKCS1-v1_5 w/ SHA-512.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_PS256 ),  // RSASSA-PSS w/ SHA-256.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_PS384 ),  // RSASSA-PSS w/ SHA-384.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_PS512 ),  // RSASSA-PSS w/ SHA-512.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ED256 ),  // EdDSA w/ SHA-256.
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ED512 ),  // EdDSA w/ SHA-512.
		);
	}

	/**
	 * Get the authenticator selection criteria.
	 *
	 * @return AuthenticatorSelectionCriteria
	 */
	public function get_authenticator_selection(): AuthenticatorSelectionCriteria {
		return AuthenticatorSelectionCriteria::create(
			AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM,
			AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
			AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
		);
	}

	/**
	 * Create user entity.
	 *
	 * @param WP_User $user Current User.
	 * @return PublicKeyCredentialUserEntity
	 */
	public function create_public_key_credential_user_entity( WP_User $user ): PublicKeyCredentialUserEntity {
		// User Entity.
		$user_entity = PublicKeyCredentialUserEntity::create(
			$user->user_login,   // Name.
			$user->user_login,  // Use user login as the ID.
			$user->display_name, // Display name.
		);

		return $user_entity;
	}

	/**
	 * Create Attestation Request for registration.
	 *
	 * @param WP_User $user Current User.
	 * @param string|null $challenge Challenge string.
	 * @return PublicKeyCredentialCreationOptions
	 */
	public function create_attestation_request( WP_User $user, ?string $challenge = null ): PublicKeyCredentialCreationOptions {
		$rp_entity                             = $this->get_relying_party();
		$user_entity                           = $this->create_public_key_credential_user_entity( $user );
		$public_key_credential_parameters_list = $this->get_public_key_credential_parameters_list();
		$authenticator_selection               = $this->get_authenticator_selection();

		if ( ! $challenge ) {
			$challenge = wp_generate_uuid4();
		}

		$public_key_credential_source_repository = new Source_Repository();
		$excluded_credentials                    = $public_key_credential_source_repository->findAllForUserEntity( $user_entity );

		$excluded_public_key_descriptors = array_map(
			function ( CredentialRecord $credential ) {
				return $credential->getPublicKeyCredentialDescriptor();
			},
			$excluded_credentials
		);

		$public_key_credential_creation_options = PublicKeyCredentialCreationOptions::create(
			$rp_entity,
			$user_entity,
			$challenge,
			$public_key_credential_parameters_list,
			$authenticator_selection,
			PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
			$excluded_public_key_descriptors,
			30_000
		);

		// Store challenge in User meta.
		update_user_meta( $user->ID, 'wp_passkey_challenge', $challenge );

		return $public_key_credential_creation_options;
	}

	/**
	 * Create assertion request for signin.
	 *
	 * @param null|string $challenge Challenge string.
	 * @return PublicKeyCredentialRequestOptions
	 */
	public function create_assertion_request( ?string $challenge = null ): PublicKeyCredentialRequestOptions {
		if ( ! $challenge ) {
			$challenge = wp_generate_uuid4();
		}

		$rp_entity                             = $this->get_relying_party();
		$public_key_credential_request_options = PublicKeyCredentialRequestOptions::create(
			$challenge,
			$rp_entity->id,
			[],
			PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
			30_000
		);

		return $public_key_credential_request_options;
	}

	/**
	 * Validate Attestation Response in Key Registration.
	 *
	 * @param string $data Data from the request.
	 * @param WP_User $user Current User.
	 * @return CredentialRecord
	 * @throws InvalidDataException If the request is invalid.
	 */
	public function validate_attestation_response( string $data, WP_User $user ): CredentialRecord {
		$attestation_statement_support_manager = AttestationStatementSupportManager::create();
		$attestation_statement_support_manager->add( NoneAttestationStatementSupport::create() );

		// Use Symfony Serializer to deserialize PublicKeyCredential from JSON.
		$serializer = $this->get_serializer();
		$public_key_credential = $serializer->deserialize( $data, PublicKeyCredential::class, 'json' );

		// Validate deserialized output type before property access.
		if ( ! $public_key_credential instanceof PublicKeyCredential ) {
			throw new InvalidDataException( $data, 'Invalid request: malformed credential data.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- This is not an output.
		}

		$authenticator_attestation_response = $public_key_credential->response;

		if ( ! $authenticator_attestation_response instanceof AuthenticatorAttestationResponse ) {
			throw new InvalidDataException( $data, 'Invalid request.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- This is not an output.
		}

		// Create CeremonyStepManagerFactory and configure for creation ceremony.
		$ceremony_factory = new CeremonyStepManagerFactory();
		$ceremony_factory->setAlgorithmManager( $this->get_algorithm_manager() );
		$ceremony_factory->setAttestationStatementSupportManager( $attestation_statement_support_manager );
		$ceremony_factory->setExtensionOutputCheckerHandler( ExtensionOutputCheckerHandler::create() );
		$ceremony_factory->setAllowedOrigins( $this->get_allowed_origins(), false );

		$creation_ceremony = $ceremony_factory->creationCeremony();

		$authenticator_attestation_response_validator = AuthenticatorAttestationResponseValidator::create(
			$creation_ceremony
		);

		// Get expected challenge from user meta.
		$challenge = get_user_meta( $user->ID, 'wp_passkey_challenge', true );

		$public_key_credential_creation_options = $this->create_attestation_request( $user, $challenge );

		// Validate the Attestation Response.
		$credential_record = $authenticator_attestation_response_validator->check(
			$authenticator_attestation_response,
			$public_key_credential_creation_options,
			$this->get_current_domain()
		);

		// Delete the challenge from user meta.
		delete_user_meta( $user->ID, 'wp_passkey_challenge' );

		return $credential_record;
	}

	/**
	 * Validate Assertion Response in Signin.
	 *
	 * @param string $data Data from the request.
	 * @param string $challenge Challenge string.
	 * @return CredentialRecord
	 * @throws InvalidDataException If the request is invalid.
	 * @throws Exception If the credential id is not found.
	 */
	public function validate_assertion_response( string $data, string $challenge ): CredentialRecord {
		$attestation_statement_support_manager = AttestationStatementSupportManager::create();
		$attestation_statement_support_manager->add( NoneAttestationStatementSupport::create() );

		// Use Symfony Serializer to deserialize PublicKeyCredential from JSON.
		$serializer                       = $this->get_serializer();
		$public_key_credential            = $serializer->deserialize( $data, PublicKeyCredential::class, 'json' );

		// Validate deserialized output type before property access.
		if ( ! $public_key_credential instanceof PublicKeyCredential ) {
			throw new InvalidDataException( $data, 'Invalid request: malformed credential data.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- This is not an output.
		}

		$authenticator_assertion_response = $public_key_credential->response;

		if ( ! $authenticator_assertion_response instanceof AuthenticatorAssertionResponse ) {
			throw new InvalidDataException( $data, 'Invalid request.' ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- This is not an output.
		}

		// Create CeremonyStepManagerFactory and configure for request ceremony.
		$ceremony_factory = new CeremonyStepManagerFactory();
		$ceremony_factory->setAlgorithmManager( $this->get_algorithm_manager() );
		$ceremony_factory->setAttestationStatementSupportManager( $attestation_statement_support_manager );
		$ceremony_factory->setExtensionOutputCheckerHandler( ExtensionOutputCheckerHandler::create() );
		$ceremony_factory->setAllowedOrigins( $this->get_allowed_origins(), false );

		$request_ceremony = $ceremony_factory->requestCeremony();

		$authenticator_assertion_response_validator = AuthenticatorAssertionResponseValidator::create(
			$request_ceremony
		);

		$public_key_credential_source_repository = new Source_Repository();
		$public_key_credential_request_options   = $this->create_assertion_request( $challenge );
		$credential_record                       = $public_key_credential_source_repository->findOneByCredentialId( $public_key_credential->rawId );

		if ( ! $credential_record instanceof CredentialRecord ) {
			throw new Exception( 'credential_not_found', 404 );
		}

		$credential_record = $authenticator_assertion_response_validator->check(
			$credential_record,
			$authenticator_assertion_response,
			$public_key_credential_request_options,
			$this->get_current_domain(),
			null
		);

		return $credential_record;
	}

	/**
	 * Get the webauthn serializer for deserializing PublicKeyCredential objects.
	 *
	 * @return \Symfony\Component\Serializer\SerializerInterface
	 */
	private function get_serializer() {
		$attestation_statement_support_manager = AttestationStatementSupportManager::create();
		$attestation_statement_support_manager->add( NoneAttestationStatementSupport::create() );

		$serializer_factory = new WebauthnSerializerFactory( $attestation_statement_support_manager );
		return $serializer_factory->create();
	}

	/**
	 * Get the algorithm manager.
	 *
	 * @return Manager
	 */
	private function get_algorithm_manager(): Manager {
		$algorithm_manager = Manager::create()
		->add(
			ES256::create(),
			ES256K::create(),
			ES384::create(),
			ES512::create(),
			RS256::create(),
			RS384::create(),
			RS512::create(),
			PS256::create(),
			PS384::create(),
			PS512::create(),
			Ed256::create(),
			Ed512::create(),
		);

		return $algorithm_manager;
	}

	/**
	 * Get the current domain.
	 */
	private function get_current_domain(): string {
		// Only get the domain frm the site url.
		$domain = wp_parse_url( get_site_url(), PHP_URL_HOST );

		return $domain;
	}

	/**
	 * Get allowed origins for WebAuthn ceremonies.
	 *
	 * @return string[] Array of allowed origins.
	 */
	private function get_allowed_origins(): array {
		$site_url = get_site_url();

		if ( empty( $site_url ) ) {
			return array();
		}

		$parsed = wp_parse_url( $site_url );

		if ( empty( $parsed['scheme'] ) || empty( $parsed['host'] ) ) {
			return array();
		}

		// Reconstruct origin: scheme://host[:port]
		$origin = $parsed['scheme'] . '://' . $parsed['host'];

		if ( ! empty( $parsed['port'] ) ) {
			$origin .= ':' . $parsed['port'];
		}

		return array( $origin );
	}

	/**
	 * Serialize PublicKeyCredentialCreationOptions to array for JSON response.
	 *
	 * @param PublicKeyCredentialCreationOptions $options The options to serialize.
	 * @return array<string, mixed> The serialized options.
	 */
	public function serialize_creation_options( PublicKeyCredentialCreationOptions $options ): array {
		$result = array(
			'challenge'             => Base64UrlSafe::encodeUnpadded( $options->challenge ),
			'timeout'               => $options->timeout,
			'rp'                    => array(
				'name' => $options->rp->name,
				'id'   => $options->rp->id,
			),
			'user'                  => array(
				'id'          => Base64UrlSafe::encodeUnpadded( $options->user->id ),
				'name'        => $options->user->name,
				'displayName' => $options->user->displayName,
			),
			'pubKeyCredParams'      => array_map(
				function ( $param ) {
					return array(
						'type' => $param->type,
						'alg'  => $param->alg,
					);
				},
				$options->pubKeyCredParams
			),
			'excludeCredentials'    => array_map(
				function ( $descriptor ) {
					return array(
						'type'       => $descriptor->type,
						'id'         => Base64UrlSafe::encodeUnpadded( $descriptor->id ),
						'transports' => $descriptor->transports,
					);
				},
				$options->excludeCredentials
			),
			'authenticatorSelection' => null,
			'attestation'           => $options->attestation,
		);

		if ( $options->authenticatorSelection ) {
			$result['authenticatorSelection'] = array(
				'authenticatorAttachment' => $options->authenticatorSelection->authenticatorAttachment,
				'requireResidentKey'      => $options->authenticatorSelection->requireResidentKey,
				'residentKey'             => $options->authenticatorSelection->residentKey,
				'userVerification'        => $options->authenticatorSelection->userVerification,
			);
		}

		return $result;
	}

	/**
	 * Serialize PublicKeyCredentialRequestOptions to array for JSON response.
	 *
	 * @param PublicKeyCredentialRequestOptions $options The options to serialize.
	 * @return array<string, mixed> The serialized options.
	 */
	public function serialize_request_options( PublicKeyCredentialRequestOptions $options ): array {
		return array(
			'challenge'        => Base64UrlSafe::encodeUnpadded( $options->challenge ),
			'timeout'          => $options->timeout,
			'rpId'             => $options->rpId,
			'allowCredentials' => array_map(
				function ( $descriptor ) {
					return array(
						'type'       => $descriptor->type,
						'id'         => Base64UrlSafe::encodeUnpadded( $descriptor->id ),
						'transports' => $descriptor->transports,
					);
				},
				$options->allowCredentials
			),
			'userVerification' => $options->userVerification,
		);
	}
}
