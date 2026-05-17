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

    /** Unique 64-char hash when the base hash collides (same file or import-anyway). */
    public static function disambiguate(string $baseHash, int $batchId, int $line): string
    {
        return hash('sha256', "{$baseHash}|{$batchId}|{$line}");
    }
}
