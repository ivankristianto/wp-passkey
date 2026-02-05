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

	/**
	 * Test saving and finding credential
	 */
	public function test_save_and_find_credential() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'testuser',
				'user_pass'  => 'password123',
			)
		);

		$repository = new Source_Repository();
		$credential = WebauthnTestHelper::mock_credential_record( 'testuser' );

		// Save credential
		$extra_data = array(
			'name'       => 'Test Device',
			'created'    => time(),
			'user_agent' => 'Test Agent',
		);
		$repository->saveCredentialSource( $credential, $extra_data );

		// Find credential
		$found = $repository->findOneByCredentialId( $credential->publicKeyCredentialId );

		$this->assertNotNull( $found );
		$this->assertEquals( $credential->publicKeyCredentialId, $found->publicKeyCredentialId );
		$this->assertEquals( $credential->type, $found->type );
		$this->assertEquals( $credential->userHandle, $found->userHandle );

		// Verify extra data
		$saved_extra = $repository->get_extra_data( $found );
		$this->assertArrayHasKey( 'name', $saved_extra );
		$this->assertEquals( 'Test Device', $saved_extra['name'] );
	}

	/**
	 * Test findOneByCredentialId returns null when not found
	 */
	public function test_find_credential_not_found() {
		$repository = new Source_Repository();
		$random_id  = random_bytes( 32 );

		$found = $repository->findOneByCredentialId( $random_id );

		$this->assertNull( $found );
	}

	/**
	 * Test findAllForUserEntity
	 */
	public function test_find_all_for_user() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'multiuser',
				'user_pass'  => 'password123',
			)
		);

		$repository  = new Source_Repository();
		$user_entity = WebauthnTestHelper::mock_user_entity( 'multiuser', 'Multi User' );

		// Save multiple credentials
		$credential1 = WebauthnTestHelper::mock_credential_record( 'multiuser' );
		$credential2 = WebauthnTestHelper::mock_credential_record( 'multiuser' );

		$repository->saveCredentialSource( $credential1 );
		$repository->saveCredentialSource( $credential2 );

		// Find all
		$all = $repository->findAllForUserEntity( $user_entity );

		$this->assertIsArray( $all );
		$this->assertCount( 2, $all );
	}

	/**
	 * Test findAllForUserEntity with no credentials
	 */
	public function test_find_all_for_user_empty() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'emptyuser',
				'user_pass'  => 'password123',
			)
		);

		$repository  = new Source_Repository();
		$user_entity = WebauthnTestHelper::mock_user_entity( 'emptyuser', 'Empty User' );

		$all = $repository->findAllForUserEntity( $user_entity );

		$this->assertIsArray( $all );
		$this->assertEmpty( $all );
	}

	/**
	 * Test deleteCredentialSource
	 */
	public function test_delete_credential() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'deleteuser',
				'user_pass'  => 'password123',
			)
		);

		$repository = new Source_Repository();
		$credential = WebauthnTestHelper::mock_credential_record( 'deleteuser' );

		// Save then delete
		$repository->saveCredentialSource( $credential );
		$repository->deleteCredentialSource( $credential );

		// Verify deleted
		$found = $repository->findOneByCredentialId( $credential->publicKeyCredentialId );
		$this->assertNull( $found );
	}

	/**
	 * Test saveCredentialSource throws exception for nonexistent user
	 */
	public function test_save_credential_user_not_found() {
		$repository = new Source_Repository();
		$credential = WebauthnTestHelper::mock_credential_record( 'nonexistentuser' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'User not found.' );

		$repository->saveCredentialSource( $credential );
	}
}
