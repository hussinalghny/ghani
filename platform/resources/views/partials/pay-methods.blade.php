{{-- أزرار الدفع المباشر: $course (اختياري) --}}
@if (\App\Services\PaymentService::enabled())
    <form method="POST" action="{{ route('pay.store') }}" class="stack" style="gap:8px">
        @csrf
        @isset($course)<input type="hidden" name="course" value="{{ $course->id }}">@endisset
        @isset($amountField)
            <div class="field" style="margin:0"><label for="pay_amount">المبلغ (د.ع)</label><input class="input" id="pay_amount" name="amount" type="number" min="1000" step="1000" dir="ltr" inputmode="numeric" required placeholder="50000"></div>
        @endisset
        @foreach (\App\Services\PaymentService::methods() as $k => $m)
            <button class="btn btn-outline btn-block" type="submit" name="method" value="{{ $k }}">ادفع بـ {{ $m['label'] }}</button>
        @endforeach
    </form>
@endif
