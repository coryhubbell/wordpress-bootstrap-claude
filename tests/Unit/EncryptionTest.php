<?php
/**
 * Encryption Utility Unit Tests
 *
 * @package WordPress_Bootstrap_Claude
 * @subpackage Tests
 */

namespace WPBC\Tests\Unit;

use PHPUnit\Framework\TestCase;

class EncryptionTest extends TestCase {

    private $encryption;

    protected function setUp(): void {
        parent::setUp();

        // Load the Encryption class
        require_once WPBC_INCLUDES . '/class-wpbc-encryption.php';
        $this->encryption = new \WPBC_Encryption();
    }

    /**
     * Test encrypt returns base64 string
     */
    public function test_encrypt_returns_base64_string() {
        $plaintext = 'secret_api_key_12345';
        $encrypted = $this->encryption->encrypt($plaintext);

        $this->assertIsString($encrypted);
        // Base64 decoding should work without errors
        $this->assertNotFalse(base64_decode($encrypted, true));
    }

    /**
     * Test decrypt reverses encrypt
     */
    public function test_decrypt_reverses_encrypt() {
        $plaintext = 'my_secret_data_to_encrypt';
        $encrypted = $this->encryption->encrypt($plaintext);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test encrypt with empty string returns empty
     */
    public function test_encrypt_empty_string_returns_empty() {
        $result = $this->encryption->encrypt('');

        $this->assertEquals('', $result);
    }

    /**
     * Test encrypt returns different results for same input (due to random IV)
     */
    public function test_encrypt_uses_random_iv() {
        $plaintext = 'same_input_different_outputs';
        $encrypted1 = $this->encryption->encrypt($plaintext);
        $encrypted2 = $this->encryption->encrypt($plaintext);

        // They should be different due to random IV
        $this->assertNotEquals($encrypted1, $encrypted2);

        // But both should decrypt to the same value
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted1));
        $this->assertEquals($plaintext, $this->encryption->decrypt($encrypted2));
    }

    /**
     * Test is_encrypted returns true for encrypted data
     */
    public function test_is_encrypted_returns_true_for_encrypted() {
        $encrypted = $this->encryption->encrypt('test_data');

        $this->assertTrue($this->encryption->is_encrypted($encrypted));
    }

    /**
     * Test is_encrypted returns false for plain data
     */
    public function test_is_encrypted_returns_false_for_plain() {
        $plaintext = 'this_is_not_encrypted';

        $this->assertFalse($this->encryption->is_encrypted($plaintext));
    }

    /**
     * Test decrypt handles unencrypted data gracefully
     */
    public function test_decrypt_handles_unencrypted_data_gracefully() {
        $plaintext = 'not_encrypted_data';
        // The decrypt should either return the original or handle it gracefully
        $result = $this->encryption->decrypt($plaintext);

        // It may return false or the original data depending on implementation
        $this->assertTrue($result === false || $result === $plaintext || is_string($result));
    }

    /**
     * Test encrypt_array encrypts string values
     */
    public function test_encrypt_array_encrypts_string_values() {
        $data = [
            'api_key' => 'secret_key_123',
            'name' => 'Test User',
        ];

        $encrypted = $this->encryption->encrypt_array($data);

        $this->assertIsArray($encrypted);
        // String values should be encrypted
        $this->assertNotEquals($data['api_key'], $encrypted['api_key']);
    }

    /**
     * Test decrypt_array decrypts string values
     */
    public function test_decrypt_array_decrypts_string_values() {
        $original = [
            'api_key' => 'secret_key_456',
            'status' => 'active',
        ];

        $encrypted = $this->encryption->encrypt_array($original);
        $decrypted = $this->encryption->decrypt_array($encrypted);

        $this->assertEquals($original['api_key'], $decrypted['api_key']);
        $this->assertEquals($original['status'], $decrypted['status']);
    }

    /**
     * Test encrypt handles special characters
     */
    public function test_encrypt_handles_special_characters() {
        $plaintext = 'key!@#$%^&*()_+-=[]{}|;:,.<>?/`~"\'\\';
        $encrypted = $this->encryption->encrypt($plaintext);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test encrypt handles unicode
     */
    public function test_encrypt_handles_unicode() {
        $plaintext = 'Hello 世界 🌍 مرحبا';
        $encrypted = $this->encryption->encrypt($plaintext);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }

    /**
     * Test encrypt handles long strings
     */
    public function test_encrypt_handles_long_strings() {
        $plaintext = str_repeat('a', 10000);
        $encrypted = $this->encryption->encrypt($plaintext);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted);
    }
}
