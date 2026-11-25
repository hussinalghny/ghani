@extends('layouts.app')
@section('title', $cohort->title.' · كورس حضوري')
@section('description', $cohort->subtitle.' · '.$cohort->seats.' مقعد فقط. سجل وادفع بالمحفظة الإلكترونية ويتأكد مقعدك تلقائيا.')
@section('body_class', 'is-course v4-cohort')

@php
    $months = ['', 'كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'];
    $date = $cohort->starts_on ? $cohort->starts_on->day.' '.$months[$cohort->starts_on->month].' '.$cohort->starts_on->year : 'يحدد قريبا';
    $taken = $cohort->seats - $left;
    $pct = $cohort->seats ? min(100, round($taken * 100 / $cohort->seats)) : 0;
    $full = $left <= 0 || ! $cohort->is_open;
    $user = auth()->user();
    $logos = ($cohort->details['logos'] ?? []);
    $gallery = ($cohort->details['gallery'] ?? []);
@endphp

@section('content')
{{-- ===== الواجهة ===== --}}
<section class="ch-hero">
    <div class="container ch-hero-in">
        <div class="ch-hero-txt">
            <div class="ch-tags">
                <span class="lp-pill">@include('partials.icon', ['n' => 'users']) كورس حضوري · <span class="ltr">{{ $cohort->seats }}</span> مقعد فقط</span>
                <span class="lp-pill">@include('partials.icon', ['n' => 'award']) شهادة إتمام</span>
            </div>
            <h1>{{ $cohort->title }}</h1>
            @if ($cohort->subtitle)<p class="ch-sub">{{ $cohort->subtitle }}</p>@endif

            <div class="ch-facts">
                <div>@include('partials.icon', ['n' => 'clock'])<span><small>يبدأ</small><b>{{ $date }}</b></span></div>
                <div>@include('partials.icon', ['n' => 'layers'])<span><small>المدة</small><b><span class="ltr">4</span> أسابيع · <span class="ltr">12</span> جلسة</b></span></div>
                <div>@include('partials.icon', ['n' => 'globe'])<span><small>المكان</small><b>@if ($cohort->map_url)<a href="{{ $cohort->map_url }}" target="_blank" rel="noopener">{{ $cohort->location ?: 'بغداد' }}</a>@else{{ $cohort->location ?: 'بغداد' }}@endif</b></span></div>
                @if ($cohort->schedule)<div>@include('partials.icon', ['n' => 'clock'])<span><small>الأوقات</small><b>{{ $cohort->schedule }}</b></span></div>@endif
            </div>

            <div class="ch-seats" aria-label="المقاعد">
                <div class="row-between"><b>@if ($full) المقاعد اكتملت @else باقي <span class="ltr">{{ $left }}</span> مقعد من <span class="ltr">{{ $cohort->seats }}</span>@endif</b><span class="tiny"><span class="ltr">{{ $pct }}%</span> محجوز</span></div>
                <span class="u-bar"><i style="width:{{ max(3, $pct) }}%"></i></span>
            </div>

            <div class="lp-cta">
                @if ($mine)
                    <a class="btn btn-primary btn-lg" href="{{ $mine->status === 'pending' ? '#pay' : route('cohort.ticket', $mine) }}">{{ $mine->status === 'pending' ? 'كمل الدفع وأكد مقعدك' : 'عرض تذكرتي' }}</a>
                @else
                    <a class="btn btn-primary btn-lg" href="#register">{{ $full ? 'سجل بقائمة الانتظار' : 'احجز مقعدك' }}</a>
                @endif
                <span class="ch-price"><b class="ltr">{{ number_format($cohort->price) }}</b> د.ع <small>دفعة وحدة</small></span>
            </div>
        </div>
        <div class="ch-hero-img">
            @if ($cohort->imageUrl())<img src="{{ $cohort->imageUrl() }}" alt="{{ $cohort->title }}">@endif
            <div class="lp-float ch-f1">@include('partials.icon', ['n' => 'wallet'])<div><b>دفع بغني باي</b><small>تأكيد تلقائي خلال ثواني</small></div></div>
        </div>
    </div>
</section>

{{-- ===== شريط سريع ===== --}}
<div class="container">
    <nav class="ch-jump" aria-label="أقسام الصفحة">
        <a href="#learn">شنو تتعلم</a><a href="#plan">المنهج</a><a href="#includes">يشمل</a><a href="#partners">التعاونات</a><a href="#payment">الدفع</a><a href="#faq">الأسئلة</a>
    </nav>
</div>

{{-- ===== شنو راح تتعلم ===== --}}
@if ($cohort->d('outcomes'))
<section class="container lp-sec" id="learn">
    <div class="lp-head"><h2>شنو راح تتعلم</h2><p>كورس عملي من أول جلسة، تطبق كل شي بإيدك.</p></div>
    <div class="ch-learn">
        @foreach ($cohort->d('outcomes') as $o)<div>@include('partials.icon', ['n' => 'check-circle'])<span>{{ $o }}</span></div>@endforeach
    </div>
</section>
@endif

{{-- ===== المنهج ===== --}}
@if ($cohort->d('weeks'))
<section class="lp-band" id="plan">
    <div class="container">
        <div class="lp-head"><h2>منهج الكورس</h2><p><span class="ltr">12</span> جلسة خلال <span class="ltr">4</span> أسابيع، وكل أسبوع يبني على اللي قبله.</p></div>
        <ol class="ch-weeks">
            @foreach ($cohort->d('weeks') as $i => $w)
                @php([$wt, $wd] = \App\Models\Cohort::pair($w))
                <li><span class="n ltr">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><div><b>{{ $wt }}</b>@if ($wd)<p>{{ $wd }}</p>@endif</div></li>
            @endforeach
        </ol>
    </div>
</section>
@endif

{{-- ===== لمن / يشمل ===== --}}
<section class="container lp-sec" id="includes">
    <div class="ch-two">
        @if ($cohort->d('audience'))
        <div class="u-box"><h3>لمن هذا الكورس</h3><ul class="ch-list">@foreach ($cohort->d('audience') as $a)<li>@include('partials.icon', ['n' => 'user']) {{ $a }}</li>@endforeach</ul></div>
        @endif
        @if ($cohort->d('includes'))
        <div class="u-box"><h3>الكورس يشمل</h3><ul class="ch-list">@foreach ($cohort->d('includes') as $a)<li>@include('partials.icon', ['n' => 'check']) {{ $a }}</li>@endforeach</ul></div>
        @endif
    </div>
    @if (! empty($cohort->details['instructor']))
        <div class="u-box ch-ins"><span class="ph">@include('partials.icon', ['n' => 'mic'])</span><div><h3 style="margin:0 0 4px">المدرب</h3><p>{{ $cohort->details['instructor'] }}</p></div></div>
    @endif
</section>

{{-- ===== صور ===== --}}
@if ($gallery)
<section class="container lp-sec">
    <div class="lp-head"><h2>من أجواء الكورس</h2></div>
    <div class="ch-gallery">@foreach ($gallery as $g)<img src="{{ asset('storage/'.$g) }}" alt="" loading="lazy">@endforeach</div>
</section>
@endif

{{-- ===== التعاونات ===== --}}
@if ($cohort->d('partners'))
<section class="container lp-sec" id="partners">
    <div class="lp-head"><h2>التعاونات والشركاء</h2><p>الكورس بالتعاون ويا جهات تدعم جودة التدريب والشهادة.</p></div>
    <div class="ch-partners">
        @foreach ($cohort->d('partners') as $i => $p)
            @php([$pn, $pr] = \App\Models\Cohort::pair($p))
            <div class="ch-partner">
                @if (! empty($logos[$i]))<img src="{{ asset('storage/'.$logos[$i]) }}" alt="{{ $pn }}">@else<span class="ph">{{ mb_substr($pn, 0, 1) }}</span>@endif
                <b>{{ $pn }}</b>@if ($pr)<small>{{ $pr }}</small>@endif
            </div>
        @endforeach
    </div>
</section>
@endif

{{-- ===== الدفع بغني باي ===== --}}
<section class="container lp-sec" id="payment">
    <div class="ch-pay-explain">
        <div class="lp-head" style="text-align:start;margin:0 0 20px"><span class="ch-k">غني باي</span><h2>دفع مباشر وتأكيد تلقائي</h2><p>ما تحتاج بطاقة ولا تراجع أحد. تحول من محفظتك والنظام يأكد مقعدك وحده.</p></div>
        <ol class="ch-steps">
            <li><span class="ltr">1</span><b>سجل واحجز مقعدك</b><p>المقعد ينحجز باسمك <span class="ltr">48</span> ساعة.</p></li>
            <li><span class="ltr">2</span><b>حول المبلغ المكتوب بالضبط</b><p>كل طلب إله مبلغ مميز بآخر رقمين، هذا هو رمز طلبك.</p></li>
            <li><span class="ltr">3</span><b>يتأكد تلقائيا</b><p>أول ما يوصل التحويل، يتأكد مقعدك ويوصلك إشعار وتذكرة برمز خاص.</p></li>
        </ol>
        @if ($methods)
            <div class="ch-wallets">@foreach ($methods as $m)<span>@include('partials.icon', ['n' => 'wallet']) {{ $m['label'] }}</span>@endforeach</div>
        @endif
    </div>
</section>

{{-- ===== التسجيل ===== --}}
<section class="container lp-sec" id="register">
    <div class="ch-reg">
        <div class="ch-reg-side">
            <h2>{{ $mine ? 'تسجيلك' : ($full ? 'قائمة الانتظار' : 'احجز مقعدك') }}</h2>
            <p>{{ $full && ! $mine ? 'المقاعد اكتملت. سجل وأول ما يتوفر مقعد نتواصل وياك.' : 'املي المعلومات، وبعدها ادفع حتى يتأكد مقعدك.' }}</p>
            <div class="ch-sum">
                <div><span>الكورس</span><b>{{ $cohort->title }} · حضوري</b></div>
                <div><span>يبدأ</span><b>{{ $date }}</b></div>
                <div><span>المكان</span><b>{{ $cohort->location ?: 'بغداد' }}</b></div>
                <div><span>المبلغ</span><b><span class="ltr">{{ number_format($cohort->price) }}</span> د.ع</b></div>
            </div>
        </div>

        <div class="ch-reg-main" id="pay">
            @if ($mine && $mine->status === 'pending')
                <div class="ch-state"><span class="badge badge-gold">بانتظار الدفع</span><span class="tiny">رمز التسجيل <b class="ltr">{{ $mine->code }}</b></span></div>
                <h3>اختار طريقة الدفع</h3>
                @if ((int) $user->balance >= $cohort->price && $cohort->price > 0)
                    <form method="POST" action="{{ route('cohort.wallet', $mine) }}" class="ch-method">@csrf
                        <button type="submit">@include('partials.icon', ['n' => 'wallet'])<span><b>من رصيدي</b><small>رصيدك <span class="ltr">{{ number_format($user->balance) }}</span> د.ع</small></span>@include('partials.icon', ['n' => 'chev-left'])</button>
                    </form>
                @endif
                @forelse ($methods as $key => $m)
                    <form method="POST" action="{{ route('pay.store') }}" class="ch-method">@csrf
                        <input type="hidden" name="method" value="{{ $key }}"><input type="hidden" name="registration" value="{{ $mine->code }}">
                        <button type="submit">@include('partials.icon', ['n' => 'wallet'])<span><b>{{ $m['label'] }}</b><small>تحويل مباشر · تأكيد تلقائي</small></span>@include('partials.icon', ['n' => 'chev-left'])</button>
                    </form>
                @empty
                    <p class="muted small">الدفع الإلكتروني متوقف مؤقتا. راسلنا على الواتساب <a class="ltr" href="https://wa.me/9647766637444">0776 663 7444</a> ونأكد مقعدك.</p>
                @endforelse
                <p class="tiny" style="margin-top:12px">مقعدك محجوز لحد {{ $mine->created_at->addHours(\App\Services\PaymentService::HOURS)->timezone('Asia/Baghdad')->format('Y/m/d H:i') }}.</p>
            @elseif ($mine)
                <div class="ch-done">
                    <span class="ic">@include('partials.icon', ['n' => $mine->status === 'paid' ? 'check-circle' : 'clock'])</span>
                    <h3>{{ $mine->status === 'paid' ? 'مقعدك مؤكد' : 'أنت بقائمة الانتظار' }}</h3>
                    <p class="muted">رمز التسجيل <b class="ltr">{{ $mine->code }}</b></p>
                    <a class="btn btn-primary" href="{{ route('cohort.ticket', $mine) }}">عرض تذكرتي</a>
                </div>
            @else
                <form method="POST" action="{{ route('cohort.register', $cohort) }}" class="ch-form" novalidate>
                    @csrf
                    <div class="ch-grid">
                        <div class="field"><label for="f-name">الاسم الكامل</label><input class="input" id="f-name" name="name" required value="{{ old('name', $user?->name) }}" autocomplete="name"></div>
                        <div class="field"><label for="f-phone">رقم الهاتف (واتساب)</label><input class="input ltr" id="f-phone" name="phone" required dir="ltr" inputmode="tel" placeholder="07XXXXXXXXX" value="{{ old('phone', $user?->phone) }}" autocomplete="tel"></div>
                        <div class="field"><label for="f-email">الإيميل <small class="muted">(اختياري)</small></label><input class="input ltr" id="f-email" name="email" type="email" dir="ltr" value="{{ old('email', $user?->email) }}" autocomplete="email"></div>
                        <div class="field"><label for="f-job">الدراسة أو العمل <small class="muted">(اختياري)</small></label><input class="input" id="f-job" name="job" value="{{ old('job') }}" placeholder="مثلا: طالب حاسبات"></div>
                    </div>
                    <fieldset class="field ch-levels"><legend>مستواك بالمجال</legend>
                        @foreach (\App\Models\CohortRegistration::LEVELS as $k => $v)
                            <label><input type="radio" name="level" value="{{ $k }}" @checked(old('level', 'basic') === $k)><span>{{ $v }}</span></label>
                        @endforeach
                    </fieldset>
                    <div class="field"><label for="f-notes">ملاحظة <small class="muted">(اختياري)</small></label><textarea class="input" id="f-notes" name="notes" rows="2" maxlength="500">{{ old('notes') }}</textarea></div>
                    @guest
                        <div class="field"><label for="f-pass">كلمة مرور لحسابك</label><input class="input ltr" id="f-pass" name="password" type="password" dir="ltr" minlength="8" required autocomplete="new-password"><small class="tiny">نسويلك حساب بغني أكاديمي حتى تتابع تسجيلك وتذكرتك. عندك حساب؟ <a href="{{ route('login') }}">سجل دخولك</a>.</small></div>
                    @endguest
                    <label class="ch-agree"><input type="checkbox" name="agree" value="1" required @checked(old('agree'))> <span>أوافق على شروط التسجيل: المقعد ينحجز <span class="ltr">48</span> ساعة لحد الدفع، والاسترجاع كامل قبل أول جلسة بـ <span class="ltr">3</span> أيام.</span></label>
                    <button class="btn btn-primary btn-lg btn-block" type="submit">{{ $full ? 'سجلني بقائمة الانتظار' : 'احجز مقعدي وكمل للدفع' }}</button>
                </form>
            @endif
        </div>
    </div>
</section>

{{-- ===== الأسئلة ===== --}}
@if ($cohort->d('faq'))
<section class="container lp-sec" id="faq">
    <div class="lp-head"><h2>أسئلة شائعة</h2></div>
    <div class="lp-faq">
        @foreach ($cohort->d('faq') as $f)
            @php([$q, $a] = \App\Models\Cohort::pair($f))
            <details><summary>{{ $q }}</summary><p>{{ $a }}</p></details>
        @endforeach
    </div>
</section>
@endif

<section class="container lp-sec">
    <div class="lp-final">
        <h2>@if ($full) المقاعد اكتملت @else باقي <span class="ltr">{{ $left }}</span> مقعد @endif</h2>
        <p>للاستفسار راسلنا على الواتساب <a class="ltr" href="https://wa.me/9647766637444">0776 663 7444</a></p>
        <div class="lp-cta" style="justify-content:center"><a class="btn btn-primary btn-lg" href="#register">{{ $mine ? 'تسجيلي' : ($full ? 'قائمة الانتظار' : 'احجز مقعدك') }}</a></div>
    </div>
</section>

@unless ($mine && $mine->status === 'paid')
<div class="ga-buybar">
    <div><b><span class="ltr">{{ number_format($cohort->price) }}</span> د.ع</b><small>@if ($full) المقاعد اكتملت @else باقي <span class="ltr">{{ $left }}</span> مقعد @endif</small></div>
    <a class="btn btn-primary" href="{{ $mine ? '#pay' : '#register' }}">{{ $mine ? 'كمل الدفع' : 'احجز مقعدك' }}</a>
</div>
@endunless
@endsection
