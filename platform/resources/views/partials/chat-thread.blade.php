{{-- محادثة مباشرة: تحديث تلقائي كل 4 ثواني --}}
<div class="chat" id="chat" data-poll="{{ route('chat.poll', $conversation) }}" data-send="{{ route('chat.send', $conversation) }}">
    <div class="chat-log" id="chatLog" aria-live="polite">
        @foreach ($messages as $m)
            <div class="msg {{ $m['mine'] ? 'mine' : '' }}" data-id="{{ $m['id'] }}">
                @unless ($m['mine'])<span class="who">{{ $m['sender'] }}</span>@endunless
                <div class="bubble">{{ $m['body'] }}</div>
                <span class="t ltr">{{ $m['time'] }}</span>
            </div>
        @endforeach
        <div class="chat-empty" @if ($messages->isNotEmpty()) hidden @endif>{{ $emptyText ?? 'اكتب أول رسالة.' }}</div>
    </div>
    <form class="chat-form" id="chatForm" autocomplete="off">
        <label for="chatBody" class="sr-only">رسالتك</label>
        <textarea id="chatBody" class="input" rows="1" maxlength="4000" placeholder="اكتب رسالتك..." required></textarea>
        <button class="btn btn-primary" type="submit" aria-label="إرسال">@include('partials.icon', ['n' => 'arrow-left'])</button>
    </form>
</div>
@push('scripts')
<script>
(function () {
  var box = document.getElementById('chat'), log = document.getElementById('chatLog'), form = document.getElementById('chatForm'), ta = document.getElementById('chatBody');
  var csrf = document.querySelector('meta[name=csrf-token]').content;
  var last = 0; log.querySelectorAll('.msg').forEach(function (m) { last = Math.max(last, +m.dataset.id); });
  var esc = function (s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; };
  var add = function (m) {
    if (log.querySelector('[data-id="' + m.id + '"]')) return;
    var el = document.createElement('div');
    el.className = 'msg' + (m.mine ? ' mine' : ''); el.dataset.id = m.id;
    el.innerHTML = (m.mine ? '' : '<span class="who">' + esc(m.sender) + '</span>') + '<div class="bubble">' + esc(m.body) + '</div><span class="t ltr">' + m.time + '</span>';
    log.insertBefore(el, log.querySelector('.chat-empty'));
    log.querySelector('.chat-empty').hidden = true;
    last = Math.max(last, m.id);
  };
  var bottom = function () { log.scrollTop = log.scrollHeight; };
  bottom();
  var poll = function () {
    fetch(box.dataset.poll + '?after=' + last, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.ok ? r.json() : null; })
      .then(function (d) { if (d && d.messages.length) { d.messages.forEach(add); bottom(); } }).catch(function () {});
  };
  setInterval(function () { if (!document.hidden) poll(); }, 4000);
  ta.addEventListener('input', function () { ta.style.height = 'auto'; ta.style.height = Math.min(ta.scrollHeight, 140) + 'px'; });
  ta.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
  form.addEventListener('submit', function (e) {
    e.preventDefault();
    var body = ta.value.trim(); if (!body) return;
    ta.value = ''; ta.style.height = 'auto';
    fetch(box.dataset.send, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, credentials: 'same-origin', body: JSON.stringify({ body: body }) })
      .then(function (r) { return r.json(); }).then(function (d) { if (d.message) { add(d.message); bottom(); } else { alert(d.message || 'تعذر الإرسال'); ta.value = body; } })
      .catch(function () { ta.value = body; });
  });
})();
</script>
@endpush
