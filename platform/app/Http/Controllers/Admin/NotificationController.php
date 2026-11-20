<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\FcmService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function create(FcmService $fcm)
    {
        return view('admin.notifications', [
            'configured' => $fcm->configured(),
            'project' => $fcm->projectId(),
            'courses' => Course::orderBy('title')->get(['id', 'title']),
            'devices' => \App\Models\PushToken::count(),
        ]);
    }

    public function credentials(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:64']]);
        $json = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        if (! is_array($json) || ($json['type'] ?? '') !== 'service_account' || empty($json['private_key'])) {
            return back()->with('error', 'هذا مو ملف Service Account. نزله من Firebase ← Project settings ← Service accounts ← Generate new private key.');
        }
        @mkdir(storage_path('app/private'), 0755, true);
        file_put_contents(storage_path('app/private/'.FcmService::FILE), json_encode($json));

        return back()->with('success', 'تم ربط Firebase بمشروع '.$json['project_id'].'.');
    }

    public function send(Request $request, FcmService $fcm)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:240'],
            'target' => ['required', 'in:all,course'],
            'course_id' => ['required_if:target,course', 'nullable', 'exists:courses,id'],
        ], [], ['title' => 'العنوان', 'body' => 'النص']);

        if ($data['target'] === 'all') {
            $ok = $fcm->toAll($data['title'], $data['body'], ['type' => 'broadcast']);

            return back()->with($ok ? 'success' : 'error', $ok ? 'تم إرسال الإشعار لكل مستخدمي التطبيق.' : 'تعذر الإرسال. تأكد من ملف Firebase.');
        }

        $course = Course::findOrFail($data['course_id']);
        $ids = $course->enrollments()->pluck('user_id')->all();
        $n = $fcm->toUsers($ids, $data['title'], $data['body'], ['type' => 'course', 'slug' => $course->slug]);

        return back()->with('success', "تم الإرسال إلى $n جهاز من طلاب {$course->title}.");
    }
}
