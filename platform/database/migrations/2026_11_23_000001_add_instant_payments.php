<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->timestamp('claimed_at')->nullable();          // الطالب ضغط «حولت» وانفتح الكورس مؤقتا
            $table->boolean('claim_failed')->default(false);      // انتهت المهلة وما وصل التحويل
        });
        Schema::table('payment_notifications', function (Blueprint $table) {
            $table->timestamp('posted_at')->nullable();           // وقت وصول الإشعار للموبايل (لقياس التأخير)
        });
    }

    public function down(): void
    {
        Schema::table('payment_orders', fn (Blueprint $t) => $t->dropColumn(['claimed_at', 'claim_failed']));
        Schema::table('payment_notifications', fn (Blueprint $t) => $t->dropColumn('posted_at'));
    }
};
