<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessException;
use App\Http\Controllers\ApiController;
use App\Http\Enums\ApiErrorCode;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Example API Controller
 * 
 * This controller demonstrates the proper usage of the unified API response system.
 * 
 * Examples include:
 * - Success responses with data
 * - Created responses (201)
 * - Paginated responses
 * - Error handling (validation, not found, business logic)
 * 
 * All responses follow the unified format defined in ApiResponseService.
 */
class ExampleController extends ApiController
{
    /**
     * Example: Success response with data
     * 
     * GET /api/v1/example
     */
    public function index(Request $request): JsonResponse
    {
        $data = [
            'users' => [
                ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
                ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com'],
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        return $this->successResponse(
            $data,
            'Users retrieved successfully'
        );
    }

    /**
     * Example: Created response (201)
     * 
     * POST /api/v1/example
     */
    public function store(Request $request): JsonResponse
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Simulate resource creation
        $createdResource = [
            'id' => 123,
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'created_at' => now()->toIso8601String(),
        ];

        return $this->createdResponse(
            $createdResource,
            'User created successfully'
        );
    }

    /**
     * Example: Paginated response
     * 
     * GET /api/v1/example/paginated
     */
    public function paginated(Request $request): JsonResponse
    {
        // Simulate paginated data
        $perPage = $request->input('per_page', 15);
        $users = User::query()->paginate($perPage);

        return $this->paginatedResponse(
            $users,
            'Users retrieved successfully'
        );
    }

    /**
     * Example: Error handling demonstrations
     * 
     * GET /api/v1/example/errors?type={validation|not_found|business|internal}
     */
    public function errorExamples(Request $request): JsonResponse
    {
        $type = $request->input('type', 'validation');

        return match ($type) {
            'validation' => $this->exampleValidationError(),
            'not_found' => $this->exampleNotFoundError(),
            'business' => $this->exampleBusinessError(),
            'unauthorized' => $this->exampleUnauthorizedError(),
            'forbidden' => $this->exampleForbiddenError(),
            default => $this->errorResponse(
                ApiErrorCode::VALIDATION_ERROR,
                'Invalid error type. Use: validation, not_found, business, unauthorized, or forbidden'
            ),
        };
    }

    /**
     * Example: Validation error (422)
     */
    private function exampleValidationError(): JsonResponse
    {
        $validator = Validator::make([], [
            'email' => 'required|email',
            'name' => 'required|min:3',
        ]);

        throw new ValidationException($validator);
    }

    /**
     * Example: Not found error (404)
     */
    private function exampleNotFoundError(): JsonResponse
    {
        // This will be caught by the global exception handler
        User::findOrFail(99999);
        
        return $this->successResponse([]); // Never reached
    }

    /**
     * Example: Business logic error (400)
     */
    private function exampleBusinessError(): JsonResponse
    {
        throw new BusinessException(
            ApiErrorCode::INSUFFICIENT_BALANCE,
            'Insufficient balance to complete this transaction. Current balance: $50.00, Required: $100.00',
            [
                'current_balance' => 50.00,
                'required_amount' => 100.00,
                'shortfall' => 50.00,
            ],
            400
        );
    }

    /**
     * Example: Unauthorized error (401)
     */
    private function exampleUnauthorizedError(): JsonResponse
    {
        // This would typically be handled by auth middleware
        // But we can demonstrate it manually
        if (!auth()->check()) {
            return $this->errorResponse(
                ApiErrorCode::UNAUTHORIZED,
                'Authentication required to access this resource.',
                null,
                401
            );
        }

        return $this->successResponse(['message' => 'You are authenticated']);
    }

    /**
     * Example: Forbidden error (403)
     */
    private function exampleForbiddenError(): JsonResponse
    {
        // This would typically be handled by authorization policies
        // But we can demonstrate it manually
        return $this->errorResponse(
            ApiErrorCode::FORBIDDEN,
            'You do not have permission to perform this action.',
            [
                'required_permission' => 'manage_users',
                'user_permissions' => ['view_users'],
            ],
            403
        );
    }
}

