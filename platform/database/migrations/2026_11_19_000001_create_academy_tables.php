<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 40)->default('code');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('instructors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title')->nullable();
            $table->text('bio')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instructor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->text('outcomes')->nullable(); // سطر لكل نقطة: ماذا ستتعلم
            $table->string('cover')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('sale_price')->nullable();
            $table->string('level', 20)->default('beginner');
            $table->string('duration_label')->nullable(); // مثال: 4 أسابيع · 12 جلسة
            $table->unsignedSmallInteger('access_days')->nullable(); // مدة الاشتراك، فارغ = دائم
            $table->boolean('has_certificate')->default(true);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type', 10)->default('video'); // video | text | file
            $table->string('bunny_video_id', 64)->nullable();
            $table->unsignedInteger('duration')->default(0); // بالثواني
            $table->longText('body')->nullable();
            $table->string('attachment')->nullable();      // مسار داخل storage/app/private
            $table->string('attachment_name')->nullable();
            $table->boolean('is_free_preview')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_paid')->default(0);
            $table->string('source', 20)->default('wallet'); // wallet | voucher | admin | free
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'lesson_id']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // voucher | purchase | admin_credit | admin_debit | voucher_issue | refund
            $table->bigInteger('amount');       // موجب = إضافة، سالب = خصم
            $table->bigInteger('balance_after');
            $table->string('reference')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('amount');
            $table->unsignedInteger('quantity');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('cost')->default(0); // ما دفعه الموزع
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('voucher_batches')->cascadeOnDelete();
            $table->string('code', 32)->unique(); // بدون شرطات، أحرف كبيرة
            $table->unsignedInteger('amount');
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('active')->index(); // active | redeemed | disabled
            $table->timestamp('sold_at')->nullable();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_id', 100);
            $table->string('name')->nullable();
            $table->string('platform', 10)->default('web'); // web | android | ios
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_blocked')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'device_id']);
        });

        Schema::create('lesson_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();
            $table->timestamp('issued_at');
            $table->timestamps();
            $table->unique(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        foreach (['certificates', 'lesson_views', 'devices', 'vouchers', 'voucher_batches', 'wallet_transactions',
            'lesson_progress', 'enrollments', 'lessons', 'sections', 'courses', 'instructors', 'categories', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
