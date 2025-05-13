<?php

namespace Saman9074\IranianValidationSuite\Rules;

use Illuminate\Contracts\Validation\Rule;

class MobileNumberRule implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * Validates an Iranian mobile number.
     * It should start with a valid Iranian mobile prefix and be 11 digits long (e.g., 09xxxxxxxxx)
     * or 10 digits long if the leading zero is omitted (e.g., 9xxxxxxxxx).
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        // 1. Preprocessing: Remove non-digits
        $mobileNumber = preg_replace('/[^0-9]/', '', (string)$value);

        // 2. Normalize to 11 digits (add leading zero if it's 10 digits and starts with 9)
        if (strlen($mobileNumber) === 10 && substr($mobileNumber, 0, 1) === '9') {
            $mobileNumber = '0' . $mobileNumber;
        }

        // 3. Check length: Must be exactly 11 digits now
        if (strlen($mobileNumber) !== 11) {
            return false;
        }

        // 4. Check for valid Iranian mobile prefixes
        // This is a simplified list. A more comprehensive list might be needed for higher accuracy.
        // Common prefixes: 090, 091, 092, 093, 099 (and their sub-ranges like 0910-0919, 0930-0939 etc.)
        // A regex can cover these patterns.
        $pattern = '/^09(0[1-5]|1[0-9]|2[0-2]|3[0-9]|9[0-4,8,9])[0-9]{7}$/';
        // More specific prefixes:
        // Hamrah-e Avval: 0910-0919, 0990-0994
        // Irancell: 0930, 0933, 0935-0939, 0901-0905
        // Rightel: 0920-0922
        // Shatel Mobile: 09981
        // Samantel: 09999
        // Anarestan (for kids, Hamrah-e Avval): 09944
        // Other MVNOs might exist.
        // For simplicity, we'll use a broader pattern that covers most common prefixes.
        // A more precise regex:
        // ^09(
        //    1[0-9]| // Hamrah-e Avval (0910-0919)
        //    9[0-489]| // Hamrah-e Avval (0990-0994, 0998, 0999) - 0998 is Shatel, 0999 is Samantel
        //    3[035-9]| // Irancell (0930, 0933, 0935-0939)
        //    0[1-5]| // Irancell (0901-0905)
        //    2[0-2] // Rightel (0920-0922)
        // )
        // \d{7}$

        // A slightly more relaxed but common pattern:
        if (!preg_match('/^09[0-9]{9}$/', $mobileNumber)) {
             return false; // Does not match the general 09xxxxxxxxx pattern
        }

        // More specific prefix check (optional, can be enhanced)
        $validPrefixes = [
            '0901', '0902', '0903', '0904', '0905', // Irancell
            '0910', '0911', '0912', '0913', '0914', '0915', '0916', '0917', '0918', '0919', // Hamrah-e Avval
            '0920', '0921', '0922', // Rightel
            '0930', '0933', '0935', '0936', '0937', '0938', '0939', // Irancell
            '0990', '0991', '0992', '0993', '0994', // Hamrah-e Avval (includes Anarestan 09944)
            '0998', // Shatel Mobile (e.g., 09981)
            '0999', // Samantel (e.g., 09999)
        ];

        $prefix3 = substr($mobileNumber, 0, 3); // e.g., 090, 091, 093
        $prefix4 = substr($mobileNumber, 0, 4); // e.g., 0912, 0935

        $isValidPrefix = false;
        if (in_array($prefix3, ['090', '091', '092', '093', '099'])) { // General check
            $isValidPrefix = true;
            // For a more precise check, iterate through $validPrefixes or use a more complex regex.
            // Example of a more precise check (can be refined):
            // foreach ($validPrefixes as $vp) {
            //     if (strpos($mobileNumber, $vp) === 0) {
            //         $isValidPrefix = true;
            //         break;
            //     }
            // }
        }
        // If you want a very strict prefix validation, the regex above is better.
        // For now, the general 09xxxxxxxxx check combined with length is often sufficient for basic validation.

        return $isValidPrefix; // Or simply return true if the regex `/^09[0-9]{9}$/` is deemed sufficient.
                               // Let's stick to the regex for simplicity and broad coverage for now.
                               // The regex `/^09[0-9]{9}$/` was already checked.
                               // The $isValidPrefix logic above is an alternative more specific check.
                               // We will use the simple regex pattern for now.
        // return preg_match('/^09[0-9]{9}$/', $mobileNumber) === 1;
        // Let's use the more comprehensive regex:
         return preg_match('/^09(0[1-5]|1[0-9]|2[0-2]|3[0-9]|9[0-489])[0-9]{7}$/', $mobileNumber) === 1;


    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'iranian-validation-suite::validation.iranian_mobile_number';
    }
}
