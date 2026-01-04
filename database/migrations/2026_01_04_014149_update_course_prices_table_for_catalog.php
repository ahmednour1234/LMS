<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Check if table exists and rename if needed
        if (Schema::hasTable('course_branch_prices')) {
            Schema::rename('course_branch_prices', 'course_prices');
        }

        // Drop foreign key
        if (Schema::hasTable('course_prices')) {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
            });

            // Drop unique constraint - handle different index names
            try {
                Schema::table('course_prices', function (Blueprint $table) {
                    $table->dropUnique(['course_id', 'branch_id']);
                });
            } catch (\Exception $e) {
                // Try alternative index names
                $driver = DB::getDriverName();
                if ($driver === 'sqlite') {
                    // SQLite doesn't support DROP INDEX IF EXISTS in the same way
                    // The unique constraint will be recreated anyway
                } else {
                    try {
                        DB::statement('DROP INDEX IF EXISTS course_prices_course_id_branch_id_unique');
                    } catch (\Exception $e2) {
                        try {
                            DB::statement('DROP INDEX IF EXISTS course_branch_prices_course_id_branch_id_unique');
                        } catch (\Exception $e3) {
                            // Ignore if index doesn't exist
                        }
                    }
                }
            }

            // Modify columns
            Schema::table('course_prices', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->change()->constrained()->nullOnDelete();
                $table->enum('delivery_type', ['onsite', 'online', 'virtual'])->nullable()->after('branch_id');
                $table->decimal('min_down_payment', 12, 2)->nullable()->after('is_installment_enabled');
                $table->integer('max_installments')->nullable()->after('min_down_payment');
                $table->renameColumn('is_installment_enabled', 'allow_installments');
            });

            // Add new unique constraint
            Schema::table('course_prices', function (Blueprint $table) {
                $table->unique(['course_id', 'branch_id', 'delivery_type'], 'course_prices_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_prices')) {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->dropUnique('course_prices_unique');
                $table->dropColumn(['delivery_type', 'min_down_payment', 'max_installments']);
                $table->renameColumn('allow_installments', 'is_installment_enabled');
                $table->foreignId('branch_id')->nullable(false)->change()->constrained()->cascadeOnDelete();
                $table->unique(['course_id', 'branch_id']);
            });

            Schema::rename('course_prices', 'course_branch_prices');
        }
    }
};
