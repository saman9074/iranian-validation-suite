<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Rules;

use Saman9074\IranianValidationSuite\Rules\MobileNumberRule;
use Saman9074\IranianValidationSuite\Tests\TestCase;
use Illuminate\Support\Facades\Validator;

class MobileNumberRuleTest extends TestCase
{
    protected MobileNumberRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new MobileNumberRule();
    }

    /**
     * @test
     * @dataProvider validMobileNumbersProvider
     */
    public function it_correctly_validates_valid_iranian_mobile_numbers(string $validMobile): void
    {
        $this->assertTrue($this->rule->passes('mobile', $validMobile), "Failed for valid mobile: {$validMobile}");

        $validator = Validator::make(['mobile' => $validMobile], ['mobile' => $this->rule]);
        $this->assertTrue($validator->passes(), "Validator failed for valid mobile: {$validMobile}");
    }

    /**
     * @test
     * @dataProvider invalidMobileNumbersProvider
     */
    public function it_correctly_invalidates_invalid_iranian_mobile_numbers(string $invalidMobile): void
    {
        $this->assertFalse($this->rule->passes('mobile', $invalidMobile), "Passed for invalid mobile: {$invalidMobile}");

        if ($invalidMobile !== '') {
            $validator = Validator::make(['mobile' => $invalidMobile], ['mobile' => $this->rule]);
            $this->assertTrue($validator->fails(), "Validator did not fail for invalid mobile: {$invalidMobile}");
        }
    }

    public static function validMobileNumbersProvider(): array
    {
        return [
            // Hamrah-e Avval
            'Hamrah-e Avval 1' => ['09121234567'],
            'Hamrah-e Avval 2' => ['09101234567'],
            'Hamrah-e Avval 3 (no leading zero)' => ['9121234567'],
            'Hamrah-e Avval 4' => ['09912345678'],
            'Hamrah-e Avval 5' => ['09941234567'], // Anarestan (sub-brand)

            // Irancell
            'Irancell 1' => ['09351234567'],
            'Irancell 2' => ['09301234567'],
            'Irancell 3 (no leading zero)' => ['9351234567'],
            'Irancell 4' => ['09011234567'],
            'Irancell 5' => ['09051234567'],


            // Rightel
            'Rightel 1' => ['09211234567'],
            'Rightel 2' => ['09221234567'],
            'Rightel 3 (no leading zero)' => ['9211234567'],

            // Shatel Mobile
            'Shatel Mobile 1' => ['09981234567'],
             // Samantel
            'Samantel 1' => ['09999876543'],


        ];
    }

    public static function invalidMobileNumbersProvider(): array
    {
        return [
            'too short' => ['0912123456'],
            'too short (no leading zero)' => ['91212345'],
            'too long' => ['091212345678'],
            'too long (no leading zero)' => ['91212345678'],
            'starts with wrong digit' => ['08121234567'],
            'starts with 9 but too short' => ['912345678'],
            'invalid prefix 1' => ['09411234567'], // Invalid prefix
            'invalid prefix 2' => ['09501234567'], // Invalid prefix
            'contains letters' => ['0912ABCDEFG'],
            'empty string' => [''],
            'all zeros' => ['00000000000'],
            'not starting with 09' => ['19121234567'],
        ];
    }
}
