<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();

        $row = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name   = ?
              AND index_name   = ?
            LIMIT 1
        ", [$db, $table, $indexName]);

        return (bool) $row;
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

    private function dropForeignIfExists(string $table, string $fkName): void
    {
        if ($this->foreignKeyExists($table, $fkName)) {
            DB::statement("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
        }
    }

    public function up(): void
    {
        // 1) create table if not exists
        if (!Schema::hasTable('course_branch_prices')) {
            Schema::create('course_branch_prices', function (Blueprint $table) {
                $table->id();

                // اعمل الأعمدة الأول بدون constrained() عشان نتحكم في أسماء الـ FK
                $table->unsignedBigInteger('course_id');
                $table->unsignedBigInteger('branch_id');

                $table->decimal('price', 12, 2);
                $table->boolean('is_installment_enabled')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // unique/index بأسماء ثابتة
                $table->unique(['course_id', 'branch_id'], 'cbp_course_branch_unique');
                $table->index(['course_id', 'is_active'], 'cbp_course_active_idx');
                $table->index(['branch_id', 'is_active'], 'cbp_branch_active_idx');
            });
        }

        // 2) تأكد إن الـ FKs مش موجودة بنفس الاسم (لو محاولة قديمة)
        $this->dropForeignIfExists('course_branch_prices', 'cbp_course_fk');
        $this->dropForeignIfExists('course_branch_prices', 'cbp_branch_fk');

        // 3) اعمل FKs بأسماء فريدة (وإعملها فقط لو الجداول موجودة)
        if (Schema::hasTable('courses') && !$this->foreignKeyExists('course_branch_prices', 'cbp_course_fk')) {
            Schema::table('course_branch_prices', function (Blueprint $table) {
                $table->foreign('course_id', 'cbp_course_fk')
                    ->references('id')
                    ->on('courses')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('branches') && !$this->foreignKeyExists('course_branch_prices', 'cbp_branch_fk')) {
            Schema::table('course_branch_prices', function (Blueprint $table) {
                $table->foreign('branch_id', 'cbp_branch_fk')
                    ->references('id')
                    ->on('branches')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        Schema::dropIfExists('course_branch_prices');

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
};
