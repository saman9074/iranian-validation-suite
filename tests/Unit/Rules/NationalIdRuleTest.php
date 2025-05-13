<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Rules;

use Saman9074\IranianValidationSuite\Rules\NationalIdRule;
use Saman9074\IranianValidationSuite\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class NationalIdRuleTest extends TestCase
{
    protected NationalIdRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NationalIdRule();
    }

    /**
     * @test
     * @dataProvider validNationalIdsProvider
     */
    public function it_correctly_validates_valid_national_ids(string $validId): void
    {
        // Test directly
        $this->assertTrue($this->rule->passes('national_id', $validId), "Failed asserting that {$validId} is a valid national ID.");

        // Test using Validator (with required rule for integration check)
        $validator = Validator::make(['national_id' => $validId], [
            'national_id' => ['required', $this->rule],
        ]);
        $this->assertTrue($validator->passes(), "Validator failed for valid ID: {$validId}");
        $this->assertFalse($validator->fails());
    }

    /**
     * @test
     * @dataProvider invalidNationalIdsProvider
     */
    public function it_correctly_invalidates_invalid_national_ids(string $invalidId, string $expectedMessageKey): void
    {
        // Test directly that the rule itself returns false
        $this->assertFalse($this->rule->passes('national_id', $invalidId), "Failed asserting that rule invalidates: {$invalidId}");

        // Check the message key returned by the rule
        $this->assertEquals($expectedMessageKey, $this->rule->message());

        // Test using Validator *without* 'required'
        // Skip this block for empty string as Validator might not run the rule reliably without 'required' or 'nullable'
        if ($invalidId !== '') {
            $validator = Validator::make(['national_id' => $invalidId], [
                'national_id' => [$this->rule], // Use only our rule instance here
            ]);

            // Assert that validation fails for non-empty invalid IDs
            $this->assertTrue($validator->fails(), "Validator did not fail for invalid ID: {$invalidId}");
            $this->assertFalse($validator->passes());

            // Check if error exists for the field
            $errors = $validator->errors();
            $this->assertTrue($errors->has('national_id'));
        }
    }

    // --- Data Providers ---

    public static function validNationalIdsProvider(): array
    {
        // Use only algorithmically verified valid IDs
        return [
            'valid generated 1' => ['1060340593'],
            'valid generated 2' => ['1050648536'],
            'valid generated 5' => ['2753968411'],
            // 'valid known 1' => ['0069819871'], // This is actually invalid
            'valid known 2' => ['0384940481'], // This one seems valid based on online checkers
        ];
    }

    public static function invalidNationalIdsProvider(): array
    {
        $messageKey = 'iranian-validation-suite::validation.iranian_national_id';
        return [
            'too short' => ['123456789', $messageKey],
            'too long' => ['12345678901', $messageKey],
            'non-numeric' => ['abcdefghij', $messageKey],
            'all same digits' => ['1111111111', $messageKey],
            'incorrect check digit 1' => ['0078569396', $messageKey], // Should be 7
            'incorrect check digit 2' => ['7731689951', $messageKey], // Should be 6
            'incorrect check digit 3 (was previously in valid)' => ['0012345671', $messageKey], // Invalid based on calculation
            'incorrect check digit 4 (was previously in valid)' => ['0499370784', $messageKey], // Invalid based on calculation
            'incorrect check digit 5 (known 1)' => ['0069819871', $messageKey], // Invalid based on calculation (check digit should be 4)
            'invalid pattern 1' => ['1234567890', $messageKey], // Example invalid
            'contains letters' => ['007856939A', $messageKey],
            'empty value' => ['', $messageKey],
        ];
    }
}
