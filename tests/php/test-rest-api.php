<?php
/**
 * REST API Tests
 */

namespace BioAuth\Tests\Phpunit;

use Mockery;
use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Test REST API Endpoints
 */
class Test_Rest_API extends WP_UnitTestCase {

	/**
	 * Tear down after each test
	 */
	public function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test register-request authenticated success
	 */
	public function test_register_request_authenticated() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'testuser',
				'user_pass'  => 'password123',
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'POST', '/wp-passkey/v1/register-request' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'challenge', $data );
		$this->assertArrayHasKey( 'rp', $data );
		$this->assertArrayHasKey( 'user', $data );
	}

	/**
	 * Test register-request unauthenticated fails
	 */
	public function test_register_request_unauthenticated() {
		wp_set_current_user( 0 );

		$request  = new WP_REST_Request( 'POST', '/wp-passkey/v1/register-request' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test register-response unauthenticated fails
	 */
	public function test_register_response_unauthenticated() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/register-response' );
		$request->set_body( '{"test":"data"}' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test register-response empty body fails
	 */
	public function test_register_response_empty_body() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'testuser2',
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $user_id );

		$request  = new WP_REST_Request( 'POST', '/wp-passkey/v1/register-response' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertArrayHasKey( 'code', $data );
		$this->assertEquals( 'invalid_request', $data['code'] );
	}

	// Note: Platform detection (User-Agent parsing) is implementation detail
	// in rest-api.php and would require integration testing with actual WebAuthn
	// flows to properly test. Unit testing user-agent parsing in isolation would
	// be brittle and not provide meaningful coverage.

	/**
	 * Test signin-request success with challenge storage
	 */
	public function test_signin_request_success() {
		$request  = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-request' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'request_id', $data );
		$this->assertArrayHasKey( 'options', $data );
		$this->assertIsString( $data['request_id'] );
		$this->assertIsArray( $data['options'] );

		// Verify challenge stored in transient
		$challenge = get_transient( 'wp_passkey_' . $data['request_id'] );
		$this->assertNotFalse( $challenge );
		$this->assertIsString( $challenge );
	}

	/**
	 * Test signin-response empty body fails
	 */
	public function test_signin_response_empty_body() {
		$request  = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-response' );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_request', $data['code'] );
	}

	/**
	 * Test signin-response invalid challenge fails
	 */
	public function test_signin_response_invalid_challenge() {
		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-response' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'request_id' => 'invalid-request-id',
					'asseResp'   => array( 'test' => 'data' ),
				)
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_challenge', $data['code'] );
	}

	/**
	 * Test signin-response expired challenge fails
	 */
	public function test_signin_response_expired_challenge() {
		// Create a request_id but don't store challenge (simulates expiration)
		$request_id = wp_generate_uuid4();

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-response' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				array(
					'request_id' => $request_id,
					'asseResp'   => array( 'test' => 'data' ),
				)
			)
		);
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_challenge', $data['code'] );
	}

	/**
	 * Test revoke unauthenticated fails
	 */
	public function test_revoke_unauthenticated() {
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/revoke' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'fingerprint' => 'test' ) ) );
		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 401, $response->get_status() );
	}

	/**
	 * Test revoke missing fingerprint fails
	 */
	public function test_revoke_missing_fingerprint() {
		$user_id = $this->factory->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/revoke' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'fingerprint' => '' ) ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertEquals( 'invalid_request', $data['code'] );
		$this->assertStringContainsString( 'Fingerprint', $data['message'] );
	}

	/**
	 * Test revoke credential not found
	 */
	public function test_revoke_credential_not_found() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'revokeuser',
				'role'       => 'subscriber',
			)
		);
		wp_set_current_user( $user_id );

		// Use a random base64url-encoded fingerprint
		$random_fingerprint = \ParagonIE\ConstantTime\Base64UrlSafe::encodeUnpadded( random_bytes( 32 ) );

		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/revoke' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( array( 'fingerprint' => $random_fingerprint ) ) );
		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertEquals( 'not_found', $data['code'] );
	}
}
