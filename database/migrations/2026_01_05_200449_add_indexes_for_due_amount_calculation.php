<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add indexes to optimize due_amount calculation queries:
     * - payments(enrollment_id, status, paid_at) for: WHERE enrollment_id = ? AND status = 'paid'
     * - ar_invoices(enrollment_id) if not already exists
     */
    public function up(): void
    {
        // Add composite index on payments table for due_amount calculation
        // This optimizes: SELECT SUM(amount) FROM payments WHERE enrollment_id = ? AND status = 'paid'
        Schema::table('payments', function (Blueprint $table) {
            // Try to add the index - if it exists, it will fail but that's OK
            try {
                $table->index(['enrollment_id', 'status', 'paid_at'], 'payments_enrollment_id_status_paid_at_index');
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        });

        // Verify ar_invoices has enrollment_id index (should already exist from previous migration)
        // Check if we need to add it
        if (!$this->hasIndex('ar_invoices', 'ar_invoices_enrollment_id_index')) {
            Schema::table('ar_invoices', function (Blueprint $table) {
                try {
                    $table->index('enrollment_id', 'ar_invoices_enrollment_id_index');
                } catch (\Exception $e) {
                    // Index might already exist with different name, continue
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            try {
                $table->dropIndex('payments_enrollment_id_status_paid_at_index');
            } catch (\Exception $e) {
                // Index might not exist
            }
        });

        // Note: We won't drop ar_invoices enrollment_id index as it might have been created by previous migration
    }

    /**
     * Check if an index exists on a table (database-agnostic)
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();

        try {
            if ($driverName === 'sqlite') {
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
                return false;
            } elseif (in_array($driverName, ['mysql', 'mariadb'])) {
                $databaseName = $connection->getDatabaseName();
                $result = DB::select(
                    "SELECT COUNT(*) as count 
                     FROM information_schema.statistics 
                     WHERE table_schema = ? 
                     AND table_name = ? 
                     AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return $result[0]->count > 0;
            } elseif ($driverName === 'pgsql') {
                $result = DB::select(
                    "SELECT COUNT(*) as count 
                     FROM pg_indexes 
                     WHERE tablename = ? 
                     AND indexname = ?",
                    [$table, $indexName]
                );
                return $result[0]->count > 0;
            }
        } catch (\Exception $e) {
            // If check fails, assume index doesn't exist and let migration try to create it
            return false;
        }

        return false;
    }
};
