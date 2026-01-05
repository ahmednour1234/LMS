<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use App\Http\Resources\Api\V1\BranchResource;
use App\Http\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Branches
 * 
 * Read-only API for retrieving branch information. No authentication required.
 */
class BranchController extends ApiController
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * List Branches
     * 
     * Get a paginated list of branches with optional filtering by name search and active status.
     * 
     * @queryParam q string optional Search branches by name. Example: Main
     * @queryParam active integer optional Filter by active status (1 for active, 0 for inactive). Default: 1. Example: 1
     * @queryParam per_page integer optional Number of items per page. Default: 15. Example: 15
     * @queryParam page integer optional Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Branches retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Main Branch",
     *       "is_active": true,
     *       "created_at": "2026-01-15T12:00:00+00:00"
     *     },
     *     {
     *       "id": 2,
     *       "name": "North Branch",
     *       "is_active": true,
     *       "created_at": "2026-01-15T12:00:00+00:00"
     *     }
     *   ],
     *   "meta": {
     *     "pagination": {
     *       "current_page": 1,
     *       "per_page": 15,
     *       "total": 2,
     *       "last_page": 1,
     *       "from": 1,
     *       "to": 2
     *     }
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'q' => $request->input('q'),
            'active' => $request->input('active', 1),
        ];

        $perPage = $request->input('per_page', 15);
        $branches = $this->branchService->getPaginated($filters, $perPage);

        return $this->paginatedResponse(
            BranchResource::collection($branches),
            'Branches retrieved successfully.'
        );
    }
}

