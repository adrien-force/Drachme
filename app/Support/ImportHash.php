<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonInterface;

final class ImportHash
{
    public static function make(
        int $accountId,
        CarbonInterface $date,
        float $amount,
        string $label,
    ): string {
        $normalizedLabel = mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $label)));
        $amountKey = number_format($amount, 2, '.', '');

        return hash('sha256', "{$accountId}|{$date->format('Y-m-d')}|{$amountKey}|{$normalizedLabel}");
    }
}
