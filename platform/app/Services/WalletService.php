<?php

namespace App\Services;

use App\Exceptions\AcademyException;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * يضيف أو يخصم من رصيد المستخدم داخل transaction مع قفل السطر
     * لتجنب الصرف المزدوج عند الضغط مرتين أو الطلبات المتزامنة.
     */
    public function apply(User $user, int $amount, string $type, ?string $note = null, ?string $reference = null): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $note, $reference) {
            /** @var User $locked */
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            $newBalance = $locked->balance + $amount;
            if ($newBalance < 0) {
                throw new AcademyException(
                    'رصيدك '.iqd($locked->balance).' والمطلوب '.iqd(abs($amount)).'. اشحن رصيدك بقسيمة وارجع.',
                    'insufficient_balance'
                );
            }

            $locked->forceFill(['balance' => $newBalance])->save();
            $user->balance = $newBalance;

            return $locked->transactions()->create([
                'type' => $type,
                'amount' => $amount,
                'balance_after' => $newBalance,
                'note' => $note,
                'reference' => $reference,
            ]);
        });
    }

    public function credit(User $user, int $amount, string $type, ?string $note = null, ?string $reference = null): WalletTransaction
    {
        return $this->apply($user, abs($amount), $type, $note, $reference);
    }

    public function debit(User $user, int $amount, string $type, ?string $note = null, ?string $reference = null): WalletTransaction
    {
        return $this->apply($user, -abs($amount), $type, $note, $reference);
    }
}
