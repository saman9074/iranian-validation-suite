<?php

// File: src/Services/Kyc/Responses/KycDriverResponse.php
namespace Saman9074\IranianValidationSuite\Services\Kyc\Responses;

use Saman9074\IranianValidationSuite\Contracts\Kyc\KycDriverResponseInterface;

/**
 * Class KycDriverResponse
 * A base implementation for KYC driver responses.
 */
class KycDriverResponse implements KycDriverResponseInterface
{
    protected bool $successful;
    protected $statusCode;
    protected ?string $message;
    protected $rawData;
    protected $processedData;

    /**
     * KycDriverResponse constructor.
     *
     * @param bool $successful Whether the operation was successful.
     * @param mixed|null $statusCode The status code.
     * @param string|null $message The response message.
     * @param mixed|null $rawData The raw response data from the provider.
     * @param mixed|null $processedData Processed data (e.g., decoded JSON).
     */
    public function __construct(
        bool $successful,
        $statusCode = null,
        ?string $message = null,
        $rawData = null,
        $processedData = null
    ) {
        $this->successful = $successful;
        $this->statusCode = $statusCode;
        $this->message = $message;
        $this->rawData = $rawData;
        $this->processedData = $processedData ?? (is_array($rawData) || is_object($rawData) ? $rawData : null);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getRawData()
    {
        return $this->rawData;
    }

    public function getData()
    {
        return $this->processedData;
    }

    public function get(string $key, $default = null)
    {
        if (is_array($this->processedData) && array_key_exists($key, $this->processedData)) {
            return $this->processedData[$key];
        }
        if (is_object($this->processedData) && property_exists($this->processedData, $key)) {
            return $this->processedData->{$key};
        }
        return $default;
    }
}

// File: src/Exceptions/Kyc/KycException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc;

/**
 * Class KycException
 * Base exception for KYC related errors.
 */
class KycException extends \Exception
{
    protected $kycErrorCode;
    protected $kycProviderName;

    public function __construct(
        string $message = "",
        int $code = 0,
        \Throwable $previous = null,
        ?string $kycErrorCode = null,
        ?string $kycProviderName = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->kycErrorCode = $kycErrorCode;
        $this->kycProviderName = $kycProviderName;
    }

    public function getKycErrorCode(): ?string
    {
        return $this->kycErrorCode;
    }

    public function getKycProviderName(): ?string
    {
        return $this->kycProviderName;
    }
}

// File: src/Exceptions/Kyc/KycConfigurationException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc;

class KycConfigurationException extends KycException
{
}

// File: src/Exceptions/Kyc/KycConnectionException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc;

class KycConnectionException extends KycException
{
}

// File: src/Exceptions/Kyc/KycInvalidResponseException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc;

class KycInvalidResponseException extends KycException
{
}

// File: src/Exceptions/Kyc/KycAuthenticationException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc;

class KycAuthenticationException extends KycException
{
}
?>
