<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\NetWorthSnapshot;
use App\Models\User;
use Carbon\CarbonImmutable;

class NetWorthSnapshotService
{
    public function __construct(
        private readonly BalanceEngine $balanceEngine,
        private readonly PortfolioValuationService $portfolioValuation,
    ) {}

    /**
     * @return array{
     *     total_assets: string,
     *     total_liabilities: string,
     *     net_worth: string,
     *     breakdown: array{accounts: list<array{
     *         id: int,
     *         name: string,
     *         type: string,
     *         balance: string,
     *         positions_value?: string,
     *         bucket: 'asset'|'liability',
     *     }>},
     * }
     */
    public function totalsForUser(User $user): array
    {
        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_archived', false)
            ->orderBy('name')
            ->get();

        $totalAssets = 0.0;
        $totalLiabilities = 0.0;
        $breakdownAccounts = [];

        foreach ($accounts as $account) {
            $this->balanceEngine->recalculateAccount($account);
            $account->refresh();

            $type = $account->type instanceof AccountType
                ? $account->type
                : AccountType::from((string) $account->type);

            $balance = (float) $account->current_balance;
            $positionsValue = $type === AccountType::Invest
                ? $this->portfolioValuation->totalForAccount($account)
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

            $accountRow = [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $type->value,
                'balance' => number_format($balance, 2, '.', ''),
                'bucket' => $bucket,
            ];

            if ($positionsValue > 0) {
                $accountRow['positions_value'] = number_format($positionsValue, 2, '.', '');
            }

            $breakdownAccounts[] = $accountRow;
        }

        $totalAssetsFormatted = number_format($totalAssets, 2, '.', '');
        $totalLiabilitiesFormatted = number_format($totalLiabilities, 2, '.', '');
        $netWorth = number_format($totalAssets - $totalLiabilities, 2, '.', '');

        return [
            'total_assets' => $totalAssetsFormatted,
            'total_liabilities' => $totalLiabilitiesFormatted,
            'net_worth' => $netWorth,
            'breakdown' => ['accounts' => $breakdownAccounts],
        ];
    }

    public function recordForUser(User $user, ?CarbonImmutable $date = null): NetWorthSnapshot
    {
        $snapshotDate = ($date ?? CarbonImmutable::today())->startOfDay();
        $totals = $this->totalsForUser($user);

        $attributes = [
            'total_assets' => $totals['total_assets'],
            'total_liabilities' => $totals['total_liabilities'],
            'net_worth' => $totals['net_worth'],
            'breakdown' => $totals['breakdown'],
        ];

        $existing = NetWorthSnapshot::query()
            ->where('user_id', $user->id)
            ->whereDate('date', $snapshotDate->toDateString())
            ->first();

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing->fresh() ?? $existing;
        }

        return NetWorthSnapshot::query()->create([
            'user_id' => $user->id,
            'date' => $snapshotDate->toDateString(),
            ...$attributes,
        ]);
    }

    /**
     * Last N monthly net worth points for charts (from stored snapshots).
     *
     * @return list<array{month: string, label: string, value: float}>
     */
    public function historyPointsForUser(User $user, int $months = 12): array
    {
        $snapshots = NetWorthSnapshot::query()
            ->where('user_id', $user->id)
            ->orderBy('date')
            ->limit($months)
            ->get();

        if ($snapshots->isEmpty()) {
            return [];
        }

        $locale = app()->getLocale();

        /** @var list<array{month: string, label: string, value: float}> $points */
        $points = [];

        foreach ($snapshots as $snapshot) {
            $date = $snapshot->date instanceof CarbonImmutable
                ? $snapshot->date
                : CarbonImmutable::parse((string) $snapshot->date);

            $points[] = [
                'month' => $date->format('Y-m'),
                'label' => $date->format('M Y'),
                'value' => (float) $snapshot->net_worth,
            ];
        }

        return $points;
    }

    public function recordForAllUsers(?CarbonImmutable $date = null): int
    {
        $count = 0;

        User::query()->orderBy('id')->chunkById(100, function ($users) use ($date, &$count): void {
            foreach ($users as $user) {
                $this->recordForUser($user, $date);
                $count++;
            }
        });

        return $count;
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
