<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('accounting.view');
    }

    public function view(User $user, Account $account): bool
    {
        if (!$user->hasPermissionTo('accounting.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('accounting.view.global')) {
            return true;
        }

        if ($user->hasPermissionTo('accounting.view.branch')) {
            return $user->branch_id === $account->branch_id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('accounting.create');
    }

    public function update(User $user, Account $account): bool
    {
        if (!$user->hasPermissionTo('accounting.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('accounting.update.global')) {
            return true;
        }

        if ($user->hasPermissionTo('accounting.update.branch')) {
            return $user->branch_id === $account->branch_id;
        }

        return false;
    }

    public function delete(User $user, Account $account): bool
    {
        if (!$user->hasPermissionTo('accounting.delete')) {
            return false;
        }

        return $user->isSuperAdmin();
    }
}

