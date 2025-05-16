<?php
// File: src/Exceptions/Kyc/KycConfigurationException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc; // Namespace صحیح

/**
 * Class KycConfigurationException
 * For errors related to KYC driver or service configuration.
 */
class KycConfigurationException extends KycException // نام کلاس و ارث‌بری صحیح
{
}

// File: src/Exceptions/Kyc/KycConnectionException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc; // Namespace صحیح

/**
 * Class KycConnectionException
 * For errors related to connecting to the KYC provider's API (e.g., network issues, timeouts).
 */
class KycConnectionException extends KycException // نام کلاس و ارث‌بری صحیح
{
}

// File: src/Exceptions/Kyc/KycInvalidResponseException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc; // Namespace صحیح

/**
 * Class KycInvalidResponseException
 * For errors when the KYC provider's response is unexpected, malformed, or cannot be parsed.
 */
class KycInvalidResponseException extends KycException // نام کلاس و ارث‌بری صحیح
{
}

// File: src/Exceptions/Kyc/KycAuthenticationException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc; // Namespace صحیح

/**
 * Class KycAuthenticationException
 * For errors related to authentication with the KYC provider (e.g., invalid API key, token expired).
 */
class KycAuthenticationException extends KycException // نام کلاس و ارث‌بری صحیح
{
}
