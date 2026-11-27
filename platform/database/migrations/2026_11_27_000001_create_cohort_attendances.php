<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohort_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained('cohort_registrations')->cascadeOnDelete();
            $table->date('day');
            $table->timestamp('scanned_at');
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unique(['registration_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cohort_attendances');
    }
};
