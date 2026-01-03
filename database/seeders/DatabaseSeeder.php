<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first (required for users)
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // Seed branches (required for users and accounts)
        $this->call([
            BranchSeeder::class,
        ]);

        // Seed users
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Seed other data
        $this->call([
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            CostCenterSeeder::class,
            SettingSeeder::class,
            AccountingAccountSeeder::class,
        ]);
    }
}
