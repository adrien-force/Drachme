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
            $bucket = $this->balanceBucket($type, $balance);

            if ($bucket === 'liability') {
                $amount = $type === AccountType::Credit
                    ? max(0.0, $balance)
                    : abs(min(0.0, $balance));
                $totalLiabilities += $amount;
            } else {
                $totalAssets += max(0.0, $balance);
            }

            $breakdownAccounts[] = [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $type->value,
                'balance' => number_format($balance, 2, '.', ''),
                'bucket' => $bucket,
            ];
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
        $snapshotDate = ($date ?? CarbonImmutable::today())->toDateString();
        $totals = $this->totalsForUser($user);

        return NetWorthSnapshot::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'date' => $snapshotDate,
            ],
            [
                'total_assets' => $totals['total_assets'],
                'total_liabilities' => $totals['total_liabilities'],
                'net_worth' => $totals['net_worth'],
                'breakdown' => $totals['breakdown'],
            ],
        );
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
