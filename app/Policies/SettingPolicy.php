<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('settings.viewAny');
    }

    public function view(User $user, Setting $setting): bool
    {
        if (!$user->hasPermissionTo('settings.view')) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Settings are generally viewable by authorized users
    }

    public function create(User $user): bool
    {
        if (!$user->hasPermissionTo('settings.create')) {
            return false;
        }

        return $user->isSuperAdmin(); // Only super admin can create settings
    }

    public function update(User $user, Setting $setting): bool
    {
        if (!$user->hasPermissionTo('settings.update')) {
            return false;
        }

        // System settings can only be updated by Super Admin
        if ($setting->isSystemSetting()) {
            return $user->isSuperAdmin();
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        return true; // Authorized users can update non-system settings
    }

    public function delete(User $user, Setting $setting): bool
    {
        // System settings cannot be deleted
        if ($setting->isSystemSetting()) {
            return false;
        }

        if (!$user->hasPermissionTo('settings.delete')) {
            return false;
        }

        return $user->isSuperAdmin(); // Only super admin can delete non-system settings
    }
}

