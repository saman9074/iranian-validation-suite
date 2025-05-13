<?php

namespace Saman9074\IranianValidationSuite\Contracts\Kyc;

/**
 * Interface ShahkarInterface
 * Defines the contract for services that match a national ID with a mobile number (like Shahkar).
 */
interface ShahkarInterface
{
    /**
     * Match a national ID with a mobile number.
     *
     * @param  string  $nationalId The national ID to verify.
     * @param  string  $mobile The mobile number to verify.
     * @return bool True if the national ID and mobile number match, false otherwise.
     * @throws \Saman9074\IranianValidationSuite\Exceptions\KycException For API errors or other issues.
     */
    public function matchMobileNationalId(string $nationalId, string $mobile): bool;
}
