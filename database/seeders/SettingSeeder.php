<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'app_name',
                'value' => ['en' => 'LMS', 'ar' => 'نظام إدارة التعلم'],
                'group' => 'general',
            ],
            [
                'key' => 'app_email',
                'value' => ['email' => 'info@lms.com'],
                'group' => 'general',
            ],
            [
                'key' => 'app_phone',
                'value' => ['phone' => '+1234567890'],
                'group' => 'general',
            ],
            [
                'key' => 'currency',
                'value' => ['code' => 'USD', 'symbol' => '$'],
                'group' => 'financial',
            ],
            [
                'key' => 'fiscal_year_start',
                'value' => ['month' => 1, 'day' => 1],
                'group' => 'financial',
            ],
            [
                'key' => 'fiscal_year_end',
                'value' => ['month' => 12, 'day' => 31],
                'group' => 'financial',
            ],
            [
                'key' => 'default_branch',
                'value' => ['branch_id' => 1],
                'group' => 'general',
            ],
            [
                'key' => 'tax_rate',
                'value' => ['rate' => 0.15],
                'group' => 'financial',
            ],
            [
                'key' => 'invoice_prefix',
                'value' => ['prefix' => 'INV-'],
                'group' => 'financial',
            ],
            [
                'key' => 'receipt_prefix',
                'value' => ['prefix' => 'RCP-'],
                'group' => 'financial',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}

