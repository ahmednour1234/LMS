<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->date('journal_date'); // ✅ من البداية بدل date
            $table->text('description')->nullable();

            $table->enum('status', ['draft', 'posted'])->default('draft');

            // ✅ اعمل العمود فقط هنا، والـ FK نضيفه بعدين بشرط وجود الجدول
            $table->unsignedBigInteger('branch_id')->nullable();

            $table->timestamp('posted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // ✅ indexes بأسماء ثابتة
            $table->index(['branch_id', 'status'], 'journals_branch_status_idx');
            $table->index(['journal_date', 'status'], 'journals_date_status_idx');
            $table->index('status', 'journals_status_idx');
        });

        // ✅ FK على branches فقط لو الجدول موجود فعلاً
        if (Schema::hasTable('branches')) {
            Schema::table('journals', function (Blueprint $table) {
                $table->foreign('branch_id', 'journals_branch_id_foreign')
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

        Schema::dropIfExists('journals');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
