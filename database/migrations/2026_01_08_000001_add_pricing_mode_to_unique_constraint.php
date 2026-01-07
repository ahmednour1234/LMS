<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Updates the unique constraint on course_prices table to include pricing_mode.
     * This prevents duplicate pricing rows for the same (course, branch, delivery_type, pricing_mode) combination.
     */
    public function up(): void
    {
        if (!Schema::hasTable('course_prices')) {
            return;
        }

        // Drop existing unique constraint
        $this->dropIndexIfExists('course_prices', 'course_prices_unique');
        $this->dropIndexIfExists('course_prices', 'course_prices_course_id_branch_id_delivery_type_unique');

        // Add new unique constraint including pricing_mode
        Schema::table('course_prices', function (Blueprint $table) {
            $table->unique(
                ['course_id', 'branch_id', 'delivery_type', 'pricing_mode'],
                'course_prices_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('course_prices')) {
            return;
        }

        // Drop new unique constraint
        $this->dropIndexIfExists('course_prices', 'course_prices_unique');

        // Restore old unique constraint (without pricing_mode)
        Schema::table('course_prices', function (Blueprint $table) {
            $table->unique(
                ['course_id', 'branch_id', 'delivery_type'],
                'course_prices_unique'
            );
        });
    }

    /**
     * Check if an index exists on the table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $result = DB::select("PRAGMA index_list('{$table}')");
            foreach ($result as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // MySQL/MariaDB
        $db = DB::getDatabaseName();
        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$db, $table, $indexName]);

        return (bool) $row;
    }

    /**
     * Drop an index if it exists.
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            $driver = DB::getDriverName();

            if ($driver === 'sqlite') {
                DB::statement("DROP INDEX IF EXISTS `{$indexName}`");
            } else {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
            }
        }
    }
};

