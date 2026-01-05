<?php

namespace App\Exceptions;

use App\Http\Enums\ApiErrorCode;
use Exception;

/**
 * Base exception class for domain/business logic errors.
 * 
 * Use this exception for business rule violations and domain-specific errors.
 * The global exception handler will automatically convert this to the unified error format.
 */
class BusinessException extends Exception
{
    protected ApiErrorCode $errorCode;
    protected mixed $details = null;

    /**
     * Create a new business exception instance.
     * 
     * @param ApiErrorCode $errorCode The error code
     * @param string $message The error message
     * @param mixed $details Optional error details
     * @param int $code HTTP status code (default: 400)
     */
    public function __construct(
        ApiErrorCode $errorCode,
        string $message,
        mixed $details = null,
        int $code = 400
    ) {
        parent::__construct($message, $code);
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): ApiErrorCode
    {
        return $this->errorCode;
    }

    /**
     * Get the error details.
     */
    public function getDetails(): mixed
    {
        return $this->details;
    }
}

