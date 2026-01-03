<?php

namespace App\Support\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasVisibilityScope
{
    /**
     * Scope to filter records based on user visibility permissions.
     *
     * @param Builder $query
     * @param User $user
     * @param string $permissionPrefix Permission prefix (e.g., 'journals', 'payments')
     * @return Builder
     */
    public function scopeVisibleTo(Builder $query, User $user, string $permissionPrefix): Builder
    {
        if ($user->isSuperAdmin() || $user->hasPermissionTo("{$permissionPrefix}.view.global")) {
            return $this->scopeForGlobalVisibility($query);
        }

        if ($user->hasPermissionTo("{$permissionPrefix}.view.branch")) {
            return $this->scopeForBranchVisibility($query, $user->branch_id);
        }

        if ($user->hasPermissionTo("{$permissionPrefix}.view.personal")) {
            return $this->scopeForPersonalVisibility($query, $user->id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope for global visibility - returns all records without filtering.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeForGlobalVisibility(Builder $query): Builder
    {
        return $query;
    }

    /**
     * Scope for branch visibility - filters records by branch_id.
     *
     * @param Builder $query
     * @param int|null $branchId
     * @return Builder
     */
    public function scopeForBranchVisibility(Builder $query, ?int $branchId): Builder
    {
        if ($branchId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('branch_id', $branchId);
    }

    /**
     * Scope for personal visibility - filters records by created_by.
     *
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeForPersonalVisibility(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }
}

