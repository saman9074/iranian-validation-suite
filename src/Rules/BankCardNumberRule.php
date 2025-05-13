<?php

namespace Saman9074\IranianValidationSuite\Rules;

use Illuminate\Contracts\Validation\Rule;

class BankCardNumberRule implements Rule
{
    /**
     * Determine if the validation rule passes using the Luhn algorithm.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // 1. Preprocessing: Remove non-digits
        $cardNumber = preg_replace('/[^0-9]/', '', $value);
        $numDigits = strlen($cardNumber);

        // 2. Basic checks: Must be 16 digits and contain only digits
        if ($numDigits !== 16 || !ctype_digit($cardNumber)) {
            return false;
        }

        // 3. Apply Luhn Algorithm (Mod 10)
        // Inspired by a robust implementation (e.g., from dragonmantank/cron-expression)
        $sumDigitsString = '';
        foreach (str_split(strrev($cardNumber)) as $index => $digit) {
            if ($index % 2 !== 0) { // Digits at odd indices of reversed string (second, fourth... from right)
                $sumDigitsString .= $digit * 2;
            } else {
                $sumDigitsString .= $digit;
            }
        }

        $totalSum = 0;
        foreach (str_split($sumDigitsString) as $char) {
            $totalSum += (int)$char;
        }

        // 4. Check if the sum is divisible by 10
        return ($totalSum % 10 === 0);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'iranian-validation-suite::validation.iranian_bank_card';
    }
}
