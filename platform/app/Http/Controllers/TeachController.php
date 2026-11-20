<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Instructor;
use App\Models\LessonProgress;
use Illuminate\Http\Request;

/** لوحة المدرب: إحصائيات كورساته وإيراداته ونسبته */
class TeachController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            $profiles = Instructor::whereKey($request->integer('instructor') ?: Instructor::min('id'))->get();
        } else {
            $profiles = Instructor::where('user_id', $user->id)->get();
        }
        $instructor = $profiles->first();
        $percent = (int) ($instructor->commission_percent ?? 50);
        $ids = $profiles->pluck('id');

        $courses = Course::whereIn('instructor_id', $ids)
            ->withCount(['lessons', 'enrollments',
                'enrollments as month_count' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth()),
                'enrollments as completed_count' => fn ($q) => $q->whereNotNull('completed_at'),
            ])
            ->withSum('enrollments as revenue', 'price_paid')
            ->withSum(['enrollments as month_revenue' => fn ($q) => $q->where('created_at', '>=', now()->startOfMonth())], 'price_paid')
            ->orderBy('sort')->get();
        $courseIds = $courses->pluck('id');

        $base = Enrollment::whereIn('course_id', $courseIds);
        $stats = [
            'students' => (clone $base)->distinct()->count('user_id'),
            'enrollments' => (int) $courses->sum('enrollments_count'),
            'month' => (int) $courses->sum('month_count'),
            'revenue' => (int) $courses->sum('revenue'),
            'month_revenue' => (int) $courses->sum('month_revenue'),
            'paid' => (clone $base)->where('price_paid', '>', 0)->count(),
            'active_week' => LessonProgress::whereIn('course_id', $courseIds)->where('updated_at', '>=', now()->subDays(7))->distinct()->count('user_id'),
        ];
        $stats['share'] = (int) round($stats['revenue'] * $percent / 100);
        $stats['month_share'] = (int) round($stats['month_revenue'] * $percent / 100);

        // آخر 6 أشهر
        $months = collect(range(5, 0))->map(function ($i) use ($courseIds, $percent) {
            $start = now()->startOfMonth()->subMonths($i);
            $q = Enrollment::whereIn('course_id', $courseIds)->whereBetween('created_at', [$start, (clone $start)->endOfMonth()]);
            $rev = (int) (clone $q)->sum('price_paid');

            return ['label' => $start->format('Y/m'), 'count' => (clone $q)->count(), 'revenue' => $rev, 'share' => (int) round($rev * $percent / 100)];
        });

        $sources = (clone $base)->selectRaw('source, count(*) c')->groupBy('source')->pluck('c', 'source');

        $recent = Enrollment::with(['user:id,name', 'course:id,title'])->whereIn('course_id', $courseIds)->latest()->limit(15)->get();

        return view('teach.index', compact('instructor', 'profiles', 'percent', 'courses', 'stats', 'months', 'sources', 'recent'));
    }
}
