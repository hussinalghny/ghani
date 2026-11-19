<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        return $this->listing($request);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active, 404);

        return $this->listing($request, $category);
    }

    private function listing(Request $request, ?Category $category = null)
    {
        $q = trim((string) $request->query('q'));

        $courses = Course::published()->with(['category', 'instructor'])->withCount('lessons')
            ->when($category, fn ($w) => $w->where('category_id', $category->id))
            ->when($q !== '', fn ($w) => $w->where(fn ($s) => $s->where('title', 'like', "%$q%")->orWhere('subtitle', 'like', "%$q%")))
            ->when($request->query('level'), fn ($w, $l) => $w->where('level', $l))
            ->orderByDesc('is_featured')->orderBy('sort')->latest()
            ->paginate(12)->withQueryString();

        $categories = Category::query()->where('is_active', true)->orderBy('sort')->get();

        return view('site.courses', compact('courses', 'categories', 'category', 'q'));
    }

    public function show(Request $request, Course $course, CertificateService $certs)
    {
        $user = $request->user();
        abort_unless($course->is_published || $user?->isAdmin(), 404);

        $course->load(['category', 'instructor', 'sections.lessons']);
        $lessonsCount = $course->sections->sum(fn ($s) => $s->lessons->count());
        $totalDuration = $course->sections->sum(fn ($s) => $s->lessons->sum('duration'));

        $enrolled = $user?->hasAccessTo($course) ?? false;
        $progress = $enrolled ? $certs->progressPercent($user, $course) : 0;
        $done = $enrolled
            ? LessonProgress::query()->where('user_id', $user->id)->where('course_id', $course->id)->whereNotNull('completed_at')->pluck('lesson_id')->all()
            : [];

        $related = Course::published()->where('category_id', $course->category_id)->whereKeyNot($course->id)
            ->withCount('lessons')->with('instructor')->take(3)->get();

        return view('site.course', compact('course', 'lessonsCount', 'totalDuration', 'enrolled', 'progress', 'done', 'related'));
    }

    public function enroll(Request $request, Course $course, EnrollmentService $service)
    {
        $service->purchase($request->user(), $course);

        return redirect()->route('learn.show', $course)
            ->with('success', 'تم تسجيلك في '.$course->title.'. ابدأ من الدرس الأول.');
    }
}
