<?php
/*
 * غني أكاديمي — معالج التثبيت من المتصفح (بدون SSH)
 * 1) فحص المتطلبات  2) تثبيت المكتبات (إذا vendor غير موجود)  3) قاعدة البيانات والأدمن  4) إنشاء الجداول
 * بعد الانتهاء يتقفل تلقائيا (storage/installed.lock). يفضل حذف هذا الملف بعد التثبيت.
 */
declare(strict_types=1);
@set_time_limit(0);
@ini_set('memory_limit', '1536M');

$root = dirname(__DIR__);
$lock = $root.'/storage/installed.lock';
$step = $_GET['step'] ?? 'check';

function h($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }

function page(string $title, string $body): void {
    echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        .'<meta name="robots" content="noindex"><title>تثبيت غني أكاديمي</title>'
        .'<link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@700;800&family=Readex+Pro:wght@400;500;600&display=swap" rel="stylesheet">'
        .'<link rel="stylesheet" href="css/app.css"><style>.auth-card{max-width:640px}.req{display:flex;justify-content:space-between;gap:12px;padding:10px 0;border-bottom:1px solid var(--line);font-size:15px}.ok{color:var(--success);font-weight:600}.no{color:var(--danger);font-weight:600}.steps{display:flex;gap:6px;justify-content:center;margin:14px 0 22px}.steps span{width:34px;height:5px;border-radius:9px;background:var(--line)}.steps span.on{background:var(--violet)}pre{direction:ltr;text-align:left;background:var(--night);color:#D2C4FF;padding:14px;border-radius:12px;max-height:260px;overflow:auto;font-size:12px;white-space:pre-wrap}</style>'
        .'</head><body><div class="auth-wrap" style="min-height:100vh"><div class="auth-card">'
        .'<img class="logo" src="img/icon.png" alt=""><h1>'.h($title).'<span class="dot">.</span></h1>'.$body.'</div></div></body></html>';
    exit;
}

function steps(int $n): string {
    $o = '<div class="steps">';
    for ($i = 1; $i <= 4; $i++) $o .= '<span class="'.($i <= $n ? 'on' : '').'"></span>';
    return $o.'</div>';
}

function setEnv(string $env, string $key, string $value): string {
    if ($value !== '' && preg_match('/[\s#="\'$]/u', $value)) {
        $value = '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }
    $line = $key.'='.$value;
    return preg_match('/^'.preg_quote($key, '/').'=.*$/m', $env)
        ? preg_replace('/^'.preg_quote($key, '/').'=.*$/m', str_replace(['\\', '$'], ['\\\\', '\\$'], $line), $env)
        : rtrim($env)."\n".$line."\n";
}

function laravel(string $root) {
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    return $kernel;
}

if (is_file($lock)) {
    page('المنصة مثبتة', '<p>تم التثبيت سابقا. لإعادة التثبيت احذف الملف <span class="ltr">storage/installed.lock</span>.</p><a class="btn btn-primary btn-block" href="./">فتح المنصة</a>');
}

/* ---------------- 1) فحص المتطلبات ---------------- */
if ($step === 'check') {
    $checks = [
        'PHP 8.2 أو أحدث' => version_compare(PHP_VERSION, '8.2.0', '>='),
        'PDO MySQL' => extension_loaded('pdo_mysql'),
        'mbstring' => extension_loaded('mbstring'),
        'openssl' => extension_loaded('openssl'),
        'fileinfo' => extension_loaded('fileinfo'),
        'ctype / tokenizer' => extension_loaded('ctype') && extension_loaded('tokenizer'),
        'curl' => extension_loaded('curl'),
        'zip' => extension_loaded('zip'),
        'storage قابل للكتابة' => is_writable($root.'/storage'),
        'bootstrap/cache قابل للكتابة' => is_writable($root.'/bootstrap/cache'),
        'المجلد الرئيسي قابل للكتابة (.env)' => is_writable($root),
    ];
    $all = !in_array(false, $checks, true);
    $vendor = is_file($root.'/vendor/autoload.php');
    $rows = '';
    foreach ($checks as $k => $v) $rows .= '<div class="req"><span>'.h($k).'</span><span class="'.($v ? 'ok' : 'no').'">'.($v ? 'جاهز' : 'غير متوفر').'</span></div>';
    $rows .= '<div class="req"><span>المكتبات (vendor)</span><span class="'.($vendor ? 'ok' : 'no').'">'.($vendor ? 'موجودة' : 'غير موجودة').'</span></div>';
    $next = !$all
        ? '<div class="alert warn" style="margin-top:18px">صلح البنود الحمراء من إعدادات PHP بـ Hostinger (الإصدار والإضافات)، أو صلاحيات المجلدات إلى 755، وحدث الصفحة.</div>'
        : ($vendor
            ? '<a class="btn btn-primary btn-block" style="margin-top:20px" href="?step=db">التالي: قاعدة البيانات</a>'
            : '<form method="post" action="?step=composer" style="margin-top:20px" onsubmit="this.querySelector(\'button\').disabled=true;this.querySelector(\'button\').textContent=\'جاري التثبيت... لا تغلق الصفحة (2-5 دقائق)\'"><button class="btn btn-primary btn-block">تثبيت المكتبات تلقائيا</button></form>'
              .'<p class="tiny" style="margin-top:10px;text-align:center">أو ارفع النسخة الجاهزة <span class="ltr">ghani-academy-ready.zip</span> اللي بيها مجلد vendor.</p>');
    page('تثبيت غني أكاديمي', steps(1).'<p>نفحص السيرفر قبل البدء.</p>'.$rows.$next);
}

/* ---------------- 2) تثبيت المكتبات بـ Composer داخل PHP ---------------- */
if ($step === 'composer' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $phar = $root.'/storage/composer.phar';
    $home = $root.'/storage/composer-home';
    @mkdir($home, 0755, true);
    if (!is_file($phar) || filesize($phar) < 100000) {
        $ch = curl_init('https://getcomposer.org/download/latest-stable/composer.phar');
        $fp = fopen($phar, 'wb');
        curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true, CURLOPT_TIMEOUT => 300]);
        $ok = curl_exec($ch);
        curl_close($ch);
        fclose($fp);
        if (!$ok || filesize($phar) < 100000) {
            @unlink($phar);
            page('تعذر تنزيل Composer', '<p>السيرفر ما كدر يوصل لـ getcomposer.org. ارفع النسخة الجاهزة <span class="ltr">ghani-academy-ready.zip</span> بدلها.</p><a class="btn btn-ghost btn-block" href="?step=check">رجوع</a>');
        }
    }
    putenv('COMPOSER_HOME='.$home);
    putenv('HOME='.$home);
    putenv('COMPOSER_ALLOW_SUPERUSER=1');
    chdir($root);
    try {
        require 'phar://'.$phar.'/vendor/autoload.php';
        $app = new Composer\Console\Application();
        $app->setAutoExit(false);
        $out = new Symfony\Component\Console\Output\BufferedOutput();
        $code = $app->run(new Symfony\Component\Console\Input\ArrayInput([
            'command' => 'install',
            '--no-dev' => true,
            '--optimize-autoloader' => true,
            '--no-interaction' => true,
            '--no-scripts' => true,
            '--prefer-dist' => true,
            '--working-dir' => $root,
        ]), $out);
        $log = $out->fetch();
    } catch (Throwable $e) {
        $code = 1;
        $log = $e->getMessage();
    }
    if ($code === 0 && is_file($root.'/vendor/autoload.php')) {
        page('تم تثبيت المكتبات', steps(2).'<p>كل المكتبات جاهزة.</p><a class="btn btn-primary btn-block" href="?step=db">التالي: قاعدة البيانات</a>');
    }
    page('فشل تثبيت المكتبات', '<p>ارفع النسخة الجاهزة <span class="ltr">ghani-academy-ready.zip</span> بدل هذه الطريقة.</p><pre>'.h(mb_substr($log, -3000)).'</pre><a class="btn btn-ghost btn-block" href="?step=check">رجوع</a>');
}

/* ---------------- 3) قاعدة البيانات والأدمن ---------------- */
if ($step === 'db') {
    if (!is_file($root.'/vendor/autoload.php')) { header('Location: ?step=check'); exit; }
    $error = '';
    $v = $_POST + [
        'db_host' => 'localhost', 'db_port' => '3306', 'db_name' => '', 'db_user' => '', 'db_pass' => '',
        'app_url' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http').'://'.($_SERVER['HTTP_HOST'] ?? 'localhost'),
        'admin_phone' => '07766637444', 'admin_pass' => '',
        'bunny_library' => '', 'bunny_api' => '', 'bunny_embed' => '', 'bunny_cdn' => '', 'bunny_cdn_key' => '',
    ];
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo = new PDO("mysql:host={$v['db_host']};port={$v['db_port']};dbname={$v['db_name']};charset=utf8mb4", $v['db_user'], $v['db_pass'], [PDO::ATTR_TIMEOUT => 8]);
            $pdo = null;
        } catch (Throwable $e) {
            $error = 'تعذر الاتصال بقاعدة البيانات: '.$e->getMessage();
        }
        if (!$error && strlen($v['admin_pass']) < 8) $error = 'كلمة مرور الأدمن 8 أحرف على الأقل.';
        if (!$error) {
            $env = is_file($root.'/.env') ? file_get_contents($root.'/.env') : file_get_contents($root.'/.env.example');
            $https = str_starts_with($v['app_url'], 'https://');
            foreach ([
                'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
                'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_URL' => rtrim($v['app_url'], '/'),
                'DB_CONNECTION' => 'mysql', 'DB_HOST' => $v['db_host'], 'DB_PORT' => $v['db_port'],
                'DB_DATABASE' => $v['db_name'], 'DB_USERNAME' => $v['db_user'], 'DB_PASSWORD' => $v['db_pass'],
                'SESSION_SECURE_COOKIE' => $https ? 'true' : 'false',
                'ADMIN_PHONE' => $v['admin_phone'], 'ADMIN_PASSWORD' => $v['admin_pass'],
                'BUNNY_LIBRARY_ID' => $v['bunny_library'], 'BUNNY_API_KEY' => $v['bunny_api'],
                'BUNNY_EMBED_TOKEN_KEY' => $v['bunny_embed'], 'BUNNY_CDN_HOST' => $v['bunny_cdn'], 'BUNNY_CDN_TOKEN_KEY' => $v['bunny_cdn_key'],
            ] as $k => $val) {
                $env = setEnv($env, $k, (string) $val);
            }
            file_put_contents($root.'/.env', $env);
            @unlink($root.'/bootstrap/cache/config.php');
            header('Location: ?step=migrate');
            exit;
        }
    }
    $f = fn($n, $l, $type = 'text', $ltr = true, $help = '') => '<div class="field"><label for="'.$n.'">'.$l.'</label><input class="input" id="'.$n.'" name="'.$n.'" type="'.$type.'" '.($ltr ? 'dir="ltr"' : '').' value="'.h($v[$n]).'">'.($help ? '<span class="help">'.$help.'</span>' : '').'</div>';
    page('قاعدة البيانات', steps(3)
        .($error ? '<div class="alert warn" style="margin-bottom:16px">'.h($error).'</div>' : '<p>أنشئ قاعدة بيانات MySQL من hPanel ← Databases واكتب بياناتها هنا.</p>')
        .'<form method="post">'
        .$f('db_host', 'المضيف').$f('db_port', 'المنفذ').$f('db_name', 'اسم قاعدة البيانات', 'text', true, 'بـ Hostinger يبدأ بـ u123456789_').$f('db_user', 'اسم المستخدم').$f('db_pass', 'كلمة مرور القاعدة', 'password')
        .'<h2 style="font-size:18px;margin-top:22px">المنصة والأدمن</h2>'
        .$f('app_url', 'رابط المنصة').$f('admin_phone', 'رقم هاتف الأدمن', 'tel').$f('admin_pass', 'كلمة مرور الأدمن', 'password', true, '8 أحرف على الأقل. تدخل بيها على /admin')
        .'<details style="margin:18px 0"><summary style="cursor:pointer;font-weight:600">Bunny Stream (اختياري، تقدر تضيفه بعدين)</summary><div style="margin-top:14px">'
        .$f('bunny_library', 'Library ID').$f('bunny_api', 'API Key').$f('bunny_embed', 'Embed Token Key').$f('bunny_cdn', 'CDN Host', 'text', true, 'مثل vz-xxxx.b-cdn.net').$f('bunny_cdn_key', 'CDN Token Key')
        .'</div></details>'
        .'<button class="btn btn-primary btn-block">حفظ وإنشاء الجداول</button></form>');
}

/* ---------------- 4) إنشاء الجداول والبيانات ---------------- */
if ($step === 'migrate') {
    if (!is_file($root.'/.env') || !is_file($root.'/vendor/autoload.php')) { header('Location: ?step=check'); exit; }
    $log = '';
    try {
        $kernel = laravel($root);
        foreach ([['migrate', ['--force' => true]], ['db:seed', ['--force' => true]]] as [$cmd, $args]) {
            $kernel->call($cmd, $args);
            $log .= "> $cmd\n".$kernel->output()."\n";
        }
        // مجلد الملفات العامة (صور، تطبيقات) داخل public مباشرة بدون symlink
        if (!is_dir(__DIR__.'/storage')) {
            @mkdir(__DIR__.'/storage', 0755, true);
        }
        $log .= "> public/storage ready\n";
        $kernel->call('optimize:clear');
        // ما نخلي باسورد الأدمن مكتوب بملف .env بعد إنشاء الحساب
        file_put_contents($root.'/.env', setEnv(file_get_contents($root.'/.env'), 'ADMIN_PASSWORD', ''));
        file_put_contents($lock, date('c'));
        page('المنصة جاهزة', steps(4)
            .'<p>تم إنشاء الجداول والأدمن والكورسات التجريبية.</p>'
            .'<div class="alert" style="margin-bottom:16px">احذف ملف <span class="ltr">public/install.php</span> من File Manager للأمان. المعالج مقفل تلقائيا بكل الأحوال.</div>'
            .'<a class="btn btn-primary btn-block" href="admin">دخول لوحة التحكم</a><a class="btn btn-plain btn-block" style="margin-top:8px" href="./">فتح المنصة</a>'
            .'<details style="margin-top:16px"><summary class="tiny" style="cursor:pointer">السجل</summary><pre>'.h($log).'</pre></details>');
    } catch (Throwable $e) {
        page('صار خطأ', '<p>الخطأ ظاهر تحت. صلحه وأعد المحاولة.</p><pre>'.h($e->getMessage()."\n".$log).'</pre><a class="btn btn-primary btn-block" href="?step=migrate">أعد المحاولة</a><a class="btn btn-ghost btn-block" style="margin-top:8px" href="?step=db">تعديل بيانات القاعدة</a>');
    }
}

header('Location: ?step=check');
