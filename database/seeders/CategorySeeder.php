<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $parentCategories = [
            ['name' => 'Revenue',     'is_active' => true, 'sort_order' => 1],
            ['name' => 'Expenses',    'is_active' => true, 'sort_order' => 2],
            ['name' => 'Assets',      'is_active' => true, 'sort_order' => 3],
            ['name' => 'Liabilities', 'is_active' => true, 'sort_order' => 4],
        ];

        $createdParents = [];

        foreach ($parentCategories as $cat) {
            $createdParents[$cat['name']] = Category::updateOrCreate(
                ['slug' => Str::slug($cat['name'])],
                array_merge($cat, ['slug' => Str::slug($cat['name'])])
            );
        }

        $childCategories = [
            ['name' => 'Tuition Fees',       'parent' => 'Revenue',  'sort_order' => 1],
            ['name' => 'Registration Fees',  'parent' => 'Revenue',  'sort_order' => 2],
            ['name' => 'Salaries',           'parent' => 'Expenses', 'sort_order' => 1],
            ['name' => 'Rent',               'parent' => 'Expenses', 'sort_order' => 2],
            ['name' => 'Utilities',          'parent' => 'Expenses', 'sort_order' => 3],
            ['name' => 'Cash',               'parent' => 'Assets',   'sort_order' => 1],
            ['name' => 'Bank Accounts',      'parent' => 'Assets',   'sort_order' => 2],
        ];

        foreach ($childCategories as $cat) {
            $slug = Str::slug($cat['name']);

            Category::updateOrCreate(
                ['slug' => $slug],
                [
                    'name'       => $cat['name'],
                    'slug'       => $slug,
                    'parent_id'  => $createdParents[$cat['parent']]->id,
                    'is_active'  => true,
                    'sort_order' => $cat['sort_order'],
                ]
            );
        }
    }
}
