<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

final readonly class MarketPriceRefreshResult
{
    public function __construct(
        public int $updated,
        public int $skipped,
        public int $failed,
        public ?string $quotaMessage = null,
    ) {}
}
