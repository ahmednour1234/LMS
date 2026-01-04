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

    public function up(): void
    {
        // ✅ 1) لو الجدول مش موجود اعمله
        if (!Schema::hasTable('journal_lines')) {
            Schema::create('journal_lines', function (Blueprint $table) {
                $table->id();

                $table->foreignId('journal_id')
                    ->constrained('journals')
                    ->cascadeOnDelete();

                $table->foreignId('account_id')
                    ->constrained('accounts')
                    ->restrictOnDelete();

                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->text('description')->nullable();

                // نضيف العمود فقط
                $table->unsignedBigInteger('cost_center_id')->nullable();

                $table->timestamps();
            });
        }

        // ✅ 2) لو الجدول موجود من قبل، تأكد إن العمود cost_center_id موجود
        if (!Schema::hasColumn('journal_lines', 'cost_center_id')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->unsignedBigInteger('cost_center_id')->nullable()->after('description');
            });
        }

        // ✅ 3) تأكد من الـ indexes (بأسماء ثابتة)
        if (!$this->indexExists('journal_lines', 'journal_lines_journal_id_idx')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->index('journal_id', 'journal_lines_journal_id_idx');
            });
        }

        if (!$this->indexExists('journal_lines', 'journal_lines_account_id_idx')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->index('account_id', 'journal_lines_account_id_idx');
            });
        }

        if (!$this->indexExists('journal_lines', 'journal_lines_cost_center_id_idx')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->index('cost_center_id', 'journal_lines_cost_center_id_idx');
            });
        }

        // ✅ 4) FK للـ cost_center_id لو جدول cost_centers موجود والـ FK مش موجود
        if (Schema::hasTable('cost_centers') && !$this->foreignKeyExists('journal_lines', 'journal_lines_cost_center_id_foreign')) {
            Schema::table('journal_lines', function (Blueprint $table) {
                $table->foreign('cost_center_id', 'journal_lines_cost_center_id_foreign')
                    ->references('id')
                    ->on('cost_centers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // زي ما اتفقنا: avoid 1451 وقت الـ rollback
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::dropIfExists('journal_lines');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
