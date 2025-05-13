<?php

namespace Saman9074\IranianValidationSuite\Services;

use Saman9074\IranianValidationSuite\Rules\NationalIdRule;
use Saman9074\IranianValidationSuite\Rules\BankCardNumberRule;
use Saman9074\IranianValidationSuite\Rules\ShebaNumberRule;
use Saman9074\IranianValidationSuite\Rules\PostalCodeRule;
use Saman9074\IranianValidationSuite\Rules\MobileNumberRule;

class IranianValidatorService
{
    /**
     * Validate an Iranian National ID.
     *
     * @param  string|null  $value
     * @return bool
     */
    public function isNationalIdValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (new NationalIdRule())->passes('national_id', $value);
    }

    /**
     * Validate an Iranian Bank Card Number.
     *
     * @param  string|null  $value
     * @return bool
     */
    public function isBankCardValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (new BankCardNumberRule())->passes('bank_card', $value);
    }

    /**
     * Validate an Iranian Sheba Number (IBAN).
     *
     * @param  string|null  $value
     * @return bool
     */
    public function isShebaValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (new ShebaNumberRule())->passes('sheba', $value);
    }

    /**
     * Validate an Iranian Postal Code.
     *
     * @param  string|null  $value
     * @return bool
     */
    public function isPostalCodeValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (new PostalCodeRule())->passes('postal_code', $value);
    }

    /**
     * Validate an Iranian Mobile Number.
     *
     * @param  string|null  $value
     * @return bool
     */
    public function isMobileNumberValid(?string $value): bool
    {
        if ($value === null) {
            return false;
        }
        return (new MobileNumberRule())->passes('mobile_number', $value);
    }
}
