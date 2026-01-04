<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $roles = [
            'super_admin',
            'admin',
            'trainer',
            'teacher',
            'accountant',
            'branch_manager',
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web'
            ]);
        }

        $this->command->info('Roles seeded successfully!');
    }
}
