<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\TransactionChanged;
use App\Services\CreditCardSettlementSyncService;

class SyncCreditCardSettlements
{
    public function __construct(
        private readonly CreditCardSettlementSyncService $sync,
    ) {}

    public function handle(TransactionChanged $event): void
    {
        $account = $event->account;

        if ($account->is_archived) {
            return;
        }

        $this->sync->syncForAccount($account);
    }
}
