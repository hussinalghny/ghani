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

    /** الرئيسية بطلب واحد: الكتالوج محفوظ بالكاش 5 دقائق + «كمل التعلم» للمسجلين */
    public function home(Request $request)
    {
        $catalog = cache()->remember('api.home', 300, function () use ($request) {
            $base = fn () => Course::published()->with(['category', 'instructor'])->withCount(['lessons', 'enrollments']);

            return [
                'featured' => CourseResource::collection($base()->where('is_featured', true)->orderBy('sort')->take(6)->get())->toArray($request),
                // المثبتة أولا بترتيبها، وبعدها الأكثر تسجيلا
                'popular' => CourseResource::collection($base()->orderByRaw('pin is null')->orderBy('pin')->orderByDesc('enrollments_count')->take(10)->get())->toArray($request),
                'latest' => CourseResource::collection($base()->latest()->take(10)->get())->toArray($request),
                'categories' => $this->categoryList()->values()->all(),
            ];
        });

        $continue = null;
        if ($user = auth('sanctum')->user()) {
            $last = \App\Models\LessonProgress::where('user_id', $user->id)->latest('updated_at')->with('lesson:id,title,course_id', 'lesson.course')->first();
            if ($last?->lesson?->course && $user->hasAccessTo($last->lesson->course)) {
                $course = $last->lesson->course->loadCount('lessons');
                $continue = [
                    'course' => (new CourseResource($course->load('category')))->toArray($request),
                    'lesson' => ['id' => $last->lesson->id, 'title' => $last->lesson->title],
                    'progress' => app(\App\Services\CertificateService::class)->progressPercent($user, $course),
                ];
            }
        }

        return response()->json($catalog + ['continue' => $continue]);
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
        $course->load(['category', 'instructor', 'sections.lessons'])->loadCount(['lessons', 'enrollments']);
        $user = auth('sanctum')->user();
        $enrolled = $user ? $user->hasAccessTo($course) : false;
        $mine = $user ? \App\Models\CourseReview::where('course_id', $course->id)->where('user_id', $user->id)->first() : null;

        return response()->json([
            'data' => (new CourseResource($course))->toArray($request) + [
                'description' => $course->description,
                'outcomes' => $course->outcomesList(),
                'access_days' => $course->access_days,
                'total_duration' => $course->sections->sum(fn ($s) => $s->lessons->sum('duration')),
                'is_enrolled' => $enrolled,
                'reviews' => $course->reviews()->where('is_visible', true)->with('user:id,name')->latest()->take(5)->get()->map->toApi(),
                'rating_breakdown' => \App\Services\Community::breakdown($course),
                'my_review' => $mine?->toApi(),
                'can_review' => $enrolled && app(\App\Services\CertificateService::class)->progressPercent($user, $course) >= \App\Services\Community::REVIEW_MIN_PROGRESS,
                'review_min_progress' => \App\Services\Community::REVIEW_MIN_PROGRESS,
                'updated_at' => $course->updated_at?->toDateString(),
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
