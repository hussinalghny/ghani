<?php

namespace App\Http\Controllers;

use App\Services\VoucherService;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $transactions = $user->transactions()->paginate(15);

        return view('account.wallet', compact('user', 'transactions'));
    }

    public function redeem(Request $request, VoucherService $vouchers)
    {
        $request->validate(['code' => ['required', 'string', 'max:40']], [], ['code' => 'كود القسيمة']);
        $result = $vouchers->redeem($request->user(), $request->input('code'));

        if ($result['type'] === 'course') {
            return redirect()->route('learn.show', $result['course'])
                ->with('success', 'تم تفعيل كورس '.$result['course']->title.'. ابدأ الآن.');
        }

        return back()->with('success', 'تمت إضافة '.iqd($result['amount']).' إلى رصيدك.');
    }
}
