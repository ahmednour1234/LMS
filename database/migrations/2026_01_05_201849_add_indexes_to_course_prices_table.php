<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Check if index exists
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $result = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$db, $table, $indexName]);

        return (bool) $result;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add composite index for PricingService lookups
        // Index on (course_id, branch_id, delivery_type, is_active) for efficient filtering
        if (!$this->indexExists('course_prices', 'course_prices_lookup_idx')) {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->index(['course_id', 'branch_id', 'delivery_type', 'is_active'], 'course_prices_lookup_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('course_prices', 'course_prices_lookup_idx')) {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->dropIndex('course_prices_lookup_idx');
            });
        }
    }
};
