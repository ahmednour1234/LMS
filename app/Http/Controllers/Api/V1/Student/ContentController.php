<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Domain\Training\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Student Content
 *
 * Student API for accessing course content (requires enrollment and payment).
 */
class ContentController extends ApiController
{
    /**
     * Get Course Content
     *
     * Get course content structure: sections, lessons, and lesson items.
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course content retrieved successfully.",
     *   "data": [...]
     * }
     * @response 403 {
     *   "success": false,
     *   "message": "Payment required to access this content.",
     *   "error": {
     *     "code": "FORBIDDEN"
     *   }
     * }
     */
    public function index(Request $request, int $course): JsonResponse
    {
        $courseModel = $request->attributes->get('course');

        $sections = $courseModel->sections()
            ->where('is_active', true)
            ->with(['lessons' => function ($q) {
                $q->where('is_active', true)
                  ->orderBy('sort_order')
                  ->with(['items' => function ($q) {
                      $q->where('is_active', true)
                        ->orderBy('order');
                  }]);
            }])
            ->orderBy('order')
            ->get();

        return $this->successResponse(
            \App\Http\Resources\V1\Student\ContentResource::collection($sections),
            'Course content retrieved successfully.'
        );
    }
}
