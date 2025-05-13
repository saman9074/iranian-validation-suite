<?php

namespace Saman9074\IranianValidationSuite\Exceptions;

use Exception; // Or \RuntimeException, \Illuminate\Contracts\Debug\ExceptionHandler, etc.

/**
 * Class KycException
 *
 * Custom exception for errors related to KYC services.
 */
class KycException extends Exception
{
    /**
     * KycException constructor.
     *
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param Exception|null $previous The previous throwable used for the exception chaining.
     */
    public function __construct(string $message = "", int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report the exception.
     *
     * You can customize how this exception is reported, e.g., logging.
     * This method is often used by Laravel's exception handler.
     *
     * @return bool|null
     */
    public function report(): ?bool
    {
        // Example: Log the exception if needed
        // \Log::error("KYC Service Error: " . $this->getMessage(), ['exception' => $this]);

        // Return false to indicate that this exception should not be reported by the default handler
        // if you handle reporting completely here. Or return null/true to use default reporting.
        return null;
    }

    /**
     * Render the exception into an HTTP response.
     *
     * You can customize how this exception is rendered as an HTTP response.
     * This is useful if your package might be used in a context where it directly handles HTTP responses,
     * though for a validation package, this is less common to override directly here.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse|null
     */
    public function render($request)
    {
        // If you want to return a specific JSON response when this exception occurs in an API context:
        // if ($request->expectsJson()) {
        //     return response()->json([
        //         'error' => 'KYC_SERVICE_ERROR',
        //         'message' => $this->getMessage()
        //     ], $this->getCode() ?: 503); // 503 Service Unavailable or a more specific code
        // }

        // Otherwise, let Laravel's default handler manage rendering.
        return null;
    }
}
