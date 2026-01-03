<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get or create super_admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

        // Ensure super_admin has all permissions
        $superAdminRole->givePermissionTo(Permission::all());

        // Get first branch if available (optional)
        $branch = Branch::first();

        // Create or update super admin user
        $user = User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'password')),
                'email_verified_at' => now(),
                'primary_role_id' => $superAdminRole->id,
                'branch_id' => $branch?->id,
            ]
        );

        // Assign super_admin role to user
        $user->assignRole($superAdminRole);

        // Give all permissions directly to user (for maximum access)
        $user->givePermissionTo(Permission::all());

        $this->command->info("Super Admin user created/updated: {$user->email}");
    }
}
