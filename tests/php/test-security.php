<?php
/**
 * End-to-end security regression tests for the passkey ceremonies.
 */

namespace BioAuth\Tests\Phpunit;

use BioAuth\Rest_API;
use BioAuth\Source_Repository;
use BioAuth\Tests\Fixtures\SoftwareAuthenticator;
use BioAuth\Webauthn_Server;
use Cose\Algorithms;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Security regression tests.
 */
class Test_Security extends WP_UnitTestCase {

	/**
	 * Test user ID.
	 *
	 * @var int
	 */
	private $user_id;

	/**
	 * REQUEST_URI before the test, restored in tear_down().
	 *
	 * @var string|null
	 */
	private $original_request_uri;

	/**
	 * Set up.
	 */
	public function set_up(): void {
		parent::set_up();

		$this->original_request_uri = $_SERVER['REQUEST_URI'] ?? null;

		// Don't emit Set-Cookie headers from the CLI test runner.
		add_filter( 'send_auth_cookies', '__return_false' );

		$this->user_id = self::factory()->user->create(
			array(
				'user_login' => 'passkey_user',
				'role'       => 'administrator',
			)
		);
	}

	/**
	 * Tear down.
	 */
	public function tear_down(): void {
		// WP only resets $_SERVER between core tests, so restore it explicitly.
		if ( null === $this->original_request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->original_request_uri;
		}

		parent::tear_down();
	}

	/**
	 * Start a registration as the test user and return the signed response body.
	 *
	 * @param SoftwareAuthenticator $authenticator Authenticator.
	 * @return string JSON registration response.
	 */
	private function start_registration( SoftwareAuthenticator $authenticator ): string {
		wp_set_current_user( $this->user_id );

		$options = rest_do_request( new WP_REST_Request( 'POST', '/wp-passkey/v1/register-request' ) )->get_data();

		return wp_json_encode( $authenticator->create( $options['challenge'] ) );
	}

	/**
	 * Submit a registration response body.
	 *
	 * @param string $body JSON registration response.
	 * @return \WP_REST_Response
	 */
	private function finish_registration( string $body ) {
		wp_set_current_user( $this->user_id );

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/register-response' );
		$request->set_header( 'User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)' );
		$request->set_body( $body );

		return rest_do_request( $request );
	}

	/**
	 * Register a passkey for the test user through the REST API.
	 *
	 * @param SoftwareAuthenticator $authenticator Authenticator.
	 * @return \WP_REST_Response
	 */
	private function register( SoftwareAuthenticator $authenticator ) {
		return $this->finish_registration( $this->start_registration( $authenticator ) );
	}

	/**
	 * Assert a response was rejected for the expected reason.
	 *
	 * @param \WP_REST_Response $response Response.
	 * @param int               $status   Expected HTTP status.
	 * @param string            $message  Expected substring of the error message.
	 */
	private function assert_rejected( $response, int $status, string $message ): void {
		$data = $response->get_data();

		$this->assertSame( $status, $response->get_status(), wp_json_encode( $data ) );
		$this->assertStringContainsString( $message, $data['message'] ?? '' );
	}

	/**
	 * Sign in through the REST API.
	 *
	 * @param SoftwareAuthenticator $authenticator Authenticator.
	 * @param int                   $sign_count    Counter to report.
	 * @param mixed                 $redirect_to   Requested redirect.
	 * @return \WP_REST_Response
	 */
	private function sign_in( SoftwareAuthenticator $authenticator, int $sign_count, $redirect_to = '' ) {
		wp_set_current_user( 0 );

		$options = rest_do_request( new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-request' ) )->get_data();

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-response' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'request_id'  => $options['request_id'],
					'asseResp'    => $authenticator->get( $options['options']['challenge'], 'passkey_user', $sign_count ),
					'redirect_to' => $redirect_to,
				)
			)
		);

		return rest_do_request( $request );
	}

	/**
	 * Unsafe redirect targets fall back to the admin URL.
	 *
	 * @dataProvider data_unsafe_redirects
	 *
	 * @param mixed $redirect Requested redirect.
	 */
	public function test_login_redirect_rejects_unsafe_targets( $redirect ) {
		$user = get_user_by( 'id', $this->user_id );

		$this->assertSame( admin_url(), Rest_API\get_login_redirect( $redirect, $user ) );
	}

	/**
	 * Unsafe redirect targets.
	 *
	 * @return array<string, array<mixed>>
	 */
	public function data_unsafe_redirects(): array {
		return array(
			'javascript scheme'     => array( 'javascript:alert(document.domain)' ),
			'mixed case javascript' => array( ' JaVaScRiPt:alert(1)' ),
			'data scheme'           => array( 'data:text/html,<script>alert(1)</script>' ),
			'external https'        => array( 'https://evil.example/phish' ),
			'protocol relative'     => array( '//evil.example/phish' ),
			'empty'                 => array( '' ),
			'non-string'            => array( array( 'https://evil.example' ) ),
		);
	}

	/**
	 * Same-site redirect targets are kept.
	 */
	public function test_login_redirect_keeps_same_site_targets() {
		$user   = get_user_by( 'id', $this->user_id );
		$target = admin_url( 'edit.php?post_type=page' );

		$this->assertSame( $target, Rest_API\get_login_redirect( $target, $user ) );
		$this->assertSame( home_url( '/sample-page/' ), Rest_API\get_login_redirect( home_url( '/sample-page/' ), $user ) );

		// Backslashes are stripped, so "/\evil.example" can't become "//evil.example"; it stays an on-site path.
		$this->assertSame( '/evil.example', Rest_API\get_login_redirect( '/\\evil.example', $user ) );
	}

	/**
	 * Path-relative redirects resolve against the site, not the REST route handling the request.
	 */
	public function test_login_redirect_resolves_path_relative_against_site() {
		$user                   = get_user_by( 'id', $this->user_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wp-passkey/v1/signin-response';

		$this->assertSame( admin_url(), Rest_API\get_login_redirect( 'wp-admin/', $user ) );
		$this->assertSame( admin_url( 'profile.php' ), Rest_API\get_login_redirect( 'wp-admin/profile.php', $user ) );
	}

	/**
	 * A path-relative URL returned by the login_redirect filter is also resolved against the site.
	 */
	public function test_login_redirect_resolves_path_relative_filter_output() {
		$user                   = get_user_by( 'id', $this->user_id );
		$_SERVER['REQUEST_URI'] = '/wp-json/wp-passkey/v1/signin-response';

		add_filter(
			'login_redirect',
			static function () {
				return 'wp-admin/profile.php';
			}
		);

		$this->assertSame( admin_url( 'profile.php' ), Rest_API\get_login_redirect( '', $user ) );
	}

	/**
	 * The login_redirect filter can't be used to smuggle an unsafe URL.
	 */
	public function test_login_redirect_filter_output_is_validated() {
		$user = get_user_by( 'id', $this->user_id );

		add_filter(
			'login_redirect',
			static function () {
				return 'javascript:alert(1)';
			}
		);

		$this->assertSame( admin_url(), Rest_API\get_login_redirect( admin_url( 'users.php' ), $user ) );
	}

	/**
	 * Full register + sign-in returns a validated redirect, persists the counter and keeps extra data.
	 */
	public function test_signin_returns_safe_redirect_and_persists_counter() {
		$authenticator = new SoftwareAuthenticator();

		$this->assertSame( 200, $this->register( $authenticator )->get_status() );

		$response = $this->sign_in( $authenticator, 10, 'javascript:alert(document.domain)' );

		$this->assertSame( 200, $response->get_status(), wp_json_encode( $response->get_data() ) );
		$this->assertSame( 'verified', $response->get_data()['status'] );
		$this->assertSame( admin_url(), $response->get_data()['redirect_to'] );

		$repository = new Source_Repository();
		$record     = $repository->findOneByCredentialId( $authenticator->credential_id );

		$this->assertSame( 10, $record->counter );
		$this->assertSame( 'Generated on Mac OS', $repository->get_extra_data( $record )['name'] );
	}

	/**
	 * A counter that goes backwards (possible cloned authenticator) is rejected.
	 */
	public function test_signin_rejects_counter_regression() {
		$authenticator = new SoftwareAuthenticator();
		$this->register( $authenticator );

		$this->assertSame( 200, $this->sign_in( $authenticator, 10 )->get_status() );
		$this->assert_rejected( $this->sign_in( $authenticator, 5 ), 400, 'Invalid counter.' );
		$this->assert_rejected( $this->sign_in( $authenticator, 10 ), 400, 'Invalid counter.' );
	}

	/**
	 * A signature from a different key is rejected.
	 */
	public function test_signin_rejects_signature_from_other_key() {
		$authenticator = new SoftwareAuthenticator();
		$this->register( $authenticator );

		$impostor                = new SoftwareAuthenticator();
		$impostor->credential_id = $authenticator->credential_id;

		$this->assert_rejected( $this->sign_in( $impostor, 1 ), 400, 'Invalid signature.' );
	}

	/**
	 * Registering the same credential ID twice is rejected.
	 */
	public function test_register_rejects_duplicate_credential_id() {
		$authenticator = new SoftwareAuthenticator();

		$this->assertSame( 200, $this->register( $authenticator )->get_status() );

		$response = $this->register( $authenticator );

		$this->assertSame( 409, $response->get_status() );
		$this->assertSame( 'credential_exists', $response->get_data()['code'] );
	}

	/**
	 * Registration fails once the challenge has expired.
	 */
	public function test_register_requires_unexpired_challenge() {
		$body = $this->start_registration( new SoftwareAuthenticator() );

		// Simulate the transient expiring.
		delete_transient( 'wp_passkey_reg_challenge_' . $this->user_id );

		$this->assert_rejected( $this->finish_registration( $body ), 400, 'Registration challenge expired' );
	}

	/**
	 * A successful registration consumes its challenge, so the response can't be replayed.
	 */
	public function test_register_challenge_is_single_use_after_success() {
		$body = $this->start_registration( new SoftwareAuthenticator() );

		$this->assertSame( 200, $this->finish_registration( $body )->get_status() );

		// Without single-use challenges this replay would reach the duplicate check (409) instead.
		$this->assert_rejected( $this->finish_registration( $body ), 400, 'Registration challenge expired' );
	}

	/**
	 * A failed registration also consumes its challenge.
	 */
	public function test_register_challenge_is_single_use_after_failure() {
		$authenticator = new SoftwareAuthenticator();
		$valid_body    = $this->start_registration( $authenticator );

		// Same challenge, but a tampered origin: validation fails after the challenge is read.
		$tampered = json_decode( $valid_body, true );
		$client   = json_decode( base64_decode( strtr( $tampered['response']['clientDataJSON'], '-_', '+/' ) ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		$client['origin']                         = 'https://evil.example';
		$tampered['response']['clientDataJSON'] = rtrim( strtr( base64_encode( wp_json_encode( $client, JSON_UNESCAPED_SLASHES ) ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode

		$this->assert_rejected( $this->finish_registration( wp_json_encode( $tampered ) ), 400, 'origin' );
		$this->assert_rejected( $this->finish_registration( $valid_body ), 400, 'Registration challenge expired' );
	}

	/**
	 * The legacy non-expiring challenge user meta is removed on registration.
	 */
	public function test_register_removes_legacy_challenge_meta() {
		update_user_meta( $this->user_id, 'wp_passkey_challenge', 'legacy-challenge' );

		$this->assertSame( 200, $this->register( new SoftwareAuthenticator() )->get_status() );
		$this->assertSame( '', get_user_meta( $this->user_id, 'wp_passkey_challenge', true ) );
	}

	/**
	 * Challenges carry 256 bits of randomness.
	 */
	public function test_challenge_is_random_and_ascii_safe() {
		$challenge = Webauthn_Server::generate_challenge();

		$this->assertMatchesRegularExpression( '/^[A-Za-z0-9_-]{43}$/', $challenge );
		$this->assertNotSame( $challenge, Webauthn_Server::generate_challenge() );
	}

	/**
	 * Non-standard Ed256/Ed512 identifiers are no longer offered.
	 */
	public function test_only_standard_eddsa_is_offered() {
		$algorithms = array_map(
			static function ( $param ) {
				return $param->alg;
			},
			( new Webauthn_Server() )->get_public_key_credential_parameters_list()
		);

		$this->assertContains( Algorithms::COSE_ALGORITHM_EDDSA, $algorithms );
		$this->assertNotContains( Algorithms::COSE_ALGORITHM_ED256, $algorithms );
		$this->assertNotContains( Algorithms::COSE_ALGORITHM_ED512, $algorithms );
		$this->assertTrue( $this->get_algorithm_manager( new Webauthn_Server() )->has( Algorithms::COSE_ALGORITHM_EDDSA ) );
	}

	/**
	 * Without sodium, EdDSA is dropped instead of breaking every ceremony.
	 */
	public function test_eddsa_is_skipped_when_sodium_is_unavailable() {
		$server = new class() extends Webauthn_Server {
			/**
			 * Simulate a PHP build without the sodium extension.
			 *
			 * @return bool
			 */
			protected function is_eddsa_supported(): bool {
				return false;
			}
		};

		$algorithms = array_map(
			static function ( $param ) {
				return $param->alg;
			},
			$server->get_public_key_credential_parameters_list()
		);

		$this->assertNotContains( Algorithms::COSE_ALGORITHM_EDDSA, $algorithms );
		$this->assertContains( Algorithms::COSE_ALGORITHM_ES256, $algorithms );

		$manager = $this->get_algorithm_manager( $server );

		$this->assertFalse( $manager->has( Algorithms::COSE_ALGORITHM_EDDSA ) );
		$this->assertTrue( $manager->has( Algorithms::COSE_ALGORITHM_ES256 ) );
	}

	/**
	 * Call the private Webauthn_Server::get_algorithm_manager().
	 *
	 * @param Webauthn_Server $server Server.
	 * @return \Cose\Algorithm\Manager
	 */
	private function get_algorithm_manager( Webauthn_Server $server ) {
		$method = new \ReflectionMethod( Webauthn_Server::class, 'get_algorithm_manager' );
		$method->setAccessible( true );

		return $method->invoke( $server );
	}
}
