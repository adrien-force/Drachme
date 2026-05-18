<?php

declare(strict_types=1);

namespace App\Http\Requests\Positions\Concerns;

use App\Support\MarketSymbol;

trait ValidatesPositionMarketSymbol
{
    /**
     * @return array<int, mixed>
     */
    protected function marketSymbolRules(): array
    {
        return [
            'nullable',
            'string',
            'max:'.MarketSymbol::MAX_LENGTH,
            'regex:/^[A-Za-z0-9][A-Za-z0-9.\-]{0,31}$/',
        ];
    }

    protected function normalizeMarketSymbolInput(): void
    {
        if (! $this->has('market_symbol')) {
            return;
        }

        $raw = $this->input('market_symbol');

        if (! is_string($raw) || trim($raw) === '') {
            $this->merge(['market_symbol' => null]);

            return;
        }

        $this->merge([
            'market_symbol' => MarketSymbol::normalize($raw),
        ]);
    }
}
