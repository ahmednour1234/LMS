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
            // Check if pricing_type column exists to determine placement
            $hasPricingType = Schema::hasColumn('enrollments', 'pricing_type');
            
            if ($hasPricingType) {
                $table->enum('registration_type', ['onsite', 'online'])
                    ->default('online')
                    ->after('pricing_type');
            } else {
                $table->enum('registration_type', ['onsite', 'online'])
                    ->default('online');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            if (Schema::hasColumn('enrollments', 'registration_type')) {
                $table->dropColumn('registration_type');
            }
        });
    }
};
