<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('categories.viewAny');
    }

    public function view(User $user, Category $category): bool
    {
        if (!$user->hasPermissionTo('categories.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Categories are generally accessible to all authorized users
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('categories.create');
    }

    public function update(User $user, Category $category): bool
    {
        if (!$user->hasPermissionTo('categories.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Categories can be updated by authorized users
    }

    public function delete(User $user, Category $category): bool
    {
        if (!$user->hasPermissionTo('categories.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return false;
        }

        return true;
    }
}

