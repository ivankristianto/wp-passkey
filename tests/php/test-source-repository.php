<?php
/**
 * Source Repository Tests
 */

namespace BioAuth\Tests\Phpunit;

use BioAuth\Source_Repository;
use BioAuth\Tests\Fixtures\WebauthnTestHelper;
use Mockery;
use ParagonIE\ConstantTime\Base64UrlSafe;
use WP_UnitTestCase;

/**
 * Test Source_Repository
 */
class Test_Source_Repository extends WP_UnitTestCase {

	/**
	 * Tear down after each test
	 */
	public function tearDown(): void {
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test credential round-trip serialization
	 */
	public function test_credential_serialization_round_trip() {
		$repository = new Source_Repository();
		$credential = WebauthnTestHelper::mock_credential_record();

		// Use reflection to test private methods
		$to_array_method   = new \ReflectionMethod( $repository, 'credential_to_array' );
		$from_array_method = new \ReflectionMethod( $repository, 'array_to_credential' );
		$to_array_method->setAccessible( true );
		$from_array_method->setAccessible( true );

		$array = $to_array_method->invoke( $repository, $credential );

		// Verify array structure
		$this->assertArrayHasKey( 'publicKeyCredentialId', $array );
		$this->assertArrayHasKey( 'type', $array );
		$this->assertArrayHasKey( 'credentialPublicKey', $array );
		$this->assertArrayHasKey( 'userHandle', $array );

		// Verify base64url encoding
		$this->assertIsString( $array['publicKeyCredentialId'] );
		$this->assertIsString( $array['credentialPublicKey'] );
		$this->assertIsString( $array['userHandle'] );

		// Round-trip test
		$restored = $from_array_method->invoke( $repository, $array );

		$this->assertEquals( $credential->publicKeyCredentialId, $restored->publicKeyCredentialId );
		$this->assertEquals( $credential->type, $restored->type );
		$this->assertEquals( $credential->credentialPublicKey, $restored->credentialPublicKey );
		$this->assertEquals( $credential->userHandle, $restored->userHandle );
	}
}
