<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1) Roles & Permissions
        $this->call([
            RolePermissionSeeder::class,
        ]);

        // 2) Branches
        $this->call([
            BranchSeeder::class,
        ]);

        // 3) Create/Update test user (no duplicates)
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                // لو مش عايز تغير باسورد كل مرة، سيبه ثابت
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // 4) Other seeders
        $this->call([
            CategorySeeder::class,
            PaymentMethodSeeder::class,
            CostCenterSeeder::class,
            SettingSeeder::class,
            AccountingAccountSeeder::class,
        ]);
    }
}
