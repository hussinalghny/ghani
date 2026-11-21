@extends('layouts.app')
@section('title', 'الدفع المباشر')

@section('content')
<div class="container container-sm" style="padding-top:40px;padding-bottom:80px">
    <div class="page-head">
        <h1>{{ $order->course ? 'ادفع واشترك' : 'شحن المحفظة' }}<span class="dot">.</span></h1>
        <p>{{ $order->course ? 'كورس '.$order->course->title : 'شحن مباشر لرصيدك' }}</p>
    </div>

    @if ($order->status === 'paid')
        <div class="panel" style="text-align:center;padding:40px 24px">
            <h2>وصل تحويلك<span class="dot">.</span></h2>
            <p class="muted">انضاف <span class="ltr">{{ iqd($order->amount) }}</span> لحسابك{{ $order->course ? ' وانفتح الكورس' : '' }}.</p>
            <a class="btn btn-primary" href="{{ $order->course ? route('learn.show', $order->course) : route('wallet') }}">{{ $order->course ? 'ابدأ التعلم' : 'المحفظة' }}</a>
        </div>
    @elseif (! $order->isOpen() || ! $method)
        <div class="panel" style="text-align:center;padding:40px 24px">
            <h2>انتهى هذا الطلب<span class="dot">.</span></h2>
            <p class="muted">إذا حولت المبلغ قبل انتهائه، راسل الدعم ونأكده لك.</p>
            <a class="btn btn-primary" href="{{ $order->course ? route('courses.show', $order->course) : route('wallet') }}">طلب جديد</a>
        </div>
    @else
        <div class="panel stack" id="payBox" data-status="{{ route('pay.status', $order) }}">
            <div>
                <span class="x-pill">{{ $method['label'] }}</span>
                <p class="small muted" style="margin:12px 0 0">{{ $method['hint'] }}</p>
            </div>

            <div>
                <label class="small muted">حول هذا المبلغ بالضبط</label>
                <div class="row-between" style="gap:12px;margin-top:6px">
                    <strong class="ltr" style="font-family:var(--font-head);font-size:clamp(34px,8vw,46px);line-height:1.2" id="amt">{{ number_format($order->amount) }}</strong>
                    <button class="btn btn-primary copy-btn" type="button" data-copy="{{ $order->amount }}">@include('partials.icon', ['n' => 'copy', 'class' => 'icon icon-sm']) نسخ المبلغ</button>
                </div>
                <p class="tiny" style="margin:6px 0 0">دينار عراقي. الدنانير الأخيرة <span class="ltr">({{ $order->amount - $order->base_amount }})</span> هي رمز طلبك، تنضاف لرصيدك. لا تقربه ولا تقسمه.</p>
            </div>

            <div>
                <label class="small muted">إلى رقم المحفظة</label>
                <div class="row-between" style="gap:12px;margin-top:6px">
                    <strong class="ltr" style="font-size:24px">{{ $method['number'] }}</strong>
                    <button class="btn btn-primary copy-btn" type="button" data-copy="{{ preg_replace('/\s+/', '', $method['number']) }}">@include('partials.icon', ['n' => 'copy', 'class' => 'icon icon-sm']) نسخ الرقم</button>
                </div>
                @if ($method['name'])<p class="tiny" style="margin:6px 0 0">باسم: {{ $method['name'] }}</p>@endif
            </div>

            <div class="alert" id="waiting" style="display:flex;gap:12px;align-items:center">
                <span class="spinner" aria-hidden="true"></span>
                <span>بانتظار التحويل. أول ما يوصل يتفعل طلبك تلقائيا خلال ثواني، خليك بهاي الصفحة أو ارجع لها بعدين.</span>
            </div>
            <p class="tiny" style="margin:0">الطلب صالح لحد <span class="ltr">{{ $order->expires_at->timezone('Asia/Baghdad')->format('Y/m/d H:i') }}</span>.</p>

            <form method="POST" action="{{ route('pay.cancel', $order) }}" data-confirm="إلغاء الطلب؟ لا تلغيه إذا حولت المبلغ.">
                @csrf<button class="btn btn-plain btn-block" type="submit">إلغاء الطلب</button>
            </form>
        </div>
    @endif
</div>
@endsection

@push('head')
<style>
.copy-btn{min-height:46px;padding:0 18px;white-space:nowrap;gap:8px}
.copy-btn.done{background:var(--success,#1E9E6A);color:#fff}
.spinner{width:20px;height:20px;border-radius:50%;border:2.5px solid var(--line-2);border-top-color:var(--violet);animation:spin 1s linear infinite;flex:none}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
@endpush

@push('scripts')
<script>
(function () {
  document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.dataset.copy, html = b.innerHTML;
      function done() { b.classList.add('done'); b.textContent = 'تم النسخ'; setTimeout(function () { b.classList.remove('done'); b.innerHTML = html; }, 1600); }
      function fallback() { var t = document.createElement('textarea'); t.value = v; t.style.position = 'fixed'; t.style.opacity = '0'; document.body.appendChild(t); t.select(); try { document.execCommand('copy'); } catch (e) {} t.remove(); done(); }
      if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(v).then(done, fallback); else fallback();
    });
  });
  var box = document.getElementById('payBox');
  if (!box) return;
  function poll() {
    fetch(box.dataset.status, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d.redirect) location.href = d.redirect; else if (d.status === 'pending') setTimeout(poll, 2000); else location.reload(); })
      .catch(function () { setTimeout(poll, 10000); });
  }
  setTimeout(poll, 2000);
})();
</script>
@endpush
