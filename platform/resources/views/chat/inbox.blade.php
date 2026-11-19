@extends('layouts.admin')
@section('heading', 'المحادثات')
@section('sub', auth()->user()->isInstructor() ? 'أسئلة طلاب كورساتك' : 'الدعم ورسائل المدربين')

@section('content')
<div class="pills" style="margin-bottom:16px">
    <a href="{{ route('inbox') }}" @class(['pill', 'active' => ! request('type') && ! request('unread')])>الكل</a>
    <a href="{{ route('inbox', ['unread' => 1]) }}" @class(['pill', 'active' => request('unread')])>غير مقروءة</a>
    @if (auth()->user()->isAdmin())
        <a href="{{ route('inbox', ['type' => 'support']) }}" @class(['pill', 'active' => request('type') === 'support'])>الدعم</a>
        <a href="{{ route('inbox', ['type' => 'instructor']) }}" @class(['pill', 'active' => request('type') === 'instructor'])>المدربين</a>
    @endif
</div>
<div class="panel" style="padding:8px 20px">
    @forelse ($conversations as $c)
        <a href="{{ route('chat.show', $c) }}" class="list-row" style="color:inherit">
            <span class="cat-icon" style="width:42px;height:42px">@include('partials.icon', ['n' => $c->type === 'instructor' ? 'mic' : 'shield'])</span>
            <span style="flex:1;min-width:0">
                <strong style="display:block">{{ $c->user->name ?? '—' }} <span class="tiny">{{ $c->type === 'instructor' ? '· '.($c->course->title ?? '').' · '.($c->instructor->name ?? '') : '· الدعم' }}</span></strong>
                <span class="tiny" style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $c->lastMessage->body ?? '' }}</span>
            </span>
            <span class="tiny">{{ $c->last_message_at?->diffForHumans() }}</span>
            @if ($c->staff_unread)<span class="badge badge-gold ltr">{{ $c->staff_unread }}</span>@endif
        </a>
    @empty
        <p class="muted" style="padding:24px 0;text-align:center">لا توجد محادثات.</p>
    @endforelse
</div>
{{ $conversations->links() }}
@endsection

@push('scripts')
<script>setTimeout(function () { if (!document.hidden) location.reload(); }, 30000);</script>
@endpush
