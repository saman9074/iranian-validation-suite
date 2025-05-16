<?php

// File: src/Contracts/Kyc/LivenessServiceInterface.php
namespace Saman9074\IranianValidationSuite\Contracts\Kyc; // Namespace صحیح

/**
 * Interface LivenessServiceInterface
 * Defines the contract for liveness detection services.
 * Any driver providing liveness detection should implement this interface.
 */
interface LivenessServiceInterface // نام اینترفیس صحیح
{
    /**
     * Initiates a liveness check session.
     * This might involve getting a challenge, a session token, or a URL for client-side interaction.
     *
     * @param array $options Optional parameters for initiating the check (e.g., user_id, national_id, callback_url).
     * @return KycDriverResponseInterface Contains information needed for the client-side, like a session_id or a redirect_url.
     */
    public function initiateLivenessCheck(array $options = []): KycDriverResponseInterface;

    /**
     * Verifies the liveness based on data provided (e.g., video, images, challenge responses from client-side SDK).
     * This method might be called after the client-side part is completed.
     *
     * @param mixed $livenessData Data captured from the client (e.g., uploaded file path, base64 string, session ID from initiate, or a unique token from SDK).
     * @param array $options Additional options for verification (e.g., session_id if not part of $livenessData).
     * @return KycDriverResponseInterface Contains the result of the liveness verification (e.g., success, failure, score).
     */
    public function verifyLiveness($livenessData, array $options = []): KycDriverResponseInterface;

    /**
     * Fetches the result of a liveness check, possibly using a transaction ID or session ID.
     * Useful for asynchronous liveness checks or when the result is not immediately available.
     *
     * @param string $transactionId The transaction or session identifier provided by the KYC service.
     * @param array $options Additional options.
     * @return KycDriverResponseInterface Contains the liveness status and any associated data.
     */
    public function getLivenessResult(string $transactionId, array $options = []): KycDriverResponseInterface;
}
