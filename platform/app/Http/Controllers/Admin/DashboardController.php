<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Voucher;
use App\Models\WalletTransaction;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'students' => User::where('role', 'student')->count(),
            'new_students' => User::where('role', 'student')->where('created_at', '>', now()->subDays(7))->count(),
            'courses' => Course::count(),
            'enrollments' => Enrollment::count(),
            'sales_month' => (int) -WalletTransaction::where('type', 'purchase')->where('created_at', '>', now()->startOfMonth())->sum('amount'),
            'vouchers_redeemed_month' => (int) Voucher::where('status', 'redeemed')->where('redeemed_at', '>', now()->startOfMonth())->sum('amount'),
            'vouchers_active' => Voucher::where('status', 'active')->count(),
            'balances' => (int) User::sum('balance'),
        ];

        $topCourses = Course::withCount('enrollments')->orderByDesc('enrollments_count')->take(5)->get();
        $recent = Enrollment::with(['user:id,name,phone', 'course:id,title,slug'])->latest()->take(8)->get();

        // تسجيلات آخر 14 يوم للرسم
        $days = collect(range(13, 0))->map(fn ($d) => now()->subDays($d)->toDateString());
        $raw = Enrollment::where('created_at', '>=', now()->subDays(13)->startOfDay())
            ->selectRaw('DATE(created_at) as d, count(*) as c')->groupBy('d')->pluck('c', 'd');
        $chart = $days->map(fn ($d) => ['d' => $d, 'c' => (int) ($raw[$d] ?? 0)]);

        return view('admin.dashboard', compact('stats', 'topCourses', 'recent', 'chart'));
    }
}
