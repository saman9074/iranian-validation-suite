<?php

namespace Saman9074\IranianValidationSuite\Rules;

use Illuminate\Contracts\Validation\Rule;

class PostalCodeRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * Validates an Iranian postal code.
     * It should be 10 digits, optionally with a hyphen after the 5th digit.
     * It should not be all zeros, all same digits, or '1234567890'.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // 1. Preprocessing: Remove hyphen and any non-digit characters
        $postalCode = preg_replace('/[^0-9]/', '', (string)$value);

        // 2. Check length: Must be exactly 10 digits after stripping
        if (strlen($postalCode) !== 10) {
            return false;
        }

        // 3. Check if it contains only digits (already handled by preg_replace)
        if (!ctype_digit($postalCode)) {
            return false;
        }

        // 4. Check for all zeros
        if ($postalCode === '0000000000') {
            return false;
        }

        // 5. Check for all same repeating digits (e.g., 1111111111)
        if (preg_match('/^(\d)\1{9}$/', $postalCode)) {
            return false;
        }

        // 6. Check for the specific sequence '1234567890'
        if ($postalCode === '1234567890') {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'iranian-validation-suite::validation.iranian_postal_code';
    }
}
