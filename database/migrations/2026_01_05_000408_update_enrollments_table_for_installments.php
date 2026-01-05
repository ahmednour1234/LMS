<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Snapshot columns مرة واحدة (بدل hasColumn جوه closure)
        $cols = Schema::getColumnListing('enrollments');
        $has  = fn(string $c) => in_array($c, $cols, true);

        Schema::table('enrollments', function (Blueprint $table) use ($has) {

            // pricing_type
            if (!$has('pricing_type')) {
                // after status لو موجود، غير كده بدون after
                if ($has('status')) {
                    $table->enum('pricing_type', ['full', 'installment'])->default('full')->after('status');
                } else {
                    $table->enum('pricing_type', ['full', 'installment'])->default('full');
                }
            }

            // total_amount
            if (!$has('total_amount')) {
                if ($has('pricing_type')) {
                    $table->decimal('total_amount', 15, 2)->default(0)->after('pricing_type');
                } else {
                    $table->decimal('total_amount', 15, 2)->default(0);
                }
            }

            // progress_percent
            if (!$has('progress_percent')) {
                if ($has('total_amount')) {
                    $table->decimal('progress_percent', 5, 2)->default(0)->after('total_amount');
                } else {
                    $table->decimal('progress_percent', 5, 2)->default(0);
                }
            }

            // started_at
            if (!$has('started_at')) {
                // prefer after registered_at if exists, else just add
                if ($has('registered_at')) {
                    $table->timestamp('started_at')->nullable()->after('registered_at');
                } else {
                    $table->timestamp('started_at')->nullable();
                }
            }

            // completed_at
            if (!$has('completed_at')) {
                if ($has('started_at')) {
                    $table->timestamp('completed_at')->nullable()->after('started_at');
                } else {
                    $table->timestamp('completed_at')->nullable();
                }
            }

            // user_id + FK
            if (!$has('user_id')) {
                // IMPORTANT: do NOT use after('student_id') unless it exists
                $userId = $table->unsignedBigInteger('user_id')->nullable();

                // place it nicely if possible
                if ($has('student_id')) {
                    $userId->after('student_id');
                } elseif ($has('course_id')) {
                    $userId->after('course_id');
                } elseif ($has('id')) {
                    $userId->after('id');
                }

                // FK (if users table exists)
                if (Schema::hasTable('users')) {
                    $table->foreign('user_id', 'enrollments_user_fk')
                        ->references('id')->on('users')
                        ->nullOnDelete();
                }
            }
        });
    }

    public function down(): void
    {
        // Snapshot columns مرة واحدة
        $cols = Schema::getColumnListing('enrollments');
        $has  = fn(string $c) => in_array($c, $cols, true);

        Schema::table('enrollments', function (Blueprint $table) use ($has) {

            // user_id
            if ($has('user_id')) {
                // drop FK by name if exists (safer)
                try {
                    $table->dropForeign('enrollments_user_fk');
                } catch (\Throwable $e) {
                    // fallback
                    try { $table->dropForeign(['user_id']); } catch (\Throwable $e) {}
                }

                try {
                    $table->dropColumn('user_id');
                } catch (\Throwable $e) {}
            }

            if ($has('completed_at')) {
                try { $table->dropColumn('completed_at'); } catch (\Throwable $e) {}
            }

            if ($has('started_at')) {
                try { $table->dropColumn('started_at'); } catch (\Throwable $e) {}
            }

            if ($has('progress_percent')) {
                try { $table->dropColumn('progress_percent'); } catch (\Throwable $e) {}
            }

            if ($has('total_amount')) {
                try { $table->dropColumn('total_amount'); } catch (\Throwable $e) {}
            }

            if ($has('pricing_type')) {
                try { $table->dropColumn('pricing_type'); } catch (\Throwable $e) {}
            }
        });
    }
};
