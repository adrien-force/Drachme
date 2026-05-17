<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;

/**
 * @deprecated Prefer BalanceEngine::recalculateAccount — kept for existing injections.
 */
class AccountBalanceService
{
    public function __construct(
        private readonly BalanceEngine $balanceEngine,
    ) {}

    public function recalculate(Account $account): void
    {
        $this->balanceEngine->recalculateAccount($account);
    }
}
