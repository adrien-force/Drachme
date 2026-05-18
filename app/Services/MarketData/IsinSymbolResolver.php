<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Models\IsinMarketSymbol;
use App\Support\Isin;
use App\Support\MarketSymbol;

class IsinSymbolResolver
{
    public function __construct(
        private readonly OpenFigiClient $openFigi,
    ) {}

    /**
     * Resolves the Yahoo ticker: manual symbol, DB cache, then OpenFIGI ISIN lookup.
     */
    public function resolve(string $isin, ?string $label = null, ?string $marketSymbol = null): ?string
    {
        $explicit = MarketSymbol::normalize($marketSymbol);

        if ($explicit !== null) {
            return $explicit;
        }

        $normalized = Isin::normalize($isin);
        $cached = IsinMarketSymbol::query()->find($normalized);

        if ($cached !== null) {
            return $cached->symbol;
        }

        $yahooSymbol = $this->openFigi->resolveYahooSymbol($normalized);

        if ($yahooSymbol === null) {
            return null;
        }

        $normalizedSymbol = MarketSymbol::normalize($yahooSymbol);

        if ($normalizedSymbol === null) {
            return null;
        }

        IsinMarketSymbol::query()->updateOrCreate(
            ['isin' => $normalized],
            ['symbol' => $normalizedSymbol, 'source' => 'openfigi'],
        );

        return $normalizedSymbol;
    }
}
