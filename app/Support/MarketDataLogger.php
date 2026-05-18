<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Market-data logs must never break price refresh when storage permissions fail.
 */
final class MarketDataLogger
{
    /**
     * @param  array<string, mixed>  $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        $logged = rescue(
            fn (): bool => (bool) Log::channel('market_data')->{$level}($message, $context),
            false,
            false,
        );

        if ($logged === true) {
            return;
        }

        rescue(
            fn (): bool => (bool) Log::{$level}('[market_data] '.$message, $context),
            null,
            false,
        );
    }
}
