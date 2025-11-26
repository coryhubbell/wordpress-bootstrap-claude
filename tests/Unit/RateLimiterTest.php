<?php
/**
 * Rate Limiter Unit Tests
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Tests
 */

namespace WPBC\Tests\Unit;

use PHPUnit\Framework\TestCase;

class RateLimiterTest extends TestCase {

    private $rate_limiter;

    protected function setUp(): void {
        parent::setUp();

        // Load the Rate Limiter class
        require_once WPBC_INCLUDES . '/class-wpbc-rate-limiter.php';
        $this->rate_limiter = new \WPBC_Rate_Limiter();
    }

    protected function tearDown(): void {
        parent::tearDown();
        // Clean up any rate limit data
        $this->rate_limiter->reset_limit('test_identifier');
        $this->rate_limiter->reset_limit('test_user_1');
        $this->rate_limiter->reset_limit('test_user_2');
    }

    /**
     * Test that check_limit allows requests within limit
     */
    public function test_check_limit_allows_within_hourly_limit() {
        $identifier = 'test_identifier';

        $result = $this->rate_limiter->check_limit($identifier, 'free');

        $this->assertTrue($result['allowed']);
        $this->assertEquals('free', $result['tier']);
        $this->assertArrayHasKey('limits', $result);
    }

    /**
     * Test that check_limit returns correct structure
     */
    public function test_check_limit_returns_correct_structure() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'basic');

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('tier', $result);
        $this->assertArrayHasKey('limits', $result);
        $this->assertArrayHasKey('hourly', $result['limits']);
        $this->assertArrayHasKey('minute', $result['limits']);
        $this->assertArrayHasKey('burst', $result['limits']);
    }

    /**
     * Test hourly limit structure
     */
    public function test_hourly_limit_has_correct_keys() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'free');

        $hourly = $result['limits']['hourly'];
        $this->assertArrayHasKey('limit', $hourly);
        $this->assertArrayHasKey('remaining', $hourly);
        $this->assertArrayHasKey('reset_at', $hourly);
    }

    /**
     * Test tier free has correct limits
     */
    public function test_tier_free_has_correct_limits() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'free');

        $this->assertEquals(100, $result['limits']['hourly']['limit']);
        $this->assertEquals(20, $result['limits']['minute']['limit']);
        $this->assertEquals(5, $result['limits']['burst']['limit']);
    }

    /**
     * Test tier basic has higher limits
     */
    public function test_tier_basic_has_higher_limits() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'basic');

        $this->assertEquals(500, $result['limits']['hourly']['limit']);
        $this->assertEquals(50, $result['limits']['minute']['limit']);
        $this->assertEquals(10, $result['limits']['burst']['limit']);
    }

    /**
     * Test tier premium has even higher limits
     */
    public function test_tier_premium_has_higher_limits() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'premium');

        $this->assertEquals(2000, $result['limits']['hourly']['limit']);
        $this->assertEquals(100, $result['limits']['minute']['limit']);
        $this->assertEquals(20, $result['limits']['burst']['limit']);
    }

    /**
     * Test tier enterprise has highest limits
     */
    public function test_tier_enterprise_has_highest_limits() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'enterprise');

        $this->assertEquals(10000, $result['limits']['hourly']['limit']);
        $this->assertEquals(500, $result['limits']['minute']['limit']);
        $this->assertEquals(50, $result['limits']['burst']['limit']);
    }

    /**
     * Test key creation tier has strict limits
     */
    public function test_key_creation_tier_has_strict_limits() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'key_creation');

        $this->assertEquals(5, $result['limits']['hourly']['limit']);
        $this->assertEquals(2, $result['limits']['minute']['limit']);
        $this->assertEquals(1, $result['limits']['burst']['limit']);
    }

    /**
     * Test unknown tier defaults to free
     */
    public function test_unknown_tier_defaults_to_free() {
        $result = $this->rate_limiter->check_limit('test_user_1', 'nonexistent_tier');

        $this->assertEquals(100, $result['limits']['hourly']['limit']);
    }

    /**
     * Test record_request returns true
     */
    public function test_record_request_returns_true() {
        $result = $this->rate_limiter->record_request('test_user_1', 'free');

        $this->assertTrue($result);
    }

    /**
     * Test reset_limit clears request count
     */
    public function test_reset_limit_clears_all_windows() {
        // Record some requests
        $this->rate_limiter->record_request('test_user_2', 'free');
        $this->rate_limiter->record_request('test_user_2', 'free');

        // Reset
        $result = $this->rate_limiter->reset_limit('test_user_2');

        $this->assertTrue($result);

        // Check limit should now show full remaining
        $check = $this->rate_limiter->check_limit('test_user_2', 'free');
        $this->assertEquals(100, $check['limits']['hourly']['remaining']);
    }

    /**
     * Test get_headers includes rate limit info
     */
    public function test_get_headers_includes_rate_limit_info() {
        $limit_data = $this->rate_limiter->check_limit('test_user_1', 'free');
        $headers = $this->rate_limiter->get_headers($limit_data);

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);
    }

    /**
     * Test get_tier_for_key with explicit tier
     */
    public function test_get_tier_for_key_respects_explicit_tier() {
        $key_data = [
            'user_id' => 1,
            'tier' => 'premium',
        ];

        $tier = $this->rate_limiter->get_tier_for_key($key_data);

        $this->assertEquals('premium', $tier);
    }

    /**
     * Test get_stats returns array
     */
    public function test_get_stats_returns_array() {
        $stats = $this->rate_limiter->get_stats();

        $this->assertIsArray($stats);
    }
}
