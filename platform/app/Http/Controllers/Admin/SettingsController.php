<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\BunnyStream;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function edit(BunnyStream $bunny)
    {
        return view('admin.settings', ['bunny' => $bunny->configured()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'max_devices' => ['required', 'integer', 'min:0', 'max:10'],
            'lesson_opens_per_hour' => ['required', 'integer', 'min:0', 'max:1000'],
            'voucher_denominations' => ['required', 'string', 'regex:/^\d+(,\s*\d+)*$/'],
            'reseller_discount' => ['required', 'integer', 'min:0', 'max:60'],
            'announcement' => ['nullable', 'string', 'max:200'],
        ]);
        $data['voucher_denominations'] = preg_replace('/\s+/', '', $data['voucher_denominations']);
        $data['single_session'] = $request->boolean('single_session') ? 1 : 0;
        $data['watermark'] = $request->boolean('watermark') ? 1 : 0;
        foreach (['web', 'app', 'desktop'] as $ch) {
            $data['video_'.$ch] = $request->boolean('video_'.$ch) ? 1 : 0;
        }
        $data['announcement'] = $data['announcement'] ?? '';
        Setting::put($data);

        return back()->with('success', 'تم حفظ الإعدادات.');
    }
}
