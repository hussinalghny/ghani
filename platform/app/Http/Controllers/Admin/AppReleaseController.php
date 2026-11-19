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
        return view('admin.app', ['release' => AppDownloadController::release()]);
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
        ], [], ['android' => 'ملف APK', 'ios' => 'ملف IPA']);

        $values = [
            'app_version' => $request->input('version'),
            'app_notes' => (string) $request->input('notes'),
            'app_android_url' => (string) $request->input('android_url'),
            'app_ios_url' => (string) $request->input('ios_url'),
        ];

        foreach (['android' => 'apk', 'ios' => 'ipa'] as $p => $ext) {
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
