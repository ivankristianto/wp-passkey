<?php
/**
 * Webauthn Server.
 */

declare( strict_types = 1 );

namespace WP_Passkey;

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
use Webauthn\AttestationStatement\AttestationObjectLoader;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticationExtensions\ExtensionOutputCheckerHandler;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialUserEntity;
use WP_User;

/**
 * Webauthn Server.
 *
 * @package WP_Passkey
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
	public function get_relying_party() : PublicKeyCredentialRpEntity {
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
	 * @return array
	 */
	public function get_public_key_credential_parameters_list() : array {
		return [
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES256 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES256K ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES384 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ES512 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_RS256 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_RS384 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_RS512 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_PS256 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_PS384 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_PS512 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ED256 ),
			PublicKeyCredentialParameters::create( 'public-key', Algorithms::COSE_ALGORITHM_ED512 ),
		];
	}

	/**
	 * Get the authenticator selection criteria.
	 *
	 * @return AuthenticatorSelectionCriteria
	 */
	public function get_authenticator_selection() : AuthenticatorSelectionCriteria {
		return AuthenticatorSelectionCriteria::create()
		->setResidentKey( AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED )
		->setAuthenticatorAttachment( AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_PLATFORM );
	}

	/**
	 * Create user entity.
	 *
	 * @param WP_User $user Current User.
	 * @return PublicKeyCredentialUserEntity
	 */
	public function create_public_key_credential_user_entity( WP_User $user ) : PublicKeyCredentialUserEntity {
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
	 * @throws InvalidDataException
	 */
	public function create_attestation_request( WP_User $user, ?string $challenge = null ) : PublicKeyCredentialCreationOptions {
		$rp_entity = $this->get_relying_party();
		$user_entity = $this->create_public_key_credential_user_entity( $user );
		$public_key_credential_parameters_list = $this->get_public_key_credential_parameters_list();
		$authenticator_selection = $this->get_authenticator_selection();

		if ( ! $challenge ) {
			$challenge = wp_generate_uuid4();
		}

		$public_key_credential_creation_options = PublicKeyCredentialCreationOptions::create(
			$rp_entity,
			$user_entity,
			$challenge,
			$public_key_credential_parameters_list,
		)
		->setTimeout( 30_000 )
		->setAuthenticatorSelection( $authenticator_selection )
		->setAttestation( PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE );

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
	public function create_assertion_request( ?string $challenge = null ) : PublicKeyCredentialRequestOptions {
		if ( ! $challenge ) {
			$challenge = 'ivan1234';// wp_generate_uuid4();
		}

		$public_key_credential_request_options = PublicKeyCredentialRequestOptions::create(
			$challenge,
		);
		// ->allowCredentials( 30_000 )

		// Store challenge in User meta.
		// update_user_meta( $user->ID, 'wp_passkey_signin_challenge', $challenge );

		return $public_key_credential_request_options;
	}

	/**
	 * Validate Attestation Response in Key Registration.
	 *
	 * @param string $data Data from the request.
	 * @param WP_User $user Current User.
	 * @return PublicKeyCredentialSource
	 * @throws InvalidDataException
	 * @throws Throwable
	 */
	public function validate_attestation_response( string $data, WP_User $user ) : PublicKeyCredentialSource {
		$attestation_statement_support_manager = AttestationStatementSupportManager::create();
		$attestation_statement_support_manager->add( NoneAttestationStatementSupport::create() );

		$attestation_object_loader = AttestationObjectLoader::create(
			$attestation_statement_support_manager
		);

		$public_key_credential_loader = PublicKeyCredentialLoader::create(
			$attestation_object_loader
		);

		$public_key_credential = $public_key_credential_loader->load( $data );
		$authenticator_attestation_response = $public_key_credential->getResponse();

		if ( ! $authenticator_attestation_response instanceof AuthenticatorAttestationResponse ) {
			throw new InvalidDataException( $data, 'Invalid request.' );
		}

		$authenticator_attestation_response_validator = AuthenticatorAttestationResponseValidator::create(
			$attestation_statement_support_manager,
			null,
			null,
			ExtensionOutputCheckerHandler::create()
		);

		// Get expected challenge from user meta.
		$challenge = get_user_meta( $user->ID, 'wp_passkey_challenge', true );

		$public_key_credential_creation_options = $this->create_attestation_request( $user, $challenge );

		// Validate the Attestation Response.
		$public_key_credential_source = $authenticator_attestation_response_validator->check(
			$authenticator_attestation_response,
			$public_key_credential_creation_options,
			$this->get_current_domain(),
			[ 'localhost' ] // Secure RelyingParty, to make localhost enable.
		);

		// Delete the challenge from user meta.
		delete_user_meta( $user->ID, 'wp_passkey_challenge' );

		return $public_key_credential_source;
	}

	/**
	 * Validate Assertion Response in Signin.
	 *
	 * @param string $data Data from the request.
	 * @return PublicKeyCredentialSource
	 * @throws InvalidDataException
	 * @throws Throwable
	 */
	public function validate_assertion_response( string $data ) : PublicKeyCredentialSource {
		$attestation_statement_support_manager = AttestationStatementSupportManager::create();
		$attestation_statement_support_manager->add( NoneAttestationStatementSupport::create() );

		$attestation_object_loader = AttestationObjectLoader::create(
			$attestation_statement_support_manager
		);

		$public_key_credential_loader = PublicKeyCredentialLoader::create(
			$attestation_object_loader
		);

		$public_key_credential = $public_key_credential_loader->load( $data );
		$authenticator_assertion_response = $public_key_credential->getResponse();

		if ( ! $authenticator_assertion_response instanceof AuthenticatorAssertionResponse ) {
			throw new InvalidDataException( $data, 'Invalid request.' );
		}

		$authenticator_assertion_response_validator = AuthenticatorAssertionResponseValidator::create(
			null,  // The Credential Repository service.
			null,                  // The token binding handler.
			ExtensionOutputCheckerHandler::create(),       // The extension output checker handler.
			$this->get_algorithm_manager()                      // The COSE Algorithm Manager.
		);

		// Get expected challenge from session.
		$challenge = 'ivan1234';// get_user_meta( $user->ID, 'wp_passkey_challenge', true );

		$public_key_credential_request_options = $this->create_assertion_request( $challenge );

		$public_key_credential_source = $authenticator_assertion_response_validator->check(
			$public_key_credential->getRawId(),
			$authenticator_assertion_response,
			$public_key_credential_request_options,
			$this->get_current_domain(),
			null, // @TODO: Come back to this to check user handle.
			[ 'localhost' ] // Secure RelyingParty, to make localhost enable.
		);

		return $public_key_credential_source;
	}

	/**
	 * Get the algorithm manager.
	 *
	 * @return Manager
	 */
	private function get_algorithm_manager() : Manager {
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
	private function get_current_domain() : string {
		// Only get the domain frm the site url.
		$domain = parse_url( get_site_url(), PHP_URL_HOST );

		return $domain;
	}
}