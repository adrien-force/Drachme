<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;

class BalanceEngine
{
    public function recalculateAccount(Account $account): void
    {
        $sum = Transaction::query()
            ->where('account_id', $account->id)
            ->sum('amount');

        $balance = number_format(
            (float) $account->initial_balance + (float) $sum,
            2,
            '.',
            '',
        );

        if ($account->current_balance === $balance) {
            return;
        }

        $account->update(['current_balance' => $balance]);
    }
}
