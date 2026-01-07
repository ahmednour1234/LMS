<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove 'virtual' from course_prices.delivery_type enum
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Remove 'virtual' from enum, keeping 'onsite' and 'online'
            DB::statement("ALTER TABLE `course_prices` MODIFY COLUMN `delivery_type` ENUM('onsite', 'online') NULL");
        } else {
            // For other databases, use Schema builder
            Schema::table('course_prices', function (Blueprint $table) {
                $table->enum('delivery_type', ['onsite', 'online'])
                    ->nullable()
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     * Add 'virtual' back to the enum
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            // Add 'virtual' back to enum
            DB::statement("ALTER TABLE `course_prices` MODIFY COLUMN `delivery_type` ENUM('onsite', 'online', 'virtual') NULL");
        } else {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->enum('delivery_type', ['onsite', 'online', 'virtual'])
                    ->nullable()
                    ->change();
            });
        }
    }
};
