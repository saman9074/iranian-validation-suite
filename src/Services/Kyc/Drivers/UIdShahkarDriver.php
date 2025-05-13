<?php

namespace Saman9074\IranianValidationSuite\Services\Kyc\Drivers;

use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarInterface;
use Saman9074\IranianValidationSuite\Exceptions\KycException;
use Illuminate\Support\Facades\Http;
use Throwable; // To catch any kind of exceptions during HTTP request

class UIdShahkarDriver implements ShahkarInterface
{
    protected ?string $businessId = null;
    protected ?string $businessToken = null;
    // The new base URL does not include the /api part, as the endpoint itself contains it.
    protected string $baseUrl = 'https://json-api.uid.ir';
    protected string $endpoint = '/api/inquiry/mobile/owner/v2';


    /**
     * UIdShahkarDriver constructor.
     */
    public function __construct()
    {
        // Configuration will be set by the KycManager via setConfig
    }

    /**
     * Set the API configuration for the driver.
     * This method would typically be called by the KycManager.
     *
     * @param array $config An array containing 'business_id', 'business_token', and optionally 'base_url' & 'endpoint'.
     * @return $this
     * @throws KycException If required configuration is missing.
     */
    public function setConfig(array $config): self
    {
        $this->businessId = $config['business_id'] ?? null;
        $this->businessToken = $config['business_token'] ?? null;

        if (isset($config['base_url'])) {
            $this->baseUrl = rtrim($config['base_url'], '/');
        }
        if (isset($config['endpoint'])) {
            $this->endpoint = $config['endpoint'];
        }

        if (empty($this->businessId) || empty($this->businessToken)) {
            throw new KycException("u-id Shahkar Business ID or Business Token is not configured for the driver instance.");
        }
        return $this;
    }

    /**
     * Match a national ID with a mobile number using u-id's Shahkar service (v2).
     *
     * @param  string  $nationalId The national ID to verify.
     * @param  string  $mobile The mobile number to verify (format 09xxxxxxxxx).
     * @return bool True if the national ID and mobile number match, false otherwise.
     * @throws KycException For API errors, configuration issues, or other problems.
     */
    public function matchMobileNationalId(string $nationalId, string $mobile): bool
    {
        if (empty($this->businessId) || empty($this->businessToken)) {
            throw new KycException("u-id Shahkar driver is not configured. Business ID or Token missing.");
        }

        $fullApiUrl = $this->baseUrl . $this->endpoint;

        $requestPayload = [
            'requestContext' => [
                'apiInfo' => [
                    'businessId' => $this->businessId,
                    'businessToken' => $this->businessToken,
                ],
            ],
            'nationalId' => $nationalId,
            'mobileNumber' => $mobile,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json;charset=UTF-8',
                'Accept' => 'application/json',
            ])->post($fullApiUrl, $requestPayload);

            $statusCode = $response->status();
            $responseData = $response->json();

            // According to docs, successful response has HTTP status 200
            // and the actual match status is in 'isMatched' field.
            // Error details are in 'responseContext.status.code' and 'responseContext.status.message'.

            if ($statusCode === 200 && isset($responseData['isMatched'])) {
                return (bool) $responseData['isMatched'];
            }

            // Handle errors based on 'responseContext.status'
            $errorCode = $responseData['responseContext']['status']['code'] ?? $statusCode; // Prefer API's error code
            $errorMessage = $responseData['responseContext']['status']['message'] ?? 'An unknown error occurred with the u-id Shahkar service.';

            // Specific error codes from u-id documentation:
            // 1: شناسه کسب‌و‌کار نامعتبر است
            // 2: توکن کسب‌و‌کار نامعتبر است
            // 3: کدملی نامعتبر است
            // 4: شماره موبایل نامعتبر است
            // 5: عدم تطابق شماره موبایل و کدملی (This would mean isMatched: false, handled above)
            // 6: خطای سرویس شاهکار
            // 7: خطای داخلی سرویس
            // More general HTTP errors:
            // 400: Bad Request (e.g. invalid JSON)
            // 401: Unauthorized (though their docs use businessId/Token in body)
            // 403: Forbidden
            // 500: Internal Server Error (on their side)

            // We can throw a generic KycException and include the specific code and message from their API.
            throw new KycException(
                "u-id Shahkar service error: " . $errorMessage . " (Code: {$errorCode})",
                (int)$errorCode // Ensure code is an integer
            );

        } catch (Throwable $e) {
            if ($e instanceof KycException) {
                throw $e; // Re-throw if it's already our custom exception
            }
            // Catch connection exceptions or other issues during the request
            throw new KycException("Error communicating with u-id Shahkar service: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
