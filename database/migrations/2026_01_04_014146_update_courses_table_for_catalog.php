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

        $result = DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = ?
              AND index_name = ?
            LIMIT 1
        ", [$db, $table, $indexName]);

        return (bool) $result;
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
    }

    public function up(): void
    {
        // 1) Add columns
        Schema::table('courses', function (Blueprint $table) {
            // لو عندك name موجود قبل كده كـ string، لازم تتأكد الأول قبل ما تضيف
            $table->json('name')->after('code');
            $table->json('description')->nullable()->after('name');
            $table->enum('delivery_type', ['onsite', 'online', 'virtual'])->after('description');
            $table->integer('duration_hours')->nullable()->after('delivery_type');
        });

        // 2) Drop old columns + indexes safely
        Schema::table('courses', function (Blueprint $table) {
            // الأعمدة
            if (Schema::hasColumn('courses', 'price')) {
                $table->dropColumn('price');
            }
            if (Schema::hasColumn('courses', 'is_installment_enabled')) {
                $table->dropColumn('is_installment_enabled');
            }
        });

        /**
         * 3) Drop indexes safely (أسماء Laravel الافتراضية + أي أسماء مخصصة كنت جرّبتها)
         * - unique(code) غالبًا: courses_code_unique
         * - unique(code, branch_id) غالبًا: courses_code_branch_id_unique
         */
        $this->dropIndexIfExists('courses', 'courses_code_unique');
        $this->dropIndexIfExists('courses', 'courses_code_branch_id_unique');

        // لو كنت جرّبت أسماء مخصصة قبل كده:
        $this->dropIndexIfExists('courses', 'courses_code_branch_unique');
        $this->dropIndexIfExists('courses', 'courses_delivery_type_idx');
        $this->dropIndexIfExists('courses', 'courses_delivery_type_index');

        // 4) Create indexes with fixed names (مهم جدًا عشان الـ rollback يبقى مضمون)
        Schema::table('courses', function (Blueprint $table) {
            $table->unique(['code', 'branch_id'], 'courses_code_branch_unique');
            $table->index('delivery_type', 'courses_delivery_type_idx');
        });
    }

    public function down(): void
    {
        // Drop our indexes safely
        $this->dropIndexIfExists('courses', 'courses_code_branch_unique');
        $this->dropIndexIfExists('courses', 'courses_delivery_type_idx');

        // Drop added columns
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'name')) {
                $table->dropColumn('name');
            }
            if (Schema::hasColumn('courses', 'description')) {
                $table->dropColumn('description');
            }
            if (Schema::hasColumn('courses', 'delivery_type')) {
                $table->dropColumn('delivery_type');
            }
            if (Schema::hasColumn('courses', 'duration_hours')) {
                $table->dropColumn('duration_hours');
            }

            // restore removed columns
            if (!Schema::hasColumn('courses', 'price')) {
                $table->decimal('price', 12, 2)->after('code');
            }
            if (!Schema::hasColumn('courses', 'is_installment_enabled')) {
                $table->boolean('is_installment_enabled')->default(false)->after('price');
            }
        });

        // Restore unique(code) safely (بس الأول شيل أي تعارض)
        $this->dropIndexIfExists('courses', 'courses_code_unique');

        Schema::table('courses', function (Blueprint $table) {
            $table->unique('code', 'courses_code_unique');
        });
    }
};
