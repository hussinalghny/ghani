<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_path')->nullable()->after('bunny_video_id'); // فيديو على سيرفرنا (مجاني)
        });

        Schema::table('instructors', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete(); // حساب المدرب للمحادثة
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // الطالب
            $table->string('type', 12)->default('support');                  // support | instructor
            $table->foreignId('instructor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('course_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->unsignedInteger('student_unread')->default(0);
            $table->unsignedInteger('staff_unread')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'type']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::table('instructors', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
        Schema::table('lessons', fn (Blueprint $t) => $t->dropColumn('video_path'));
    }
};
