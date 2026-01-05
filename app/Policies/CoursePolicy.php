<?php

namespace App\Policies;

use App\Models\User;
use App\Domain\Training\Models\Course;

class CoursePolicy
{
    public function viewStudio(User $user, Course $course): bool
    {
        // Only admin or super-admin can access studio
        return $user->isSuperAdmin() || $user->hasRole('admin');
    }
}

