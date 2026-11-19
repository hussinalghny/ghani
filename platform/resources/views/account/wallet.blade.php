@extends('layouts.app')
@section('title', 'المحفظة')

@section('content')
<div class="container container-sm">
    <div class="page-head">
        <h1>المحفظة<span class="dot">.</span></h1>
        <p>اشحن رصيدك بقسيمة واشتر أي كورس بضغطة.</p>
    </div>

    <div class="stack">
        <div class="wallet-card">
            <div class="row-between">
                <span class="lbl">رصيدك الحالي</span>
                <img src="{{ asset('img/logo-dark.png') }}" alt="">
            </div>
            <div class="amt"><span class="ltr">{{ iqd($user->balance, false) }}</span> <small>د.ع</small></div>
            <span class="lbl ltr" style="text-align:left">{{ $user->phone }}</span>
        </div>

        <div class="panel">
            <h2>شحن بقسيمة</h2>
            <form method="POST" action="{{ route('wallet.redeem') }}" data-once>
                @csrf
                <div class="field">
                    <label for="code">كود القسيمة</label>
                    <input class="input code-input" id="code" name="code" dir="ltr" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="XXXX-XXXX-XXXX" data-voucher required>
                    <span class="help">القسيمة إما تضيف رصيدا أو تفتح كورسا محددا مباشرة.</span>
                </div>
                <button class="btn btn-primary btn-block" type="submit">تفعيل القسيمة</button>
            </form>
        </div>

        <div class="panel">
            <h2>سجل العمليات</h2>
            @forelse ($transactions as $t)
                <div class="tx">
                    <span @class(['ic', 'out' => $t->amount < 0])>@include('partials.icon', ['n' => $t->type === 'voucher' ? 'ticket' : ($t->amount < 0 ? 'play-circle' : 'plus')])</span>
                    <span><strong style="display:block;font-size:15px">{{ $t->typeLabel() }}</strong><span class="tiny">{{ $t->note }} · {{ $t->created_at->format('Y/m/d H:i') }}</span></span>
                    <span @class(['amount', 'plus' => $t->amount > 0])>{{ $t->amount > 0 ? '+' : '−' }}{{ iqd(abs($t->amount), false) }}</span>
                </div>
            @empty
                <p class="small muted" style="margin:0">لا توجد عمليات بعد.</p>
            @endforelse
            {{ $transactions->links() }}
        </div>
    </div>
    <div style="height:64px"></div>
</div>
@endsection
