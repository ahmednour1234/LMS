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

        // Migrate virtual to online in course_prices table
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
