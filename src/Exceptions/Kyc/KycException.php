<?php

// File: src/Exceptions/Kyc/KycException.php
namespace Saman9074\IranianValidationSuite\Exceptions\Kyc; // Namespace صحیح

/**
 * Class KycException
 * Base exception for KYC related errors.
 */
class KycException extends \Exception // نام کلاس صحیح
{
    protected $kycErrorCode; // Provider-specific error code
    protected $kycProviderName; // Name of the KYC provider (e.g., 'farashenasa', 'uid')

    /**
     * KycException constructor.
     *
     * @param string $message The exception message.
     * @param int $code The HTTP status code or internal error code.
     * @param \Throwable|null $previous The previous throwable used for the exception chaining.
     * @param string|null $kycErrorCode Specific error code from the KYC provider or internal.
     * @param string|null $kycProviderName The name of the KYC provider that caused the error.
     */
    public function __construct(
        string $message = "",
        int $code = 0, // Often an HTTP status code
        \Throwable $previous = null,
        ?string $kycErrorCode = null,
        ?string $kycProviderName = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->kycErrorCode = $kycErrorCode;
        $this->kycProviderName = $kycProviderName;
    }

    /**
     * Get the KYC specific error code from the provider.
     *
     * @return string|null
     */
    public function getKycErrorCode(): ?string
    {
        return $this->kycErrorCode;
    }

    /**
     * Get the name of the KYC provider.
     *
     * @return string|null
     */
    public function getKycProviderName(): ?string
    {
        return $this->kycProviderName;
    }
}

