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
        if (!Schema::hasTable('ar_invoices')) {
            Schema::create('ar_invoices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('enrollments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
                $table->decimal('total_amount', 15, 2);
                $table->decimal('due_amount', 15, 2);
                $table->enum('status', ['open', 'partial', 'paid', 'canceled'])->default('open');
                $table->timestamp('issued_at');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['enrollment_id', 'status']);
                $table->index(['user_id', 'status']);
                $table->index('branch_id');
                $table->index('status');
                $table->index('issued_at');
            });
        } else {
            Schema::table('ar_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('ar_invoices', 'enrollment_id')) {
                    $table->foreignId('enrollment_id')->after('id')->constrained('enrollments')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('ar_invoices', 'user_id')) {
                    $table->foreignId('user_id')->after('enrollment_id')->constrained('users')->restrictOnDelete();
                }
                if (!Schema::hasColumn('ar_invoices', 'status')) {
                    $table->enum('status', ['open', 'partial', 'paid', 'canceled'])->default('open')->after('user_id');
                }
                if (!Schema::hasColumn('ar_invoices', 'issued_at')) {
                    $table->timestamp('issued_at')->after('status');
                }
                if (!Schema::hasColumn('ar_invoices', 'due_amount')) {
                    $table->decimal('due_amount', 15, 2)->after('total_amount');
                }
                if (Schema::hasColumn('ar_invoices', 'invoice_number')) {
                    $table->dropColumn('invoice_number');
                }
                if (Schema::hasColumn('ar_invoices', 'customer_id')) {
                    $table->dropColumn('customer_id');
                }
                if (Schema::hasColumn('ar_invoices', 'tax_amount')) {
                    $table->dropColumn('tax_amount');
                }
                if (Schema::hasColumn('ar_invoices', 'due_date')) {
                    $table->dropColumn('due_date');
                }
                if (Schema::hasColumn('ar_invoices', 'paid_at')) {
                    $table->dropColumn('paid_at');
                }
                if (Schema::hasColumn('ar_invoices', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ar_invoices')) {
            Schema::table('ar_invoices', function (Blueprint $table) {
                if (Schema::hasColumn('ar_invoices', 'enrollment_id')) {
                    $table->dropForeign(['enrollment_id']);
                    $table->dropColumn('enrollment_id');
                }
                if (Schema::hasColumn('ar_invoices', 'user_id')) {
                    $table->dropForeign(['user_id']);
                    $table->dropColumn('user_id');
                }
                if (Schema::hasColumn('ar_invoices', 'status')) {
                    $table->dropColumn('status');
                }
                if (Schema::hasColumn('ar_invoices', 'issued_at')) {
                    $table->dropColumn('issued_at');
                }
                if (Schema::hasColumn('ar_invoices', 'due_amount')) {
                    $table->dropColumn('due_amount');
                }
            });
        }
    }
};
