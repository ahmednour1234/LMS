<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent categories
        $parentCategories = [
            [
                'name' => 'Revenue',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Expenses',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Assets',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Liabilities',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        $createdParents = [];
        foreach ($parentCategories as $category) {
            $createdParents[] = Category::create($category);
        }

        // Create child categories
        $childCategories = [
            [
                'name' => 'Tuition Fees',
                'parent_id' => $createdParents[0]->id, // Revenue
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Registration Fees',
                'parent_id' => $createdParents[0]->id, // Revenue
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Salaries',
                'parent_id' => $createdParents[1]->id, // Expenses
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Rent',
                'parent_id' => $createdParents[1]->id, // Expenses
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Utilities',
                'parent_id' => $createdParents[1]->id, // Expenses
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Cash',
                'parent_id' => $createdParents[2]->id, // Assets
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Bank Accounts',
                'parent_id' => $createdParents[2]->id, // Assets
                'is_active' => true,
                'sort_order' => 2,
            ],
        ];

        foreach ($childCategories as $category) {
            Category::create($category);
        }
    }
}

