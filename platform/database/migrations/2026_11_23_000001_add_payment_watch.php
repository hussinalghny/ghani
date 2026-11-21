<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** مراقبة غني باي: رصيد المحفظة بكل إشعار، وفترة فرق الرصيد (إشعار ضايع) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('balance')->nullable()->after('amount');
            $table->timestamp('gap_from')->nullable()->after('received_at');
        });
    }

    public function down(): void
    {
        Schema::table('payment_notifications', function (Blueprint $table) {
            $table->dropColumn(['balance', 'gap_from']);
        });
    }
};
