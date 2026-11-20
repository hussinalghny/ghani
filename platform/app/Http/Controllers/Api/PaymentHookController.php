<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * يستقبل إشعارات الاستلام من تطبيق «غني باي» (أندرويد).
 * التوقيع: X-Ghani-Sign = HMAC-SHA256(X-Ghani-Time + "." + body, pay_hook_secret)
 */
class PaymentHookController extends Controller
{
    public function __invoke(Request $request, PaymentService $payments)
    {
        $secret = (string) setting('pay_hook_secret');
        $time = (int) $request->header('X-Ghani-Time');
        $sign = (string) $request->header('X-Ghani-Sign');
        abort_if($secret === '' || abs(time() - $time) > 600, 401, 'expired');
        abort_unless(hash_equals(hash_hmac('sha256', $time.'.'.$request->getContent(), $secret), $sign), 401, 'bad signature');

        $data = $request->validate([
            'source' => ['nullable', 'in:notification,sms,test'],
            'package' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:2000'],
            'posted_at' => ['nullable', 'integer'],
        ]);
        \App\Models\Setting::put(['pay_hook_seen' => now()->toDateTimeString()]);
        if (($data['source'] ?? null) === 'test') {
            return response()->json(['ok' => true, 'test' => true]);
        }

        $posted = isset($data['posted_at']) ? Carbon::createFromTimestampMs($data['posted_at']) : now();
        $n = $payments->ingest($data['source'] ?? 'notification', $data['package'] ?? null, $data['title'] ?? null, $data['text'], $posted);

        return response()->json(['ok' => true, 'status' => $n->status, 'amount' => $n->amount, 'order' => $n->order_id]);
    }
}
