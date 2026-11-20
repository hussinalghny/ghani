<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Instructor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /** حقول ما يغيرها المدرب */
    public const ADMIN_ONLY = ['category_id', 'instructor_id', 'price', 'sale_price', 'access_days', 'sort'];

    public function index(Request $request)
    {
        $courses = Course::with(['category', 'instructor'])->withCount(['lessons', 'enrollments'])
            ->when($request->query('q'), fn ($q, $s) => $q->where('title', 'like', "%$s%"))
            ->orderBy('sort')->latest()->paginate(20)->withQueryString();

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        return view('admin.courses.form', $this->formData(new Course(['level' => 'beginner', 'has_certificate' => true])));
    }

    public function store(Request $request)
    {
        $course = Course::create($this->validated($request));

        return redirect()->route('admin.courses.curriculum', $course)->with('success', 'تم إنشاء الكورس. أضف الفصول والدروس.');
    }

    public function edit(Course $course)
    {
        return view('admin.courses.form', $this->formData($course));
    }

    public function update(Request $request, Course $course)
    {
        $admin = $request->user()->isAdmin();
        if (! $admin) {
            // المدرب يعدل المحتوى والنشر، والسعر والقسم والترتيب بيد الإدارة
            $request->merge($course->only(self::ADMIN_ONLY) + [
                'is_featured' => $course->is_featured ? '1' : null,
                'slug' => $course->slug,
            ]);
        }
        $course->update($this->validated($request, $course));

        return $admin
            ? redirect()->route('admin.courses.index')->with('success', 'تم حفظ الكورس.')
            : redirect()->route('teach')->with('success', 'تم حفظ الكورس.');
    }

    public function destroy(Course $course)
    {
        if ($course->enrollments()->exists()) {
            return back()->with('error', 'الكورس عليه مشتركين. أخفه بدل الحذف.');
        }
        if ($course->cover) {
            Storage::disk('public')->delete($course->cover);
        }
        $course->delete();

        return back()->with('success', 'تم حذف الكورس.');
    }

    public function curriculum(Course $course)
    {
        $course->load('sections.lessons');

        return view('admin.courses.curriculum', compact('course'));
    }

    private function formData(Course $course): array
    {
        return [
            'course' => $course,
            'categories' => Category::orderBy('sort')->pluck('name', 'id'),
            'instructors' => Instructor::orderBy('name')->pluck('name', 'id'),
        ];
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:150', 'alpha_dash', Rule::unique('courses')->ignore($course?->id)],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'instructor_id' => ['nullable', 'exists:instructors,id'],
            'description' => ['nullable', 'string'],
            'outcomes' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'sale_price' => ['nullable', 'integer', 'min:0'],
            'level' => ['required', Rule::in(array_keys(Course::LEVELS))],
            'duration_label' => ['nullable', 'string', 'max:80'],
            'access_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'sort' => ['nullable', 'integer'],
            'cover' => ['nullable', 'image', 'max:4096'],
        ], [], ['title' => 'العنوان', 'price' => 'السعر', 'category_id' => 'القسم']);

        $data['slug'] = $data['slug'] ?: (Str::slug($data['title']) ?: 'course-'.Str::lower(Str::random(6)));
        $data['sort'] = $data['sort'] ?? 0;
        foreach (['is_published', 'is_featured', 'has_certificate'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        if ($request->hasFile('cover')) {
            if ($course?->cover) {
                Storage::disk('public')->delete($course->cover);
            }
            $data['cover'] = $request->file('cover')->store('courses', 'public');
        } else {
            unset($data['cover']);
        }

        return $data;
    }
}
