<?php

namespace App\Domain\Media\Policies;

use App\Domain\Media\Models\MediaFile;
use App\Models\User;

class MediaFilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('media_files.viewAny');
    }

    public function view(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->hasPermissionTo('media_files.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can view their own files
        if ($mediaFile->user_id === $user->id) {
            return true;
        }

        // Users can view files from their branch
        if ($user->branch_id && $mediaFile->branch_id === $user->branch_id) {
            return true;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('media_files.create');
    }

    public function update(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->hasPermissionTo('media_files.update')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can update their own files
        if ($mediaFile->user_id === $user->id) {
            return true;
        }

        // Branch managers can update files from their branch
        if ($user->branch_id && $mediaFile->branch_id === $user->branch_id) {
            return true;
        }

        return false;
    }

    public function delete(User $user, MediaFile $mediaFile): bool
    {
        if (!$user->hasPermissionTo('media_files.delete')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        // Users can delete their own files
        if ($mediaFile->user_id === $user->id) {
            return true;
        }

        // Branch managers can delete files from their branch
        if ($user->branch_id && $mediaFile->branch_id === $user->branch_id) {
            return true;
        }

        return false;
    }
}

