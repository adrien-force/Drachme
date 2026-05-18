<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BillingPeriod;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

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
            ->whereHas(
                'account',
                static fn (Builder $accountQuery): Builder => $accountQuery
                    ->where('type', '!=', AccountType::CreditCard),
            )
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

    /**
     * Monthly income vs expenses for the dashboard (user billing period).
     *
     * @return list<array{
     *     month: string,
     *     label: string,
     *     income: float,
     *     expense: float,
     *     period_start: string,
     *     period_end: string,
     * }>
     */
    public function monthlySeriesForUser(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        bool $includeInvestAccounts = false,
    ): array {
        $from = $from->startOfDay();
        $to = $to->startOfDay();

        if ($from->greaterThan($to)) {
            $from = $to;
        }

        $locale = $user->locale ?? config('app.locale', 'fr');
        $monthStartDay = $this->userMonthStartDay($user);
        $monthsSpan = max(1, (int) $from->diffInMonths($to) + 2);

        $chartPeriods = array_values(array_filter(
            BillingPeriod::recentPeriodsChronological($monthStartDay, $monthsSpan, $to),
            static fn (array $period): bool => $period['end']->greaterThanOrEqualTo($from)
                && $period['start']->lessThanOrEqualTo($to),
        ));

        if ($chartPeriods === []) {
            return [];
        }

        $lookbackStart = $chartPeriods[0]['start'];
        $transactions = $this->scopedTransactionQuery(
            $user,
            $lookbackStart,
            $to,
            $includeInvestAccounts,
        )->get(['date', 'amount']);

        $points = [];

        foreach ($chartPeriods as $period) {
            $income = 0.0;
            $expense = 0.0;

            foreach ($transactions as $transaction) {
                $transactionDate = CarbonImmutable::parse((string) $transaction->date);

                if ($transactionDate->lt($period['start']) || $transactionDate->gt($period['end'])) {
                    continue;
                }

                $amount = (float) $transaction->amount;
                if ($amount > 0) {
                    $income += $amount;
                } elseif ($amount < 0) {
                    $expense += abs($amount);
                }
            }

            $points[] = [
                'month' => $period['start']->format('Y-m'),
                'label' => BillingPeriod::formatLabel($period['start'], $period['end'], $locale),
                'income' => $income,
                'expense' => $expense,
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
            ];
        }

        return $points;
    }

    public function currentPeriodNetForUser(
        User $user,
        ?CarbonImmutable $referenceDate = null,
        bool $includeInvestAccounts = false,
    ): float {
        $reference = ($referenceDate ?? CarbonImmutable::today())->startOfDay();
        $bounds = BillingPeriod::boundsContaining(
            $reference,
            $this->userMonthStartDay($user),
        );

        $totals = $this->totalsForPeriodByAmount(
            $user,
            $bounds['start'],
            $bounds['end'],
            $includeInvestAccounts,
        );

        return (float) $totals['income'] - (float) $totals['expense'];
    }

    /**
     * Income / expense totals using amount signs (dashboard rules).
     *
     * @return array{income: string, expense: string, net: string}
     */
    public function totalsForPeriodByAmount(
        User $user,
        CarbonInterface $from,
        CarbonInterface $to,
        bool $includeInvestAccounts = false,
    ): array {
        $base = $this->scopedTransactionQuery($user, $from, $to, $includeInvestAccounts);

        $income = (clone $base)
            ->where('amount', '>', 0)
            ->sum('amount');

        $expenseSum = (clone $base)
            ->where('amount', '<', 0)
            ->sum('amount');

        $incomeFormatted = number_format((float) $income, 2, '.', '');
        $expenseFormatted = number_format(abs((float) $expenseSum), 2, '.', '');
        $net = (float) $incomeFormatted - (float) $expenseFormatted;

        return [
            'income' => $incomeFormatted,
            'expense' => $expenseFormatted,
            'net' => number_format($net, 2, '.', ''),
        ];
    }

    private function userMonthStartDay(User $user): int
    {
        return BillingPeriod::normalizeStartDay((int) ($user->month_start_day ?? 1));
    }

    /**
     * @return Builder<Transaction>
     */
    private function scopedTransactionQuery(
        User $user,
        CarbonInterface $from,
        CarbonInterface $to,
        bool $includeInvestAccounts,
    ): Builder {
        $accountIds = Account::query()
            ->where('user_id', $user->id)
            ->active()
            ->where('type', '!=', AccountType::CreditCard)
            ->when(
                ! $includeInvestAccounts,
                fn (Builder $accountQuery): Builder => $accountQuery->where('type', '!=', AccountType::Invest),
            )
            ->pluck('id');

        return Transaction::query()
            ->where('user_id', $user->id)
            ->whereIn('account_id', $accountIds)
            ->where('type', '!=', TransactionType::Transfer)
            ->whereDate('date', '>=', $from->toDateString())
            ->whereDate('date', '<=', $to->toDateString());
    }
}
