<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_vouchers', function (Blueprint $table) {
            $table->foreignId('cash_bank_account_id')->nullable()->after('description')->constrained('accounts')->nullOnDelete();
            $table->foreignId('counterparty_account_id')->nullable()->after('cash_bank_account_id')->constrained('accounts')->nullOnDelete();
            $table->decimal('amount', 15, 3)->nullable()->after('counterparty_account_id');
            $table->foreignId('cost_center_id')->nullable()->after('amount')->constrained('cost_centers')->nullOnDelete();
            $table->text('line_description')->nullable()->after('cost_center_id');
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::table('account_vouchers', function (Blueprint $table) {
            $table->dropForeign(['cash_bank_account_id']);
            $table->dropForeign(['counterparty_account_id']);
            $table->dropForeign(['cost_center_id']);
            $table->dropColumn([
                'cash_bank_account_id',
                'counterparty_account_id',
                'amount',
                'cost_center_id',
                'line_description',
            ]);
        });

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
