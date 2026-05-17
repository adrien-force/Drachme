<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\Transaction;

readonly class TransferSuggestion
{
    public function __construct(
        public Transaction $outgoing,
        public Transaction $incoming,
        public int $score,
    ) {}

    /**
     * @return array{int, int}
     */
    public function canonicalPairIds(): array
    {
        $first = $this->outgoing->id;
        $second = $this->incoming->id;

        return $first < $second
            ? [$first, $second]
            : [$second, $first];
    }
}
