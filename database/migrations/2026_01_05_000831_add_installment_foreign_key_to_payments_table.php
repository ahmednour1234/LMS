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
        if (Schema::hasTable('payments') && Schema::hasTable('ar_installments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'installment_id')) {
                    $table->foreign('installment_id')->references('id')->on('ar_installments')->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (Schema::hasColumn('payments', 'installment_id')) {
                    $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('payments');
                    foreach ($foreignKeys as $fk) {
                        if (in_array('installment_id', $fk->getLocalColumns())) {
                            $table->dropForeign([$fk->getName()]);
                            break;
                        }
                    }
                }
            });
        }
    }
};
