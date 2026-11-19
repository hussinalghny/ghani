<?php

namespace App\Http\Controllers;

use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $enrollments = $user->enrollments()->with(['course' => fn ($q) => $q->withCount('lessons')->with('instructor')])
            ->latest()->get()->filter(fn ($e) => $e->course);

        $doneCounts = LessonProgress::query()->where('user_id', $user->id)->whereNotNull('completed_at')
            ->selectRaw('course_id, count(*) as c')->groupBy('course_id')->pluck('c', 'course_id');

        return view('account.index', compact('user', 'enrollments', 'doneCounts'));
    }

    public function settings(Request $request)
    {
        $user = $request->user();
        $devices = $user->devices()->latest('last_seen_at')->get();

        return view('account.settings', compact('user', 'devices'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:40'],
        ], [], ['name' => 'الاسم', 'email' => 'البريد', 'city' => 'المحافظة']);
        $user->update($data);

        return back()->with('success', 'تم حفظ معلوماتك.');
    }

    public function password(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], ['current_password.current_password' => 'كلمة المرور الحالية غير صحيحة.'], ['password' => 'كلمة المرور']);

        $user->update(['password' => $request->input('password')]);
        $user->tokens()->delete(); // يطلع من التطبيق بكل الأجهزة

        return back()->with('success', 'تم تغيير كلمة المرور.');
    }
}
