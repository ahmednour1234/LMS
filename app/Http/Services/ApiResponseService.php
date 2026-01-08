<?php

namespace App\Http\Services;

use App\Http\Enums\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Unified API Response Service
 * 
 * This service provides a centralized way to format all API responses
 * to ensure consistency across the entire backend.
 * 
 * ====================================
 * SUCCESS RESPONSE FORMAT
 * ====================================
 * {
 *   "success": true,
 *   "message": "Human readable message",
 *   "data": { ... },
 *   "meta": { ... } // optional, for pagination, totals, etc.
 * }
 * 
 * ====================================
 * ERROR RESPONSE FORMAT
 * ====================================
 * {
 *   "success": false,
 *   "message": "Error summary message",
 *   "error": {
 *     "code": "ERROR_CODE_CONSTANT",
 *     "details": { ... } // optional
 *   }
 * }
 * 
 * ====================================
 * HTTP STATUS CODE MAPPING
 * ====================================
 * - 200: Successful read/update
 * - 201: Resource created
 * - 400: General client errors / business logic errors
 * - 401: Unauthorized (authentication required)
 * - 403: Forbidden (insufficient permissions)
 * - 404: Not found
 * - 409: Conflict (e.g., duplicate entry)
 * - 422: Validation error
 * - 500: Internal server error
 * 
 * ====================================
 * USAGE GUIDELINES
 * ====================================
 * - Always use this service for API responses
 * - Never return raw JSON, dump(), or custom formats
 * - Use appropriate HTTP status codes
 * - Provide meaningful messages for all responses
 * - Include error codes from ApiErrorCode enum only
 */
class ApiResponseService
{
    /**
     * Return a successful response.
     * 
     * @param mixed $data The response data (object, array, or null)
     * @param string $message Human-readable success message
     * @param int $statusCode HTTP status code (default: 200)
     * @param array|null $meta Optional metadata (pagination, totals, etc.)
     * @return JsonResponse
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operation completed successfully',
        int $statusCode = 200,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a successful response for resource creation.
     * 
     * @param mixed $data The created resource data
     * @param string $message Human-readable success message
     * @return JsonResponse
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return self::success($data, $message, 201);
    }

    /**
     * Return a paginated response.
     * 
     * @param LengthAwarePaginator|ResourceCollection $paginator The paginated data
     * @param string $message Human-readable success message
     * @return JsonResponse
     */
    public static function pagination(
        LengthAwarePaginator|ResourceCollection $paginator,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        // Handle Laravel Resource Collections
        if ($paginator instanceof ResourceCollection) {
            // Get the underlying paginator from the resource collection
            // When using ResourceCollection::collection($paginator), the resource property is the paginator
            $underlyingPaginator = $paginator->resource;
            
            // Extract data from the resource collection
            $data = $paginator->resolve();
            
            // Format pagination meta consistently - ensure we have a paginator
            if ($underlyingPaginator instanceof LengthAwarePaginator) {
                $meta = [
                    'pagination' => [
                        'current_page' => $underlyingPaginator->currentPage(),
                        'per_page' => $underlyingPaginator->perPage(),
                        'total' => $underlyingPaginator->total(),
                        'last_page' => $underlyingPaginator->lastPage(),
                        'from' => $underlyingPaginator->firstItem(),
                        'to' => $underlyingPaginator->lastItem(),
                    ],
                ];
                
                return self::success($data, $message, 200, $meta);
            }
            
            // Fallback if resource is not a paginator (shouldn't happen in normal usage)
            return self::success($data, $message, 200);
        }

        // Handle standard paginators
        $meta = [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];

        return self::success($paginator->items(), $message, 200, $meta);
    }

    /**
     * Return an error response.
     * 
     * @param ApiErrorCode $errorCode The error code enum
     * @param string|null $message Custom error message (uses default if null)
     * @param mixed $details Optional error details (validation errors, etc.)
     * @param int $statusCode HTTP status code (default: 400)
     * @return JsonResponse
     */
    public static function error(
        ApiErrorCode $errorCode,
        ?string $message = null,
        mixed $details = null,
        int $statusCode = 400
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message ?? $errorCode->getDefaultMessage(),
            'error' => [
                'code' => $errorCode->value,
            ],
        ];

        if ($details !== null) {
            $response['error']['details'] = $details;
        }

        return response()->json($response, $statusCode);
    }
}

