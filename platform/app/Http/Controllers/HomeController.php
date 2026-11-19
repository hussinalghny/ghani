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

        return view('site.home', compact('featured', 'latest', 'categories', 'stats'));
    }
}
