<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Section;
use App\Services\BunnyStream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CurriculumController extends Controller
{
    public function storeSection(Request $request, Course $course)
    {
        $data = $request->validate(['title' => ['required', 'string', 'max:150']]);
        $course->sections()->create($data + ['sort' => (int) $course->sections()->max('sort') + 1]);

        return back()->with('success', 'تمت إضافة الفصل.');
    }

    public function updateSection(Request $request, Section $section)
    {
        $section->update($request->validate(['title' => ['required', 'string', 'max:150']]));

        return back()->with('success', 'تم حفظ الفصل.');
    }

    public function destroySection(Section $section)
    {
        foreach ($section->lessons as $lesson) {
            $this->deleteAttachment($lesson);
        }
        $section->delete();

        return back()->with('success', 'تم حذف الفصل ودروسه.');
    }

    public function createLesson(Request $request, Course $course, BunnyStream $bunny)
    {
        $lesson = new Lesson(['type' => 'video', 'section_id' => $request->query('section')]);

        return view('admin.courses.lesson', ['course' => $course->load('sections'), 'lesson' => $lesson, 'bunny' => $bunny->configured()]);
    }

    public function storeLesson(Request $request, Course $course, BunnyStream $bunny)
    {
        $data = $this->validated($request, $course, $bunny);
        $data['sort'] = (int) Lesson::where('section_id', $data['section_id'])->max('sort') + 1;
        $course->lessons()->create($data);

        return redirect()->route('admin.courses.curriculum', $course)->with('success', 'تمت إضافة الدرس.');
    }

    public function editLesson(Lesson $lesson, BunnyStream $bunny)
    {
        return view('admin.courses.lesson', ['course' => $lesson->course->load('sections'), 'lesson' => $lesson, 'bunny' => $bunny->configured()]);
    }

    public function updateLesson(Request $request, Lesson $lesson, BunnyStream $bunny)
    {
        $lesson->update($this->validated($request, $lesson->course, $bunny, $lesson));

        return redirect()->route('admin.courses.curriculum', $lesson->course)->with('success', 'تم حفظ الدرس.');
    }

    public function destroyLesson(Lesson $lesson)
    {
        $this->deleteAttachment($lesson);
        $lesson->delete();

        return back()->with('success', 'تم حذف الدرس.');
    }

    /** يستقبل الترتيب الجديد بعد السحب والإفلات: sections[] و lessons[section_id][] */
    public function reorder(Request $request, Course $course)
    {
        foreach ((array) $request->input('sections', []) as $i => $id) {
            Section::where('course_id', $course->id)->whereKey($id)->update(['sort' => $i]);
        }
        foreach ((array) $request->input('lessons', []) as $sectionId => $ids) {
            if (! Section::where('course_id', $course->id)->whereKey($sectionId)->exists()) {
                continue;
            }
            foreach ((array) $ids as $i => $id) {
                Lesson::where('course_id', $course->id)->whereKey($id)->update(['sort' => $i, 'section_id' => $sectionId]);
            }
        }

        return response()->json(['ok' => true]);
    }

    public function bunnyPrepare(Request $request, BunnyStream $bunny)
    {
        $request->validate(['title' => ['required', 'string', 'max:200']]);

        return response()->json($bunny->prepareUpload($request->input('title')));
    }

    private function validated(Request $request, Course $course, BunnyStream $bunny, ?Lesson $lesson = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'section_id' => ['required', Rule::exists('sections', 'id')->where('course_id', $course->id)],
            'type' => ['required', Rule::in(array_keys(Lesson::TYPES))],
            'bunny_video_id' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-]+$/'],
            'video_path' => ['nullable', 'string', 'max:200', 'regex:/^(r2:)?videos\/[0-9]{4}-[0-9]{2}\/[a-z0-9]+\.(mp4|m4v|webm|mov)$/'],
            'duration_min' => ['nullable', 'numeric', 'min:0'],
            'body' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:51200', 'mimes:pdf,zip,rar,docx,pptx,xlsx,txt,png,jpg'],
        ], [], ['title' => 'العنوان', 'section_id' => 'الفصل']);

        $data['is_free_preview'] = $request->boolean('is_free_preview');

        // فيديو مرفوع على سيرفرنا: نتأكد إنه موجود، ونحذف القديم إذا تبدل
        if (! empty($data['video_path']) && ! \App\Services\R2::isR2($data['video_path']) && ! is_file(storage_path('app/private/'.$data['video_path']))) {
            unset($data['video_path']);
        }
        if ($lesson && $lesson->video_path && (($data['video_path'] ?? $lesson->video_path) !== $lesson->video_path || $request->boolean('remove_video'))) {
            self::dropVideo($lesson->video_path);
        }
        if ($request->boolean('remove_video')) {
            $data['video_path'] = null;
        } elseif (empty($data['video_path'])) {
            unset($data['video_path']);
        }
        $data['duration'] = (int) round(((float) ($data['duration_min'] ?? 0)) * 60);
        unset($data['duration_min']);

        // جلب المدة تلقائيا من Bunny إذا ما انكتبت
        if ($data['type'] === 'video' && ! empty($data['bunny_video_id']) && ! $data['duration']) {
            $info = $bunny->videoInfo($data['bunny_video_id']);
            $data['duration'] = $info['length'] ?? 0;
        }

        if ($request->hasFile('attachment')) {
            if ($lesson) {
                $this->deleteAttachment($lesson);
            }
            $file = $request->file('attachment');
            $data['attachment'] = $file->store('lessons/'.$course->id, 'local');
            $data['attachment_name'] = $file->getClientOriginalName();
        } else {
            unset($data['attachment']);
        }
        if ($lesson && $request->boolean('remove_attachment')) {
            $this->deleteAttachment($lesson);
            $data['attachment'] = null;
            $data['attachment_name'] = null;
        }

        return $data;
    }

    /** يحذف ملف الفيديو من سيرفرنا أو من R2 */
    public static function dropVideo(string $path): void
    {
        if (\App\Services\R2::isR2($path)) {
            try {
                \App\Services\R2::delete(\App\Services\R2::key($path));
            } catch (\Throwable $e) {
                report($e);
            }

            return;
        }
        @unlink(storage_path('app/private/'.$path));
    }

    private function deleteAttachment(Lesson $lesson): void
    {
        if ($lesson->video_path && ! request()->isMethod('put')) {
            self::dropVideo($lesson->video_path);
        }
        if ($lesson->attachment) {
            Storage::disk('local')->delete($lesson->attachment);
        }
    }
}
