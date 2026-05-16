<?php

declare(strict_types=1);

namespace App\Data;

use Carbon\CarbonImmutable;

readonly class NormalizedRow
{
    public function __construct(
        public CarbonImmutable $date,
        public string $label,
        public float $amount,
        public ?float $balance = null,
    ) {}
}
