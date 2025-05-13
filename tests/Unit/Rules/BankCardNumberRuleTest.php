<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Rules;

use Saman9074\IranianValidationSuite\Rules\BankCardNumberRule;
use Saman9074\IranianValidationSuite\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class BankCardNumberRuleTest extends TestCase
{
    protected BankCardNumberRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new BankCardNumberRule();
    }

    /**
     * @test
     * @dataProvider validBankCardNumbersProvider
     */
    public function it_correctly_validates_valid_bank_card_numbers(string $validCardNumber): void
    {
        // Test directly
        $this->assertTrue($this->rule->passes('card_number', $validCardNumber), "Failed asserting that {$validCardNumber} is a valid card number.");

        // Test using Validator
        $validator = Validator::make(['card_number' => $validCardNumber], [
            'card_number' => ['required', $this->rule],
        ]);
        $this->assertTrue($validator->passes(), "Validator failed for valid card number: {$validCardNumber}");
        $this->assertFalse($validator->fails());
    }

    /**
     * @test
     * @dataProvider invalidBankCardNumbersProvider
     */
    public function it_correctly_invalidates_invalid_bank_card_numbers(string $invalidCardNumber, string $expectedMessageKey): void
    {
        // Test directly that the rule itself returns false
        $this->assertFalse($this->rule->passes('card_number', $invalidCardNumber), "Failed asserting that rule invalidates: {$invalidCardNumber}");

        // Check the message key returned by the rule
        $this->assertEquals($expectedMessageKey, $this->rule->message());

        // Test using Validator *without* 'required'
        if ($invalidCardNumber !== '') {
            $validator = Validator::make(['card_number' => $invalidCardNumber], [
                'card_number' => [$this->rule],
            ]);

            $this->assertTrue($validator->fails(), "Validator did not fail for invalid card number: {$invalidCardNumber}");
            $this->assertFalse($validator->passes());
            $this->assertTrue($validator->errors()->has('card_number'));
        }
    }

    // --- Data Providers ---

    public static function validBankCardNumbersProvider(): array
    {
        // Use only algorithmically verified valid 16-digit Luhn numbers.
        // Verified using online Luhn calculators/validators.
        return [
            'Melli Verified' => ['6037997541920212'], // Sum = 90
            'Saman Verified' => ['6219861917481227'], // Sum = 80
            'Pasargad Verified' => ['5022291501681942'], // Sum = 40
            'Mellat Verified' => ['6104338904857675'], // Sum = 50 (Generated)
            'Tejarat Verified' => ['5859831061224852'], // Sum = 60 (Generated)
        ];
    }

    public static function invalidBankCardNumbersProvider(): array
    {
        $messageKey = 'iranian-validation-suite::validation.iranian_bank_card';
        return [
            // Length issues
            'too short' => ['123456789012345', $messageKey],
            'too long' => ['12345678901234567', $messageKey],
            'empty value' => ['', $messageKey],
            'not 16 digits (Luhn valid but wrong length)' => ['79927398713', $messageKey],
            'not 16 digits (Luhn valid but wrong length 2)' => ['49927398716', $messageKey],

            // Format issues
            'non-numeric' => ['abcdefghijklmnop', $messageKey],
            'contains letters' => ['603799123456789A', $messageKey],

            // Luhn check failures (16 digits but invalid checksum)
            'fails luhn check (prev valid Saman)' => ['6219861012345670', $messageKey], // Sum=57
            'fails luhn check (prev valid Melli)' => ['6037991234567882', $messageKey], // Sum=77
            'fails luhn check (prev valid Pasargad)' => ['5022291098765432', $messageKey], // Sum=68
            'fails luhn check (prev valid Mellat)' => ['6104337900000000', $messageKey], // Sum=31
            'fails luhn check (prev valid Tejarat)' => ['6273531011111111', $messageKey], // Sum=31
            'fails luhn check (prev Visa)' => ['4539970000000000', $messageKey], // Sum=44
            'fails luhn check (prev Discover)' => ['6011111111111111', $messageKey], // Sum=24
            'fails luhn check (prev Mastercard)' => ['5100000000000000', $messageKey], // Sum=2
            'fails luhn check (prev Another 1)' => ['6273811010101010', $messageKey], // Sum=31
            'fails luhn check (prev Another 2)' => ['5892101010101010', $messageKey], // Sum=34
            'fails luhn check general 1' => ['6037991234567890', $messageKey], // Sum=79
            'fails luhn check general 2' => ['6219861012345671', $messageKey], // Changed last digit
        ];
    }
}
