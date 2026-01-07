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
        $cols = Schema::getColumnListing('enrollments');
        $has = fn(string $c) => in_array($c, $cols, true);

        Schema::table('enrollments', function (Blueprint $table) use ($has) {
            // Add enrollment_mode enum
            if (!$has('enrollment_mode')) {
                if ($has('registration_type')) {
                    $table->enum('enrollment_mode', ['course_full', 'per_session', 'trial'])
                        ->nullable()
                        ->after('registration_type');
                } elseif ($has('pricing_type')) {
                    $table->enum('enrollment_mode', ['course_full', 'per_session', 'trial'])
                        ->nullable()
                        ->after('pricing_type');
                } else {
                    $table->enum('enrollment_mode', ['course_full', 'per_session', 'trial'])
                        ->nullable();
                }
            }

            // Add sessions_purchased integer
            if (!$has('sessions_purchased')) {
                if ($has('enrollment_mode')) {
                    $table->integer('sessions_purchased')->nullable()->after('enrollment_mode');
                } else {
                    $table->integer('sessions_purchased')->nullable();
                }
            }

            // Add currency_code string
            if (!$has('currency_code')) {
                if ($has('sessions_purchased')) {
                    $table->string('currency_code', 3)->default('OMR')->after('sessions_purchased');
                } elseif ($has('total_amount')) {
                    $table->string('currency_code', 3)->default('OMR')->after('total_amount');
                } else {
                    $table->string('currency_code', 3)->default('OMR');
                }
            }

            // Add delivery_type enum (separate from registration_type for clarity)
            if (!$has('delivery_type')) {
                if ($has('currency_code')) {
                    $table->enum('delivery_type', ['online', 'onsite'])->nullable()->after('currency_code');
                } elseif ($has('registration_type')) {
                    $table->enum('delivery_type', ['online', 'onsite'])->nullable()->after('registration_type');
                } else {
                    $table->enum('delivery_type', ['online', 'onsite'])->nullable();
                }
            }
        });

        // Migrate existing data: set enrollment_mode = 'course_full' for existing enrollments
        if (Schema::hasTable('enrollments')) {
            \Illuminate\Support\Facades\DB::table('enrollments')
                ->whereNull('enrollment_mode')
                ->update(['enrollment_mode' => 'course_full']);

            // Map registration_type to delivery_type for existing enrollments
            \Illuminate\Support\Facades\DB::table('enrollments')
                ->whereNotNull('registration_type')
                ->whereNull('delivery_type')
                ->update([
                    'delivery_type' => \Illuminate\Support\Facades\DB::raw('registration_type')
                ]);

            // Set currency_code = 'OMR' for existing enrollments
            \Illuminate\Support\Facades\DB::table('enrollments')
                ->whereNull('currency_code')
                ->update(['currency_code' => 'OMR']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $cols = Schema::getColumnListing('enrollments');
        $has = fn(string $c) => in_array($c, $cols, true);

        Schema::table('enrollments', function (Blueprint $table) use ($has) {
            if ($has('delivery_type')) {
                $table->dropColumn('delivery_type');
            }

            if ($has('currency_code')) {
                $table->dropColumn('currency_code');
            }

            if ($has('sessions_purchased')) {
                $table->dropColumn('sessions_purchased');
            }

            if ($has('enrollment_mode')) {
                $table->dropColumn('enrollment_mode');
            }
        });
    }
};
