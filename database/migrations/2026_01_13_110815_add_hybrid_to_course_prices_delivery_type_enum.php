<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'hybrid' to course_prices.delivery_type enum
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `course_prices` MODIFY COLUMN `delivery_type` ENUM('onsite', 'online', 'hybrid') NULL");
        } else {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->enum('delivery_type', ['onsite', 'online', 'hybrid'])
                    ->nullable()
                    ->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     * Remove 'hybrid' from the enum
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE `course_prices` MODIFY COLUMN `delivery_type` ENUM('onsite', 'online') NULL");
        } else {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->enum('delivery_type', ['onsite', 'online'])
                    ->nullable()
                    ->change();
            });
        }
    }
};
