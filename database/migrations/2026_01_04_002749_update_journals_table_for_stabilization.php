<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add posted_by column if it doesn't exist
        if (!Schema::hasColumn('journals', 'posted_by')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->foreignId('posted_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            });
        }

        // Rename date to journal_date if date column exists and journal_date doesn't
        if (Schema::hasColumn('journals', 'date') && !Schema::hasColumn('journals', 'journal_date')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->renameColumn('date', 'journal_date');
            });
        }

        // Update status enum to include 'void'
        // Note: MySQL/MariaDB requires recreating the column
        // Check current enum values first
        $columnInfo = DB::select("SHOW COLUMNS FROM journals WHERE Field = 'status'");
        if (!empty($columnInfo)) {
            $type = $columnInfo[0]->Type;
            if (strpos($type, "'void'") === false) {
                DB::statement("ALTER TABLE journals MODIFY COLUMN status ENUM('draft', 'posted', 'void') DEFAULT 'draft'");
            }
        }

        // Add unique index on (reference_type, reference_id) if columns exist and index doesn't
        if (Schema::hasColumn('journals', 'reference_type') && Schema::hasColumn('journals', 'reference_id')) {
            $uniqueIndexes = DB::select("SHOW INDEXES FROM journals WHERE Key_name = 'journals_reference_unique'");
            if (empty($uniqueIndexes)) {
                // Check for any existing index on these columns
                $allIndexes = DB::select("SHOW INDEXES FROM journals WHERE Column_name IN ('reference_type', 'reference_id')");
                $indexNames = array_unique(array_map(fn($idx) => $idx->Key_name, $allIndexes));

                // Drop existing non-unique index if it exists (Laravel auto-generates index names)
                foreach ($indexNames as $indexName) {
                    if ($indexName !== 'journals_reference_unique' && $indexName !== 'PRIMARY') {
                        // Check if this index covers both columns
                        $indexColumns = array_filter($allIndexes, fn($idx) => $idx->Key_name === $indexName);
                        $columnNames = array_map(fn($idx) => $idx->Column_name, $indexColumns);
                        if (in_array('reference_type', $columnNames) && in_array('reference_id', $columnNames)) {
                            Schema::table('journals', function (Blueprint $table) use ($indexName) {
                                $table->dropIndex($indexName);
                            });
                            break;
                        }
                    }
                }

                // Create unique index
                Schema::table('journals', function (Blueprint $table) {
                    $table->unique(['reference_type', 'reference_id'], 'journals_reference_unique');
                });
            }
        }

        // Update indexes for journal_date
        if (Schema::hasColumn('journals', 'journal_date')) {
            // Check if old index exists before dropping
            $oldIndexes = DB::select("SHOW INDEXES FROM journals WHERE Key_name = 'journals_date_status_index'");
            if (!empty($oldIndexes)) {
                Schema::table('journals', function (Blueprint $table) {
                    $table->dropIndex(['date', 'status']);
                });
            }

            // Check if new index exists before adding
            $newIndexes = DB::select("SHOW INDEXES FROM journals WHERE Key_name = 'journals_journal_date_status_index'");
            if (empty($newIndexes)) {
                Schema::table('journals', function (Blueprint $table) {
                    $table->index(['journal_date', 'status']);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropIndex('journals_reference_unique');
            $table->dropIndex(['journal_date', 'status']);
            $table->index(['date', 'status']);
        });

        DB::statement("ALTER TABLE journals MODIFY COLUMN status ENUM('draft', 'posted') DEFAULT 'draft'");

        Schema::table('journals', function (Blueprint $table) {
            $table->renameColumn('journal_date', 'date');
            $table->dropForeign(['posted_by']);
            $table->dropColumn('posted_by');
        });
    }
};
