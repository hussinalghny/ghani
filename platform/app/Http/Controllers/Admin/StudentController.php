<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Device;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public const ROLES = ['student' => 'طالب', 'instructor' => 'مدرب', 'reseller' => 'موزع قسائم', 'support' => 'دعم فني', 'admin' => 'مدير'];

    public function index(Request $request)
    {
        $users = User::query()->withCount(['enrollments', 'devices'])
            ->when($request->query('q'), function ($q, $s) {
                $p = normalize_phone($s);
                $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('phone', 'like', '%'.($p ?: $s).'%'));
            })
            ->when($request->query('role'), fn ($q, $r) => $q->where('role', $r))
            ->when($request->user()->isSupport(), fn ($q) => $q->where('role', 'student'))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.students.index', ['users' => $users, 'roles' => self::ROLES]);
    }

    public function create()
    {
        return view('admin.students.create', ['roles' => self::ROLES]);
    }

    public function store(Request $request)
    {
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'reseller_discount' => ['nullable', 'integer', 'min:0', 'max:60'],
        ]);
        $user = User::create($data);
        $user->forceFill([
            'role' => $data['role'],
            'reseller_discount' => $data['reseller_discount'] ?? ($data['role'] === 'reseller' ? (int) setting('reseller_discount') : 0),
        ])->save();

        return redirect()->route('admin.students.show', $user)->with('success', 'تم إنشاء الحساب.');
    }

    /** الدعم الفني يتعامل ويا حسابات الطلاب فقط */
    private function guardSupport(Request $request, User $user): void
    {
        abort_if($request->user()->isSupport() && $user->role !== 'student', 403, 'الدعم الفني يتعامل ويا حسابات الطلاب فقط.');
    }

    public function show(Request $request, User $user)
    {
        $this->guardSupport($request, $user);
        $user->load(['enrollments.course', 'devices', 'certificates.course']);
        $transactions = $user->transactions()->take(30)->get();
        $courses = Course::orderBy('title')->get(['id', 'title']);

        return view('admin.students.show', ['user' => $user, 'transactions' => $transactions, 'courses' => $courses, 'roles' => self::ROLES]);
    }

    public function update(Request $request, User $user)
    {
        $this->guardSupport($request, $user);
        if ($request->user()->isSupport()) {
            $request->merge(['role' => 'student', 'reseller_discount' => 0]);
        }
        $request->merge(['phone' => normalize_phone($request->input('phone'))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['required', 'regex:/^07[3-9]\d{8}$/', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys(self::ROLES))],
            'reseller_discount' => ['nullable', 'integer', 'min:0', 'max:60'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('error', 'لا يمكنك إزالة صلاحية المدير عن نفسك.');
        }

        $user->fill(['name' => $data['name'], 'phone' => $data['phone']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
            $user->tokens()->delete();
            $user->web_session_id = null;
        }
        $user->forceFill([
            'role' => $data['role'],
            'reseller_discount' => (int) ($data['reseller_discount'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ])->save();

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return back()->with('success', 'تم حفظ الحساب.');
    }

    public function wallet(Request $request, User $user, WalletService $wallet)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:200'],
        ]);
        $note = trim(($data['note'] ?? '').' · بواسطة '.$request->user()->name, ' ·');
        $data['direction'] === 'credit'
            ? $wallet->credit($user, $data['amount'], 'admin_credit', $note)
            : $wallet->debit($user, $data['amount'], 'admin_debit', $note);

        return back()->with('success', 'تم تحديث الرصيد إلى '.iqd($user->balance).'.');
    }

    public function enroll(Request $request, User $user, EnrollmentService $service)
    {
        $data = $request->validate(['course_id' => ['required', 'exists:courses,id']]);
        $service->enroll($user, Course::findOrFail($data['course_id']), 'admin');

        return back()->with('success', 'تم تسجيل الطالب بالكورس.');
    }

    public function unenroll(User $user, Enrollment $enrollment)
    {
        abort_unless($enrollment->user_id === $user->id, 404);
        $enrollment->delete();

        return back()->with('success', 'تم إلغاء الاشتراك.');
    }

    public function resetDevices(Request $request, User $user)
    {
        $this->guardSupport($request, $user);
        $user->devices()->where('is_blocked', false)->delete();
        $user->tokens()->delete();
        $user->forceFill(['web_session_id' => null])->save();

        return back()->with('success', 'تم تصفير الأجهزة. الطالب يقدر يسجل من جهاز جديد.');
    }

    public function toggleDevice(Request $request, Device $device)
    {
        $this->guardSupport($request, $device->user);
        $device->update(['is_blocked' => ! $device->is_blocked]);
        if ($device->is_blocked) {
            $device->user->tokens()->where('name', $device->device_id)->delete();
        }

        return back()->with('success', $device->is_blocked ? 'تم حظر الجهاز.' : 'تم فك حظر الجهاز.');
    }
}
