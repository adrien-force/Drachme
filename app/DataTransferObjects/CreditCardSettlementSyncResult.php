<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

readonly class CreditCardSettlementSyncResult
{
    public function __construct(
        public int $linkedPairs = 0,
        public int $markedSettlements = 0,
        public int $skippedMissingConfig = 0,
        public int $skippedNoMatch = 0,
    ) {}

    public function hasChanges(): bool
    {
        return $this->linkedPairs > 0 || $this->markedSettlements > 0;
    }
}
