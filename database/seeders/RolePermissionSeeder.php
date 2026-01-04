<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Users
            'users.viewAny',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Branches
            'branches.viewAny',
            'branches.view',
            'branches.create',
            'branches.update',
            'branches.delete',

            // Roles
            'roles.viewAny',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            // Permissions
            'permissions.viewAny',
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',

            // Accounting
            'accounting.view',
            'accounting.view.global',
            'accounting.view.branch',
            'accounting.create',
            'accounting.update',
            'accounting.update.global',
            'accounting.update.branch',
            'accounting.delete',

            // Journals
            'journals.view',
            'journals.view.global',
            'journals.view.branch',
            'journals.view.personal',
            'journals.create',
            'journals.update',
            'journals.update.global',
            'journals.update.branch',
            'journals.update.personal',
            'journals.delete',
            'journals.delete.branch',
            'journals.delete.personal',
            'journals.post',
            'journals.post.global',
            'journals.post.branch',
            'journals.post.personal',

            // Categories
            'categories.viewAny',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',

            // Cost Centers
            'cost_centers.viewAny',
            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.update',
            'cost_centers.delete',

            // Payment Methods
            'payment_methods.viewAny',
            'payment_methods.view',
            'payment_methods.create',
            'payment_methods.update',
            'payment_methods.delete',

            // Media Files
            'media_files.viewAny',
            'media_files.view',
            'media_files.create',
            'media_files.update',
            'media_files.delete',

            // Settings
            'settings.viewAny',
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',

            // Students
            'students.view',
            'students.view.global',
            'students.view.branch',
            'students.view.personal',
            'students.create',
            'students.update',
            'students.update.global',
            'students.update.branch',
            'students.update.personal',
            'students.delete',
            'students.delete.branch',
            'students.delete.personal',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $trainer = Role::firstOrCreate(['name' => 'trainer', 'guard_name' => 'web']);
        $accountant = Role::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web']);
        $branchManager = Role::firstOrCreate(['name' => 'branch_manager', 'guard_name' => 'web']);

        // Assign all permissions to super_admin
        $superAdmin->givePermissionTo(Permission::all());

        // Assign permissions to admin
        $admin->givePermissionTo([
            'users.viewAny',
            'users.view',
            'users.create',
            'users.update',
            'branches.viewAny',
            'branches.view',
            'accounting.view',
            'accounting.view.global',
            'accounting.view.branch',
            'accounting.create',
            'accounting.update',
            'accounting.update.global',
            'accounting.update.branch',
            'journals.view',
            'journals.view.global',
            'journals.view.branch',
            'journals.create',
            'journals.update',
            'journals.update.global',
            'journals.update.branch',
            'journals.post',
            'journals.post.global',
            'journals.post.branch',
            'categories.viewAny',
            'categories.view',
            'categories.create',
            'categories.update',
            'categories.delete',
            'cost_centers.viewAny',
            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.update',
            'cost_centers.delete',
            'payment_methods.viewAny',
            'payment_methods.view',
            'payment_methods.create',
            'payment_methods.update',
            'payment_methods.delete',
            'media_files.viewAny',
            'media_files.view',
            'media_files.create',
            'media_files.update',
            'media_files.delete',
            'settings.viewAny',
            'settings.view',
            'settings.update',
        ]);

        // Assign permissions to trainer
        $trainer->givePermissionTo([
            'users.viewAny',
            'users.view',
            'journals.view',
            'journals.view.personal',
            'journals.create',
            'journals.update',
            'journals.update.personal',
            'journals.post',
            'journals.post.personal',
            'categories.viewAny',
            'categories.view',
            'media_files.viewAny',
            'media_files.view',
            'media_files.create',
            'media_files.update',
        ]);

        // Assign permissions to accountant
        $accountant->givePermissionTo([
            'accounting.view',
            'accounting.view.branch',
            'accounting.create',
            'accounting.update',
            'accounting.update.branch',
            'journals.view',
            'journals.view.branch',
            'journals.create',
            'journals.update',
            'journals.update.branch',
            'journals.post',
            'journals.post.branch',
            'cost_centers.viewAny',
            'cost_centers.view',
            'payment_methods.viewAny',
            'payment_methods.view',
        ]);

        // Assign permissions to branch_manager
        $branchManager->givePermissionTo([
            'users.viewAny',
            'users.view',
            'users.create',
            'users.update',
            'branches.view',
            'accounting.view',
            'accounting.view.branch',
            'accounting.create',
            'accounting.update',
            'accounting.update.branch',
            'journals.view',
            'journals.view.branch',
            'journals.create',
            'journals.update',
            'journals.update.branch',
            'journals.post',
            'journals.post.branch',
            'categories.viewAny',
            'categories.view',
            'categories.create',
            'categories.update',
            'cost_centers.viewAny',
            'cost_centers.view',
            'cost_centers.create',
            'cost_centers.update',
            'payment_methods.viewAny',
            'payment_methods.view',
            'media_files.viewAny',
            'media_files.view',
            'media_files.create',
            'media_files.update',
            'settings.viewAny',
            'settings.view',
        ]);
    }
}

