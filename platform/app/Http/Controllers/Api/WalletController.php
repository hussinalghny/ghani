<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VoucherService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tx = $user->transactions()->paginate(20);

        return response()->json([
            'balance' => (int) $user->balance,
            'balance_label' => iqd($user->balance),
            'transactions' => collect($tx->items())->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'type_label' => $t->typeLabel(),
                'amount' => (int) $t->amount,
                'balance_after' => (int) $t->balance_after,
                'note' => $t->note,
                'created_at' => $t->created_at->toIso8601String(),
            ]),
            'meta' => ['current_page' => $tx->currentPage(), 'last_page' => $tx->lastPage()],
        ]);
    }

    public function redeem(Request $request, VoucherService $vouchers)
    {
        $request->validate(['code' => ['required', 'string', 'max:40']]);
        $result = $vouchers->redeem($request->user(), $request->input('code'));

        return response()->json([
            'type' => $result['type'],
            'message' => $result['type'] === 'course'
                ? 'تم تفعيل كورس '.$result['course']->title.'.'
                : 'تمت إضافة '.iqd($result['amount']).' إلى رصيدك.',
            'course' => $result['course'] ? ['id' => $result['course']->id, 'slug' => $result['course']->slug] : null,
            'balance' => (int) $request->user()->balance,
        ]);
    }
}
