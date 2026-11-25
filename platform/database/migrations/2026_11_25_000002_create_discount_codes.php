<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->foreignId('cohort_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('percent'); // percent | fixed
            $table->unsignedInteger('value');
            $table->unsignedInteger('max_uses')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('cohort_registrations', function (Blueprint $table) {
            $table->foreignId('discount_code_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->unsignedInteger('price')->nullable()->after('discount_code_id');
        });
    }

    public function down(): void
    {
        Schema::table('cohort_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_code_id');
            $table->dropColumn('price');
        });
        Schema::dropIfExists('discount_codes');
    }
};
