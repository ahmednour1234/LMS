<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
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
        if (!Schema::hasTable('account_vouchers')) {
            return;
        }

        if (!$this->columnExists('journals', 'voucher_id')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->unsignedBigInteger('voucher_id')->nullable()->after('reference_id');
            });
        }

        if (!$this->foreignKeyExists('journals', 'journals_voucher_id_foreign')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->foreign('voucher_id', 'journals_voucher_id_foreign')
                    ->references('id')
                    ->on('account_vouchers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('journals', 'journals_voucher_id_foreign')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropForeign('journals_voucher_id_foreign');
            });
        }

        if ($this->columnExists('journals', 'voucher_id')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->dropColumn('voucher_id');
            });
        }
    }
};
