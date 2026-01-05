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
        Schema::create('pdf_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->string('invoice_no')->unique();
            $table->foreignId('pdf_file_id')->nullable()->constrained('media_files')->nullOnDelete();
            $table->timestamp('issued_at');
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->index('invoice_no');
            $table->index('payment_id');
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_invoices');
    }
};
