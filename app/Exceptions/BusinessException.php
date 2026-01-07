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
    protected ?ApiErrorCode $errorCode = null;
    protected mixed $details = null;

    /**
     * Create a new business exception instance.
     * 
     * Supports two constructor signatures:
     * 1. BusinessException(string $message) - Simple message only
     * 2. BusinessException(ApiErrorCode $errorCode, string $message, mixed $details, int $code) - Full API format
     * 
     * @param ApiErrorCode|string $errorCodeOrMessage The error code or message
     * @param string|null $message The error message (when using error code)
     * @param mixed $details Optional error details
     * @param int $code HTTP status code (default: 400)
     */
    public function __construct(
        ApiErrorCode|string $errorCodeOrMessage,
        ?string $message = null,
        mixed $details = null,
        int $code = 400
    ) {
        // Support simple string message constructor
        if (is_string($errorCodeOrMessage)) {
            parent::__construct($errorCodeOrMessage, $code);
            $this->details = $details;
            return;
        }

        // Full constructor with ApiErrorCode
        parent::__construct($message ?? '', $code);
        $this->errorCode = $errorCodeOrMessage;
        $this->details = $details;
    }

    /**
     * Get the error code.
     */
    public function getErrorCode(): ?ApiErrorCode
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

