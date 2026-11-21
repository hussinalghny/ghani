@extends('layouts.admin')
@section('heading', 'الدفع المباشر')
@section('sub', 'تحويل من المحفظة الإلكترونية، والتفعيل تلقائي عبر تطبيق غني باي')

@section('content')
<div class="kpis">
    <div class="kpi"><span>مدفوعات اليوم</span><strong class="ltr">{{ iqd($stats['today'], false) }}</strong></div>
    <div class="kpi"><span>مدفوعات الشهر</span><strong class="ltr">{{ iqd($stats['month'], false) }}</strong><em><span class="ltr">{{ $stats['auto'] }}</span> طلب تفعل تلقائيا</em></div>
    <div class="kpi"><span>طلبات معلقة</span><strong class="ltr">{{ $stats['pending'] }}</strong></div>
    @php($online = \App\Services\PaymentService::deviceOnline())
    @php($dev = \App\Services\PaymentService::device())
    <div class="kpi"><span>موبايل غني باي</span>
        <strong style="font-size:18px;color:{{ $online === true ? 'var(--success,#1E9E6A)' : ($online === false ? 'var(--danger,#D64545)' : 'inherit') }}">{{ $online === true ? 'متصل' : ($online === false ? 'منقطع' : 'غير مربوط') }}</strong>
        <em style="color:var(--text-3)">@if (setting('pay_device_seen'))آخر نبض <span class="ltr">{{ \Illuminate\Support\Carbon::parse(setting('pay_device_seen'))->timezone('Asia/Baghdad')->format('m/d H:i') }}</span>@endif @isset($dev['battery'])· بطارية <span class="ltr">{{ $dev['battery'] }}%{{ ! empty($dev['charging']) ? ' ⚡' : '' }}</span>@endisset @if (! empty($dev['queue']))· بالطابور <span class="ltr">{{ $dev['queue'] }}</span>@endif</em></div>
</div>

@if ($review->isNotEmpty())
<div class="panel" style="margin-bottom:16px;border-color:var(--gold)">
    <h2>دفعات تحتاج مراجعة</h2>
    <p class="help">وصلت دفعة بس ما تطابقت ويا طلب واحد (مبلغ غلط، طلب منتهي، أو أكثر من طلب). اكتب رقم الطلب واربطها.</p>
    @foreach ($review as $n)
        <div class="list-row" style="align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div style="flex:1;min-width:260px"><strong class="ltr">{{ $n->amount ? number_format($n->amount) : '—' }}</strong> <span class="tiny muted ltr">{{ $n->received_at?->format('Y/m/d H:i') }}</span><div class="tiny muted">{{ \Illuminate\Support\Str::limit($n->text, 140) }}</div>
                @php($cands = \App\Models\PaymentOrder::with('user:id,name')->whereIn('status', ['pending', 'expired'])->whereBetween('amount', [($n->amount ?? 0) - 1000, ($n->amount ?? 0) + 1000])->latest()->limit(3)->get())
                @foreach ($cands as $c)<div class="tiny">مقترح: طلب <span class="ltr">#{{ $c->id }}</span> · {{ $c->user->name }} · <span class="ltr">{{ number_format($c->amount) }}</span></div>@endforeach
            </div>
            <form method="POST" action="{{ route('admin.payments.attach', $n) }}" class="row">@csrf<input class="input" name="order" type="number" dir="ltr" placeholder="رقم الطلب" style="width:120px" required aria-label="رقم الطلب" value="{{ $cands->first()?->id }}"><button class="btn btn-primary btn-sm" type="submit">ربط وتفعيل</button></form>
            <form method="POST" action="{{ route('admin.payments.ignore', $n) }}">@csrf<button class="btn btn-plain btn-sm" type="submit">تجاهل</button></form>
        </div>
    @endforeach
</div>
@endif

<div class="panel" style="margin-bottom:16px">
    <div class="row-between" style="flex-wrap:wrap;gap:10px"><h2 style="margin:0">الطلبات</h2>
        <div class="row">@foreach (['pending' => 'معلقة', 'paid' => 'مدفوعة', 'expired' => 'منتهية', 'all' => 'الكل'] as $k => $v)<a href="{{ route('admin.payments', ['status' => $k]) }}" @class(['pill', 'active' => $status === $k])>{{ $v }}</a>@endforeach</div>
    </div>
    <div class="table-wrap" style="border:0;margin-top:12px">
        <table class="table">
            <thead><tr><th>#</th><th>الطالب</th><th>الطلب</th><th>المبلغ المطلوب</th><th>الطريقة</th><th>الحالة</th><th>التاريخ</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr>
                    <td class="ltr">{{ $o->id }}</td>
                    <td>{{ $o->user?->name }}<div class="tiny muted ltr">{{ $o->user?->phone }}</div></td>
                    <td>{{ $o->course?->title ?? 'شحن محفظة' }}</td>
                    <td class="ltr"><strong>{{ number_format($o->amount) }}</strong></td>
                    <td>{{ \App\Services\PaymentService::methods()[$o->method]['label'] ?? $o->method }}</td>
                    <td><span @class(['badge', 'badge-green' => $o->status === 'paid', 'badge-gray' => in_array($o->status, ['expired', 'cancelled'])])>{{ \App\Models\PaymentOrder::STATUSES[$o->status] ?? $o->status }}{{ $o->paid_by === 'auto' ? ' · تلقائي' : '' }}</span></td>
                    <td class="tiny ltr">{{ $o->created_at->timezone('Asia/Baghdad')->format('m/d H:i') }}</td>
                    <td>@if (in_array($o->status, ['pending', 'expired']))<form method="POST" action="{{ route('admin.payments.approve', $o) }}" data-confirm="تأكيد إن المبلغ وصل وتفعيل الطلب؟">@csrf<button class="btn btn-plain btn-sm" type="submit">تأكيد يدوي</button></form>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted">لا توجد طلبات.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $orders->links() }}
</div>

<div class="grid" style="grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px">
    <form class="panel" method="POST" action="{{ route('admin.payments.settings') }}">
        @csrf
        <h2>المحافظ</h2>
        <label class="switch" style="margin-bottom:14px"><input type="checkbox" name="pay_enabled" value="1" @checked(setting('pay_enabled'))> تفعيل الدفع المباشر للطلاب</label>
        <div class="form-grid">
            <div class="field"><label for="qn">رقم كي كارد / سوبر كي</label><input class="input" id="qn" name="pay_qi_number" dir="ltr" value="{{ setting('pay_qi_number') }}" placeholder="07XXXXXXXXX"></div>
            <div class="field"><label for="qa">الاسم الظاهر</label><input class="input" id="qa" name="pay_qi_name" value="{{ setting('pay_qi_name') }}"></div>
            <div class="field"><label for="zn">رقم زين كاش</label><input class="input" id="zn" name="pay_zaincash_number" dir="ltr" value="{{ setting('pay_zaincash_number') }}"></div>
            <div class="field"><label for="za">الاسم الظاهر</label><input class="input" id="za" name="pay_zaincash_name" value="{{ setting('pay_zaincash_name') }}"></div>
            <div class="field"><label for="fn">رقم FastPay</label><input class="input" id="fn" name="pay_fastpay_number" dir="ltr" value="{{ setting('pay_fastpay_number') }}"></div>
            <div class="field"><label for="fa">الاسم الظاهر</label><input class="input" id="fa" name="pay_fastpay_name" value="{{ setting('pay_fastpay_name') }}"></div>
        </div>
        <span class="help">الطريقة اللي بدون رقم ما تطلع للطلاب.</span>
        <h2 style="margin-top:18px">تنبيهات تيليگرام</h2>
        <div class="form-grid">
            <div class="field"><label for="tgt">توكن البوت</label><input class="input" id="tgt" name="pay_tg_token" dir="ltr" value="{{ setting('pay_tg_token') }}" placeholder="123456:ABC..." autocomplete="off"></div>
            <div class="field"><label for="tgc">رقم المحادثة (chat id)</label><input class="input" id="tgc" name="pay_tg_chat" dir="ltr" value="{{ setting('pay_tg_chat') }}"></div>
        </div>
        <span class="help">يوصلك تنبيه إذا موبايل غني باي انقطع أكثر من <span class="ltr">{{ \App\Services\PaymentService::OFFLINE_MIN }}</span> دقايق، أو البطارية خلصت، أو الرصيد زاد بدون إشعار.</span>
        <div class="row" style="margin-top:12px;gap:8px"><button class="btn btn-primary" type="submit">حفظ</button><button class="btn btn-plain" type="submit" name="tg_test" value="1">حفظ وتجربة التنبيه</button></div>
    </form>

    <div class="panel">
        <h2>ربط تطبيق غني باي</h2>
        <p class="small muted">نصب التطبيق على موبايل أندرويد عليه تطبيق المحفظة بنفس حسابك، وحط بيه هالمعلومتين:</p>
        <div class="field"><label>رابط المنصة</label><input class="input" dir="ltr" readonly value="{{ url('/') }}" onclick="this.select()"></div>
        <div class="field"><label>المفتاح السري</label><input class="input" dir="ltr" readonly value="{{ setting('pay_hook_secret') }}" onclick="this.select()"></div>
        <form method="POST" action="{{ route('admin.payments.settings') }}" data-confirm="تغيير المفتاح يوقف التطبيق لحد ما تحدثه. متأكد؟">
            @csrf
            @foreach (['pay_qi_number', 'pay_qi_name', 'pay_zaincash_number', 'pay_zaincash_name', 'pay_fastpay_number', 'pay_fastpay_name', 'pay_tg_token', 'pay_tg_chat'] as $k)<input type="hidden" name="{{ $k }}" value="{{ setting($k) }}">@endforeach
            @if (setting('pay_enabled'))<input type="hidden" name="pay_enabled" value="1">@endif
            <input type="hidden" name="new_secret" value="1">
            <button class="btn btn-plain btn-sm" type="submit">مفتاح جديد</button>
        </form>
        <p class="help" style="margin-top:12px">آخر الإشعارات:</p>
        @foreach ($recent as $n)
            <div class="tiny muted" style="padding:4px 0;border-bottom:1px solid var(--line)"><span @class(['badge', 'badge-green' => $n->status === 'matched', 'badge-gray' => $n->status === 'ignored'])>{{ $n->status === 'matched' ? 'تطابق' : 'متجاهل' }}</span> <span class="ltr">{{ $n->amount ? number_format($n->amount) : '' }}</span> {{ \Illuminate\Support\Str::limit($n->text, 70) }}</div>
        @endforeach
    </div>
</div>
@endsection
