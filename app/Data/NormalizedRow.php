<?php

namespace App\Data;

use Carbon\CarbonImmutable;

readonly class NormalizedRow
{
    public function __construct(
        public CarbonImmutable $date,
        public string $label,
        public float $amount,
    ) {}
}
