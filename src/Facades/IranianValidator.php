<?php

namespace Saman9074\IranianValidationSuite\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool isNationalIdValid(?string $value)
 * @method static bool isBankCardValid(?string $value)
 * @method static bool isShebaValid(?string $value)
 * @method static bool isPostalCodeValid(?string $value)
 * @method static bool isMobileNumberValid(?string $value)
 *
 * @see \Saman9074\IranianValidationSuite\Services\IranianValidatorService
 */
class IranianValidator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        // This is the key we will use to bind our service in the Service Container
        return 'iranian.validator';
    }
}
