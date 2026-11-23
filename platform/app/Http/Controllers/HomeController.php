<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Course;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        $featured = Course::published()->with(['category', 'instructor'])->withCount('lessons')
            ->where('is_featured', true)->orderBy('sort')->latest()->take(6)->get();

        $latest = Course::published()->with(['category', 'instructor'])->withCount('lessons')
            ->latest()->take(8)->get();

        $categories = Category::query()->where('is_active', true)->orderBy('sort')
            ->withCount(['courses' => fn ($q) => $q->where('is_published', true)])->get();

        $stats = cache()->remember('home.stats', 600, fn () => [
            'courses' => Course::published()->count(),
            'students' => User::query()->where('role', 'student')->count(),
        ]);

        $popular = Course::published()->with(['category', 'instructor'])->withCount(['lessons', 'enrollments'])
            ->orderByDesc('enrollments_count')->orderByDesc('is_featured')->take(8)->get();

        $continue = null;
        if ($user = auth()->user()) {
            $last = \App\Models\LessonProgress::where('user_id', $user->id)->latest('updated_at')->with('lesson.course')->first();
            if ($last?->lesson?->course && $user->hasAccessTo($last->lesson->course)) {
                $continue = ['lesson' => $last->lesson, 'course' => $last->lesson->course,
                    'progress' => app(\App\Services\CertificateService::class)->progressPercent($user, $last->lesson->course)];
            }
        }

        return view('site.home', compact('featured', 'latest', 'categories', 'stats', 'popular', 'continue'));
    }
}
