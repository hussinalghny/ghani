<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>شهادة إتمام · {{ $certificate->user->name }} · غني أكاديمي</title>
    <link rel="icon" href="{{ asset('favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Readex+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v=1">
    <style>
        @page { size: A4 landscape; margin: 0; }
        body { background: var(--bg-alt); }
        .verify { max-width: 1000px; margin: 24px auto 0; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .cert-wrap { padding: 20px; display: grid; place-items: center; }
        .cert { direction: ltr; width: 1000px; max-width: 100%; aspect-ratio: 297 / 210; background: var(--mist); display: grid; grid-template-columns: 22% 1fr; box-shadow: var(--shadow-lg); border-radius: 6px; overflow: hidden; }
        .cert .side { background: url({{ asset('img/pattern-violet.svg') }}), linear-gradient(160deg, #6A45E6, #5A35D6 35%, #3A1F9A); background-size: 300px, auto; display: flex; flex-direction: column; justify-content: space-between; align-items: center; padding: 8% 0; }
        .cert .side img { width: 54%; border-radius: 18%; }
        .cert .main { direction: rtl; padding: 6% 7%; display: flex; flex-direction: column; background: url({{ asset('img/pattern-mist.svg') }}) , var(--mist); background-size: 420px; background-blend-mode: normal; position: relative; }
        .cert .main::before { content: ""; position: absolute; inset: 0; background: rgba(241, 237, 255, .9); }
        .cert .main > * { position: relative; }
        .cert .k { color: var(--violet); font-weight: 600; font-size: clamp(11px, 1.5vw, 15px); }
        .cert h1 { font-size: clamp(30px, 5.4vw, 58px); font-weight: 800; margin: 2% 0 1%; }
        .cert .name { font-family: var(--font-head); color: var(--violet); font-size: clamp(24px, 4.2vw, 46px); font-weight: 700; margin: 2% 0; line-height: 1.4; }
        .cert .txt { color: var(--text-2); font-size: clamp(12px, 1.7vw, 17px); max-width: 90%; }
        .cert .foot { margin-top: auto; display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; }
        .seal { width: clamp(64px, 11vw, 112px); aspect-ratio: 1; border-radius: 50%; background: var(--gold); display: grid; place-items: center; box-shadow: 0 0 0 6px rgba(245, 184, 61, .3); }
        .seal img { width: 52%; border-radius: 20%; }
        .sig { text-align: center; min-width: 22%; font-size: clamp(10px, 1.3vw, 13.5px); color: var(--text-2); }
        .sig .line { border-top: 1px solid var(--soft); margin-bottom: 6px; }
        .meta { font-size: clamp(9px, 1.1vw, 12px); color: var(--text-3); margin-top: 3%; display: flex; gap: 18px; }
        @media print { .verify { display: none; } .cert-wrap { padding: 0; } .cert { width: 100vw; box-shadow: none; border-radius: 0; } body { background: #fff; } }
    </style>
</head>
<body>
    <div class="verify no-print">
        <span class="badge badge-green" style="font-size:14px;padding:6px 14px">@include('partials.icon', ['n' => 'check-circle', 'class' => 'icon icon-sm']) شهادة موثقة صادرة من غني أكاديمي</span>
        <button class="btn btn-primary btn-sm" onclick="window.print()">@include('partials.icon', ['n' => 'printer', 'class' => 'icon icon-sm']) طباعة / حفظ PDF</button>
    </div>
    <div class="cert-wrap">
        <div class="cert">
            <div class="side">
                <img src="{{ asset('img/icon.png') }}" alt="">
                <img src="{{ asset('img/logo-dark.png') }}" alt="Ghani Academy" style="width:72%;border-radius:0">
            </div>
            <div class="main">
                <span class="k">غني أكاديمي</span>
                <h1>شهادة إتمام<span class="dot">.</span></h1>
                <span class="txt">تشهد غني أكاديمي بأن</span>
                <div class="name">{{ $certificate->user->name }}</div>
                <p class="txt">أتم بنجاح كورس <strong style="color:var(--text)">{{ $certificate->course->title }}</strong>@if ($certificate->course->duration_label) بمدة {{ $certificate->course->duration_label }}@endif، وقدم المشروع النهائي.</p>
                <div class="foot">
                    <div class="sig"><div class="line"></div>المدير العام</div>
                    <div class="seal"><img src="{{ asset('img/icon.png') }}" alt=""></div>
                    <div class="sig"><div class="line"></div>{{ $certificate->course->instructor->name ?? 'المدرب' }}</div>
                </div>
                <div class="meta"><span>رقم الشهادة: <span class="ltr">{{ $certificate->code }}</span></span><span>التاريخ: <span class="ltr">{{ $certificate->issued_at->format('Y/m/d') }}</span></span><span class="ltr">{{ route('certificates.show', $certificate) }}</span></div>
            </div>
        </div>
    </div>
</body>
</html>
