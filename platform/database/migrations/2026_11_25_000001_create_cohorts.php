<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('seats')->default(50);
            $table->date('starts_on')->nullable();
            $table->string('schedule')->nullable();
            $table->string('location')->nullable();
            $table->string('map_url')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_open')->default(true);
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('cohort_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('job')->nullable();
            $table->string('level', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending'); // pending | paid | waitlist | cancelled
            $table->timestamp('paid_at')->nullable();
            $table->string('paid_by', 20)->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            $table->unique(['cohort_id', 'user_id']);
            $table->index(['cohort_id', 'status']);
        });

        Schema::table('payment_orders', function (Blueprint $table) {
            $table->foreignId('registration_id')->nullable()->after('course_id')->constrained('cohort_registrations')->nullOnDelete();
        });

        $course = DB::table('courses')->where('slug', 'smart-defense')->value('id');
        DB::table('cohorts')->insert([
            'slug' => 'smart-defense',
            'title' => 'الدفاع الذكي',
            'subtitle' => 'الأمن السيبراني بالذكاء الاصطناعي · كورس حضوري',
            'course_id' => $course,
            'price' => 200000,
            'seats' => 50,
            'schedule' => 'جلستين بالأسبوع · ساعتين لكل جلسة',
            'location' => 'بغداد',
            'image' => 'img/inperson/smart-defense.jpg',
            'is_open' => true,
            'details' => json_encode(\App\Models\Cohort::defaultDetails(), JSON_UNESCAPED_UNICODE),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('registration_id');
        });
        Schema::dropIfExists('cohort_registrations');
        Schema::dropIfExists('cohorts');
    }
};
