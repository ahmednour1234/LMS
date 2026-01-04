<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        /**
         * 0) تنظيف: احذف أي FK على parent_id مهما كان اسمه
         * ثم احذف أي indexes متعلقة
         */
        if (Schema::hasColumn('programs', 'parent_id')) {
            $this->dropAllForeignKeysForColumn('programs', 'parent_id');
        }

        // indexes/uniques محتملة
        $this->dropIndexIfExists('programs', 'programs_code_unique');
        $this->dropIndexIfExists('programs', 'programs_code_branch_id_unique');
        $this->dropIndexIfExists('programs', 'programs_code_branch_unique');
        $this->dropIndexIfExists('programs', 'programs_parent_id_idx');
        $this->dropIndexIfExists('programs', 'programs_parent_id_index');

        /**
         * 1) Add columns if not exists
         */
        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'parent_id')) {
                $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('programs', 'name')) {
                $table->json('name')->after('code');
            }

            if (!Schema::hasColumn('programs', 'description')) {
                $table->json('description')->nullable()->after('name');
            }
        });

        /**
         * 2) Create FK (name ثابت) + index ثابت
         * لاحظ: MySQL يحتاج index على parent_id — هنستخدم programs_parent_id_idx
         */
        if (!$this->indexExists('programs', 'programs_parent_id_idx')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->index('parent_id', 'programs_parent_id_idx');
            });
        }

        // اعمل FK باسم ثابت (لو مش موجود)
        // (لو كان موجود باسم آخر، احنا شيلناه فوق)
        Schema::table('programs', function (Blueprint $table) {
            $table->foreign('parent_id', 'programs_parent_id_foreign')
                ->references('id')
                ->on('programs')
                ->cascadeOnDelete();
        });

        /**
         * 3) Unique(code, branch_id)
         */
        $this->dropIndexIfExists('programs', 'programs_code_unique');

        Schema::table('programs', function (Blueprint $table) {
            $table->unique(['code', 'branch_id'], 'programs_code_branch_unique');
        });
    }

    public function down(): void
    {
        /**
         * 1) Drop FK أولاً (أي اسم) ثم Drop index
         */
        if (Schema::hasColumn('programs', 'parent_id')) {
            $this->dropAllForeignKeysForColumn('programs', 'parent_id');
        }

        $this->dropIndexIfExists('programs', 'programs_parent_id_idx');
        $this->dropIndexIfExists('programs', 'programs_code_branch_unique');

        /**
         * 2) Drop columns if exist
         */
        Schema::table('programs', function (Blueprint $table) {
            if (Schema::hasColumn('programs', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('programs', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('programs', 'parent_id')) {
                $table->dropColumn('parent_id');
            }
        });

        /**
         * 3) Restore unique(code)
         */
        $this->dropIndexIfExists('programs', 'programs_code_unique');

        Schema::table('programs', function (Blueprint $table) {
            $table->unique('code', 'programs_code_unique');
        });
    }
};
