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
        return view('admin.instructors.form', ['instructor' => new Instructor]);
    }

    public function store(Request $request)
    {
        Instructor::create($this->validated($request));

        return redirect()->route('admin.instructors.index')->with('success', 'تمت إضافة المدرب.');
    }

    public function edit(Instructor $instructor)
    {
        return view('admin.instructors.form', compact('instructor'));
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

    private function validated(Request $request, ?Instructor $instructor = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
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
