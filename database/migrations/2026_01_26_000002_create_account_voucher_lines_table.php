<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_voucher_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained('account_vouchers')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->text('description')->nullable();
            $table->decimal('debit', 15, 3)->default(0);
            $table->decimal('credit', 15, 3)->default(0);
            $table->enum('line_type', ['main', 'fee', 'tax', 'discount'])->default('main');
            $table->timestamps();
            
            $table->index(['voucher_id', 'account_id'], 'voucher_lines_voucher_account_idx');
        });

        if (Schema::hasTable('cost_centers')) {
            Schema::table('account_voucher_lines', function (Blueprint $table) {
                $table->foreign('cost_center_id', 'voucher_lines_cost_center_id_foreign')
                    ->references('id')
                    ->on('cost_centers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::dropIfExists('account_voucher_lines');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
