<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionChanged;
use App\Services\BalanceEngine;

class RecalculateAccountBalance
{
    public function __construct(
        private readonly BalanceEngine $balanceEngine,
    ) {}

    public function handle(TransactionChanged $event): void
    {
        $this->balanceEngine->recalculateAccount($event->account);
    }
}
