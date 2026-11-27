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
// أي ملف: بايتات فيها mvhd بمدة 83 ثانية
header('Content-Type: video/mp4');
http_response_code(isset($_SERVER['HTTP_RANGE']) ? 206 : 200);
echo str_repeat("\0", 64).'mvhd'.pack('C', 0)."\0\0\0".pack('N', 0).pack('N', 0).pack('N', 1000).pack('N', 83000).str_repeat("\0", 64);
