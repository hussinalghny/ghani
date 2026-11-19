<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public const ICONS = ['code' => 'برمجة', 'shield' => 'أمن', 'phone' => 'تطبيقات', 'cpu' => 'ذكاء اصطناعي', 'database' => 'قواعد بيانات', 'chart' => 'تحليل', 'pen' => 'تصميم', 'globe' => 'ويب', 'book' => 'عام'];

    public function index()
    {
        $categories = Category::withCount('courses')->orderBy('sort')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.form', ['category' => new Category(['is_active' => true]), 'icons' => self::ICONS]);
    }

    public function store(Request $request)
    {
        Category::create($this->validated($request));

        return redirect()->route('admin.categories.index')->with('success', 'تمت إضافة القسم.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', ['category' => $category, 'icons' => self::ICONS]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.categories.index')->with('success', 'تم حفظ القسم.');
    }

    public function destroy(Category $category)
    {
        if ($category->courses()->exists()) {
            return back()->with('error', 'القسم فيه كورسات. انقلها أولا.');
        }
        $category->delete();

        return back()->with('success', 'تم حذف القسم.');
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'slug' => ['nullable', 'string', 'max:80', 'alpha_dash', Rule::unique('categories')->ignore($category?->id)],
            'icon' => ['required', Rule::in(array_keys(self::ICONS))],
            'description' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'integer'],
        ]);
        $data['slug'] = $data['slug'] ?: (Str::slug($data['name']) ?: 'cat-'.Str::lower(Str::random(6)));
        $data['sort'] = $data['sort'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
