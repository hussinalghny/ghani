<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /** إعدادات يقرأها التطبيق عند الفتح */
    public function config()
    {
        return response()->json([
            'name' => 'غني أكاديمي',
            'slogan' => 'تعلم. طبق. اشتغل.',
            'whatsapp' => config('academy.whatsapp'),
            'currency' => config('academy.currency'),
            'announcement' => setting('announcement') ?: null,
            'security' => [
                'prevent_screenshots' => true,   // Android: FLAG_SECURE / iOS: إخفاء الفيديو عند التسجيل
                'watermark' => (bool) setting('watermark'),
                'max_devices' => (int) setting('max_devices'),
            ],
            'app' => [
                'version' => setting('app_version') ?: '1.0.0',
                'download_page' => route('app'),
            ],
            'colors' => ['primary' => '#5A35D6', 'night' => '#1B1446', 'gold' => '#F5B83D', 'mist' => '#F1EDFF'],
        ]);
    }

    public function home()
    {
        $base = fn () => Course::published()->with(['category', 'instructor'])->withCount('lessons');

        return response()->json([
            'featured' => CourseResource::collection($base()->where('is_featured', true)->orderBy('sort')->take(6)->get()),
            'latest' => CourseResource::collection($base()->latest()->take(10)->get()),
            'categories' => $this->categoryList(),
        ]);
    }

    public function categories()
    {
        return response()->json(['data' => $this->categoryList()]);
    }

    private function categoryList()
    {
        return Category::where('is_active', true)->orderBy('sort')
            ->withCount(['courses' => fn ($q) => $q->where('is_published', true)])->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'icon' => $c->icon, 'description' => $c->description, 'courses_count' => $c->courses_count]);
    }

    public function courses(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $courses = Course::published()->with(['category', 'instructor'])->withCount('lessons')
            ->when($request->query('category'), fn ($w, $slug) => $w->whereHas('category', fn ($c) => $c->where('slug', $slug)))
            ->when($q !== '', fn ($w) => $w->where('title', 'like', "%$q%"))
            ->orderByDesc('is_featured')->orderBy('sort')->latest()->paginate(20);

        return CourseResource::collection($courses);
    }

    public function course(Request $request, Course $course)
    {
        abort_unless($course->is_published, 404);
        $course->load(['category', 'instructor', 'sections.lessons'])->loadCount('lessons');
        $user = auth('sanctum')->user();

        return response()->json([
            'data' => (new CourseResource($course))->toArray($request) + [
                'description' => $course->description,
                'outcomes' => $course->outcomesList(),
                'access_days' => $course->access_days,
                'total_duration' => $course->sections->sum(fn ($s) => $s->lessons->sum('duration')),
                'is_enrolled' => $user ? $user->hasAccessTo($course) : false,
                'sections' => $course->sections->map(fn ($s) => [
                    'id' => $s->id,
                    'title' => $s->title,
                    'lessons' => $s->lessons->map(fn ($l) => [
                        'id' => $l->id,
                        'title' => $l->title,
                        'type' => $l->type,
                        'duration' => $l->duration,
                        'is_free_preview' => $l->is_free_preview,
                        'has_attachment' => (bool) $l->attachment,
                    ]),
                ]),
            ],
        ]);
    }

    public function verifyCertificate(Certificate $certificate)
    {
        $certificate->load(['user:id,name', 'course:id,title']);

        return response()->json(['data' => [
            'code' => $certificate->code,
            'student' => $certificate->user->name,
            'course' => $certificate->course->title,
            'issued_at' => $certificate->issued_at->toDateString(),
            'url' => route('certificates.show', $certificate),
        ]]);
    }
}
