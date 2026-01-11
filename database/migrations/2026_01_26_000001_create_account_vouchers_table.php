<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->enum('voucher_type', ['receipt', 'payment']);
            $table->date('voucher_date');
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->unsignedBigInteger('currency_id')->nullable();
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->string('payee_name')->nullable();
            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            
            $table->index(['voucher_type', 'status'], 'vouchers_type_status_idx');
            $table->index('voucher_date', 'vouchers_date_idx');
        });

        if (Schema::hasTable('branches')) {
            Schema::table('account_vouchers', function (Blueprint $table) {
                $table->foreign('branch_id', 'vouchers_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
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

        Schema::dropIfExists('account_vouchers');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
