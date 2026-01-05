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
                'is_system' => true,
            ],
            [
                'key' => 'app_email',
                'value' => ['email' => 'info@lms.com'],
                'group' => 'general',
                'is_system' => true,
            ],
            [
                'key' => 'app_phone',
                'value' => ['phone' => '+1234567890'],
                'group' => 'general',
                'is_system' => true,
            ],
            [
                'key' => 'app_whatsapp',
                'value' => ['phone' => '+1234567890'],
                'group' => 'general',
                'is_system' => true,
            ],
            [
                'key' => 'currency',
                'value' => ['code' => 'USD', 'symbol' => '$'],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'fiscal_year_start',
                'value' => ['month' => 1, 'day' => 1],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'fiscal_year_end',
                'value' => ['month' => 12, 'day' => 31],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'default_branch',
                'value' => ['branch_id' => 1],
                'group' => 'general',
                'is_system' => true,
            ],
            [
                'key' => 'tax_rate',
                'value' => ['rate' => 0.15],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'invoice_prefix',
                'value' => ['prefix' => 'INV-'],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'receipt_prefix',
                'value' => ['prefix' => 'RCP-'],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'tax_registration_number',
                'value' => ['number' => ''],
                'group' => 'financial',
                'is_system' => true,
            ],
            [
                'key' => 'commercial_registration_number',
                'value' => ['number' => ''],
                'group' => 'financial',
                'is_system' => true,
            ],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'group' => $setting['group'],
                    'is_system' => $setting['is_system'],
                ]
            );
        }
    }
}
