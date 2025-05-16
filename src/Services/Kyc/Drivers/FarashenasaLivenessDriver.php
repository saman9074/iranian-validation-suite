<?php
// File: src/Services/Kyc/Drivers/FarashenasaLivenessDriver.php
namespace Saman9074\IranianValidationSuite\Services\Kyc\Drivers;

use Saman9074\IranianValidationSuite\Contracts\Kyc\LivenessServiceInterface;
use Saman9074\IranianValidationSuite\Contracts\Kyc\KycDriverResponseInterface;
use Saman9074\IranianValidationSuite\Services\Kyc\Responses\KycDriverResponse;
use Saman9074\IranianValidationSuite\Exceptions\Kyc\KycException;
use Saman9074\IranianValidationSuite\Exceptions\Kyc\KycConfigurationException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Http\UploadedFile; // Import UploadedFile

class FarashenasaLivenessDriver extends AbstractKycDriver implements LivenessServiceInterface
{
    protected string $gatewayToken;
    protected string $gatewaySystemValue;

    public function __construct(array $config, HttpFactory $httpClientFactory)
    {
        parent::__construct($config, $httpClientFactory);
        $this->gatewayToken = $this->getConfigValue('api_key');
        $this->gatewaySystemValue = $this->getConfigValue('gateway_system_value', 'sahab');

        if (empty($this->gatewayToken) || empty($this->getBaseUrl())) {
            throw new KycConfigurationException("Farashenasa Liveness Driver: 'api_key' (for gateway-token) or 'base_url' is not configured for provider '{$this->providerName}'.");
        }
    }

    public function initiateLivenessCheck(array $options = []): KycDriverResponseInterface
    {
        Log::info("FarashenasaLivenessDriver: initiateLivenessCheck called.", ['options_received' => $options]);

        if (empty($options['uniqueKey'])) {
            throw new KycConfigurationException("Farashenasa Liveness Driver: 'uniqueKey' is required in options for initiateLivenessCheck.");
        }

        $uniqueKey = $options['uniqueKey'];
        $endpoint = $this->getConfigValue('endpoints.getText', '/farashenasa/v1/test');

        $initiateTimeout = $this->getConfigValue('initiate_timeout', $this->getConfigValue('timeout', 30));
        $requestOptions = [
            'data' => ['uniqueKey' => $uniqueKey],
            'timeout' => $initiateTimeout,
        ];
        
        try {
            $response = $this->makeRequest('GET', $endpoint, $requestOptions);
            
            $successCondition = fn($r) => $r->successful() && !empty(Arr::get($r->json(), 'data.speaking.text'));

            return $this->processResponse(
                $response,
                $successCondition,
                function ($r) { 
                    $responseData = $r->json();
                    return [
                        'text_to_read' => Arr::get($responseData, 'data.speaking.text'), 
                        'speaking_id' => Arr::get($responseData, 'data.speaking.id'),
                        'full_response' => $responseData,
                    ];
                },
                function ($r, $isSuccessfulApiCall) { 
                    if ($isSuccessfulApiCall) return "Text for liveness check retrieved successfully.";
                    
                    $responseData = $r->json();
                    $errorMsg = Arr::get($responseData, 'data.data.additionalInfo.data.message.fa') ??
                                Arr::get($responseData, 'data.data.message.fa') ??
                                Arr::get($responseData, 'data.message.fa') ?? 
                                Arr::get($responseData, 'data.meta.message.fa') ??
                                Arr::get($responseData, 'meta.message.fa') ??
                                Arr::get($responseData, 'message') ??
                                "Failed to retrieve text for liveness check.";
                    return $errorMsg;
                }
            );
        } catch (KycException $e) {
            Log::error("Farashenasa GetText Error for '{$this->providerName}': " . $e->getMessage(), ['uniqueKey' => $uniqueKey, 'exception_code' => $e->getCode(), 'kyc_error_code' => $e->getKycErrorCode()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error("Farashenasa GetText - General Error for '{$this->providerName}': " . $e->getMessage(), ['uniqueKey' => $uniqueKey]);
            throw new KycException("Farashenasa GetText failed for '{$this->providerName}': " . $e->getMessage(), 0, $e, null, $this->providerName);
        }
    }

    /**
     * Verifies the liveness.
     * $livenessData is expected to be an array containing 'selfie_file_info' and 'video_file_info'.
     * Each of these _file_info arrays should contain either:
     * - 'uploaded_file': An instance of Illuminate\Http\UploadedFile (when file comes from user upload)
     * - 'path': A string path to a local file on the server (for hardcoded files)
     * and optionally 'original_filename' if 'path' is used.
     */
    public function verifyLiveness($livenessData, array $options = []): KycDriverResponseInterface
    {
        Log::info("FarashenasaLivenessDriver: verifyLiveness called.", [
            'livenessData_structure_check' => [
                'has_selfie_file_info' => isset($livenessData['selfie_file_info']),
                'has_video_file_info' => isset($livenessData['video_file_info']),
            ],
            'options_received' => $options
        ]);

        if (empty($options['uniqueKey'])) {
            throw new KycConfigurationException("Farashenasa Liveness Driver: 'uniqueKey' is required in options for verifyLiveness.");
        }

        // --- Validate and Prepare Selfie File ---
        if (empty($livenessData['selfie_file_info']) || !is_array($livenessData['selfie_file_info'])) {
            throw new KycConfigurationException("Farashenasa Liveness Driver: 'selfie_file_info' (array) is required in livenessData.");
        }
        $selfieContents = null;
        $selfieFilename = null;
        if (isset($livenessData['selfie_file_info']['uploaded_file']) && $livenessData['selfie_file_info']['uploaded_file'] instanceof UploadedFile) {
            /** @var UploadedFile $selfieFileObj */
            $selfieFileObj = $livenessData['selfie_file_info']['uploaded_file'];
            if (!$selfieFileObj->isValid()) {
                 throw new KycException("Uploaded selfie file is not valid. Error code: " . $selfieFileObj->getError(), 0, null, null, $this->providerName);
            }
            $selfieContents = fopen($selfieFileObj->getRealPath(), 'r');
            $selfieFilename = $selfieFileObj->getClientOriginalName();
            Log::info("FarashenasaLivenessDriver: Using UploadedFile for selfie.", ['original_name' => $selfieFilename, 'temp_path' => $selfieFileObj->getRealPath()]);
        } elseif (isset($livenessData['selfie_file_info']['path'])) {
            $path = $livenessData['selfie_file_info']['path'];
            if (!file_exists($path) || !is_readable($path)) {
                throw new KycConfigurationException("Farashenasa Liveness Driver: Selfie file path '{$path}' not found or not readable.");
            }
            $selfieContents = fopen($path, 'r');
            $selfieFilename = $livenessData['selfie_file_info']['original_filename'] ?? basename($path);
            Log::info("FarashenasaLivenessDriver: Using path for selfie.", ['path' => $path, 'filename_used' => $selfieFilename]);
        } else {
            throw new KycConfigurationException("Farashenasa Liveness Driver: Invalid 'selfie_file_info' structure in livenessData. Expected 'uploaded_file' or 'path'.");
        }

        // --- Validate and Prepare Video File ---
        if (empty($livenessData['video_file_info']) || !is_array($livenessData['video_file_info'])) {
            throw new KycConfigurationException("Farashenasa Liveness Driver: 'video_file_info' (array) is required in livenessData.");
        }
        $videoContents = null;
        $videoFilename = null;
        if (isset($livenessData['video_file_info']['uploaded_file']) && $livenessData['video_file_info']['uploaded_file'] instanceof UploadedFile) {
            /** @var UploadedFile $videoFileObj */
            $videoFileObj = $livenessData['video_file_info']['uploaded_file'];
             if (!$videoFileObj->isValid()) {
                 throw new KycException("Uploaded video file is not valid. Error code: " . $videoFileObj->getError(), 0, null, null, $this->providerName);
            }
            $videoContents = fopen($videoFileObj->getRealPath(), 'r');
            $videoFilename = $videoFileObj->getClientOriginalName();
            Log::info("FarashenasaLivenessDriver: Using UploadedFile for video.", ['original_name' => $videoFilename, 'temp_path' => $videoFileObj->getRealPath()]);
        } elseif (isset($livenessData['video_file_info']['path'])) {
            $path = $livenessData['video_file_info']['path'];
            if (!file_exists($path) || !is_readable($path)) {
                throw new KycConfigurationException("Farashenasa Liveness Driver: Video file path '{$path}' not found or not readable.");
            }
            $videoContents = fopen($path, 'r');
            $videoFilename = $livenessData['video_file_info']['original_filename'] ?? basename($path);
            Log::info("FarashenasaLivenessDriver: Using path for video.", ['path' => $path, 'filename_used' => $videoFilename]);
        } else {
            throw new KycConfigurationException("Farashenasa Liveness Driver: Invalid 'video_file_info' structure in livenessData. Expected 'uploaded_file' or 'path'.");
        }

        // --- Validate additionalInfo ---
        if (empty($options['additionalInfo']) || !is_array($options['additionalInfo'])) {
            throw new KycConfigurationException("Farashenasa Liveness Driver: 'additionalInfo' (array) is required in options.");
        }
        if (empty(Arr::get($options, 'additionalInfo.nationalCode')) || empty(Arr::get($options, 'additionalInfo.birthDate'))) {
             throw new KycConfigurationException("Farashenasa Liveness Driver: 'nationalCode' and 'birthDate' are required within 'additionalInfo'. 'nationalCardSerialNumber' is also expected by API (can be empty string if not applicable).");
        }

        $endpoint = $this->getConfigValue('endpoints.authenticate', '/farashenasa/v1/authenticate');
        $additionalInfoJson = json_encode($options['additionalInfo']);
        $verifyTimeout = $this->getConfigValue('verify_timeout', $this->getConfigValue('timeout', 120));
        
        $requestOptions = [
            'headers' => [
                'gateway-token' => $this->gatewayToken,
                'gateway-system' => $this->gatewaySystemValue,
            ],
            'multipart' => [ 
                [ 'name' => 'uniqueKey', 'contents' => $options['uniqueKey'] ],
                [ 'name' => 'mode', 'contents' => 'speaking' ],
                [ 'name' => 'additionalInfo', 'contents' => $additionalInfoJson ],
                [ 'name' => 'selfie', 'contents' => $selfieContents, 'filename' => $selfieFilename ],
                [ 'name' => 'testVideo', 'contents' => $videoContents, 'filename' => $videoFilename ],
            ],
            'timeout' => $verifyTimeout,
        ];
        
        try {
            $response = $this->makeRequest('POST', $endpoint, $requestOptions);

            return $this->processResponse(
                $response,
                function ($r) { 
                    $apiStatus = $r->status();
                    $jsonResponse = $r->json(); 

                    Log::debug("Farashenasa VerifyLiveness - SuccessCondition Check:", [
                        'api_http_status' => $apiStatus,
                        'json_response_type' => gettype($jsonResponse),
                        'json_response_for_status_check_snippet' => substr(json_encode($jsonResponse), 0, 500) 
                    ]);

                    if ($apiStatus !== 200) {
                        Log::debug("Farashenasa VerifyLiveness - SuccessCondition: API HTTP status not 200, returning false.");
                        return false; 
                    }
                    
                    $livenessStatus = null;
                    if (is_array($jsonResponse) && 
                        isset($jsonResponse['data']['data']['status'])) { 
                        $livenessStatus = $jsonResponse['data']['data']['status'];
                    } 
                    elseif ($jsonResponse !== null) { 
                        $livenessStatus = Arr::get($jsonResponse, 'data.data.status');
                    }

                    Log::debug("Farashenasa VerifyLiveness - SuccessCondition - Extracted Liveness Status:", [
                        'liveness_status_value' => $livenessStatus,
                        'liveness_status_type' => gettype($livenessStatus),
                        'path_attempted' => 'data.data.status' 
                    ]);

                    $conditionResult = ($livenessStatus === true || $livenessStatus === false);
                    Log::debug("Farashenasa VerifyLiveness - SuccessCondition - Final Result:", ['condition_evaluates_to' => $conditionResult]);
                    return $conditionResult;
                },
                function ($r) { 
                    $responseData = $r->json();
                    $coreKycData = Arr::get($responseData, 'data.data'); 
                    if ($coreKycData && (Arr::has($coreKycData, 'status'))) { 
                        return [
                            'liveness_successful' => (bool) Arr::get($coreKycData, 'status'),
                            'result_message' => Arr::get($coreKycData, 'result'),
                            'state' => Arr::get($coreKycData, 'state'),
                            'retry_allowed' => (bool) Arr::get($coreKycData, 'retry'),
                            'ai_results' => Arr::get($coreKycData, 'additionalInfo.aiResults'),
                            'full_response' => $responseData,
                        ];
                    }
                    return ['full_response' => $responseData];
                },
                function ($r, $isLivenessProcessingSuccessful) { 
                    $responseData = $r->json();
                    if ($isLivenessProcessingSuccessful) { 
                        return Arr::get($responseData, 'data.data.result', "Liveness check processed.");
                    }
                    
                    $specificValidationErrorFa = Arr::get($responseData, 'data.additionalInfo.data.message.fa');
                    if ($specificValidationErrorFa) return $specificValidationErrorFa;

                    $generalAiErrorFa = Arr::get($responseData, 'data.message.fa');
                    if ($generalAiErrorFa) return $generalAiErrorFa;
                    
                    $genericMessage = Arr::get($responseData, 'message');
                    if($genericMessage) return $genericMessage;

                    return "Farashenasa authentication request failed or returned an unexpected response.";
                }
            );
        } catch (KycException $e) {
            Log::error("Farashenasa Authenticate Error for '{$this->providerName}': " . $e->getMessage(), ['uniqueKey' => $options['uniqueKey'], 'exception_code' => $e->getCode()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error("Farashenasa Authenticate - General Error for '{$this->providerName}': " . $e->getMessage(), ['uniqueKey' => $options['uniqueKey']]);
            throw new KycException("Farashenasa authentication failed for '{$this->providerName}': " . $e->getMessage(), 0, $e, null, $this->providerName);
        }
    }

    public function getLivenessResult(string $transactionId, array $options = []): KycDriverResponseInterface
    {
        $notSupportedMessage = "Farashenasa getLivenessResult is not supported by the current API flow. Result is typically provided by verifyLiveness.";
        Log::warning("FarashenasaLivenessDriver::getLivenessResult called but not implemented based on current API docs.", [
            'transactionId' => $transactionId,
            'provider' => $this->providerName,
            'message' => $notSupportedMessage
        ]);
        
        return new KycDriverResponse(
            false,
            501,
            $notSupportedMessage,
            null,
            ['transactionId' => $transactionId, 'status_message' => $notSupportedMessage]
        );
    }
}
?>
