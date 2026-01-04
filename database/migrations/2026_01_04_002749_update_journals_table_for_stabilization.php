<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------- Helpers (MySQL/MariaDB safe drops) ----------
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

    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = ?
              AND table_name   = ?
              AND constraint_name = ?
              AND constraint_type = 'FOREIGN KEY'
            LIMIT 1
        ", [$db, $table, $fkName]);

        return (bool) $row;
    }

    private function dropForeignIfExists(string $table, string $fkName): void
    {
        if ($this->foreignKeyExists($table, $fkName)) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
        }
    }

    private function mysqlEnumHasValue(string $table, string $column, string $value): bool
    {
        $row = DB::selectOne("SHOW COLUMNS FROM `$table` WHERE Field = ?", [$column]);
        if (!$row || empty($row->Type)) {
            return false;
        }
        return strpos($row->Type, "'" . $value . "'") !== false;
    }

    public function up(): void
    {
        // 1) posted_by column + FK (explicit name)
        if (!Schema::hasColumn('journals', 'posted_by')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->unsignedBigInteger('posted_by')->nullable()->after('updated_by');
            });
        }

        // drop FK if exists (in case of previous partial run), then create
        $this->dropForeignIfExists('journals', 'journals_posted_by_foreign');

        // create FK only if users table exists and column exists
        if (Schema::hasTable('users') && Schema::hasColumn('journals', 'posted_by')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->foreign('posted_by', 'journals_posted_by_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        // 2) Rename date -> journal_date (only if needed)
        if (Schema::hasColumn('journals', 'date') && !Schema::hasColumn('journals', 'journal_date')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->renameColumn('date', 'journal_date');
            });
        }

        // 3) status enum add 'void' (MySQL/MariaDB)
        $driver = DB::getDriverName();
        if (($driver === 'mysql' || $driver === 'mariadb') && Schema::hasColumn('journals', 'status')) {
            if (!$this->mysqlEnumHasValue('journals', 'status', 'void')) {
                DB::statement("ALTER TABLE `journals` MODIFY COLUMN `status` ENUM('draft','posted','void') DEFAULT 'draft'");
            }
        }

        // 4) Unique index on (reference_type, reference_id)
        if (Schema::hasColumn('journals', 'reference_type') && Schema::hasColumn('journals', 'reference_id')) {
            // drop any old conflicting index names you might have
            $this->dropIndexIfExists('journals', 'journals_reference_type_reference_id_index');
            $this->dropIndexIfExists('journals', 'journals_reference_type_reference_id_unique');

            if (!$this->indexExists('journals', 'journals_reference_unique')) {
                Schema::table('journals', function (Blueprint $table) {
                    $table->unique(['reference_type', 'reference_id'], 'journals_reference_unique');
                });
            }
        }

        // 5) Index on (journal_date, status) instead of (date, status)
        // drop old one safely (names may differ)
        $this->dropIndexIfExists('journals', 'journals_date_status_index');
        $this->dropIndexIfExists('journals', 'journals_date_status_idx');

        if (Schema::hasColumn('journals', 'journal_date') && Schema::hasColumn('journals', 'status')) {
            if (!$this->indexExists('journals', 'journals_journal_date_status_index')) {
                Schema::table('journals', function (Blueprint $table) {
                    $table->index(['journal_date', 'status'], 'journals_journal_date_status_index');
                });
            }
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        // 1) Drop indexes safely
        $this->dropIndexIfExists('journals', 'journals_reference_unique');
        $this->dropIndexIfExists('journals', 'journals_journal_date_status_index');
        $this->dropIndexIfExists('journals', 'journals_date_status_index');

        // 2) Restore (date,status) index BUT only on existing column
        // - If journal_date exists (and date doesn't), create index on journal_date temporarily before rename
        if (Schema::hasColumn('journals', 'journal_date') && Schema::hasColumn('journals', 'status')) {
            // create index on journal_date if you want (optional) — لكن مش لازم
        }

        // 3) Revert enum (remove void) MySQL/MariaDB
        if (($driver === 'mysql' || $driver === 'mariadb') && Schema::hasColumn('journals', 'status')) {
            // لو فيه قيم void في الداتا، تغيير الـ enum هيكسر!
            // حل سريع: حوّل void -> draft قبل تعديل enum
            DB::statement("UPDATE `journals` SET `status` = 'draft' WHERE `status` = 'void'");

            DB::statement("ALTER TABLE `journals` MODIFY COLUMN `status` ENUM('draft','posted') DEFAULT 'draft'");
        }

        // 4) Rename journal_date -> date (only if needed)
        if (Schema::hasColumn('journals', 'journal_date') && !Schema::hasColumn('journals', 'date')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->renameColumn('journal_date', 'date');
            });
        }

        // 5) Now create index on (date,status) ONLY if date exists
        if (Schema::hasColumn('journals', 'date') && Schema::hasColumn('journals', 'status')) {
            if (!$this->indexExists('journals', 'journals_date_status_index')) {
                Schema::table('journals', function (Blueprint $table) {
                    $table->index(['date', 'status'], 'journals_date_status_index');
                });
            }
        }

        // 6) Drop posted_by FK + column safely
        $this->dropForeignIfExists('journals', 'journals_posted_by_foreign');

        if (Schema::hasColumn('journals', 'posted_by')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('posted_by');
            });
        }
    }
};
