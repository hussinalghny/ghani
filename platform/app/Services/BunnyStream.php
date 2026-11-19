<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use Illuminate\Support\Facades\Http;

/**
 * تكامل Bunny Stream:
 * - رابط Embed موقع (Token Authentication) للموقع
 * - رابط HLS موقع (CDN Token Authentication) للتطبيق
 * - رفع مباشر من المتصفح (TUS) بدون ما يمر الفيديو على سيرفر Hostinger
 */
class BunnyStream
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = config('academy.bunny');
    }

    public function configured(): bool
    {
        return ! empty($this->cfg['library_id']);
    }

    private function expires(): int
    {
        return time() + max(5, (int) $this->cfg['token_ttl']) * 60;
    }

    public function embedUrl(string $videoId): string
    {
        $lib = $this->cfg['library_id'];
        $params = ['autoplay' => 'false', 'preload' => 'true', 'responsive' => 'true'];

        if (! empty($this->cfg['embed_token_key'])) {
            $expires = $this->expires();
            $params['token'] = hash('sha256', $this->cfg['embed_token_key'].$videoId.$expires);
            $params['expires'] = $expires;
        }

        return "https://iframe.mediadelivery.net/embed/{$lib}/{$videoId}?".http_build_query($params);
    }

    /** رابط HLS للتطبيق بتوقيع على مستوى المجلد حتى تشتغل كل المقاطع (segments) */
    public function hlsUrl(string $videoId): ?string
    {
        $host = $this->cfg['cdn_host'];
        if (! $host) {
            return null;
        }
        $dir = "/{$videoId}/";
        $file = $dir.'playlist.m3u8';

        if (empty($this->cfg['cdn_token_key'])) {
            return "https://{$host}{$file}";
        }

        $expires = $this->expires();
        $hashable = $this->cfg['cdn_token_key'].$dir.$expires.'token_path='.$dir;
        $token = rtrim(strtr(base64_encode(hash('sha256', $hashable, true)), '+/', '-_'), '=');

        return "https://{$host}/bcdn_token={$token}&expires={$expires}&token_path=".rawurlencode($dir).$file;
    }

    public function thumbnailUrl(string $videoId): ?string
    {
        return $this->cfg['cdn_host'] ? "https://{$this->cfg['cdn_host']}/{$videoId}/thumbnail.jpg" : null;
    }

    private function api()
    {
        if (empty($this->cfg['api_key']) || empty($this->cfg['library_id'])) {
            throw new AcademyException('أضف BUNNY_LIBRARY_ID و BUNNY_API_KEY في ملف .env أولا.', 'bunny_not_configured');
        }

        return Http::withHeaders(['AccessKey' => $this->cfg['api_key'], 'Accept' => 'application/json'])
            ->baseUrl('https://video.bunnycdn.com/library/'.$this->cfg['library_id'])
            ->timeout(20);
    }

    /** ينشئ فيديو فارغ ويرجع بيانات الرفع المباشر (TUS) للمتصفح */
    public function prepareUpload(string $title): array
    {
        $res = $this->api()->post('/videos', ['title' => $title]);
        if (! $res->successful()) {
            throw new AcademyException('Bunny رفض إنشاء الفيديو: '.$res->status(), 'bunny_error');
        }
        $videoId = $res->json('guid');
        $expire = time() + 6 * 3600;

        return [
            'endpoint' => 'https://video.bunnycdn.com/tusupload',
            'video_id' => $videoId,
            'library_id' => (string) $this->cfg['library_id'],
            'expire' => $expire,
            'signature' => hash('sha256', $this->cfg['library_id'].$this->cfg['api_key'].$expire.$videoId),
        ];
    }

    /** مدة الفيديو بالثواني وحالة المعالجة */
    public function videoInfo(string $videoId): ?array
    {
        try {
            $res = $this->api()->get('/videos/'.$videoId);
        } catch (\Throwable) {
            return null;
        }

        return $res->successful() ? ['length' => (int) $res->json('length'), 'status' => (int) $res->json('status')] : null;
    }
}
