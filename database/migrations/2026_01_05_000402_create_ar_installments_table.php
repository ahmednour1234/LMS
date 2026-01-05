<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ar_installments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ar_invoice_id');
            $table->integer('installment_no');
            $table->date('due_date');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['ar_invoice_id', 'installment_no']);
            $table->index(['due_date', 'status']);
            $table->index('status');
        });

        if (Schema::hasTable('ar_invoices')) {
            Schema::table('ar_installments', function (Blueprint $table) {
                $table->foreign('ar_invoice_id')->references('id')->on('ar_invoices')->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ar_installments');
    }
};
