<?php

namespace App\Policies;

use App\Domain\Booking\Models\CourseBookingRequest;
use App\Models\User;

class CourseBookingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function view(User $user, CourseBookingRequest $courseBookingRequest): bool
    {
        return $user->hasRole('super_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, CourseBookingRequest $courseBookingRequest): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, CourseBookingRequest $courseBookingRequest): bool
    {
        return $user->hasRole('super_admin');
    }
}
