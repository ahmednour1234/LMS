<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('roles.viewAny');
    }

    public function view(User $user, Role $role): bool
    {
        if (!$user->hasPermissionTo('roles.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return in_array($role->name, ['admin', 'trainer']);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('roles.create');
    }

    public function update(User $user, Role $role): bool
    {
        if (!$user->hasPermissionTo('roles.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return $role->name !== 'super_admin';
        }

        return in_array($role->name, ['admin', 'trainer']);
    }

    public function delete(User $user, Role $role): bool
    {
        if (!$user->hasPermissionTo('roles.delete')) {
            return false;
        }

        return $user->isSuperAdmin() && !in_array($role->name, ['super_admin', 'admin', 'trainer']);
    }
}
