<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('users.viewAny');
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->branch_id === $model->branch_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('users.create');
    }

    public function update(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return $user->branch_id === $model->branch_id;
    }

    public function delete(User $user, User $model): bool
    {
        if (!$user->hasPermissionTo('users.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return $user->id !== $model->id;
        }

        return $user->branch_id === $model->branch_id && $user->id !== $model->id;
    }
}
