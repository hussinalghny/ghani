<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة القسائم · {{ $batch->name }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Readex+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Readex Pro', sans-serif; color: #1B1446; background: #F1EDFF; letter-spacing: 0; }
        .bar { display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; background: #fff; border-bottom: 1px solid #E4DDFB; position: sticky; top: 0; }
        .bar button { background: #5A35D6; color: #fff; border: 0; border-radius: 12px; height: 44px; padding: 0 22px; font: 600 15px 'Readex Pro'; cursor: pointer; }
        .sheet { display: grid; grid-template-columns: repeat(3, 1fr); gap: 5mm; padding: 8mm; max-width: 210mm; margin: 0 auto; }
        .card { height: 52mm; border-radius: 4mm; overflow: hidden; display: grid; grid-template-rows: auto 1fr; background: #fff; break-inside: avoid; border: 1px solid #E4DDFB; }
        .top { background: url({{ asset('img/pattern-violet.svg') }}), linear-gradient(160deg, #6A45E6, #5A35D6 40%, #3A1F9A); background-size: 180px, auto; color: #fff; padding: 3mm 4mm; display: flex; justify-content: space-between; align-items: center; }
        .top img { height: 7mm; }
        .amt { font-family: 'Alexandria'; font-weight: 800; font-size: 15pt; line-height: 1.3; }
        .amt small { font: 500 8pt 'Readex Pro'; color: #D2C4FF; }
        .bottom { padding: 2.5mm 4mm; display: flex; flex-direction: column; justify-content: space-between; }
        .lbl { font-size: 7pt; color: #5F5A7A; }
        .code { font: 700 13pt ui-monospace, Menlo, Consolas, monospace; direction: ltr; text-align: center; letter-spacing: .08em; background: #F1EDFF; border: 1px dashed #B3A8D9; border-radius: 2mm; padding: 1.5mm 0; }
        .how { font-size: 6.6pt; color: #5F5A7A; line-height: 1.5; }
        .serial { font-size: 6pt; color: #8C87A6; direction: ltr; text-align: left; }
        .course { font-size: 7.5pt; font-weight: 600; color: #F5B83D; }
        @media print { .bar { display: none; } body { background: #fff; } .sheet { padding: 0; } }
    </style>
</head>
<body>
    <div class="bar">
        <strong>{{ $batch->name }} · <span dir="ltr">{{ $vouchers->count() }}</span> قسيمة فعالة</strong>
        <button onclick="window.print()">طباعة</button>
    </div>
    <div class="sheet">
        @foreach ($vouchers as $v)
            <div class="card">
                <div class="top">
                    <div>
                        @if ($batch->course)
                            <div class="course">قسيمة كورس</div>
                            <div class="amt" style="font-size:11pt">{{ \Illuminate\Support\Str::limit($batch->course->title, 26) }}</div>
                        @else
                            <div class="amt"><span dir="ltr">{{ number_format($v->amount) }}</span> <small>د.ع</small></div>
                        @endif
                    </div>
                    <img src="{{ asset('img/logo-dark.png') }}" alt="Ghani Academy">
                </div>
                <div class="bottom">
                    <span class="lbl">كود الشحن</span>
                    <div class="code">{{ $v->formatted() }}</div>
                    <div class="how">ادخل منصة غني أكاديمي ← المحفظة ← اكتب الكود. للاستخدام مرة واحدة.</div>
                    <div class="serial">#{{ $batch->id }}-{{ $v->id }} @if ($v->expires_at) · EXP {{ $v->expires_at->format('Y/m/d') }}@endif</div>
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
