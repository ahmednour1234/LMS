<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alter enum to include 'hybrid' value
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `courses` MODIFY COLUMN `delivery_type` ENUM('onsite', 'online', 'virtual', 'hybrid') NOT NULL DEFAULT 'online'");
        } else {
            // For other databases, use Schema builder
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('delivery_type', ['onsite', 'online', 'virtual', 'hybrid'])
                    ->default('online')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Remove 'hybrid' from enum, revert to original values
            DB::statement("ALTER TABLE `courses` MODIFY COLUMN `delivery_type` ENUM('onsite', 'online', 'virtual') NOT NULL DEFAULT 'online'");
        } else {
            Schema::table('courses', function (Blueprint $table) {
                $table->enum('delivery_type', ['onsite', 'online', 'virtual'])
                    ->default('online')
                    ->change();
            });
        }
    }
};
