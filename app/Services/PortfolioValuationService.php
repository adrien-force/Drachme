<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Position;
use App\Models\User;

class PortfolioValuationService
{
    public function __construct(
        private readonly PositionService $positions,
    ) {}

    public function totalForAccount(Account $account): float
    {
        if ($account->type !== AccountType::Invest) {
            return 0.0;
        }

        return $this->totalsByAccountId([$account])[$account->id] ?? 0.0;
    }

    /**
     * @param  iterable<Account>  $accounts
     * @return array<int, float>
     */
    public function totalsByAccountId(iterable $accounts): array
    {
        $ids = [];
        foreach ($accounts as $account) {
            if ($account->type === AccountType::Invest) {
                $ids[] = $account->id;
            }
        }

        if ($ids === []) {
            return [];
        }

        /** @var array<int, float> $totals */
        $totals = array_fill_keys($ids, 0.0);

        Position::query()
            ->whereIn('account_id', $ids)
            ->get()
            ->each(function (Position $position) use (&$totals): void {
                $accountId = (int) $position->account_id;
                $totals[$accountId] += $this->positions->marketValue($position);
            });

        return $totals;
    }

    public function totalForUser(User $user): float
    {
        $accountIds = Account::query()
            ->where('user_id', $user->id)
            ->where('is_archived', false)
            ->where('type', AccountType::Invest)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return 0.0;
        }

        return (float) Position::query()
            ->whereIn('account_id', $accountIds)
            ->get()
            ->sum(fn (Position $position): float => $this->positions->marketValue($position));
    }
}
