<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrate all existing 'virtual' records to 'online' before removing the enum value
     */
    public function up(): void
    {
        // Migrate virtual to online in courses table
        DB::table('courses')
            ->where('delivery_type', 'virtual')
            ->update(['delivery_type' => 'online']);

        // For course_prices, we need to handle unique constraint violations
        // Delete virtual records that would create duplicates when changed to online
        // (if an online record with same course_id + branch_id already exists)
        // We keep the online record and delete the virtual one
        DB::statement("
            DELETE cp_virtual FROM course_prices cp_virtual
            WHERE cp_virtual.delivery_type = 'virtual'
            AND EXISTS (
                SELECT 1 FROM course_prices cp_online
                WHERE cp_online.course_id = cp_virtual.course_id
                AND (
                    (cp_virtual.branch_id IS NULL AND cp_online.branch_id IS NULL)
                    OR (cp_virtual.branch_id IS NOT NULL AND cp_online.branch_id = cp_virtual.branch_id)
                )
                AND cp_online.delivery_type = 'online'
            )
        ");

        // Now update remaining virtual records to online (those without conflicts)
        DB::table('course_prices')
            ->where('delivery_type', 'virtual')
            ->update(['delivery_type' => 'online']);
    }

    /**
     * Reverse the migrations.
     * Note: This cannot be reversed as we don't know which records were originally virtual
     */
    public function down(): void
    {
        // Cannot reverse this migration - data loss would occur
        // If rollback is needed, the enum changes would need to be rolled back first
    }
};
