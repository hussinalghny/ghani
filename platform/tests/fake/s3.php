<?php
// خادم S3 وهمي للاختبار بالـ CI فقط (php -S 127.0.0.1:9000 tests/fake/s3.php)
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($path === '/ghani-videos' && ($_GET['list-type'] ?? '') === '2') {
    $files = ['smart-defense/01 الأساسيات/01 - مقدمة الكورس.mp4', 'smart-defense/01 الأساسيات/02 - كيف يفكر المهاجم.mp4', 'smart-defense/02 فحص الشبكات/01 - تنصيب الأدوات.mp4', 'smart-defense/notes.txt'];
    header('Content-Type: application/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?><ListBucketResult><IsTruncated>false</IsTruncated>';
    $prefix = $_GET['prefix'] ?? '';
    if (isset($_GET['delimiter'])) {
        foreach (array_unique(array_map(fn ($f) => explode('/', $f)[0].'/', $files)) as $p) {
            echo '<CommonPrefixes><Prefix>'.htmlspecialchars($p).'</Prefix></CommonPrefixes>';
        }
    } else {
        foreach ($files as $f) {
            if (str_starts_with($f, $prefix)) {
                echo '<Contents><Key>'.htmlspecialchars($f).'</Key><Size>3000000</Size></Contents>';
            }
        }
    }
    echo '</ListBucketResult>';
    exit;
}
// أي ملف: MP4 حقيقي الشكل بحجم 3,000,000 بايت ومدته 83 ثانية.
// «مقدمة» الـ moov بالبداية، والباقي بالنهاية بعد mdat (مثل أغلب ملفات الكاميرا والمونتاج)
$size = 3000000;
$box = fn ($type, $body) => pack('N', 8 + strlen($body)).$type.$body;
$ftyp = $box('ftyp', 'isom'.pack('N', 512).'isomiso2avc1mp41');
$mvhd = $box('mvhd', "\0\0\0\0".pack('N', 0).pack('N', 0).pack('N', 1000).pack('N', 83000).str_repeat("\0", 80));
$moov = $box('moov', $mvhd.$box('trak', str_repeat("\0", 200)));
$mdatLen = $size - strlen($ftyp) - strlen($moov);
$mdat = pack('N', $mdatLen).'mdat'.str_repeat("\0", $mdatLen - 8);
$file = str_contains($path, 'مقدمة') ? $ftyp.$moov.$mdat : $ftyp.$mdat.$moov;
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
if (preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'] ?? '', $m)) {
    $a = (int) $m[1];
    $b = $m[2] === '' ? $size - 1 : min((int) $m[2], $size - 1);
    if ($a >= $size) {
        http_response_code(416);
        exit;
    }
    http_response_code(206);
    header("Content-Range: bytes $a-$b/$size");
    echo substr($file, $a, $b - $a + 1);
    exit;
}
echo $file;
