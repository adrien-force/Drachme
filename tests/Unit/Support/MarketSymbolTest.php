<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\MarketSymbol;
use PHPUnit\Framework\TestCase;

class MarketSymbolTest extends TestCase
{
    public function test_normalize_accepts_common_tickers(): void
    {
        $this->assertSame('IBM', MarketSymbol::normalize('ibm'));
        $this->assertSame('IWDA.AS', MarketSymbol::normalize(' iwda.as '));
    }

    public function test_normalize_rejects_invalid_symbols(): void
    {
        $this->assertNull(MarketSymbol::normalize(''));
        $this->assertNull(MarketSymbol::normalize('bad ticker'));
    }
}
