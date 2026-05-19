<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;

class BalanceEngine
{
    public function __construct(
        private readonly LoanAccountService $loanAccounts,
    ) {}

    public function transactionSum(Account $account): string
    {
        $sum = Transaction::query()
            ->where('account_id', $account->id)
            ->sum('amount');

        return number_format((float) $sum, 2, '.', '');
    }

    public function recalculateAccount(Account $account): void
    {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);

        if ($type === AccountType::Loan) {
            $this->loanAccounts->syncBalances($account);

            return;
        }

        $balance = $this->computedBalance($account);

        if ($account->current_balance === $balance) {
            return;
        }

        $account->update(['current_balance' => $balance]);
    }

    /**
     * Aligns initial_balance so that transactions + initial equals the stated actual balance.
     */
    public function reconcileActualBalance(Account $account, float $actualBalance): void
    {
        $sum = $this->transactionSum($account);
        $newInitial = number_format($actualBalance - (float) $sum, 2, '.', '');
        $balance = number_format((float) $newInitial + (float) $sum, 2, '.', '');

        $account->update([
            'initial_balance' => $newInitial,
            'current_balance' => $balance,
        ]);
    }

    private function computedBalance(Account $account): string
    {
        return number_format(
            (float) $account->initial_balance + (float) $this->transactionSum($account),
            2,
            '.',
            '',
        );
    }
}
