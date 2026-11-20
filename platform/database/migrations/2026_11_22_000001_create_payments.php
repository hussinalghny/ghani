<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method', 20)->default('qi');
            $table->unsignedInteger('base_amount');
            $table->unsignedInteger('amount')->index();          // المبلغ المميز اللي يحوله الطالب بالضبط
            $table->string('status', 12)->default('pending')->index(); // pending | paid | expired | cancelled
            $table->timestamp('expires_at');
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_by', 12)->nullable();             // auto | admin
            $table->timestamps();
        });

        Schema::create('payment_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->string('source', 12)->default('notification'); // notification | sms
            $table->string('package', 120)->nullable();
            $table->string('title')->nullable();
            $table->text('text');
            $table->unsignedInteger('amount')->nullable();
            $table->string('status', 12)->default('review')->index(); // matched | review | ignored
            $table->foreignId('order_id')->nullable()->constrained('payment_orders')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_notifications');
        Schema::dropIfExists('payment_orders');
    }
};
