/*
 * غني أكاديمي — حماية مشغل الدروس
 * 1) علامة مائية متحركة باسم ورقم الطالب فوق الفيديو (تتبع أي تسريب لصاحبه)
 * 2) ملء الشاشة على الغلاف وليس على الـ iframe حتى تبقى العلامة ظاهرة
 * 3) إيقاف الفيديو عند مغادرة التبويب، وحجب عند ضغط PrintScreen
 * 4) منع القائمة اليمنى واختصارات الحفظ وأدوات المطور
 * 5) حفظ التقدم تلقائيا ووضع "مكتمل" عند انتهاء الفيديو
 */
(function () {
  var shell = document.getElementById('player');
  var wm = document.getElementById('wm');
  var iframe = document.getElementById('bunny');
  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var progressUrl = shell ? shell.dataset.progressUrl : null;
  var enrolled = shell ? shell.dataset.enrolled === '1' : false;
  var player = null;

  // ---- العلامة المائية المتحركة ----
  if (shell && wm) {
    var move = function () {
      var maxX = Math.max(0, shell.clientWidth - wm.offsetWidth - 16);
      var maxY = Math.max(0, shell.clientHeight - wm.offsetHeight - 16);
      wm.style.left = (8 + Math.random() * maxX) + 'px';
      wm.style.top = (8 + Math.random() * maxY) + 'px';
    };
    move();
    setInterval(move, 4000);
    // إذا أحد حذف العلامة من أدوات المطور نوقف الفيديو
    new MutationObserver(function () {
      if (!document.body.contains(wm) || getComputedStyle(wm).display === 'none' || getComputedStyle(wm).opacity === '0') {
        if (iframe) iframe.remove();
        var v = document.getElementById('vid'); if (v) { v.pause(); v.removeAttribute('src'); v.load(); }
        shell.classList.add('hidden-guard');
      }
    }).observe(wm, { attributes: true, attributeFilter: ['style', 'class', 'hidden'] });
    new MutationObserver(function () { if (!shell.contains(wm)) { shell.classList.add('hidden-guard'); var v = document.getElementById('vid'); if (v) v.removeAttribute('src'); } }).observe(shell, { childList: true });
  }

  // ---- ملء الشاشة على الغلاف (حتى تبقى العلامة المائية). آيفون: ملء شاشة بالـ CSS ----
  var toggleFs = function () {
    if (!shell) return;
    if (shell.classList.contains('pseudo-fs')) { shell.classList.remove('pseudo-fs'); return; }
    if (document.fullscreenElement || document.webkitFullscreenElement) {
      (document.exitFullscreen || document.webkitExitFullscreen).call(document);
    } else if (shell.requestFullscreen) {
      shell.requestFullscreen().catch(function () { shell.classList.add('pseudo-fs'); });
    } else if (shell.webkitRequestFullscreen) {
      shell.webkitRequestFullscreen();
    } else {
      shell.classList.add('pseudo-fs');
    }
  };
  ['fs', 'vFs'].forEach(function (id) { var b = document.getElementById(id); b && b.addEventListener('click', toggleFs); });

  // ---- Bunny player.js: التقدم والإيقاف ----
  var lastSent = 0;
  var send = function (data) {
    if (!enrolled || !progressUrl) return Promise.resolve(null);
    return fetch(progressUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
      body: JSON.stringify(data),
      credentials: 'same-origin'
    }).then(function (r) { return r.ok ? r.json() : null; }).catch(function () { return null; });
  };
  var applyProgress = function (res) {
    if (!res) return;
    var pct = document.getElementById('pct'), bar = document.getElementById('bar');
    if (pct) pct.textContent = res.progress + '%';
    if (bar) bar.style.width = res.progress + '%';
    if (res.certificate) {
      var t = document.createElement('div');
      t.className = 'flash';
      t.innerHTML = '<div class="toast success"><span>أكملت الكورس. <a style="color:#F5B83D" href="' + res.certificate + '">شهادتك جاهزة</a></span></div>';
      document.body.appendChild(t);
    }
  };
  var markDone = function () {
    var b = document.getElementById('markDone');
    send({ completed: true }).then(function (res) {
      applyProgress(res);
      if (b && res) { b.disabled = true; b.classList.remove('btn-primary'); b.classList.add('btn-plain'); b.querySelector('span').textContent = 'مكتمل'; }
    });
  };
  var doneBtn = document.getElementById('markDone');
  doneBtn && doneBtn.addEventListener('click', markDone);

  if (iframe && window.playerjs) {
    player = new playerjs.Player(iframe);
    player.on('ready', function () {
      var start = parseInt(shell.dataset.start || '0', 10);
      if (start > 10) player.setCurrentTime(start - 3);
      player.on('timeupdate', function (d) {
        var s = Math.floor(d.seconds || 0);
        if (Math.abs(s - lastSent) >= 15) { lastSent = s; send({ position: s }); }
      });
      player.on('ended', markDone);
    });
  }

  // ---- المشغل المجاني (فيديو على سيرفرنا) بأزرار خاصة ----
  var vid = document.getElementById('vid');
  if (vid && shell) {
    var fmt = function (t) { t = Math.floor(t || 0); return Math.floor(t / 60) + ':' + ('0' + t % 60).slice(-2); };
    var seek = document.getElementById('vSeek'), time = document.getElementById('vTime');
    var toggle = function () { vid.paused ? vid.play() : vid.pause(); };
    document.getElementById('vBig').addEventListener('click', toggle);
    document.getElementById('vPlay').addEventListener('click', toggle);
    vid.addEventListener('click', toggle);
    document.getElementById('vRate').addEventListener('change', function (e) { vid.playbackRate = +e.target.value; });
    vid.addEventListener('play', function () { shell.classList.remove('paused'); });
    vid.addEventListener('pause', function () { shell.classList.add('paused'); });
    vid.addEventListener('loadedmetadata', function () {
      var start = parseInt(shell.dataset.start || '0', 10);
      if (start > 10 && start < vid.duration - 5) vid.currentTime = start - 3;
    });
    var seeking = false;
    seek.addEventListener('input', function () { seeking = true; time.textContent = fmt(seek.value / 1000 * vid.duration) + ' / ' + fmt(vid.duration); });
    seek.addEventListener('change', function () { vid.currentTime = seek.value / 1000 * vid.duration; seeking = false; });
    vid.addEventListener('timeupdate', function () {
      if (!seeking && vid.duration) seek.value = vid.currentTime / vid.duration * 1000;
      time.textContent = fmt(vid.currentTime) + ' / ' + fmt(vid.duration);
      var s = Math.floor(vid.currentTime);
      if (Math.abs(s - lastSent) >= 15) { lastSent = s; send({ position: s }); }
    });
    vid.addEventListener('ended', markDone);
    shell.addEventListener('touchstart', function () { shell.classList.add('touch'); clearTimeout(shell._t); shell._t = setTimeout(function () { shell.classList.remove('touch'); }, 3000); }, { passive: true });
    document.addEventListener('keydown', function (e) {
      if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
      if (e.code === 'Space') { e.preventDefault(); toggle(); }
      if (e.key === 'ArrowLeft') vid.currentTime += 10;   // RTL: اليسار = تقديم
      if (e.key === 'ArrowRight') vid.currentTime -= 10;
    });
    player = { pause: function () { vid.pause(); } };
  }

  // ---- مغادرة التبويب = إيقاف ----
  document.addEventListener('visibilitychange', function () {
    if (document.hidden && player) { try { player.pause(); } catch (e) {} }
  });

  // ---- منع اختصارات النسخ والحفظ وأدوات المطور ----
  document.addEventListener('contextmenu', function (e) { e.preventDefault(); });
  document.addEventListener('keydown', function (e) {
    var k = (e.key || '').toLowerCase();
    var ctrl = e.ctrlKey || e.metaKey;
    if (k === 'f12' || (ctrl && e.shiftKey && ['i', 'j', 'c'].indexOf(k) > -1) || (ctrl && ['s', 'u', 'p'].indexOf(k) > -1)) {
      e.preventDefault();
    }
  });
  document.addEventListener('keyup', function (e) {
    if (e.key === 'PrintScreen' && shell) {
      shell.classList.add('hidden-guard');
      if (player) { try { player.pause(); } catch (err) {} }
      try { navigator.clipboard.writeText(''); } catch (err) {}
      setTimeout(function () { shell.classList.remove('hidden-guard'); }, 2500);
    }
  });
  document.addEventListener('dragstart', function (e) { e.preventDefault(); });
})();
