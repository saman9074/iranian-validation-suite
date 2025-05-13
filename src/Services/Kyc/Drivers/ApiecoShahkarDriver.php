<?php

namespace Saman9074\IranianValidationSuite\Services\Kyc\Drivers;

use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarInterface;
use Saman9074\IranianValidationSuite\Exceptions\KycException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config; // To access package configuration
use Throwable; // To catch any kind of exceptions during HTTP request

class ApiecoShahkarDriver implements ShahkarInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $configPrefix = 'iranian-validation-suite.kyc_drivers.apieco.services.shahkar'; // More specific config path

    /**
     * ApiecoShahkarDriver constructor.
     *
     * @throws KycException If API key or base URL is not configured.
     */
    public function __construct()
    {
        // It's better to resolve configurations within the methods or ensure they are set
        // when the KycManager creates the driver instance, passing the config array.
        // For now, we'll attempt to load them here, but this might be refactored
        // when we build the KycManager.

        // Let's assume the KycManager will pass the specific driver config.
        // For this standalone class, we'll make them configurable via a method or constructor injection later.
        // $this->apiKey = Config::get($this->configPrefix . '.api_key');
        // $this->baseUrl = Config::get($this->configPrefix . '.base_url');

        // if (empty($this->apiKey) || empty($this->baseUrl)) {
        //     throw new KycException("Apieco Shahkar API key or base URL is not configured.");
        // }
    }

    /**
     * Set the API configuration for the driver.
     * This method would typically be called by the KycManager.
     *
     * @param array $config An array containing 'api_key' and 'base_url'.
     * @return $this
     * @throws KycException If required configuration is missing.
     */
    public function setConfig(array $config): self
    {
        $this->apiKey = $config['api_key'] ?? null;
        $this->baseUrl = $config['base_url'] ?? null; // Example: 'https://api.apieco.ir/v2/'

        if (empty($this->apiKey) || empty($this->baseUrl)) {
            throw new KycException("Apieco Shahkar API key or base URL is not configured for the driver instance.");
        }
        return $this;
    }


    /**
     * Match a national ID with a mobile number using Apieco's Shahkar service.
     *
     * @param  string  $nationalId The national ID to verify.
     * @param  string  $mobile The mobile number to verify.
     * @return bool True if the national ID and mobile number match, false otherwise.
     * @throws KycException For API errors, configuration issues, or other problems.
     */
    public function matchMobileNationalId(string $nationalId, string $mobile): bool
    {
        if (empty($this->apiKey) || empty($this->baseUrl)) {
            // This check is crucial if setConfig wasn't called (e.g. direct instantiation for tests)
            // However, the KycManager should ensure setConfig is called.
            // For robustness, let's re-fetch from global config if not set,
            // assuming a global config structure exists.
            // This part is tricky without the Manager. Let's assume setConfig was called.
             throw new KycException("Apieco Shahkar driver is not configured. Please call setConfig.");
        }

        // Endpoint for Apieco Shahkar (this is an example, refer to actual Apieco docs)
        $endpoint = rtrim($this->baseUrl, '/') . '/shahkar/match'; // Example endpoint

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey, // Example Authorization
                'Accept' => 'application/json',
            ])->post($endpoint, [
                'national-code' => $nationalId, // Parameter names based on Apieco's documentation
                'mobile' => $mobile,
            ]);

            if ($response->successful()) {
                // Assuming Apieco returns a specific field for the match status, e.g., 'matched' or 'result.matched'
                // This needs to be adapted based on the actual Apieco API response structure.
                // Example: return $response->json('data.matched', false);
                // For now, let's assume a simple boolean in a 'matched' field.
                $matchStatus = $response->json('matched'); // Adjust key as per Apieco's actual response
                if ($matchStatus === null) {
                    // If the 'matched' key is not present or null, it's an unexpected response format.
                    throw new KycException("Unexpected response format from Apieco Shahkar service. 'matched' field missing.", $response->status());
                }
                return (bool) $matchStatus;
            }

            // Handle specific HTTP error codes from Apieco if documented
            if ($response->status() === 401) {
                throw new KycException("Apieco Shahkar: Unauthorized - Invalid API Key.", $response->status());
            }
            if ($response->status() === 400) {
                 $errorBody = $response->json();
                 $errorMessage = $errorBody['message'] ?? 'Bad Request - Invalid input.';
                throw new KycException("Apieco Shahkar: Bad Request - {$errorMessage}", $response->status());
            }
            // Add more specific error handling based on Apieco's API documentation

            // Generic error for other failed responses
            throw new KycException(
                "Apieco Shahkar service request failed with status: " . $response->status() . ". Body: " . $response->body(),
                $response->status()
            );

        } catch (Throwable $e) {
            // Catch any exception during the HTTP request (e.g., connection timeout, DNS resolution)
            // or from $response->json() if the body is not valid JSON.
            // Re-throw as KycException to provide a consistent exception type from the package.
            if ($e instanceof KycException) {
                throw $e; // Re-throw if it's already our custom exception
            }
            throw new KycException("Error communicating with Apieco Shahkar service: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
