<?php

declare(strict_types=1);

namespace App\Http\Requests\Positions\Concerns;

use App\Enums\AccountType;
use App\Models\Account;

trait ValidatesInvestAccount
{
    protected function investAccount(): Account
    {
        /** @var Account $account */
        $account = $this->route('account');

        return $account;
    }

    protected function accountIsInvest(): bool
    {
        return $this->investAccount()->type === AccountType::Invest;
    }
}
