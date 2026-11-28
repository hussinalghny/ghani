<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\DiscountCode;
use Illuminate\Http\Request;

/** كل أكواد الخصم: للكورسات الإلكترونية والحضوري */
class DiscountController extends Controller
{
    public function index()
    {
        $codes = DiscountCode::with(['course:id,title,slug', 'cohort:id,title,slug'])->latest()->get();
        $courses = Course::orderBy('title')->get(['id', 'title', 'slug']);
        $cohorts = Cohort::orderByDesc('id')->get(['id', 'title', 'slug']);

        return view('admin.discounts', compact('codes', 'courses', 'cohorts'));
    }

    public function store(Request $request)
    {
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'code' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', 'unique:discount_codes,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'integer', 'min:1', $request->input('type') === 'percent' ? 'max:100' : 'max:100000000'],
            'target' => ['required', 'string', 'regex:/^(all|course|cohort|course:\d+|cohort:\d+)$/'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:120'],
        ], ['code.regex' => 'الكود حروف إنكليزية وأرقام فقط.'], ['code' => 'الكود', 'value' => 'قيمة الخصم', 'target' => 'ينطبق على']);

        [$scope, $id] = array_pad(explode(':', $data['target']), 2, null);
        abort_if($scope === 'course' && $id && ! Course::whereKey($id)->exists(), 422);
        abort_if($scope === 'cohort' && $id && ! Cohort::whereKey($id)->exists(), 422);
        unset($data['target']);

        DiscountCode::create($data + [
            'applies_to' => $scope,
            'course_id' => $scope === 'course' ? $id : null,
            'cohort_id' => $scope === 'cohort' ? $id : null,
        ]);

        return back()->with('success', 'انضاف كود الخصم '.$data['code'].'.');
    }

    public function toggle(DiscountCode $discount)
    {
        $discount->update(['is_active' => ! $discount->is_active]);

        return back()->with('success', $discount->is_active ? 'الكود مفعل.' : 'الكود متوقف.');
    }
}
