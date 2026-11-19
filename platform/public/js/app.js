// غني أكاديمي — سكربت عام خفيف
(function () {
  // قائمة الحساب
  document.querySelectorAll('[data-menu]').forEach(function (menu) {
    var btn = menu.querySelector('[data-menu-btn]');
    btn && btn.addEventListener('click', function (e) {
      e.stopPropagation();
      menu.classList.toggle('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('[data-menu].open').forEach(function (m) { m.classList.remove('open'); });
  });

  // إخفاء الرسائل تلقائيا
  setTimeout(function () {
    document.querySelectorAll('.toast.success').forEach(function (t) {
      t.style.transition = 'opacity .4s'; t.style.opacity = '0';
      setTimeout(function () { t.remove(); }, 400);
    });
  }, 5000);

  // تأكيد قبل الإجراءات الحساسة: <form data-confirm="...">
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.dataset.confirm)) e.preventDefault();
    });
  });

  // منع الضغط المزدوج على أزرار الشراء والدفع
  document.querySelectorAll('form[data-once]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('[type=submit]');
      if (b) { b.disabled = true; b.style.opacity = '.6'; }
    });
  });

  // تنسيق كود القسيمة أثناء الكتابة: XXXX-XXXX-XXXX
  document.querySelectorAll('[data-voucher]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var v = inp.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 12);
      inp.value = v.match(/.{1,4}/g) ? v.match(/.{1,4}/g).join('-') : v;
    });
  });
})();
