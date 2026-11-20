<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('instructors', 'commission_percent')) {
            Schema::table('instructors', function (Blueprint $table) {
                $table->unsignedTinyInteger('commission_percent')->default(50); // نسبة المدرب من الإيراد
            });
        }
    }

    public function down(): void
    {
        Schema::table('instructors', fn (Blueprint $t) => $t->dropColumn('commission_percent'));
    }
};
