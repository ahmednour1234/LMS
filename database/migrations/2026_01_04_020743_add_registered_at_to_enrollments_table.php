<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // Add registered_at if it doesn't exist
            if (!Schema::hasColumn('enrollments', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('enrolled_at');
            }
            
            // Copy data from enrolled_at to registered_at if enrolled_at exists and registered_at is null
            if (Schema::hasColumn('enrollments', 'enrolled_at') && Schema::hasColumn('enrollments', 'registered_at')) {
                \DB::statement('UPDATE enrollments SET registered_at = enrolled_at WHERE registered_at IS NULL AND enrolled_at IS NOT NULL');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'registered_at')) {
                $table->dropColumn('registered_at');
            }
        });
    }
};
