<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\Journal;
use App\Models\User;

class JournalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('journals.view');
    }

    public function view(User $user, Journal $journal): bool
    {
        if (!$user->hasPermissionTo('journals.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('journals.view.global')) {
            return true;
        }

        if ($user->hasPermissionTo('journals.view.branch')) {
            return $user->branch_id === $journal->branch_id;
        }

        if ($user->hasPermissionTo('journals.view.personal')) {
            return $journal->created_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('journals.create');
    }

    public function update(User $user, Journal $journal): bool
    {
        if (!$user->hasPermissionTo('journals.update')) {
            return false;
        }

        if ($journal->status->value === 'posted' || $journal->status->value === 'void') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('journals.update.global')) {
            return true;
        }

        if ($user->hasPermissionTo('journals.update.branch')) {
            return $user->branch_id === $journal->branch_id;
        }

        if ($user->hasPermissionTo('journals.update.personal')) {
            return $journal->created_by === $user->id;
        }

        return false;
    }

    public function delete(User $user, Journal $journal): bool
    {
        if (!$user->hasPermissionTo('journals.delete')) {
            return false;
        }

        if ($journal->status->value === 'posted' || $journal->status->value === 'void') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('journals.delete.branch')) {
            return $user->branch_id === $journal->branch_id;
        }

        if ($user->hasPermissionTo('journals.delete.personal')) {
            return $journal->created_by === $user->id;
        }

        return false;
    }

    public function post(User $user, Journal $journal): bool
    {
        if (!$user->hasPermissionTo('journals.post')) {
            return false;
        }

        if ($journal->status->value !== 'draft') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('journals.post.global')) {
            return true;
        }

        if ($user->hasPermissionTo('journals.post.branch')) {
            return $user->branch_id === $journal->branch_id;
        }

        if ($user->hasPermissionTo('journals.post.personal')) {
            return $journal->created_by === $user->id;
        }

        return false;
    }

    public function void(User $user, Journal $journal): bool
    {
        if (!$user->hasPermissionTo('journals.void')) {
            return false;
        }

        if ($journal->status->value !== 'posted') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasPermissionTo('journals.void.global')) {
            return true;
        }

        if ($user->hasPermissionTo('journals.void.branch')) {
            return $user->branch_id === $journal->branch_id;
        }

        if ($user->hasPermissionTo('journals.void.personal')) {
            return $journal->created_by === $user->id;
        }

        return false;
    }
}

