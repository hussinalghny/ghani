<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;

/**
 * Cloudflare R2 (متوافق ويا S3) بدون مكتبات إضافية:
 * توقيع AWS SigV4 للروابط المؤقتة والرفع، حتى يشتغل على Hostinger بدون composer.
 */
class R2
{
    public const PREFIX = 'r2:';

    public static function enabled(): bool
    {
        return Setting::get('r2_enabled') === '1' && static::conf() !== null;
    }

    /** [account, key, secret, bucket] أو null إذا ناقص */
    public static function conf(): ?array
    {
        $acc = trim((string) Setting::get('r2_account_id'));
        $ak = trim((string) Setting::get('r2_access_key'));
        $bucket = trim((string) Setting::get('r2_bucket'));
        $enc = (string) Setting::get('r2_secret');
        if ($acc === '' || $ak === '' || $bucket === '' || $enc === '') {
            return null;
        }
        try {
            $sk = Crypt::decryptString($enc);
        } catch (\Throwable) {
            return null;
        }

        return [$acc, $ak, $sk, $bucket];
    }

    public static function isR2(?string $path): bool
    {
        return $path !== null && str_starts_with($path, self::PREFIX);
    }

    public static function key(string $path): string
    {
        return substr($path, strlen(self::PREFIX));
    }

    private static function uri(string $bucket, string $key): string
    {
        return '/'.rawurlencode($bucket).'/'.implode('/', array_map('rawurlencode', explode('/', $key)));
    }

    private static function signingKey(string $secret, string $date): string
    {
        $k = hash_hmac('sha256', $date, 'AWS4'.$secret, true);
        $k = hash_hmac('sha256', 'auto', $k, true);
        $k = hash_hmac('sha256', 's3', $k, true);

        return hash_hmac('sha256', 'aws4_request', $k, true);
    }

    /** رابط GET مؤقت (موقّع) لملف خاص على R2 */
    public static function presign(string $key, int $ttl = 10800, ?array $conf = null, ?int $now = null, string $method = 'GET', array $query = []): string
    {
        [$acc, $ak, $sk, $bucket] = $conf ?? static::conf() ?? throw new \RuntimeException('R2 غير مضبوط');
        $now ??= time();
        $t = gmdate('Ymd\THis\Z', $now);
        $d = substr($t, 0, 8);
        $scope = "$d/auto/s3/aws4_request";
        // r2_endpoint: للاختبار أو لخدمة متوافقة ويا S3 (الافتراضي R2)
        $base = rtrim((string) Setting::get('r2_endpoint'), '/') ?: "https://$acc.r2.cloudflarestorage.com";
        $host = parse_url($base, PHP_URL_HOST).(parse_url($base, PHP_URL_PORT) ? ':'.parse_url($base, PHP_URL_PORT) : '');
        $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
        $uri = $key === '' ? '/'.rawurlencode($bucket) : static::uri($bucket, $key);
        $q = $query + [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => "$ak/$scope",
            'X-Amz-Date' => $t,
            'X-Amz-Expires' => (string) $ttl,
            'X-Amz-SignedHeaders' => 'host',
        ];
        ksort($q);
        $qs = http_build_query($q, '', '&', PHP_QUERY_RFC3986);
        $canonical = "$method\n$uri\n$qs\nhost:$host\n\nhost\nUNSIGNED-PAYLOAD";
        $sts = "AWS4-HMAC-SHA256\n$t\n$scope\n".hash('sha256', $canonical);
        $sig = hash_hmac('sha256', $sts, static::signingKey($sk, $d));

        return "$scheme://$host$uri?$qs&X-Amz-Signature=$sig";
    }

    private static function get(string $url, array $headers = [], int $timeout = 60): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_HTTPHEADER => $headers]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('R2 ('.$code.') '.($err ?: mb_substr(strip_tags((string) $body), 0, 200)));
        }

        return [$code, (string) $body];
    }

    /** محتوى مجلد: ['folders' => [...], 'files' => [['key'=>..,'size'=>..], ...]] */
    public static function list(string $prefix = '', bool $recursive = false, ?array $conf = null): array
    {
        $folders = [];
        $files = [];
        $token = null;
        do {
            $q = ['list-type' => '2', 'prefix' => $prefix, 'max-keys' => '1000'];
            if (! $recursive) {
                $q['delimiter'] = '/';
            }
            if ($token) {
                $q['continuation-token'] = $token;
            }
            [, $xml] = static::get(static::presign('', 300, $conf, null, 'GET', $q));
            $x = simplexml_load_string($xml);
            if (! $x) {
                throw new \RuntimeException('رد غير مفهوم من R2.');
            }
            foreach ($x->CommonPrefixes ?? [] as $p) {
                $folders[] = (string) $p->Prefix;
            }
            foreach ($x->Contents ?? [] as $c) {
                $files[] = ['key' => (string) $c->Key, 'size' => (int) $c->Size];
            }
            $token = ((string) ($x->IsTruncated ?? 'false')) === 'true' ? (string) $x->NextContinuationToken : null;
        } while ($token);

        return ['folders' => $folders, 'files' => $files];
    }

    /** مدة فيديو MP4 بالثواني من الـ moov atom (بدون تنزيل الفيديو كامل) */
    /**
     * مدة الفيديو من صندوق mvhd: نمشي على صناديق الملف بقطع صغيرة (64KB) بدل ما ننزل ميغات.
     * يشتغل إذا الـ moov بالبداية أو بالنهاية (بعد mdat)، وبأقصى 6 طلبات.
     */
    public static function mp4Duration(string $key, int $size, ?array $conf = null): int
    {
        $url = static::presign($key, 300, $conf);
        $off = 0;
        for ($req = 0; $req < 6; $req++) {
            if ($size && $off >= $size) {
                break;
            }
            $end = $off + 65535;
            if ($size) {
                $end = min($end, $size - 1);
            }
            [, $buf] = static::get($url, ['Range: bytes='.$off.'-'.$end], 20);
            $len = strlen($buf);
            if ($len < 8) {
                break;
            }
            $p = 0;
            while ($p + 8 <= $len) {
                $bs = unpack('N', substr($buf, $p, 4))[1];
                $type = substr($buf, $p + 4, 4);
                $hdr = 8;
                if ($bs === 1) {
                    if ($p + 16 > $len) {
                        break;
                    }
                    $bs = unpack('N', substr($buf, $p + 8, 4))[1] * 4294967296 + unpack('N', substr($buf, $p + 12, 4))[1];
                    $hdr = 16;
                } elseif ($bs === 0) {
                    $bs = $size ? $size - ($off + $p) : PHP_INT_MAX;
                }
                if ($type === 'moov') {
                    $sec = static::parseMvhd(substr($buf, $p, 4096));
                    if ($sec > 0) {
                        return $sec;
                    }
                    if ($p === 0 || $p + 4096 <= $len) {
                        return 0; // moov بدون mvhd واضح
                    }
                    break; // الـ moov مقصوص بنهاية القطعة: نجيبه من بدايته
                }
                if ($bs < $hdr) {
                    return 0; // ملف مو MP4 أو خربان
                }
                $p += $bs;
            }
            $off += $p;
        }

        return 0;
    }

    public static function parseMvhd(string $buf): int
    {
        $i = strpos($buf, 'mvhd');
        if ($i === false || strlen($buf) < $i + 32) {
            return 0;
        }
        $v = ord($buf[$i + 4]);
        if ($v === 1) {
            $scale = unpack('N', substr($buf, $i + 24, 4))[1];
            $hi = unpack('N', substr($buf, $i + 28, 4))[1];
            $lo = unpack('N', substr($buf, $i + 32, 4))[1];
            $dur = $hi * 4294967296 + $lo;
        } else {
            $scale = unpack('N', substr($buf, $i + 16, 4))[1];
            $dur = unpack('N', substr($buf, $i + 20, 4))[1];
        }

        return $scale > 0 ? (int) round($dur / $scale) : 0;
    }

    /** «03 - عنوان الدرس.mp4» ← [3, 'عنوان الدرس'] */
    public static function titleFromKey(string $key): array
    {
        $name = pathinfo(basename($key), PATHINFO_FILENAME);
        $name = str_replace(['_'], ' ', $name);
        $n = null;
        if (preg_match('/^\s*(\d{1,3})\s*[-.)_ ]*\s*(.*)$/u', $name, $m)) {
            $n = (int) $m[1];
            $name = $m[2] !== '' ? $m[2] : $name;
        }

        return [$n, trim(preg_replace('/\s+/u', ' ', $name))];
    }

    /** يرفع ملف محلي لـ R2 (يتدفق من القرص بدون ما يتحمل كامل بالذاكرة) */
    public static function put(string $localPath, string $key, ?array $conf = null): void
    {
        $url = static::presign($key, 3600, $conf, null, 'PUT');
        $size = filesize($localPath);
        $fh = fopen($localPath, 'rb');
        $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        $type = ['webm' => 'video/webm', 'mov' => 'video/quicktime', 'txt' => 'text/plain'][$ext] ?? 'video/mp4';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_UPLOAD => true,
            CURLOPT_INFILE => $fh,
            CURLOPT_INFILESIZE => $size,
            CURLOPT_HTTPHEADER => ['Content-Type: '.$type],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_CONNECTTIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        fclose($fh);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('فشل الرفع لـ R2 ('.$code.') '.($err ?: mb_substr(strip_tags((string) $body), 0, 200)));
        }
    }

    public static function delete(string $key, ?array $conf = null): void
    {
        $ch = curl_init(static::presign($key, 600, $conf, null, 'DELETE'));
        curl_setopt_array($ch, [CURLOPT_CUSTOMREQUEST => 'DELETE', CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
        curl_exec($ch);
        curl_close($ch);
    }

    /** اختبار: يرفع ملف صغير، يقراه برابط مؤقت، ويحذفه */
    public static function test(?array $conf = null): string
    {
        $conf ??= static::conf();
        if (! $conf) {
            return 'المعلومات ناقصة.';
        }
        $tmp = tempnam(sys_get_temp_dir(), 'r2');
        file_put_contents($tmp, 'ghani-ok');
        try {
            static::put($tmp, 'ghani-test.txt', $conf);
            $ch = curl_init(static::presign('ghani-test.txt', 120, $conf));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20]);
            $got = curl_exec($ch);
            curl_close($ch);
            static::delete('ghani-test.txt', $conf);
            if ($got !== 'ghani-ok') {
                return 'الرفع نجح بس القراءة فشلت.';
            }
        } catch (\Throwable $e) {
            return $e->getMessage();
        } finally {
            @unlink($tmp);
        }

        return 'ok';
    }

    /** ينقل فيديو درس من سيرفرنا لـ R2 ويحذف النسخة المحلية */
    public static function moveLesson(\App\Models\Lesson $lesson): string
    {
        abort_if(static::isR2($lesson->video_path) || ! $lesson->video_path, 422, 'الفيديو مو على السيرفر.');
        $local = storage_path('app/private/'.$lesson->video_path);
        abort_unless(is_file($local), 404, 'ملف الفيديو غير موجود على السيرفر.');
        @set_time_limit(0);
        static::put($local, $lesson->video_path);
        $lesson->forceFill(['video_path' => static::PREFIX.$lesson->video_path])->save();
        @unlink($local);

        return $lesson->video_path;
    }
}
