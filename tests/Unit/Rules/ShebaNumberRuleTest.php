<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Rules;

use Saman9074\IranianValidationSuite\Rules\ShebaNumberRule;
use Saman9074\IranianValidationSuite\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class ShebaNumberRuleTest extends TestCase
{
    protected ShebaNumberRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new ShebaNumberRule();
    }

    /**
     * @test
     * @dataProvider validShebaNumbersProvider
     */
    public function it_correctly_validates_valid_sheba_numbers(string $validSheba): void
    {
        // Test directly
        $this->assertTrue($this->rule->passes('sheba', $validSheba), "Failed asserting that {$validSheba} is a valid Sheba number.");

        // Test using Validator
        $validator = Validator::make(['sheba' => $validSheba], [
            'sheba' => ['required', $this->rule],
        ]);
        $this->assertTrue($validator->passes(), "Validator failed for valid Sheba: {$validSheba}");
        $this->assertFalse($validator->fails());
    }

    /**
     * @test
     * @dataProvider invalidShebaNumbersProvider
     */
    public function it_correctly_invalidates_invalid_sheba_numbers(string $invalidSheba, string $expectedMessageKey): void
    {
        // Test directly that the rule itself returns false
        $this->assertFalse($this->rule->passes('sheba', $invalidSheba), "Failed asserting that rule invalidates: {$invalidSheba}");

        // Check the message key returned by the rule
        $this->assertEquals($expectedMessageKey, $this->rule->message());

        // Test using Validator *without* 'required'
        if ($invalidSheba !== '') {
            $validator = Validator::make(['sheba' => $invalidSheba], [
                'sheba' => [$this->rule],
            ]);

            $this->assertTrue($validator->fails(), "Validator did not fail for invalid Sheba: {$invalidSheba}");
            $this->assertFalse($validator->passes());
            $this->assertTrue($validator->errors()->has('sheba'));
        }
    }

    // --- Data Providers ---

    public static function validShebaNumbersProvider(): array
    {
        // شماره شباهای معتبر ارائه شده توسط کاربر و چند نمونه دیگر
        return [
            'Resalat Bank (User Provided)' => ['IR160700001000118733818001'],
            'Melli Bank (User Provided)' => ['IR450170000000366171740001'],
            'Mehr Bank (User Provided)' => ['IR070600361472415474398001'],
            'Pasargad Bank (User Provided)' => ['IR760570077700000210527001'],
            'Saderat Bank (Example)' => ['IR880190000000219008509002'], // نمونه از مستندات شبا
            'Valid with spaces' => ['IR45 0170 0000 0036 6171 7400 01'], // تست با فاصله
            'Valid lowercase ir' => ['ir160700001000118733818001'], // تست با حروف کوچک ir
        ];
    }

    public static function invalidShebaNumbersProvider(): array
    {
        $messageKey = 'iranian-validation-suite::validation.iranian_sheba';
        return [
            // Length issues
            'too short' => ['IR12345678901234567890123', $messageKey], // 25 chars
            'too long' => ['IR1234567890123456789012345', $messageKey], // 27 chars
            'empty value' => ['', $messageKey],

            // Format issues
            'no IR prefix' => ['12345678901234567890123456', $messageKey],
            'incorrect prefix' => ['XX123456789012345678901234', $messageKey],
            'contains invalid chars' => ['IR12345678901234567890123X', $messageKey],
            'digits after IR are not all digits (initial check)' => ['IRAB0170000000366171740001', $messageKey],


            // Checksum failures (correct format but invalid checksum)
            'invalid checksum 1' => ['IR160700001000118733818002', $messageKey], // Last digit changed
            'invalid checksum 2' => ['IR450170000000366171740000', $messageKey], // Last digit changed
            'invalid checksum 3 (all zeros after IR)' => ['IR000000000000000000000000', $messageKey], // Often invalid
            'invalid checksum 4' => ['IR880170000000100043210002', $messageKey], // Random but correctly formatted
        ];
    }
}
