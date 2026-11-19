@if (session('success') || session('error') || $errors->any())
    <div class="flash" role="status" aria-live="polite">
        @if (session('success'))
            <div class="toast success">@include('partials.icon', ['n' => 'check-circle'])<span>{{ session('success') }}</span><button type="button" onclick="this.parentElement.remove()" aria-label="إغلاق">@include('partials.icon', ['n' => 'x', 'class' => 'icon icon-sm'])</button></div>
        @endif
        @if (session('error'))
            <div class="toast error">@include('partials.icon', ['n' => 'alert'])<span>{{ session('error') }}</span><button type="button" onclick="this.parentElement.remove()" aria-label="إغلاق">@include('partials.icon', ['n' => 'x', 'class' => 'icon icon-sm'])</button></div>
        @endif
        @if ($errors->any() && ! session('error'))
            <div class="toast error">@include('partials.icon', ['n' => 'alert'])<span>{{ $errors->first() }}</span><button type="button" onclick="this.parentElement.remove()" aria-label="إغلاق">@include('partials.icon', ['n' => 'x', 'class' => 'icon icon-sm'])</button></div>
        @endif
    </div>
@endif
