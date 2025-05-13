<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Rules;

use Saman9074\IranianValidationSuite\Rules\PostalCodeRule;
use Saman9074\IranianValidationSuite\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class PostalCodeRuleTest extends TestCase
{
    protected PostalCodeRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new PostalCodeRule();
    }

    /**
     * @test
     * @dataProvider validPostalCodesProvider
     */
    public function it_correctly_validates_valid_postal_codes(string $validPostalCode): void
    {
        // Test directly
        $this->assertTrue($this->rule->passes('postal_code', $validPostalCode), "Failed asserting that {$validPostalCode} is a valid postal code.");

        // Test using Validator
        $validator = Validator::make(['postal_code' => $validPostalCode], [
            'postal_code' => ['required', $this->rule],
        ]);
        $this->assertTrue($validator->passes(), "Validator failed for valid postal code: {$validPostalCode}");
        $this->assertFalse($validator->fails());
    }

    /**
     * @test
     * @dataProvider invalidPostalCodesProvider
     */
    public function it_correctly_invalidates_invalid_postal_codes(string $invalidPostalCode, string $expectedMessageKey): void
    {
        // Test directly that the rule itself returns false
        $this->assertFalse($this->rule->passes('postal_code', $invalidPostalCode), "Failed asserting that rule invalidates: {$invalidPostalCode}");

        // Check the message key returned by the rule
        $this->assertEquals($expectedMessageKey, $this->rule->message());

        // Test using Validator *without* 'required'
        if ($invalidPostalCode !== '') {
            $validator = Validator::make(['postal_code' => $invalidPostalCode], [
                'postal_code' => [$this->rule],
            ]);

            $this->assertTrue($validator->fails(), "Validator did not fail for invalid postal code: {$invalidPostalCode}");
            $this->assertFalse($validator->passes());
            $this->assertTrue($validator->errors()->has('postal_code'));
        }
    }

    // --- Data Providers ---

    public static function validPostalCodesProvider(): array
    {
        return [
            'standard 10 digits' => ['1234512345'], // Changed from 1234567890
            'another 10 digits' => ['9876598765'], // Changed from 9876543210
            'with hyphen' => ['12345-12345'],
            'another with hyphen' => ['11122-33344'], // Changed from all same digits
            'Tehran example' => ['1458812345'],
            'Isfahan example' => ['8199954321'],
            'some other valid' => ['3155577777'],
        ];
    }

    public static function invalidPostalCodesProvider(): array
    {
        $messageKey = 'iranian-validation-suite::validation.iranian_postal_code';
        return [
            // Length issues
            'too short' => ['123456789', $messageKey],
            'too long' => ['12345678901', $messageKey],
            'too short with hyphen' => ['1234-56789', $messageKey],
            'too long with hyphen' => ['12345-678901', $messageKey],
            'empty value' => ['', $messageKey],

            // Format issues
            'contains letters' => ['12345ABCDE', $messageKey],
            'contains letters with hyphen' => ['12345-ABCDE', $messageKey],
            'hyphen in wrong place' => ['123-4567890', $messageKey],
            'multiple hyphens' => ['123-45-67890', $messageKey],
            'starts with hyphen' => ['-123456789', $messageKey],
            'ends with hyphen' => ['123456789-', $messageKey],

            // Specific invalid patterns
            'all zeros' => ['0000000000', $messageKey],
            'all same digits (1s)' => ['1111111111', $messageKey],
            'all same digits (5s)' => ['5555555555', $messageKey],
            'specific sequence 1234567890' => ['1234567890', $messageKey],
           // 'specific sequence 9876543210' => ['9876543210', $messageKey], // If this is also invalid
        ];
    }
}
