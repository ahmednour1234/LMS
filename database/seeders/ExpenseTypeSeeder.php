<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\ExpenseType;
use Illuminate\Database\Seeder;

class ExpenseTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Electricity', 'sort_order' => 1],
            ['name' => 'Rent', 'sort_order' => 2],
            ['name' => 'Internet', 'sort_order' => 3],
            ['name' => 'Other', 'sort_order' => 99],
        ];

        foreach ($types as $type) {
            ExpenseType::updateOrCreate(
                ['name' => $type['name']],
                ['sort_order' => $type['sort_order'], 'is_active' => true]
            );
        }
    }
}
