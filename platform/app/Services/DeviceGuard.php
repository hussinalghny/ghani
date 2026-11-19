<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\Device;
use App\Models\User;

/**
 * ربط الحساب بعدد محدد من الأجهزة لمنع مشاركة الحسابات.
 * الجهاز الجديد يقبل فقط إذا ما وصل الطالب للحد الأعلى، والإدارة تقدر تصفر الأجهزة.
 */
class DeviceGuard
{
    public function register(User $user, string $deviceId, string $platform, ?string $name, ?string $ip): Device
    {
        $deviceId = substr(trim($deviceId), 0, 100);
        if ($deviceId === '') {
            throw new AcademyException('تعذر التعرف على الجهاز.', 'device_missing');
        }

        $device = $user->devices()->where('device_id', $deviceId)->first();

        if ($device?->is_blocked) {
            throw new AcademyException('هذا الجهاز محظور من الدخول. تواصل مع الدعم.', 'device_blocked', 403);
        }

        if (! $device && ! $user->isAdmin()) {
            $max = (int) setting('max_devices');
            $count = $user->devices()->where('is_blocked', false)->count();
            if ($max > 0 && $count >= $max) {
                throw new AcademyException(
                    "حسابك مربوط بـ $max أجهزة وهذا هو الحد الأعلى. تواصل مع الدعم على الواتساب لإعادة تعيين الأجهزة.",
                    'device_limit',
                    403
                );
            }
        }

        $device ??= new Device(['user_id' => $user->id, 'device_id' => $deviceId]);
        $device->fill([
            'platform' => in_array($platform, ['web', 'android', 'ios'], true) ? $platform : 'web',
            'name' => $name ? mb_substr($name, 0, 120) : $device->name,
            'last_ip' => $ip,
            'last_seen_at' => now(),
        ])->save();

        return $device;
    }
}
