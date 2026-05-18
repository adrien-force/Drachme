<?php

declare(strict_types=1);

namespace App\Services\MarketData;

/**
 * Maps OpenFIGI exchange codes to Yahoo Finance ticker suffixes.
 */
final class YahooExchangeSuffix
{
    /** @var array<string, string> */
    private const SUFFIX_BY_EXCH_CODE = [
        'FP' => 'PA',
        'NA' => 'AS',
        'LN' => 'L',
        'LO' => 'L',
        'GY' => 'DE',
        'GR' => 'DE',
        'SW' => 'SW',
        'IM' => 'MI',
        'SS' => 'ST',
        'DC' => 'CO',
        'NO' => 'OL',
        'HK' => 'HK',
        'TO' => 'TO',
        'AU' => 'AX',
    ];

    /** @var list<string> */
    private const PREFERRED_EXCH_CODES = [
        'FP',
        'NA',
        'LN',
        'GY',
        'SW',
        'IM',
        'US',
    ];

    /**
     * @return list<string>
     */
    public static function preferredExchangeCodes(): array
    {
        return self::PREFERRED_EXCH_CODES;
    }

    public static function toYahooSymbol(string $ticker, string $exchCode): string
    {
        $normalizedTicker = strtoupper(trim($ticker));
        $suffix = self::SUFFIX_BY_EXCH_CODE[strtoupper($exchCode)] ?? null;

        if ($suffix === null) {
            return $normalizedTicker;
        }

        return $normalizedTicker.'.'.$suffix;
    }
}
