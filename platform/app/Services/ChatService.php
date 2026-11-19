<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Conversation;
use App\Models\Course;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** المحادثات المباشرة: طالب ↔ الدعم، وطالب ↔ مدرب الكورس */
class ChatService
{
    public function support(User $user): Conversation
    {
        return Conversation::query()->firstOrCreate(['user_id' => $user->id, 'type' => 'support']);
    }

    public function withInstructor(User $user, Course $course): Conversation
    {
        if (! $user->hasAccessTo($course)) {
            throw new AcademyException('اشترك بالكورس حتى تكدر تراسل المدرب.', 'not_enrolled', 403);
        }
        $instructor = $course->instructor;
        if (! $instructor || ! $instructor->user_id) {
            throw new AcademyException('محادثة المدرب غير متاحة لهذا الكورس حاليا. راسل الدعم.', 'instructor_unavailable');
        }

        return Conversation::query()->firstOrCreate(
            ['user_id' => $user->id, 'type' => 'instructor', 'instructor_id' => $instructor->id],
            ['course_id' => $course->id]
        );
    }

    /** محادثات يقدر يشوفها الموظف (الأدمن: الكل، المدرب: محادثاته فقط) */
    public function staffScope(User $staff): Builder
    {
        $q = Conversation::query()->with(['user:id,name,phone', 'instructor:id,name', 'course:id,title', 'lastMessage']);
        if ($staff->isInstructor()) {
            $q->whereHas('instructor', fn ($i) => $i->where('user_id', $staff->id));
        }

        return $q;
    }

    public function canAccess(User $user, Conversation $c): bool
    {
        if ($c->user_id === $user->id || $user->isAdmin()) {
            return true;
        }

        return $user->isInstructor() && $c->type === 'instructor' && optional($c->instructor)->user_id === $user->id;
    }

    public function authorize(User $user, Conversation $c): void
    {
        if (! $this->canAccess($user, $c)) {
            throw new AcademyException('ما عندك صلاحية لهذه المحادثة.', 'forbidden', 403);
        }
    }

    public function send(User $user, Conversation $c, string $body): Message
    {
        $this->authorize($user, $c);
        $body = trim($body);
        if ($body === '') {
            throw new AcademyException('اكتب رسالتك.', 'empty');
        }

        return DB::transaction(function () use ($user, $c, $body) {
            $m = $c->messages()->create(['sender_id' => $user->id, 'body' => mb_substr($body, 0, 4000)]);
            $student = $c->user_id === $user->id;
            $c->forceFill(['last_message_at' => now()])->save();
            $c->increment($student ? 'staff_unread' : 'student_unread');

            return $m;
        });
    }

    public function markRead(User $user, Conversation $c): void
    {
        $col = $c->user_id === $user->id ? 'student_unread' : 'staff_unread';
        if ($c->{$col} > 0) {
            $c->forceFill([$col => 0])->save();
        }
    }

    public function messages(Conversation $c, int $after = 0, int $limit = 100)
    {
        return $c->messages()->with('sender:id,name,role')->where('id', '>', $after)
            ->orderBy('id', $after ? 'asc' : 'desc')->take($limit)->get()->sortBy('id')->values();
    }

    public function present(Message $m, User $viewer): array
    {
        return [
            'id' => $m->id,
            'body' => $m->body,
            'mine' => $m->sender_id === $viewer->id,
            'sender' => $m->sender->name ?? '',
            'staff' => in_array($m->sender->role ?? '', ['admin', 'instructor'], true),
            'time' => $m->created_at->format('H:i'),
            'date' => $m->created_at->toDateString(),
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }

    public function presentConversation(Conversation $c, User $viewer): array
    {
        $student = $c->user_id === $viewer->id;

        return [
            'id' => $c->id,
            'type' => $c->type,
            'title' => $student ? $c->title() : ($c->user->name ?? 'طالب'),
            'subtitle' => $student ? $c->subtitle() : ($c->type === 'instructor' ? $c->course?->title : 'الدعم'),
            'last' => $c->lastMessage?->body,
            'unread' => $student ? $c->student_unread : $c->staff_unread,
            'last_message_at' => $c->last_message_at?->toIso8601String(),
        ];
    }
}
