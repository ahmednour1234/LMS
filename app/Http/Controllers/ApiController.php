<?php

namespace App\Http\Controllers;

use App\Http\Enums\ApiErrorCode;
use App\Http\Services\ApiResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base API Controller
 * 
 * All API controllers MUST extend this class to ensure consistent response formatting.
 * 
 * This controller provides helper methods that wrap ApiResponseService for convenience.
 */
abstract class ApiController extends Controller
{
    /**
     * Return a successful response.
     * 
     * @param mixed $data The response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code (default: 200)
     * @param array|null $meta Optional metadata
     * @return JsonResponse
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operation completed successfully',
        int $statusCode = 200,
        ?array $meta = null
    ): JsonResponse {
        return ApiResponseService::success($data, $message, $statusCode, $meta);
    }

    /**
     * Return a successful response for resource creation.
     * 
     * @param mixed $data The created resource data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function createdResponse(
        mixed $data = null,
        string $message = 'Resource created successfully'
    ): JsonResponse {
        return ApiResponseService::created($data, $message);
    }

    /**
     * Return a paginated response.
     * 
     * @param LengthAwarePaginator|ResourceCollection $paginator The paginated data
     * @param string $message Success message
     * @return JsonResponse
     */
    protected function paginatedResponse(
        LengthAwarePaginator|ResourceCollection $paginator,
        string $message = 'Data retrieved successfully'
    ): JsonResponse {
        return ApiResponseService::pagination($paginator, $message);
    }

    /**
     * Return an error response.
     * 
     * @param ApiErrorCode $errorCode The error code
     * @param string|null $message Custom error message
     * @param mixed $details Optional error details
     * @param int $statusCode HTTP status code (default: 400)
     * @return JsonResponse
     */
    protected function errorResponse(
        ApiErrorCode $errorCode,
        ?string $message = null,
        mixed $details = null,
        int $statusCode = 400
    ): JsonResponse {
        return ApiResponseService::error($errorCode, $message, $details, $statusCode);
    }
}

