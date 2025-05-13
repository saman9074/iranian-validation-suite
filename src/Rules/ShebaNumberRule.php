<?php

namespace Saman9074\IranianValidationSuite\Rules;

use Illuminate\Contracts\Validation\Rule;

class ShebaNumberRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * Iranian Sheba (IBAN) validation based on ISO 7064 Mod 97-10.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // 1. Preprocessing: Remove spaces and convert to uppercase
        $sheba = strtoupper(str_replace(' ', '', (string)$value));

        // 2. Basic checks: Length and format (must start with IR and be 26 chars long)
        if (strlen($sheba) !== 26) {
            return false;
        }
        if (substr($sheba, 0, 2) !== 'IR') {
            return false;
        }
        // The rest (after IR) should be digits for a valid *Iranian* Sheba structure before rearrangement
        if (!ctype_digit(substr($sheba, 2))) {
             // This check is for the initial format. After rearrangement, letters will appear from 'IR'.
        }


        // 3. Rearrange: Move the first 4 characters (country code and check digits) to the end
        $rearrangedSheba = substr($sheba, 4) . substr($sheba, 0, 4);

        // 4. Convert letters to numbers (A=10, B=11, ..., Z=35)
        $numericSheba = '';
        foreach (str_split($rearrangedSheba) as $char) {
            if (ctype_alpha($char)) {
                // ASCII value of 'A' is 65. So, 'A' becomes 10 (65 - 55), 'B' becomes 11 (66 - 55), etc.
                $numericSheba .= (ord($char) - 55);
            } elseif (ctype_digit($char)) {
                $numericSheba .= $char;
            } else {
                return false; // Invalid character found
            }
        }

        // 5. Calculate Modulo 97
        // PHP's native % operator might not work correctly for very large numbers.
        // We need to perform modulo on a string representation of the large number.
        // A common way is to process the number in chunks.
        $remainder = 0;
        $chunkSize = 7; // Process in chunks to avoid overflow with standard integer types
                        // Max integer in PHP is around 9*10^18, 97 is small enough that larger chunks are fine.
                        // Let's use a more direct approach for bcmath or gmp if available, or a loop.

        if (function_exists('bcmod')) {
            $remainder = bcmod($numericSheba, '97');
        } else {
            // Manual modulo for large numbers if bcmath is not available
            $tempSheba = $numericSheba;
            while (strlen($tempSheba) > 2) { // Process until the number is small enough for native modulo
                $chunk = substr($tempSheba, 0, $chunkSize);
                $tempSheba = ($chunk % 97) . substr($tempSheba, $chunkSize);
            }
            $remainder = $tempSheba % 97;
        }

        // 6. Check if the remainder is 1
        return (int)$remainder === 1;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'iranian-validation-suite::validation.iranian_sheba';
    }
}
