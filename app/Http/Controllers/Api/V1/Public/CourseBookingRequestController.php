<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Domain\Booking\Models\CourseBookingRequest;
use App\Http\Controllers\ApiController;
use App\Http\Requests\Api\V1\Public\StoreCourseBookingRequest;
use App\Http\Resources\Api\V1\Public\CourseBookingRequestResource;

class CourseBookingRequestController extends ApiController
{
    public function store(StoreCourseBookingRequest $request)
    {
        $bookingRequest = CourseBookingRequest::create($request->validated());

        return $this->createdResponse(
            new CourseBookingRequestResource($bookingRequest),
            __('course_booking_requests.messages.success_submit')
        );
    }
}
