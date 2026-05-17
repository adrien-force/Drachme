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

        return (float) Position::query()
            ->where('account_id', $account->id)
            ->get()
            ->sum(fn (Position $position): float => $this->positions->marketValue($position));
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
