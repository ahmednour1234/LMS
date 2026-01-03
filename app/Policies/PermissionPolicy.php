<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('permissions.viewAny');
    }

    public function view(User $user, Permission $permission): bool
    {
        if (!$user->hasPermissionTo('permissions.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $allowedDomains = ['users', 'training'];
        $domain = explode('.', $permission->name)[0] ?? '';

        return in_array($domain, $allowedDomains);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('permissions.create');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('permissions.update');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->isSuperAdmin() && $user->hasPermissionTo('permissions.delete');
    }
}
