<?php

namespace App\Policies;

use App\Domain\Enrollment\Models\Student;
use App\Models\User;

class StudentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('students.view');
    }

    public function view(User $user, Student $student): bool
    {
        if (!$user->hasPermissionTo('students.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('students.view.global')) {
            return true;
        }

        if ($user->hasPermissionTo('students.view.branch')) {
            return $user->branch_id === $student->branch_id;
        }

        if ($user->hasPermissionTo('students.view.personal')) {
            return $student->user_id === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('students.create');
    }

    public function update(User $user, Student $student): bool
    {
        if (!$user->hasPermissionTo('students.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('students.update.global')) {
            return true;
        }

        if ($user->hasPermissionTo('students.update.branch')) {
            return $user->branch_id === $student->branch_id;
        }

        if ($user->hasPermissionTo('students.update.personal')) {
            return $student->user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Student $student): bool
    {
        if (!$user->hasPermissionTo('students.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('students.delete.branch')) {
            return $user->branch_id === $student->branch_id;
        }

        if ($user->hasPermissionTo('students.delete.personal')) {
            return $student->user_id === $user->id;
        }

        return false;
    }
}

