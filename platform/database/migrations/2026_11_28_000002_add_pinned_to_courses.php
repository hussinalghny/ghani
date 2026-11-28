<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** تثبيت كورس بأول «الأكثر تسجيلا» (الرئيسية بالتطبيق والموقع) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedTinyInteger('pin')->nullable()->after('is_featured'); // 1 = الأول، 2 = الثاني...
        });
    }

    public function down(): void
    {
        Schema::table('courses', fn (Blueprint $t) => $t->dropColumn('pin'));
    }
};
