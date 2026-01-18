<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\ApiController;
use App\Domain\Training\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Student Content Controller
 *
 * This controller handles student access to course content (sections, lessons, and lesson items).
 *
 * SECURITY & ACCESS CONTROL:
 * - Protected by middleware: EnsureEnrolledInCourse and EnsureEnrollmentPaid
 * - Only enrolled students can access content
 * - Payment verification is required (unless course allows unpaid access)
 * - Course model is pre-loaded by middleware and available in request attributes
 *
 * ENDPOINT:
 * GET /api/v1/student/courses/{course}/content
 *
 * @group Student Content
 *
 * Student API for accessing course content (requires enrollment and payment).
 */
class ContentController extends ApiController
{
    /**
     * Get Course Content Structure
     *
     * Retrieves the complete course content hierarchy:
     * - Course Sections (ordered by 'order' field)
     *   - Lessons (ordered by 'sort_order' field)
     *     - Lesson Items (ordered by 'order' field)
     *
     * Only active sections, lessons, and items are returned.
     *
     * MIDDLEWARE PROTECTION:
     * - EnsureEnrolledInCourse: Verifies student is enrolled in the course
     * - EnsureEnrollmentPaid: Verifies payment is completed (or course allows unpaid access)
     *
     * The course model is automatically loaded by middleware and available via:
     * $request->attributes->get('course')
     *
     * @urlParam course integer required The ID of the course. Example: 1
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Course content retrieved successfully.",
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": {...},
     *       "order": 1,
     *       "lessons": [
     *         {
     *           "id": 1,
     *           "title": {...},
     *           "description": {...},
     *           "lesson_type": "recorded",
     *           "items": [...]
     *         }
     *       ]
     *     }
     *   ]
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
        // Get course model from request attributes (set by EnsureEnrolledInCourse middleware)
        $courseModel = $request->attributes->get('course');

        // Query course sections with nested relationships
        // Only fetch active sections, lessons, and items
        $sections = $courseModel->sections()
            ->where('is_active', true)
            ->with([
                // Eager load lessons with nested items
                'lessons' => function ($q) {
                    // Only active lessons, ordered by sort_order
                    $q->where('is_active', true)
                      ->orderBy('sort_order')
                      // Eager load lesson items
                      ->with(['items' => function ($q) {
                          // Only active items, ordered by order
                          $q->where('is_active', true)
                            ->orderBy('order');
                      }]);
                }
            ])
            ->orderBy('order')
            ->get();

        // Return formatted response using ContentResource
        // ContentResource transforms sections and includes nested lessons/items
        return $this->successResponse(
            \App\Http\Resources\V1\Student\ContentResource::collection($sections),
            'Course content retrieved successfully.'
        );
    }
}
