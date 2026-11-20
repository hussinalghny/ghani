<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InstructorController extends Controller
{
    public function index()
    {
        $instructors = Instructor::withCount('courses')->latest()->get();

        return view('admin.instructors.index', compact('instructors'));
    }

    public function create()
    {
        return view('admin.instructors.form', ['instructor' => new Instructor, 'accounts' => $this->accounts()]);
    }

    public function store(Request $request)
    {
        Instructor::create($this->validated($request));

        return redirect()->route('admin.instructors.index')->with('success', 'تمت إضافة المدرب.');
    }

    public function edit(Instructor $instructor)
    {
        return view('admin.instructors.form', ['instructor' => $instructor, 'accounts' => $this->accounts()]);
    }

    public function update(Request $request, Instructor $instructor)
    {
        $instructor->update($this->validated($request, $instructor));

        return redirect()->route('admin.instructors.index')->with('success', 'تم الحفظ.');
    }

    public function destroy(Instructor $instructor)
    {
        if ($instructor->photo) {
            Storage::disk('public')->delete($instructor->photo);
        }
        $instructor->delete();

        return back()->with('success', 'تم حذف المدرب.');
    }

    private function accounts()
    {
        return \App\Models\User::whereIn('role', ['instructor', 'admin'])->orderBy('name')->get(['id', 'name', 'phone', 'role']);
    }

    private function validated(Request $request, ?Instructor $instructor = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'user_id' => ['nullable', 'exists:users,id'],
            'commission_percent' => ['required', 'integer', 'min:0', 'max:100'],
        ], [], ['commission_percent' => 'نسبة المدرب']);
        $data['user_id'] = $data['user_id'] ?? null;
        if ($data['user_id']) {
            \App\Models\User::whereKey($data['user_id'])->where('role', 'student')->update(['role' => 'instructor']);
        }
        if ($request->hasFile('photo')) {
            if ($instructor?->photo) {
                Storage::disk('public')->delete($instructor->photo);
            }
            $data['photo'] = $request->file('photo')->store('instructors', 'public');
        } else {
            unset($data['photo']);
        }

        return $data;
    }
}
