<?php

namespace Saman9074\IranianValidationSuite\Services\Kyc\Drivers;

use Saman9074\IranianValidationSuite\Contracts\Kyc\ShahkarInterface;
use Saman9074\IranianValidationSuite\Exceptions\KycException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache; // For caching the access token
use Illuminate\Support\Str;
use Throwable;

class FinnotechShahkarDriver implements ShahkarInterface
{
    protected ?string $clientId = null;
    protected ?string $clientSecret = null;
    protected ?string $tokenNid = null; // NID required for generating the token
    protected string $shahkarScope = 'kyc:sms-shahkar-send:get'; // Default scope for Shahkar

    protected string $baseUrl = 'https://api.finnotech.ir'; // Default to production
    protected string $tokenEndpoint = '/dev/v2/oauth2/token';
    protected string $shahkarEndpoint = '/kyc/v2/clients/{clientId}/shahkar/smsSend';

    protected ?string $accessToken = null;
    protected ?int $tokenExpiresAt = null; // Timestamp when the token expires

    /**
     * FinnotechShahkarDriver constructor.
     */
    public function __construct()
    {
        // Configuration will be set by the KycManager via setConfig
    }

    /**
     * Set the API configuration for the driver.
     *
     * @param array $config An array containing 'client_id', 'client_secret', 'token_nid',
     * optionally 'shahkar_scope', 'base_url', 'token_endpoint', 'shahkar_endpoint'.
     * @return $this
     * @throws KycException If required configuration is missing.
     */
    public function setConfig(array $config): self
    {
        $this->clientId = $config['client_id'] ?? null;
        $this->clientSecret = $config['client_secret'] ?? null;
        $this->tokenNid = $config['token_nid'] ?? null; // NID for token generation

        if (isset($config['shahkar_scope'])) {
            $this->shahkarScope = $config['shahkar_scope'];
        }
        if (isset($config['base_url'])) {
            $this->baseUrl = rtrim($config['base_url'], '/');
        }
        if (isset($config['token_endpoint'])) {
            $this->tokenEndpoint = $config['token_endpoint'];
        }
        if (isset($config['shahkar_endpoint'])) {
            $this->shahkarEndpoint = $config['shahkar_endpoint'];
        }


        if (empty($this->clientId) || empty($this->clientSecret) || empty($this->tokenNid)) {
            throw new KycException("Finnotech Shahkar Client ID, Client Secret, or Token NID is not configured.");
        }
        return $this;
    }

    /**
     * Fetch a new access token from Finnotech.
     *
     * @return string The access token.
     * @throws KycException If token request fails.
     */
    protected function fetchAccessToken(): string
    {
        // Check cache first or if current token is still valid
        $cacheKey = 'finnotech_access_token_' . md5($this->clientId . $this->tokenNid . $this->shahkarScope);
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt) {
            // Try to retrieve from instance property first
             return $this->accessToken;
        }
        
        $cachedToken = Cache::get($cacheKey);
        if ($cachedToken) {
            $this->accessToken = $cachedToken['value'];
            $this->tokenExpiresAt = $cachedToken['expires_at'];
            if (time() < $this->tokenExpiresAt) {
                return $this->accessToken;
            }
        }

        $authString = base64_encode($this->clientId . ':' . $this->clientSecret);
        $fullTokenUrl = $this->baseUrl . $this->tokenEndpoint;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Basic ' . $authString,
            ])->post($fullTokenUrl, [
                'grant_type' => 'client_credentials',
                'nid' => $this->tokenNid,
                'scopes' => $this->shahkarScope, // Scope for Shahkar
            ]);

            $responseData = $response->json();
            $statusCode = $response->status();

            if ($statusCode === 200 && isset($responseData['result']['value'])) {
                $this->accessToken = $responseData['result']['value'];
                $lifeTimeSeconds = ($responseData['result']['lifeTime'] ?? 86400000) / 1000; // Convert ms to seconds
                $this->tokenExpiresAt = time() + $lifeTimeSeconds - 60; // Subtract 60s buffer

                Cache::put($cacheKey, ['value' => $this->accessToken, 'expires_at' => $this->tokenExpiresAt], $lifeTimeSeconds - 60);
                return $this->accessToken;
            }

            $apiResponseCode = $responseData['responseCode'] ?? null;
            $errorMessage = $responseData['error']['message'] ?? 'Failed to retrieve Finnotech access token.';
            throw new KycException(
                "Finnotech token request failed: " . $errorMessage . " (Response Code: {$apiResponseCode})",
                $statusCode
            );

        } catch (Throwable $e) {
            if ($e instanceof KycException) throw $e;
            throw new KycException("Error requesting Finnotech access token: " . $e->getMessage(), $e->getCode(), $e);
        }
    }


    /**
     * Match a national ID with a mobile number using Finnotech's Shahkar service.
     *
     * @param  string  $nationalId The national ID to verify.
     * @param  string  $mobile The mobile number to verify (format 09xxxxxxxxx).
     * @param  string|null $trackId Optional track ID for the request.
     * @return bool True if the national ID and mobile number match (indicated by successful SMS send), false otherwise.
     * @throws KycException For API errors, configuration issues, or other problems.
     */
    public function matchMobileNationalId(string $nationalId, string $mobile, ?string $trackId = null): bool
    {
        $token = $this->fetchAccessToken(); // Ensure we have a valid token

        $trackId = $trackId ?? (string) Str::uuid();
        // Replace {clientId} in the endpoint string
        $currentShahkarEndpoint = str_replace('{clientId}', $this->clientId, $this->shahkarEndpoint);
        $fullApiUrl = $this->baseUrl . $currentShahkarEndpoint;

        $queryParameters = [
            'trackId' => $trackId,
            'mobile' => $mobile,
            'nationalCode' => $nationalId,
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->get($fullApiUrl, $queryParameters);

            $statusCode = $response->status();
            $responseData = $response->json();

            if ($statusCode === 200 && isset($responseData['responseCode']) && $responseData['responseCode'] === 'FN-KCAT-20000100000') {
                return isset($responseData['result']['smsSent']) && $responseData['result']['smsSent'] === true;
            }

            $apiResponseCode = $responseData['responseCode'] ?? null;
            $errorMessage = $responseData['error']['message'] ?? ($responseData['result']['description'] ?? 'Finnotech Shahkar service error.');
            $errorCodeForException = $apiResponseCode ?? $statusCode;
            
            $finnotechErrorMessages = [ /* ... as defined before ... */ ];
             if ($apiResponseCode && isset($finnotechErrorMessages[$apiResponseCode])) {
                $errorMessage = $finnotechErrorMessages[$apiResponseCode];
            }

            throw new KycException(
                "Finnotech Shahkar service error: " . $errorMessage . " (Response Code: {$errorCodeForException})",
                is_string($errorCodeForException) ? $statusCode : (int)$errorCodeForException
            );

        } catch (Throwable $e) {
            if ($e instanceof KycException) throw $e;
            throw new KycException("Error communicating with Finnotech Shahkar service: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
