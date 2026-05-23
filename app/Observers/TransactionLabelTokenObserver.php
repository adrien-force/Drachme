<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Transaction;
use App\Services\TransactionLabelTokenService;

final class TransactionLabelTokenObserver
{
    public function __construct(
        private readonly TransactionLabelTokenService $labelTokens,
    ) {}

    public function saved(Transaction $transaction): void
    {
        $this->labelTokens->syncForTransaction($transaction);
    }

    public function deleted(Transaction $transaction): void
    {
        $transaction->labelTokens()->delete();
    }
}
