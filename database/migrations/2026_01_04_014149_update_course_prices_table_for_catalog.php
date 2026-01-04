<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // ---------- Helpers ----------
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

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if ($this->indexExists($table, $indexName)) {
            DB::statement("ALTER TABLE `$table` DROP INDEX `$indexName`");
        }
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
        // 1) Rename only if destination does NOT exist
        $hasOld = Schema::hasTable('course_branch_prices');
        $hasNew = Schema::hasTable('course_prices');

        if ($hasOld && !$hasNew) {
            Schema::rename('course_branch_prices', 'course_prices');
            $hasNew = true;
        }

        // لو course_prices مش موجود أصلاً، مفيش حاجة نعدلها
        if (!$hasNew) {
            return;
        }

        /**
         * 2) Drop old FK/unique safely (names may vary)
         */
        // FK default name could be: course_prices_branch_id_foreign
        $this->dropForeignIfExists('course_prices', 'course_prices_branch_id_foreign');
        // or from old table rename attempts:
        $this->dropForeignIfExists('course_prices', 'course_branch_prices_branch_id_foreign');
        // or custom:
        $this->dropForeignIfExists('course_prices', 'cbp_branch_fk');

        // unique old might be one of these:
        $this->dropIndexIfExists('course_prices', 'course_prices_course_id_branch_id_unique');
        $this->dropIndexIfExists('course_prices', 'course_branch_prices_course_id_branch_id_unique');
        $this->dropIndexIfExists('course_prices', 'cbp_course_branch_unique');

        // Also drop any previously-created new unique if rerun
        $this->dropIndexIfExists('course_prices', 'course_prices_unique');

        /**
         * 3) Ensure columns exist & apply changes
         */
        // add columns if missing
        Schema::table('course_prices', function (Blueprint $table) {
            if (!Schema::hasColumn('course_prices', 'delivery_type')) {
                $table->enum('delivery_type', ['onsite', 'online', 'virtual'])->nullable()->after('branch_id');
            }
            if (!Schema::hasColumn('course_prices', 'min_down_payment')) {
                $table->decimal('min_down_payment', 12, 2)->nullable()->after('is_installment_enabled');
            }
            if (!Schema::hasColumn('course_prices', 'max_installments')) {
                $table->integer('max_installments')->nullable()->after('min_down_payment');
            }

            // rename column only if source exists and target not exists
            if (Schema::hasColumn('course_prices', 'is_installment_enabled') && !Schema::hasColumn('course_prices', 'allow_installments')) {
                $table->renameColumn('is_installment_enabled', 'allow_installments');
            }
        });

        /**
         * 4) Make branch_id nullable (only if column exists)
         * IMPORTANT: change() requires doctrine/dbal in many setups.
         * To avoid breaking, we run raw SQL for MySQL/MariaDB.
         */
        if (Schema::hasColumn('course_prices', 'branch_id')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                // branch_id should be BIGINT UNSIGNED NULL
                DB::statement("ALTER TABLE `course_prices` MODIFY `branch_id` BIGINT UNSIGNED NULL");
            } else {
                // fallback
                Schema::table('course_prices', function (Blueprint $table) {
                    // may require doctrine/dbal depending on env
                    $table->unsignedBigInteger('branch_id')->nullable()->change();
                });
            }
        }

        /**
         * 5) Recreate FK for branch_id as nullable + nullOnDelete (only if branches exists)
         */
        if (Schema::hasTable('branches') && Schema::hasColumn('course_prices', 'branch_id')) {
            if (!$this->foreignKeyExists('course_prices', 'course_prices_branch_id_foreign')) {
                Schema::table('course_prices', function (Blueprint $table) {
                    $table->foreign('branch_id', 'course_prices_branch_id_foreign')
                        ->references('id')
                        ->on('branches')
                        ->nullOnDelete();
                });
            }
        }

        /**
         * 6) Add new unique (course_id, branch_id, delivery_type)
         */
        if (!$this->indexExists('course_prices', 'course_prices_unique')) {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->unique(['course_id', 'branch_id', 'delivery_type'], 'course_prices_unique');
            });
        }
    }

    public function down(): void
    {
        // rollback target table could be either
        if (!Schema::hasTable('course_prices') && Schema::hasTable('course_branch_prices')) {
            return;
        }

        if (!Schema::hasTable('course_prices')) {
            return;
        }

        // drop new unique safely
        $this->dropIndexIfExists('course_prices', 'course_prices_unique');

        // drop added columns if exist
        Schema::table('course_prices', function (Blueprint $table) {
            if (Schema::hasColumn('course_prices', 'delivery_type')) {
                $table->dropColumn('delivery_type');
            }
            if (Schema::hasColumn('course_prices', 'min_down_payment')) {
                $table->dropColumn('min_down_payment');
            }
            if (Schema::hasColumn('course_prices', 'max_installments')) {
                $table->dropColumn('max_installments');
            }

            // rename back if needed
            if (Schema::hasColumn('course_prices', 'allow_installments') && !Schema::hasColumn('course_prices', 'is_installment_enabled')) {
                $table->renameColumn('allow_installments', 'is_installment_enabled');
            }
        });

        // drop FK then make branch_id not null and cascade
        $this->dropForeignIfExists('course_prices', 'course_prices_branch_id_foreign');

        if (Schema::hasColumn('course_prices', 'branch_id')) {
            $driver = DB::getDriverName();

            if ($driver === 'mysql' || $driver === 'mariadb') {
                DB::statement("ALTER TABLE `course_prices` MODIFY `branch_id` BIGINT UNSIGNED NOT NULL");
            } else {
                Schema::table('course_prices', function (Blueprint $table) {
                    $table->unsignedBigInteger('branch_id')->nullable(false)->change();
                });
            }
        }

        // restore old unique (course_id, branch_id)
        if (!$this->indexExists('course_prices', 'course_prices_course_id_branch_id_unique')) {
            Schema::table('course_prices', function (Blueprint $table) {
                $table->unique(['course_id', 'branch_id'], 'course_prices_course_id_branch_id_unique');
            });
        }

        // restore FK cascade (if branches exists)
        if (Schema::hasTable('branches') && Schema::hasColumn('course_prices', 'branch_id')) {
            if (!$this->foreignKeyExists('course_prices', 'course_prices_branch_id_foreign')) {
                Schema::table('course_prices', function (Blueprint $table) {
                    $table->foreign('branch_id', 'course_prices_branch_id_foreign')
                        ->references('id')
                        ->on('branches')
                        ->cascadeOnDelete();
                });
            }
        }

        // rename back only if target does not exist
        if (Schema::hasTable('course_prices') && !Schema::hasTable('course_branch_prices')) {
            Schema::rename('course_prices', 'course_branch_prices');
        }
    }
};
