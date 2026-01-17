<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use App\Http\Controllers\ApiController;
use App\Domain\Training\Models\Course;
use App\Http\Resources\Api\V1\Public\CourseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Teacher - My Courses
 */
class TeacherCourseMeController extends ApiController
{
    /**
     * Get my courses
     * @queryParam active boolean Filter by active. Example: 1
     * @queryParam q string Search by name. Example: flutter
     * @queryParam per_page int Example: 15
     */
    public function index(): JsonResponse
    {
        $teacherId = Auth::guard('teacher-api')->id();

        $q = Course::query()->where('owner_teacher_id', $teacherId);

        if (request()->filled('active')) {
            $q->where('is_active', (bool) request('active'));
        }

        if (request()->filled('q')) {
            $term = request('q');
            $q->where(function ($b) use ($term) {
                $b->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$term}%"])
                  ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$term}%"]);
            });
        }

        $perPage = (int) request('per_page', 15);
        $data = $q->orderByDesc('id')->paginate($perPage);

        return $this->successResponse(CourseResource::collection($data)->response()->getData(true), 'OK');
    }
}
