<?php

namespace Database\Seeders;

use App\Domain\Branch\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'name' => 'Main Branch',
                'is_active' => true,
            ],
            [
                'name' => 'North Branch',
                'is_active' => true,
            ],
            [
                'name' => 'South Branch',
                'is_active' => true,
            ],
            [
                'name' => 'East Branch',
                'is_active' => false,
            ],
        ];

        foreach ($branches as $branch) {
            Branch::create($branch);
        }
    }
}

