<?php

namespace Saman9074\IranianValidationSuite\Rules;

use Illuminate\Contracts\Validation\Rule;

class NationalIdRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute The name of the attribute being validated.
     * @param  mixed  $value The value of the attribute being validated.
     * @return bool True if the validation passes, false otherwise.
     */
    public function passes($attribute, $value): bool
    {
        // 1. Preprocessing: Remove non-digits and check length
        $nationalId = preg_replace('/[^0-9]/', '', $value);
        if (strlen($nationalId) !== 10) {
            return false; // Must be exactly 10 digits
        }

        // 2. Check for all same digits (invalid case)
        if (preg_match('/^(\d)\1{9}$/', $nationalId)) {
            return false;
        }

        // 3. Calculate the check digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$nationalId[$i] * (10 - $i);
        }

        $remainder = $sum % 11;
        $checkDigit = (int)substr($nationalId, 9, 1); // Get the last digit

        // 4. Compare calculated check digit with the actual one
        $calculatedCheckDigit = ($remainder < 2) ? $remainder : 11 - $remainder;

        return $checkDigit === $calculatedCheckDigit;
    }

    /**
     * Get the validation error message.
     *
     * @return string The translation key for the error message.
     */
    public function message(): string
    {
        // Return the key for the translation string.
        // The Service Provider's replacer will handle the actual translation.
        return 'iranian-validation-suite::validation.iranian_national_id';
    }
}
