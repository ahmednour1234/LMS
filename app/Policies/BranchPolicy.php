<?php

namespace App\Policies;

use App\Domain\Branch\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('branches.viewAny');
    }

    public function view(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branches.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->branch_id === $branch->id;
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('branches.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branches.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->branch_id === $branch->id;
    }

    public function delete(User $user, Branch $branch): bool
    {
        if (!$user->hasPermissionTo('branches.delete')) {
            return false;
        }

        return $user->isSuperAdmin();
    }
}
