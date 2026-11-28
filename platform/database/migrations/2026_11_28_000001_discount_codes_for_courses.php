<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** أكواد الخصم تشتغل للكورسات الإلكترونية هم، ونربط الاشتراك والطلب بالكود للتقارير */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->string('applies_to', 10)->default('cohort')->after('code'); // cohort | course | all
            $table->foreignId('course_id')->nullable()->after('cohort_id')->constrained()->nullOnDelete();
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('discount_code_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('discount_code_id')->nullable()->after('registration_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', fn (Blueprint $t) => $t->dropConstrainedForeignId('discount_code_id'));
        Schema::table('enrollments', fn (Blueprint $t) => $t->dropConstrainedForeignId('discount_code_id'));
        Schema::table('discount_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_id');
            $table->dropColumn('applies_to');
        });
    }
};
