<?php

// File: src/Contracts/Kyc/KycDriverResponseInterface.php
namespace Saman9074\IranianValidationSuite\Contracts\Kyc; // Namespace صحیح

/**
 * Interface KycDriverResponseInterface
 * Defines the structure for responses from KYC drivers.
 */
interface KycDriverResponseInterface // نام اینترفیس صحیح
{
    /**
     * Checks if the KYC operation was successful.
     *
     * @return bool
     */
    public function isSuccessful(): bool;

    /**
     * Gets the status code from the KYC provider or internal status.
     *
     * @return string|int|null
     */
    public function getStatusCode();

    /**
     * Gets the message associated with the response.
     * Could be a success message or an error message.
     *
     * @return string|null
     */
    public function getMessage(): ?string;

    /**
     * Gets the raw data received from the KYC provider.
     *
     * @return array|object|string|null
     */
    public function getRawData();

    /**
     * Gets any processed or specific data extracted from the raw response.
     * This data should ideally be in a consistent format (e.g., array or a dedicated DTO).
     *
     * @return array|object|null
     */
    public function getData();

    /**
     * Gets a specific piece of data by key from the processed data.
     *
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, $default = null);
}
