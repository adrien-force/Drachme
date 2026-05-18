<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MarketData;

use App\Services\MarketData\YahooExchangeSuffix;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YahooExchangeSuffixTest extends TestCase
{
    #[DataProvider('exchangeProvider')]
    public function test_maps_openfigi_exchange_to_yahoo_suffix(string $ticker, string $exchCode, string $expected): void
    {
        $this->assertSame($expected, YahooExchangeSuffix::toYahooSymbol($ticker, $exchCode));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function exchangeProvider(): array
    {
        return [
            'paris' => ['WPEA', 'FP', 'WPEA.PA'],
            'amsterdam' => ['IWDA', 'NA', 'IWDA.AS'],
            'us' => ['AAPL', 'US', 'AAPL'],
            'london' => ['VOD', 'LN', 'VOD.L'],
        ];
    }
}
