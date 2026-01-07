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
        Schema::table('course_prices', function (Blueprint $table) {
            $table->enum('pricing_mode', ['course_total', 'per_session', 'both'])
                ->default('course_total')
                ->after('delivery_type');
            $table->decimal('session_price', 10, 2)
                ->nullable()
                ->after('pricing_mode');
            $table->integer('sessions_count')
                ->nullable()
                ->after('session_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_prices', function (Blueprint $table) {
            $table->dropColumn(['pricing_mode', 'session_price', 'sessions_count']);
        });
    }
};
