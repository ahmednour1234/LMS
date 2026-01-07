<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds discount and tax fields to ar_invoices for proper invoice calculation:
     * - subtotal: Base amount before discounts
     * - manual_discount: Discount applied manually by staff
     * - manual_discount_reason: Audit trail for why discount was applied
     * - manual_discount_by: Who applied the discount
     * - promo_code_id: Reference to promo code if used
     * - promo_discount: Discount from promo code
     * - tax_rate: Tax percentage (e.g., 5 for 5%)
     * - tax_total: Calculated tax amount
     */
    public function up(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            // Subtotal (base amount before discounts)
            $table->decimal('subtotal', 15, 3)->nullable()->after('total_amount');
            
            // Manual discount fields
            $table->decimal('manual_discount', 15, 3)->default(0)->after('subtotal');
            $table->string('manual_discount_reason')->nullable()->after('manual_discount');
            $table->unsignedBigInteger('manual_discount_by')->nullable()->after('manual_discount_reason');
            $table->timestamp('manual_discount_at')->nullable()->after('manual_discount_by');
            
            // Promo code fields
            $table->unsignedBigInteger('promo_code_id')->nullable()->after('manual_discount_at');
            $table->decimal('promo_discount', 15, 3)->default(0)->after('promo_code_id');
            
            // Tax fields
            $table->decimal('tax_rate', 5, 2)->default(0)->after('promo_discount');
            $table->decimal('tax_total', 15, 3)->default(0)->after('tax_rate');
            
            // Foreign key for manual_discount_by
            $table->foreign('manual_discount_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            
            // Index for promo code lookups
            $table->index('promo_code_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ar_invoices', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['manual_discount_by']);
            
            // Drop index
            $table->dropIndex(['promo_code_id']);
            
            // Drop columns
            $table->dropColumn([
                'subtotal',
                'manual_discount',
                'manual_discount_reason',
                'manual_discount_by',
                'manual_discount_at',
                'promo_code_id',
                'promo_discount',
                'tax_rate',
                'tax_total',
            ]);
        });
    }
};

