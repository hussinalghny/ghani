<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PaymentService;
use Illuminate\Http\Request;

/**
 * رسائل الاستلام من الآيفون عبر «الاختصارات» (أتمتة: عند استلام رسالة من المحفظة).
 * الاختصارات ما يدعم HMAC، فالتحقق بتوكن سري بالهيدر X-Ghani-Token (HTTPS فقط).
 * body: {text, sender}
 */
class PaymentIosHookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments)
    {
        $token = (string) setting('pay_ios_token');
        abort_if($token === '' || ! hash_equals($token, (string) $request->header('X-Ghani-Token')), 401, 'bad token');

        $data = $request->validate([
            'text' => ['required', 'string', 'max:2000'],
            'sender' => ['nullable', 'string', 'max:120'],
        ]);
        $sender = trim((string) ($data['sender'] ?? ''));
        // نفس قاعدة الأندرويد: المحافظ ترسل باسم، مو من رقم عادي
        if ($sender !== '' && ! preg_match('/\p{L}/u', $sender)) {
            return response()->json(['ok' => true, 'status' => 'ignored', 'reason' => 'sender is a phone number']);
        }
        Setting::put(['pay_ios_seen' => now()->toDateTimeString()]);

        $n = $payments->ingest('sms', $sender ?: 'ios', $sender ?: null, $data['text'], now());

        return response()->json(['ok' => true, 'status' => $n->status, 'amount' => $n->amount, 'order' => $n->order_id]);
    }
}
