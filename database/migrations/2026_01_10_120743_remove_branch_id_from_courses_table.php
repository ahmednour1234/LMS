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

    public function up(): void
    {
        if (Schema::hasColumn('courses', 'branch_id')) {
            $this->dropIndexIfExists('courses', 'courses_code_branch_unique');
            
            Schema::table('courses', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
            });
            
            $this->dropIndexIfExists('courses', 'courses_branch_id_is_active_index');
            
            Schema::table('courses', function (Blueprint $table) {
                $table->dropColumn('branch_id');
            });
            
            $this->dropIndexIfExists('courses', 'courses_code_unique');
            Schema::table('courses', function (Blueprint $table) {
                $table->unique('code', 'courses_code_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'branch_id')) {
                $this->dropIndexIfExists('courses', 'courses_code_unique');
                $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                $table->index(['branch_id', 'is_active']);
                $table->unique(['code', 'branch_id'], 'courses_code_branch_unique');
            }
        });
    }
};
