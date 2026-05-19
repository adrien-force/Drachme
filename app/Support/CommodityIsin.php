<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Deterministic 12-character identifier for commodity positions (unique per label).
 */
final class CommodityIsin
{
    private const PREFIX = 'C';

    public static function fromLabel(string $label): string
    {
        $normalized = mb_strtolower(trim($label));
        $hash = strtoupper(substr(hash('sha256', 'commodity:'.$normalized), 0, 11));

        return self::PREFIX.$hash;
    }
}
