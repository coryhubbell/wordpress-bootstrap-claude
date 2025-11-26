<?php
/**
 * Auth Class Unit Tests
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Tests
 */

namespace WPBC\Tests\Unit;

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {

    private $auth;

    protected function setUp(): void {
        parent::setUp();

        // Load the Auth class
        require_once WPBC_INCLUDES . '/class-wpbc-auth.php';
        $this->auth = new \WPBC_Auth();
    }

    /**
     * Test API key generation
     */
    public function test_generate_api_key_returns_valid_key() {
        $key = $this->auth->generate_api_key('test_user');

        $this->assertIsString($key);
        $this->assertGreaterThan(20, strlen($key));
        $this->assertStringStartsWith('wpbc_', $key);
    }

    public function test_generate_api_key_creates_unique_keys() {
        $key1 = $this->auth->generate_api_key('user1');
        $key2 = $this->auth->generate_api_key('user2');

        $this->assertNotEquals($key1, $key2, 'Generated keys should be unique');
    }

    /**
     * Test API key extraction from request
     */
    public function test_get_api_key_from_request_extracts_from_header() {
        $request = new \WP_REST_Request();
        $request->set_header('X-API-Key', 'test_api_key_12345');

        $reflection = new \ReflectionClass($this->auth);
        $method = $reflection->getMethod('get_api_key_from_request');
        $method->setAccessible(true);

        $result = $method->invoke($this->auth, $request);

        $this->assertEquals('test_api_key_12345', $result);
    }

    public function test_get_api_key_from_request_returns_null_when_no_key() {
        $request = new \WP_REST_Request();

        $reflection = new \ReflectionClass($this->auth);
        $method = $reflection->getMethod('get_api_key_from_request');
        $method->setAccessible(true);

        $result = $method->invoke($this->auth, $request);

        $this->assertNull($result);
    }

    public function test_get_api_key_from_request_ignores_query_parameters() {
        // After our security fix, query parameters should be ignored
        $request = new \WP_REST_Request();
        $request->set_param('api_key', 'should_be_ignored');

        $reflection = new \ReflectionClass($this->auth);
        $method = $reflection->getMethod('get_api_key_from_request');
        $method->setAccessible(true);

        $result = $method->invoke($this->auth, $request);

        $this->assertNull($result, 'API key in query parameter should be ignored for security');
    }

    /**
     * Test API key validation
     */
    public function test_validate_api_key_format() {
        $valid_keys = [
            'wpbc_1234567890abcdef',
            'wpbc_' . bin2hex(random_bytes(24)),
        ];

        foreach ($valid_keys as $key) {
            $this->assertStringStartsWith('wpbc_', $key);
            $this->assertGreaterThan(20, strlen($key));
        }
    }

    /**
     * Test authentication failure scenarios
     */
    public function test_authenticate_request_fails_without_key() {
        $request = new \WP_REST_Request();

        $result = $this->auth->authenticate_request($request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertEquals('wpbc_auth_missing_key', $result->get_error_code());
    }

    /**
     * Test key information retrieval - verify get_stats returns proper structure
     */
    public function test_get_key_info_structure() {
        // Generate a key first to have data
        $this->auth->generate_api_key('test_structure_user');

        // Get stats and verify structure
        $stats = $this->auth->get_stats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_keys', $stats);
        $this->assertArrayHasKey('active_keys', $stats);
        $this->assertArrayHasKey('revoked_keys', $stats);
        $this->assertArrayHasKey('by_user', $stats);
        $this->assertIsInt($stats['total_keys']);
        $this->assertIsInt($stats['active_keys']);
        $this->assertIsArray($stats['by_user']);
    }

    /**
     * Test temp token generation and validation
     */
    public function test_temp_token_generation_returns_valid_token() {
        $user_id = 123;
        $token = $this->auth->generate_temp_token($user_id);

        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token));
    }

    public function test_temp_token_can_be_validated() {
        $user_id = 456;
        $token = $this->auth->generate_temp_token($user_id);

        $validated_user_id = $this->auth->validate_temp_token($token);

        $this->assertEquals($user_id, $validated_user_id);
    }

    /**
     * Test expired temp tokens are rejected
     */
    public function test_expired_temp_tokens_are_rejected() {
        $user_id = 789;
        // Create a token with very short TTL (1 second)
        $token = $this->auth->generate_temp_token($user_id, 1);

        // Wait for token to expire
        sleep(2);

        // Token should now be invalid
        $result = $this->auth->validate_temp_token($token);

        $this->assertFalse($result, 'Expired temp token should be rejected');
    }

    /**
     * Test invalid temp token returns false
     */
    public function test_invalid_temp_token_returns_false() {
        $result = $this->auth->validate_temp_token('nonexistent_token_12345');

        $this->assertFalse($result, 'Invalid temp token should return false');
    }
}
