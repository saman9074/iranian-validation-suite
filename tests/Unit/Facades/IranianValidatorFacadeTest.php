<?php

namespace Saman9074\IranianValidationSuite\Tests\Unit\Facades;

use Saman9074\IranianValidationSuite\Facades\IranianValidator; // The Facade we are testing
use Saman9074\IranianValidationSuite\Services\IranianValidatorService;
use Saman9074\IranianValidationSuite\Tests\TestCase;

class IranianValidatorFacadeTest extends TestCase
{
    /**
     * Test that the Facade is correctly resolved and can call the underlying service.
     * @test
     */
    public function facade_can_access_underlying_service_methods(): void
    {
        // Test with a known valid National ID
        $this->assertTrue(IranianValidator::isNationalIdValid('1050648536'));
        // Test with a known invalid National ID
        $this->assertFalse(IranianValidator::isNationalIdValid('0000000000'));

        // Test with a known valid Bank Card
        $this->assertTrue(IranianValidator::isBankCardValid('6063731239374920')); // Verified Saman card
        // Test with a known invalid Bank Card
        $this->assertFalse(IranianValidator::isBankCardValid('1234567890123456'));

        // Test with a known valid Sheba
        $this->assertTrue(IranianValidator::isShebaValid('IR160700001000118733818001')); // User provided Resalat
        // Test with a known invalid Sheba
        $this->assertFalse(IranianValidator::isShebaValid('IR1234'));

        // Test with a known valid Postal Code
        $this->assertTrue(IranianValidator::isPostalCodeValid('1458812345'));
        // Test with a known invalid Postal Code
        $this->assertFalse(IranianValidator::isPostalCodeValid('0000000000')); // Invalid based on our rule

        // Test with a known valid Mobile Number
        $this->assertTrue(IranianValidator::isMobileNumberValid('09158282780'));
        // Test with a known invalid Mobile Number
        $this->assertFalse(IranianValidator::isMobileNumberValid('1234567890')); // Invalid based on our rule
    }

    /**
     * Test that the facade resolves to the correct service class.
     * @test
     */
    public function facade_resolves_to_correct_service_instance(): void
    {
        // Get the instance from the service container using the facade's accessor
        $resolvedInstance = $this->app->make('iranian.validator'); // Accessor key defined in Facade

        $this->assertInstanceOf(IranianValidatorService::class, $resolvedInstance);

        // You can also check if the Facade's root is the same instance
        // Note: This requires the Facade to be "resolved" at least once.
        // Calling a method on the facade (like above) resolves it.
        $facadeRootInstance = IranianValidator::getFacadeRoot();
        $this->assertInstanceOf(IranianValidatorService::class, $facadeRootInstance);
        $this->assertSame($resolvedInstance, $facadeRootInstance, "Facade root should be the same instance resolved from container.");
    }
}
