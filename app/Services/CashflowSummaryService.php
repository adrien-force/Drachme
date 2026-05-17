<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;
use App\Models\User;
use Carbon\CarbonInterface;

class CashflowSummaryService
{
    /**
     * Income and expense totals for a period, excluding internal transfers.
     *
     * @return array{income: string, expense: string, net: string}
     */
    public function totalsForPeriod(
        User $user,
        CarbonInterface $from,
        CarbonInterface $to,
    ): array {
        $base = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', '!=', TransactionType::Transfer)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString());

        $income = (clone $base)
            ->where('type', TransactionType::Income)
            ->sum('amount');

        $expense = (clone $base)
            ->where('type', TransactionType::Expense)
            ->sum('amount');

        $incomeFormatted = number_format((float) $income, 2, '.', '');
        $expenseFormatted = number_format((float) $expense, 2, '.', '');
        $net = (float) $incomeFormatted + (float) $expenseFormatted;

        return [
            'income' => $incomeFormatted,
            'expense' => $expenseFormatted,
            'net' => number_format($net, 2, '.', ''),
        ];
    }
}
