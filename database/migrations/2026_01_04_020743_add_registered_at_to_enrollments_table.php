<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ لو الجدول مش موجود، اعتبر الميجريشن No-Op وما يوقعش migrate
        if (!Schema::hasTable('enrollments')) {
            return;
        }

        // ✅ أضف العمود لو مش موجود
        if (!Schema::hasColumn('enrollments', 'registered_at')) {
            Schema::table('enrollments', function (Blueprint $table) {
                // لو enrolled_at موجود نستخدم after(enrolled_at)، وإلا نخليه في الآخر
                if (Schema::hasColumn('enrollments', 'enrolled_at')) {
                    $table->timestamp('registered_at')->nullable()->after('enrolled_at');
                } else {
                    $table->timestamp('registered_at')->nullable();
                }
            });
        }

        // ✅ انسخ الداتا (بعد إضافة العمود) لو enrolled_at موجود
        if (Schema::hasColumn('enrollments', 'enrolled_at') && Schema::hasColumn('enrollments', 'registered_at')) {
            DB::statement("
                UPDATE `enrollments`
                SET `registered_at` = `enrolled_at`
                WHERE `registered_at` IS NULL
                  AND `enrolled_at` IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        // ✅ لو الجدول مش موجود، مفيش rollback
        if (!Schema::hasTable('enrollments')) {
            return;
        }

        if (Schema::hasColumn('enrollments', 'registered_at')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->dropColumn('registered_at');
            });
        }
    }
};
