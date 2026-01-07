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
        // For MySQL/MariaDB, we need to alter the enum column
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE `enrollments` MODIFY COLUMN `status` ENUM('pending', 'pending_payment', 'active', 'completed', 'cancelled') DEFAULT 'pending'");
        } else {
            // For other databases (SQLite, PostgreSQL), we'll need to recreate the column
            // This is a simplified approach - in production you might want more robust handling
            Schema::table('enrollments', function (Blueprint $table) {
                $table->enum('status', ['pending', 'pending_payment', 'active', 'completed', 'cancelled'])
                    ->default('pending')
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' || DB::getDriverName() === 'mariadb') {
            DB::statement("ALTER TABLE `enrollments` MODIFY COLUMN `status` ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending'");
        } else {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->enum('status', ['pending', 'active', 'completed', 'cancelled'])
                    ->default('pending')
                    ->change();
            });
        }
    }
};
