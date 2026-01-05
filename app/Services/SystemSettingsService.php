<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SystemSettingsService
{
    /**
     * System settings keys that must always exist
     */
    protected const SYSTEM_SETTINGS = [
        'general' => [
            'app_name' => ['en' => 'LMS', 'ar' => 'نظام إدارة التعلم'],
            'app_email' => ['email' => 'info@lms.com'],
            'app_phone' => ['phone' => '+1234567890'],
            'app_whatsapp' => ['phone' => '+1234567890'],
            'default_branch' => ['branch_id' => 1],
        ],
        'financial' => [
            'currency' => ['code' => 'USD', 'symbol' => '$'],
            'fiscal_year_start' => ['month' => 1, 'day' => 1],
            'fiscal_year_end' => ['month' => 12, 'day' => 31],
            'tax_rate' => ['rate' => 0.15],
            'invoice_prefix' => ['prefix' => 'INV-'],
            'receipt_prefix' => ['prefix' => 'RCP-'],
            'tax_registration_number' => ['number' => ''],
            'commercial_registration_number' => ['number' => ''],
        ],
    ];

    /**
     * Ensure all system settings exist in the database
     * This is called on app boot to guarantee system settings exist
     */
    public function ensureSystemSettingsExist(): void
    {
        try {
            // Check if settings table exists
            if (!DB::getSchemaBuilder()->hasTable('settings')) {
                return;
            }

            foreach (self::SYSTEM_SETTINGS as $group => $settings) {
                foreach ($settings as $key => $defaultValue) {
                    Setting::updateOrCreate(
                        ['key' => $key],
                        [
                            'value' => $defaultValue,
                            'group' => $group,
                            'is_system' => true,
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            // Fail silently - don't break app boot if database is not ready
            // Log error if logging is available
            if (function_exists('logger')) {
                logger()->warning('Failed to ensure system settings exist: ' . $e->getMessage());
            }
        }
    }
}

