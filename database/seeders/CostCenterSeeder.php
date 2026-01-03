<?php

namespace Database\Seeders;

use App\Domain\Accounting\Models\CostCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CostCenterSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create parent cost centers
        $parentCostCenters = [
            [
                'name' => 'Administration',
                'code' => 'ADMIN',
                'is_active' => true,
            ],
            [
                'name' => 'Academic',
                'code' => 'ACAD',
                'is_active' => true,
            ],
            [
                'name' => 'Support Services',
                'code' => 'SUPPORT',
                'is_active' => true,
            ],
        ];

        $createdParents = [];
        foreach ($parentCostCenters as $costCenter) {
            $createdParents[] = CostCenter::create($costCenter);
        }

        // Create child cost centers
        $childCostCenters = [
            [
                'name' => 'Human Resources',
                'code' => 'ADMIN-HR',
                'parent_id' => $createdParents[0]->id, // Administration
                'is_active' => true,
            ],
            [
                'name' => 'Finance',
                'code' => 'ADMIN-FIN',
                'parent_id' => $createdParents[0]->id, // Administration
                'is_active' => true,
            ],
            [
                'name' => 'IT Department',
                'code' => 'ADMIN-IT',
                'parent_id' => $createdParents[0]->id, // Administration
                'is_active' => true,
            ],
            [
                'name' => 'Teaching',
                'code' => 'ACAD-TEACH',
                'parent_id' => $createdParents[1]->id, // Academic
                'is_active' => true,
            ],
            [
                'name' => 'Research',
                'code' => 'ACAD-RES',
                'parent_id' => $createdParents[1]->id, // Academic
                'is_active' => true,
            ],
            [
                'name' => 'Library',
                'code' => 'SUPPORT-LIB',
                'parent_id' => $createdParents[2]->id, // Support Services
                'is_active' => true,
            ],
            [
                'name' => 'Maintenance',
                'code' => 'SUPPORT-MAINT',
                'parent_id' => $createdParents[2]->id, // Support Services
                'is_active' => true,
            ],
        ];

        foreach ($childCostCenters as $costCenter) {
            CostCenter::create($costCenter);
        }
    }
}

