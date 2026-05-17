<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\PortfolioSnapshot;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BillingPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class NetWorthHistoryService
{
    public function __construct(
        private readonly PortfolioValuationService $portfolioValuation,
    ) {}

    /**
     * Net worth at each billing period end, from balances + portfolio snapshots over time.
     *
     * @return list<array{
     *     month: string,
     *     label: string,
     *     value: float,
     *     period_start: string,
     *     period_end: string,
     * }>
     */
    public function pointsForUser(
        User $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
        int $monthStartDay,
    ): array {
        $monthStartDay = BillingPeriod::normalizeStartDay($monthStartDay);
        $to = $to->startOfDay();
        $from = $from->startOfDay();

        if ($from->greaterThan($to)) {
            $from = $to;
        }

        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_archived', false)
            ->orderBy('id')
            ->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        $periods = $this->periodsWithinRange($monthStartDay, $from, $to);

        if ($periods === []) {
            return [];
        }

        /** @var array<int, float> $runningBalances */
        $runningBalances = [];

        foreach ($accounts as $account) {
            $runningBalances[$account->id] = (float) $account->initial_balance;
        }

        $accountIds = $accounts->pluck('id')->all();

        $transactions = Transaction::query()
            ->whereIn('account_id', $accountIds)
            ->whereDate('date', '<=', $to->toDateString())
            ->orderBy('date')
            ->orderBy('id')
            ->get(['account_id', 'date', 'amount']);

        $portfolioByAccount = $this->portfolioSnapshotsByAccount($user, $accounts, $to);

        $locale = app()->getLocale();
        $points = [];
        $transactionIndex = 0;
        $transactionCount = $transactions->count();

        foreach ($periods as $period) {
            $periodEnd = $period['end']->toDateString();

            while ($transactionIndex < $transactionCount) {
                $transaction = $transactions->get($transactionIndex);

                if ($transaction === null) {
                    break;
                }

                $transactionDate = $transaction->date instanceof \DateTimeInterface
                    ? $transaction->date->format('Y-m-d')
                    : (string) $transaction->date;

                if ($transactionDate > $periodEnd) {
                    break;
                }

                $accountId = (int) $transaction->account_id;
                $runningBalances[$accountId] = ($runningBalances[$accountId] ?? 0.0)
                    + (float) $transaction->amount;
                $transactionIndex++;
            }

            $points[] = [
                'month' => $period['start']->format('Y-m'),
                'label' => BillingPeriod::formatLabel($period['start'], $period['end'], $locale),
                'value' => round(
                    $this->netWorthFromBalances($accounts, $runningBalances, $portfolioByAccount, $period['end']),
                    2,
                ),
                'period_start' => $period['start']->toDateString(),
                'period_end' => $period['end']->toDateString(),
            ];
        }

        return $points;
    }

    /**
     * @return list<array{start: CarbonImmutable, end: CarbonImmutable}>
     */
    private function periodsWithinRange(
        int $monthStartDay,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $monthsSpan = max(1, (int) $from->diffInMonths($to) + 2);
        $periods = BillingPeriod::recentPeriodsChronological($monthStartDay, $monthsSpan, $to);

        return array_values(array_filter(
            $periods,
            static fn (array $period): bool => $period['end']->greaterThanOrEqualTo($from)
                && $period['start']->lessThanOrEqualTo($to),
        ));
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @param  array<int, float>  $balances
     * @param  array<int, list<array{imported_at: CarbonImmutable, total: float}>>  $portfolioByAccount
     */
    private function netWorthFromBalances(
        Collection $accounts,
        array $balances,
        array $portfolioByAccount,
        CarbonImmutable $asOf,
    ): float {
        $totalAssets = 0.0;
        $totalLiabilities = 0.0;

        foreach ($accounts as $account) {
            $type = $account->type instanceof AccountType
                ? $account->type
                : AccountType::from((string) $account->type);

            $balance = $balances[$account->id] ?? 0.0;
            $positionsValue = $type === AccountType::Invest
                ? $this->portfolioValueAtDate($account, $portfolioByAccount, $asOf)
                : 0.0;
            $bucket = $this->balanceBucket($type, $balance);

            if ($bucket === 'liability') {
                $amount = $type === AccountType::Credit
                    ? max(0.0, $balance)
                    : abs(min(0.0, $balance));
                $totalLiabilities += $amount;
            } else {
                $totalAssets += max(0.0, $balance) + $positionsValue;
            }
        }

        return $totalAssets - $totalLiabilities;
    }

    /**
     * @param  Collection<int, Account>  $accounts
     * @return array<int, list<array{imported_at: CarbonImmutable, total: float}>>
     */
    private function portfolioSnapshotsByAccount(
        User $user,
        Collection $accounts,
        CarbonImmutable $to,
    ): array {
        $investAccountIds = $accounts
            ->filter(fn (Account $account): bool => ($account->type instanceof AccountType
                ? $account->type
                : AccountType::from((string) $account->type)) === AccountType::Invest)
            ->pluck('id')
            ->all();

        if ($investAccountIds === []) {
            return [];
        }

        $snapshots = PortfolioSnapshot::query()
            ->where('user_id', $user->id)
            ->whereIn('account_id', $investAccountIds)
            ->where('imported_at', '<=', $to->endOfDay())
            ->orderBy('imported_at')
            ->orderBy('id')
            ->get(['account_id', 'imported_at', 'total_market_value']);

        /** @var array<int, list<array{imported_at: CarbonImmutable, total: float}>> $grouped */
        $grouped = [];

        foreach ($snapshots as $snapshot) {
            $accountId = (int) $snapshot->account_id;
            $grouped[$accountId] ??= [];
            $grouped[$accountId][] = [
                'imported_at' => CarbonImmutable::parse((string) $snapshot->imported_at),
                'total' => (float) $snapshot->total_market_value,
            ];
        }

        return $grouped;
    }

    /**
     * @param  array<int, list<array{imported_at: CarbonImmutable, total: float}>>  $portfolioByAccount
     */
    private function portfolioValueAtDate(
        Account $account,
        array $portfolioByAccount,
        CarbonImmutable $asOf,
    ): float {
        $history = $portfolioByAccount[$account->id] ?? [];

        $value = 0.0;

        foreach ($history as $entry) {
            if ($entry['imported_at']->lessThanOrEqualTo($asOf->endOfDay())) {
                $value = $entry['total'];
            }
        }

        if ($value > 0) {
            return $value;
        }

        if ($asOf->greaterThanOrEqualTo(CarbonImmutable::today()->startOfDay())) {
            return $this->portfolioValuation->totalForAccount($account);
        }

        return 0.0;
    }

    /**
     * @return 'asset'|'liability'
     */
    private function balanceBucket(AccountType $type, float $balance): string
    {
        if ($type === AccountType::Credit) {
            return 'liability';
        }

        return $balance < 0 ? 'liability' : 'asset';
    }
}
