<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
    }

    private function fkNamesForColumn(string $table, string $column): array
    {
        $db = DB::getDatabaseName();
        $rows = DB::select("
            SELECT CONSTRAINT_NAME as name
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$db, $table, $column]);
        return array_values(array_unique(array_map(fn($r) => $r->name, $rows)));
    }

    private function dropAllForeignKeysForColumn(string $table, string $column): void
    {
        foreach ($this->fkNamesForColumn($table, $column) as $fkName) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
        }
    }

    public function up(): void
    {
        if (Schema::hasColumn('programs', 'branch_id')) {
            $this->dropIndexIfExists('programs', 'programs_code_branch_unique');
            
            $this->dropAllForeignKeysForColumn('programs', 'branch_id');
            
            $this->dropIndexIfExists('programs', 'programs_branch_id_is_active_index');
            
            Schema::table('programs', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
            
            $this->dropIndexIfExists('programs', 'programs_code_unique');
            if (!$this->indexExists('programs', 'programs_code_unique')) {
                Schema::table('programs', function (Blueprint $table) {
                    $table->unique('code', 'programs_code_unique');
                });
            }
        }
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'branch_id')) {
                $this->dropIndexIfExists('programs', 'programs_code_unique');
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->index(['branch_id', 'is_active'], 'programs_branch_id_is_active_index');
                $table->unique(['code', 'branch_id'], 'programs_code_branch_unique');
            }
        });
    }
};

