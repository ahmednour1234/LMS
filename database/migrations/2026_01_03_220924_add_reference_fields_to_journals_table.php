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

        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name   = ?
              AND index_name   = ?
            LIMIT 1
        ", [$db, $table, $indexName]);

        return (bool) $row;
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
    }

    public function up(): void
    {
        // ✅ add columns only if missing
        Schema::table('journals', function (Blueprint $table) {
            if (!Schema::hasColumn('journals', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('reference');
            }
            if (!Schema::hasColumn('journals', 'reference_id')) {
                $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            }
        });

        // ✅ add index with fixed name (only if not exists)
        if (!$this->indexExists('journals', 'journals_reference_idx')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->index(['reference_type', 'reference_id'], 'journals_reference_idx');
            });
        }
    }

    public function down(): void
    {
        // ✅ drop index safely
        $this->dropIndexIfExists('journals', 'journals_reference_idx');

        // ✅ drop columns only if exist
        Schema::table('journals', function (Blueprint $table) {
            if (Schema::hasColumn('journals', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('journals', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
        });

        // ✅ (اختياري) لو كان فيه index قديم باسم Laravel الافتراضي، شيله كمان
        $this->dropIndexIfExists('journals', 'journals_reference_type_reference_id_index');
    }
};
