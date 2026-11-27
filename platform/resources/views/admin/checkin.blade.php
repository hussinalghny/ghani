<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#0E0B1F">
<title>مسح التذاكر · {{ $cohort->title }}</title>
<link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ @filemtime(public_path('css/theme.css')) }}">
<style>
*{box-sizing:border-box}
body{margin:0;background:#0E0B1F;color:#fff;font-family:'Thmanyah Sans',system-ui,sans-serif;min-height:100vh;display:flex;flex-direction:column}
.top{display:flex;justify-content:space-between;align-items:center;padding:calc(12px + env(safe-area-inset-top)) 16px 12px}
.top a{color:#CFC4FF;text-decoration:none;font-size:14px}
.top b{font-size:16px}
.count{display:flex;gap:10px;padding:0 16px}
.count div{flex:1;background:rgba(255,255,255,.07);border-radius:14px;padding:10px;text-align:center}
.count strong{display:block;font-size:26px;line-height:1.2}
.count span{font-size:12px;color:#B3A8D9}
.cam{position:relative;margin:14px 16px 0;border-radius:22px;overflow:hidden;background:#000;aspect-ratio:1/1}
.cam video{width:100%;height:100%;object-fit:cover;display:block}
.cam .frame{position:absolute;inset:18%;border:3px solid rgba(255,255,255,.85);border-radius:20px;box-shadow:0 0 0 999px rgba(0,0,0,.35)}
.cam .hint{position:absolute;bottom:12px;left:0;right:0;text-align:center;font-size:13px;color:#ddd}
.cam .start{position:absolute;inset:0;display:grid;place-items:center;background:#120E2B}
.cam .start button{background:#5A35D6;color:#fff;border:0;border-radius:14px;padding:14px 22px;font:700 16px inherit;font-family:inherit}
.res{margin:14px 16px 0;border-radius:20px;padding:18px;min-height:110px;background:rgba(255,255,255,.07);transition:background .2s}
.res.ok{background:#12805C}.res.again{background:#9A6A00}.res.bad{background:#B3263A}
.res b{display:block;font-size:22px;line-height:1.35}
.res p{margin:6px 0 0;font-size:15px;opacity:.95}
.res small{display:block;margin-top:6px;font-size:13px;opacity:.85;direction:ltr;text-align:right}
form.manual{display:flex;gap:8px;margin:14px 16px calc(16px + env(safe-area-inset-bottom))}
form.manual input{flex:1;height:50px;border-radius:14px;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.06);color:#fff;padding:0 14px;font-size:17px;direction:ltr;text-transform:uppercase;font-family:inherit}
form.manual button{height:50px;border-radius:14px;border:0;background:#fff;color:#1B1446;font-weight:700;padding:0 18px;font-family:inherit;font-size:15px}
</style>
</head>
<body>
<div class="top"><a href="{{ route('admin.cohorts.show', $cohort) }}">← رجوع</a><b>{{ $cohort->title }}</b><span style="width:48px"></span></div>
<div class="count">
    <div><strong id="today">{{ $today }}</strong><span>حضروا اليوم</span></div>
    <div><strong>{{ $paid }}</strong><span>مقعد مؤكد</span></div>
</div>
<div class="cam" id="cam">
    <video id="v" playsinline muted></video>
    <div class="frame"></div>
    <div class="hint">وجه الكاميرا على رمز QR بالتذكرة</div>
    <div class="start" id="start"><button type="button" id="go">تشغيل الكاميرا</button></div>
</div>
<div class="res" id="res" aria-live="assertive"><b>جاهز</b><p>امسح تذكرة أو اكتب الرمز تحت.</p></div>
<form class="manual" id="manual"><label class="sr-only" for="code" style="position:absolute;left:-9999px">رمز التذكرة</label><input id="code" placeholder="GA-XXXXXX" autocomplete="off"><button type="submit">تسجيل</button></form>

<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
(function () {
  var url = @json(route('admin.cohorts.checkin.scan', $cohort)), token = document.querySelector('meta[name="csrf-token"]').content;
  var v = document.getElementById('v'), res = document.getElementById('res'), busy = false, last = '', lastAt = 0;
  var cv = document.createElement('canvas'), cx = cv.getContext('2d', { willReadFrequently: true });
  var audio; try { audio = new (window.AudioContext || window.webkitAudioContext)(); } catch (e) {}
  function beep(ok) { if (!audio) return; var o = audio.createOscillator(), g = audio.createGain(); o.frequency.value = ok ? 880 : 220; o.connect(g); g.connect(audio.destination); g.gain.value = .15; o.start(); o.stop(audio.currentTime + (ok ? .12 : .35)); }
  function esc(s) { return String(s || '').replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }
  function send(code) {
    if (busy) return; busy = true;
    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' }, body: JSON.stringify({ code: code }) })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        res.className = 'res ' + j.state;
        res.innerHTML = '<b>' + esc(j.name || (j.state === 'bad' ? 'مرفوض' : '')) + '</b><p>' + esc(j.message) + '</p>' + (j.code ? '<small>' + esc(j.code) + ' · ' + esc(j.phone) + (j.days != null ? ' · حضر ' + j.days + ' يوم' : '') + '</small>' : '');
        if (j.today != null) document.getElementById('today').textContent = j.today;
        beep(j.state === 'ok'); if (navigator.vibrate) navigator.vibrate(j.state === 'ok' ? 80 : [80, 60, 80]);
      })
      .catch(function () { res.className = 'res bad'; res.innerHTML = '<b>ما وصل</b><p>تأكد من الإنترنت وأعد المحاولة.</p>'; })
      .finally(function () { setTimeout(function () { busy = false; }, 900); });
  }
  function tick() {
    if (v.readyState === v.HAVE_ENOUGH_DATA && !busy) {
      var w = 480, h = Math.round(v.videoHeight * w / v.videoWidth); cv.width = w; cv.height = h;
      cx.drawImage(v, 0, 0, w, h);
      var q = jsQR(cx.getImageData(0, 0, w, h).data, w, h, { inversionAttempts: 'dontInvert' });
      if (q && q.data && (q.data !== last || Date.now() - lastAt > 4000)) { last = q.data; lastAt = Date.now(); send(q.data); }
    }
    requestAnimationFrame(tick);
  }
  document.getElementById('go').addEventListener('click', function () {
    if (audio && audio.resume) audio.resume();
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false }).then(function (s) {
      v.srcObject = s; v.play(); document.getElementById('start').remove(); requestAnimationFrame(tick);
    }).catch(function () { res.className = 'res bad'; res.innerHTML = '<b>الكاميرا ما اشتغلت</b><p>اسمح للمتصفح باستخدام الكاميرا، أو اكتب رمز التذكرة تحت.</p>'; });
  });
  document.getElementById('manual').addEventListener('submit', function (e) { e.preventDefault(); var c = document.getElementById('code'); if (c.value.trim()) { send(c.value.trim()); c.value = ''; } });
})();
</script>
</body>
</html>
