<?php

namespace App\Services;

use App\Models\PushToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * إشعارات Firebase (FCM HTTP v1) بدون مكتبات إضافية.
 * يكفي ترفع ملف Service Account من لوحة التحكم ← الإشعارات.
 */
class FcmService
{
    public const FILE = 'firebase-service-account.json';

    private function creds(): ?array
    {
        $path = storage_path('app/private/'.self::FILE);
        if (! is_file($path)) {
            return null;
        }
        $c = json_decode((string) file_get_contents($path), true);

        return is_array($c) && ! empty($c['private_key']) && ! empty($c['client_email']) ? $c : null;
    }

    public function configured(): bool
    {
        return $this->creds() !== null;
    }

    public function projectId(): ?string
    {
        return $this->creds()['project_id'] ?? null;
    }

    private function accessToken(): ?string
    {
        $c = $this->creds();
        if (! $c) {
            return null;
        }

        return Cache::remember('fcm.token.'.md5($c['client_email']), 3000, function () use ($c) {
            $b64 = fn ($v) => rtrim(strtr(base64_encode($v), '+/', '-_'), '=');
            $now = time();
            $head = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claim = $b64(json_encode([
                'iss' => $c['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $c['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            openssl_sign("$head.$claim", $sig, $c['private_key'], 'sha256WithRSAEncryption');
            $jwt = "$head.$claim.".$b64($sig);
            $res = Http::asForm()->timeout(15)->post($c['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            return $res->json('access_token');
        });
    }

    /** إرسال لهدف واحد: ['token' => ...] أو ['topic' => 'all'] */
    public function send(array $target, string $title, string $body, array $data = []): bool
    {
        $token = $this->accessToken();
        if (! $token) {
            return false;
        }
        $message = $target + [
            'notification' => ['title' => $title, 'body' => mb_substr($body, 0, 240)],
            'data' => array_map('strval', $data),
            'android' => ['priority' => 'high', 'notification' => ['sound' => 'default', 'channel_id' => 'ghani_default']],
            'apns' => ['payload' => ['aps' => ['sound' => 'default']]],
        ];
        $res = Http::withToken($token)->timeout(15)
            ->post('https://fcm.googleapis.com/v1/projects/'.$this->projectId().'/messages:send', ['message' => $message]);

        if (! $res->successful() && isset($target['token']) && in_array($res->status(), [400, 404], true)) {
            PushToken::where('token', $target['token'])->delete(); // جهاز ما عاد موجود
        }

        return $res->successful();
    }

    public function toUsers(array $userIds, string $title, string $body, array $data = []): int
    {
        if (! $this->configured()) {
            return 0;
        }
        $sent = 0;
        PushToken::whereIn('user_id', $userIds)->orderBy('id')->chunk(200, function ($tokens) use (&$sent, $title, $body, $data) {
            foreach ($tokens as $t) {
                $sent += $this->send(['token' => $t->token], $title, $body, $data) ? 1 : 0;
            }
        });

        return $sent;
    }

    public function toAll(string $title, string $body, array $data = []): bool
    {
        return $this->configured() && $this->send(['topic' => 'all'], $title, $body, $data);
    }
}
