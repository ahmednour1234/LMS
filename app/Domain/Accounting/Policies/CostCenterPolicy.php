<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\CostCenter;
use App\Models\User;

class CostCenterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('cost_centers.viewAny');
    }

    public function view(User $user, CostCenter $costCenter): bool
    {
        if (!$user->hasPermissionTo('cost_centers.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Cost centers are generally accessible to all authorized users
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('cost_centers.create');
    }

    public function update(User $user, CostCenter $costCenter): bool
    {
        if (!$user->hasPermissionTo('cost_centers.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Cost centers can be updated by authorized users
    }

    public function delete(User $user, CostCenter $costCenter): bool
    {
        if (!$user->hasPermissionTo('cost_centers.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            // Check if cost center has children
            if ($costCenter->children()->count() > 0) {
                return false;
            }

            // Check if cost center is used in journal lines
            if ($costCenter->journalLines()->count() > 0) {
                return false;
            }

            return true;
        }

        return false; // Only super admin can delete cost centers
    }
}

