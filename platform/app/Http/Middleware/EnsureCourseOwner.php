<?php

namespace App\Http\Middleware;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** الأدمن يمر دائما، والمدرب يعدل كورساته فقط */
class EnsureCourseOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user->isAdmin()) {
            return $next($request);
        }

        $route = $request->route();
        $course = $route->parameter('course');
        if (! $course && ($section = $route->parameter('section'))) {
            $course = ($section instanceof Section ? $section : Section::find($section))?->course;
        }
        if (! $course && ($lesson = $route->parameter('lesson'))) {
            $course = ($lesson instanceof Lesson ? $lesson : Lesson::find($lesson))?->course;
        }
        if ($course && ! $course instanceof Course) {
            $course = Course::where('slug', $course)->orWhere('id', $course)->first();
        }

        // رفع الفيديو بدون كورس محدد: يكفي إن عنده كورس واحد على الأقل
        $mine = Course::ownedBy($user)->pluck('id');
        if ($course ? ! $mine->contains($course->id) : $mine->isEmpty()) {
            abort(403, 'هذا الكورس ليس من كورساتك.');
        }

        return $next($request);
    }
}
