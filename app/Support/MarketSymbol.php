<?php

declare(strict_types=1);

namespace App\Support;

final class MarketSymbol
{
    public const MAX_LENGTH = 32;

    /**
     * Yahoo Finance tickers: AAPL, IWDA.AS, WPEA.PA, etc.
     */
    public static function isValid(string $symbol): bool
    {
        return preg_match('/^[A-Za-z0-9][A-Za-z0-9.\-]{0,31}$/', $symbol) === 1;
    }

    public static function normalize(?string $symbol): ?string
    {
        if ($symbol === null) {
            return null;
        }

        $normalized = strtoupper(trim($symbol));

        if ($normalized === '' || ! self::isValid($normalized)) {
            return null;
        }

        return $normalized;
    }
}
