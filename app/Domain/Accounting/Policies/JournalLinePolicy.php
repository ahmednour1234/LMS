<?php

namespace App\Domain\Accounting\Policies;

use App\Domain\Accounting\Models\JournalLine;
use App\Models\User;

class JournalLinePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('journals.view');
    }

    public function view(User $user, JournalLine $journalLine): bool
    {
        return $user->can('view', $journalLine->journal);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('journals.create');
    }

    public function update(User $user, JournalLine $journalLine): bool
    {
        return $user->can('update', $journalLine->journal);
    }

    public function delete(User $user, JournalLine $journalLine): bool
    {
        return $user->can('delete', $journalLine->journal);
    }
}

