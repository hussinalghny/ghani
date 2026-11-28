<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\AppDownloadController;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AppReleaseController extends Controller
{
    public function edit()
    {
        return view('admin.app', [
            'release' => AppDownloadController::release(),
            'banner' => \App\Http\Controllers\Api\CatalogController::banner(),
            'courses' => \App\Models\Course::published()->orderBy('title')->get(['id', 'title', 'slug']),
            'cohorts' => \App\Models\Cohort::orderByDesc('id')->get(['id', 'title', 'slug']),
        ]);
    }

    /** بنر الرئيسية بالتطبيق: النصوص، اللون، الصورة، ووين يودي */
    public function banner(Request $request)
    {
        $data = $request->validate([
            'banner_badge' => ['nullable', 'string', 'max:40'],
            'banner_title' => ['required', 'string', 'max:80'],
            'banner_text' => ['nullable', 'string', 'max:140'],
            'banner_button' => ['nullable', 'string', 'max:30'],
            'banner_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'banner_action' => ['required', 'string', 'regex:/^(courses|url|course:[\w-]+|cohort:[\w-]+)$/'],
            'banner_url' => ['nullable', 'required_if:banner_action,url', 'url:https', 'max:300'],
            'banner_image' => ['nullable', 'image', 'max:4096'],
        ], [], ['banner_title' => 'العنوان', 'banner_url' => 'الرابط', 'banner_image' => 'الصورة']);
        foreach (['banner_badge', 'banner_text', 'banner_button', 'banner_url'] as $k) {
            $data[$k] = (string) ($data[$k] ?? '');
        }
        unset($data['banner_image']);
        if ($request->hasFile('banner_image') || $request->boolean('banner_image_clear')) {
            if ($old = setting('banner_image')) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
            }
            $data['banner_image'] = $request->hasFile('banner_image') ? $request->file('banner_image')->store('banners', 'public') : '';
        }
        $data['banner_on'] = $request->boolean('banner_on') ? 1 : 0;
        Setting::put($data);

        return back()->with('success', 'انحفظ البنر. يظهر بالتطبيق أول ما يفتح الطالب الرئيسية.');
    }

    public function update(Request $request)
    {
        $request->validate([
            'version' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
            'android' => ['nullable', 'file', 'max:204800'],
            'ios' => ['nullable', 'file', 'max:204800'],
            'android_url' => ['nullable', 'url', 'max:500'],
            'ios_url' => ['nullable', 'url', 'max:500'],
            'windows' => ['nullable', 'file', 'max:204800'],
            'windows_url' => ['nullable', 'url', 'max:500'],
        ], [], ['android' => 'ملف APK', 'ios' => 'ملف IPA', 'windows' => 'ملف EXE']);

        $values = [
            'app_version' => $request->input('version'),
            'app_notes' => (string) $request->input('notes'),
            'app_android_url' => (string) $request->input('android_url'),
            'app_ios_url' => (string) $request->input('ios_url'),
            'app_windows_url' => (string) $request->input('windows_url'),
        ];

        foreach (['android' => 'apk', 'ios' => 'ipa', 'windows' => 'exe'] as $p => $ext) {
            if ($request->hasFile($p)) {
                $f = $request->file($p);
                if (strtolower($f->getClientOriginalExtension()) !== $ext) {
                    return back()->with('error', "الملف لازم يكون بصيغة .$ext");
                }
                if ($old = setting("app_{$p}_file")) {
                    Storage::disk('public')->delete($old);
                }
                $values["app_{$p}_file"] = $f->storeAs('apps', "ghani-academy-{$p}-".time().".$ext", 'public');
            }
            if ($request->boolean("remove_$p")) {
                if ($old = setting("app_{$p}_file")) {
                    Storage::disk('public')->delete($old);
                }
                $values["app_{$p}_file"] = '';
            }
        }

        $values['app_updated_at'] = now()->format('Y/m/d');
        Setting::put($values);

        return back()->with('success', 'تم نشر الإصدار '.$values['app_version'].'. صفحة التنزيل محدثة.');
    }
}
