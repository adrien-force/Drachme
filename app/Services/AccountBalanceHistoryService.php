<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Support\AccountNetWorth;
use Carbon\CarbonImmutable;

class AccountBalanceHistoryService
{
    private const DEFAULT_DAYS = 90;

    /**
     * @return array{
     *     points: list<array{date: string, balance: float}>,
     *     from: string,
     *     to: string,
     *     is_all_time: bool,
     *     mode: 'balance'|'amount_owed',
     * }
     */
    public function build(
        Account $account,
        ?string $from,
        ?string $to,
        bool $allTime = false,
    ): array {
        $type = $account->type instanceof AccountType
            ? $account->type
            : AccountType::from((string) $account->type);
        $amountOwedMode = $type === AccountType::CreditCard;

        $today = CarbonImmutable::today();

        $toDate = $to !== null && $to !== ''
            ? CarbonImmutable::parse($to)->startOfDay()
            : $today;

        if ($toDate->greaterThan($today)) {
            $toDate = $today;
        }

        if ($allTime) {
            $fromDate = $this->resolveAllTimeStart($account, $toDate);
        } elseif ($from !== null && $from !== '') {
            $fromDate = CarbonImmutable::parse($from)->startOfDay();
        } else {
            $fromDate = $toDate->subDays(self::DEFAULT_DAYS);
        }

        if (! $allTime) {
            $openedAt = $account->opened_at;
            if ($openedAt !== null) {
                $opened = CarbonImmutable::parse((string) $openedAt)->startOfDay();
                if ($fromDate->lessThan($opened)) {
                    $fromDate = $opened;
                }
            }
        }

        if ($fromDate->greaterThan($toDate)) {
            $fromDate = $toDate;
        }

        $balanceBefore = (float) $account->initial_balance
            + (float) Transaction::query()
                ->where('account_id', $account->id)
                ->where('date', '<', $fromDate->toDateString())
                ->sum('amount');

        $transactions = Transaction::query()
            ->where('account_id', $account->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get(['date', 'amount']);

        /** @var array<string, list<float>> $amountsByDate */
        $amountsByDate = [];

        foreach ($transactions as $transaction) {
            $date = $transaction->date;
            $dateStr = $date instanceof \DateTimeInterface
                ? $date->format('Y-m-d')
                : (string) $date;
            $amountsByDate[$dateStr][] = (float) $transaction->amount;
        }

        $points = [];
        $current = $balanceBefore;
        $cursor = $fromDate;

        while ($cursor->lessThanOrEqualTo($toDate)) {
            $dateStr = $cursor->format('Y-m-d');

            if (isset($amountsByDate[$dateStr])) {
                foreach ($amountsByDate[$dateStr] as $amount) {
                    $current += $amount;
                }
            }

            $displayBalance = $amountOwedMode
                ? AccountNetWorth::creditCardAmountOwed($current)
                : $current;

            $points[] = [
                'date' => $dateStr,
                'balance' => round($displayBalance, 2),
            ];

            $cursor = $cursor->addDay();
        }

        if ($points === []) {
            $displayBefore = $amountOwedMode
                ? AccountNetWorth::creditCardAmountOwed($balanceBefore)
                : $balanceBefore;

            $points[] = [
                'date' => $fromDate->format('Y-m-d'),
                'balance' => round($displayBefore, 2),
            ];
        }

        return [
            'points' => $points,
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
            'is_all_time' => $allTime,
            'mode' => $amountOwedMode ? 'amount_owed' : 'balance',
        ];
    }

    private function resolveAllTimeStart(Account $account, CarbonImmutable $toDate): CarbonImmutable
    {
        $candidates = [];

        $openedAt = $account->opened_at;
        if ($openedAt !== null) {
            $candidates[] = CarbonImmutable::parse((string) $openedAt)->startOfDay();
        }

        $firstTransactionDate = Transaction::query()
            ->where('account_id', $account->id)
            ->min('date');

        if ($firstTransactionDate !== null) {
            $candidates[] = CarbonImmutable::parse((string) $firstTransactionDate)->startOfDay();
        }

        if ($candidates === []) {
            return $toDate;
        }

        $start = $candidates[0];
        foreach ($candidates as $candidate) {
            if ($candidate->lessThan($start)) {
                $start = $candidate;
            }
        }

        return $start;
    }
}
