<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Models\IsinMarketSymbol;
use App\Support\Isin;

class IsinSymbolResolver
{
    public function __construct(
        private readonly AlphaVantageClient $client,
    ) {}

    public function resolve(string $isin, ?string $label = null): ?string
    {
        $normalized = Isin::normalize($isin);

        $cached = IsinMarketSymbol::query()->find($normalized);

        if ($cached !== null) {
            return $cached->symbol;
        }

        $keywords = $label !== null && trim($label) !== ''
            ? trim($label)
            : $normalized;

        $body = $this->client->symbolSearch($keywords);
        $matches = $body['bestMatches'] ?? null;

        if (! is_array($matches) || $matches === []) {
            return null;
        }

        $first = $matches[0];

        if (! is_array($first)) {
            return null;
        }

        $symbol = $first['1. symbol'] ?? null;

        if (! is_string($symbol) || $symbol === '') {
            return null;
        }

        IsinMarketSymbol::query()->updateOrCreate(
            ['isin' => $normalized],
            ['symbol' => $symbol, 'source' => 'search'],
        );

        return $symbol;
    }
}
