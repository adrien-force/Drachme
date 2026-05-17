<?php

declare(strict_types=1);

namespace App\Support;

final class Isin
{
    public const int LENGTH = 12;

    public static function normalize(string $isin): string
    {
        return strtoupper(trim($isin));
    }

    public static function isValid(string $isin): bool
    {
        return preg_match('/^[A-Z0-9]{12}$/', self::normalize($isin)) === 1;
    }
}
