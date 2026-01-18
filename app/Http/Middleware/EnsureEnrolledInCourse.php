<?php

namespace App\Http\Middleware;

use App\Domain\Enrollment\Models\Enrollment;
use App\Domain\Training\Models\Course;
use App\Http\Enums\ApiErrorCode;
use App\Http\Services\ApiResponseService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEnrolledInCourse
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = auth('students')->user();
        
        if (!$student) {
            return ApiResponseService::error(
                ApiErrorCode::UNAUTHORIZED,
                'Authentication required.',
                null,
                401
            );
        }

        $courseId = $request->route('course');
        
        if (!$courseId) {
            $courseId = $request->route('courseId');
        }

        if (!$courseId) {
            $exam = $request->route('exam');
            if ($exam) {
                $courseId = $exam->course_id ?? null;
            }
        }

        if (!$courseId) {
            $task = $request->route('task');
            if ($task) {
                $courseId = $task->course_id ?? null;
            }
        }

        if (!$courseId) {
            $lesson = $request->route('lesson');
            if ($lesson) {
                $courseId = $lesson->section->course_id ?? null;
            }
        }

        if (!$courseId) {
            return ApiResponseService::error(
                ApiErrorCode::NOT_FOUND,
                'Course not found in route.',
                null,
                404
            );
        }

        $course = Course::find($courseId);
        if (!$course) {
            return ApiResponseService::error(
                ApiErrorCode::NOT_FOUND,
                'Course not found.',
                null,
                404
            );
        }

        $enrollment = Enrollment::where('student_id', $student->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'pending', 'pending_payment'])
            ->first();

        if (!$enrollment) {
            return ApiResponseService::error(
                ApiErrorCode::FORBIDDEN,
                'You are not enrolled in this course.',
                null,
                403
            );
        }

        $request->attributes->set('enrollment', $enrollment);
        $request->attributes->set('course', $course);

        return $next($request);
    }
}
