<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;

class AccountBalanceService
{
    public function recalculate(Account $account): void
    {
        $sum = Transaction::query()
            ->where('account_id', $account->id)
            ->sum('amount');

        $account->update([
            'current_balance' => (string) ((float) $account->initial_balance + (float) $sum),
        ]);
    }
}
