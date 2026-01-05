<?php

namespace App\Http\Enums;

/**
 * Standard API error codes used across the application.
 * 
 * All error codes must be defined here and reused consistently.
 * Never use arbitrary strings as error codes.
 */
enum ApiErrorCode: string
{
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case CONFLICT = 'CONFLICT';
    case BUSINESS_RULE_VIOLATION = 'BUSINESS_RULE_VIOLATION';
    case INSUFFICIENT_BALANCE = 'INSUFFICIENT_BALANCE';
    case RESOURCE_LOCKED = 'RESOURCE_LOCKED';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case EMAIL_NOT_VERIFIED = 'EMAIL_NOT_VERIFIED';

    /**
     * Get a human-readable default message for this error code.
     */
    public function getDefaultMessage(): string
    {
        return match ($this) {
            self::VALIDATION_ERROR => 'The provided data is invalid.',
            self::UNAUTHORIZED => 'Authentication required.',
            self::FORBIDDEN => 'You do not have permission to perform this action.',
            self::NOT_FOUND => 'The requested resource was not found.',
            self::CONFLICT => 'A conflict occurred while processing your request.',
            self::BUSINESS_RULE_VIOLATION => 'A business rule violation occurred.',
            self::INSUFFICIENT_BALANCE => 'Insufficient balance to complete this operation.',
            self::RESOURCE_LOCKED => 'The resource is currently locked and cannot be modified.',
            self::INTERNAL_ERROR => 'An internal server error occurred.',
            self::EMAIL_NOT_VERIFIED => 'Email address has not been verified. Please verify your email to continue.',
        };
    }
}

