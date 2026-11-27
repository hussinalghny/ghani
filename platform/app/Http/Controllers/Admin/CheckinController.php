<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\CohortAttendance;
use App\Models\CohortRegistration;
use Illuminate\Http\Request;

/** مسح تذاكر الكورس الحضوري عند الباب: يسجل حضور اليوم لكل طالب */
class CheckinController extends Controller
{
    public function index(Cohort $cohort)
    {
        return view('admin.checkin', ['cohort' => $cohort, 'today' => $this->todayCount($cohort), 'paid' => $cohort->paidCount()]);
    }

    public function scan(Request $request, Cohort $cohort)
    {
        $raw = trim((string) $request->input('code'));
        // الكاميرا تقرا رابط التذكرة كامل، أو يكتب الرمز يدويا
        if (preg_match('/GA-[A-Z0-9]{6}/i', $raw, $m)) {
            $raw = strtoupper($m[0]);
        }
        $reg = CohortRegistration::where('code', strtoupper($raw))->first();
        $out = fn (string $state, string $msg, ?CohortRegistration $r = null) => response()->json([
            'state' => $state, 'message' => $msg, 'name' => $r?->name, 'phone' => $r?->phone, 'code' => $r?->code,
            'days' => $r ? $r->attendances()->count() : null, 'today' => $this->todayCount($cohort),
        ]);

        if (! $reg) {
            return $out('bad', 'التذكرة غير موجودة.');
        }
        if ($reg->cohort_id !== $cohort->id) {
            return $out('bad', 'هذي التذكرة لكورس ثاني: '.$reg->cohort->title, $reg);
        }
        if ($reg->status !== 'paid') {
            return $out('bad', 'التسجيل غير مؤكد ('.(CohortRegistration::STATUSES[$reg->status] ?? $reg->status).').', $reg);
        }
        $day = now('Asia/Baghdad')->toDateString();
        $a = CohortAttendance::firstOrCreate(['registration_id' => $reg->id, 'day' => $day], ['scanned_at' => now(), 'scanned_by' => $request->user()->id]);
        if (! $reg->checked_in_at) {
            $reg->update(['checked_in_at' => now()]);
        }

        return $a->wasRecentlyCreated
            ? $out('ok', 'أهلا '.\Illuminate\Support\Str::of($reg->name)->explode(' ')->first().'. انسجل حضوره.', $reg)
            : $out('again', 'حضوره مسجل اليوم من '.$a->scanned_at->timezone('Asia/Baghdad')->format('H:i').'.', $reg);
    }

    private function todayCount(Cohort $cohort): int
    {
        return CohortAttendance::where('day', now('Asia/Baghdad')->toDateString())
            ->whereIn('registration_id', $cohort->registrations()->select('id'))->count();
    }
}
