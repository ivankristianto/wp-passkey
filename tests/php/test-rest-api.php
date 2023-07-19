<?php
/**
 * Sample Test file
 */

namespace WP_Passkey\Tests\Phpunit;

use WP_REST_Request;
use WP_UnitTestCase;

/**
 * Defines a basic fixture to run multiple tests.
 *
 * All unit tests for this project should inherit this class.
 */
class Test_Rest_API extends WP_UnitTestCase {
	/**
	 * Test Default, no async attribute
	 *
	 * @return void
	 */
	public function test_default() {
		$this->assertTrue( true );
	}

	/**
	 * Test signin_request
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws Exception
	 * @throws ExpectationFailedException
	 */
	public function test_signin_request() {
		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-request' );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertArrayHasKey( 'request_id', $data );
		$this->assertArrayHasKey( 'options', $data );
		// $response = $request->

		// $this->assertNotEmpty( $response->get_data() );
	}

	/**
	 * Test signin_response
	 *
	 * @return void
	 * @throws InvalidArgumentException
	 * @throws Exception
	 * @throws ExpectationFailedException
	 */
	public function test_signin_response() {
		$request = new WP_REST_Request( 'POST', '/wp-passkey/v1/signin-response' );
		$response = rest_get_server()->dispatch( $request );
		$data = $response->get_data();

		$this->assertIsArray( $data );
	}
}
