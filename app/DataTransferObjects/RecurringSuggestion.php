<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Enums\RecurringFrequency;
use App\Enums\TransactionType;
use App\Models\Transaction;

readonly class RecurringSuggestion
{
    /**
     * @param  list<Transaction>  $sampleTransactions
     */
    public function __construct(
        public string $labelPattern,
        public string $displayLabel,
        public string $expectedAmount,
        public RecurringFrequency $frequency,
        public TransactionType $transactionType,
        public int $occurrenceCount,
        public int $score,
        public ?int $suggestedCategoryId,
        public ?int $accountId,
        public array $sampleTransactions,
    ) {}
}
