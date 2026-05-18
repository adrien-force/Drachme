<?php

declare(strict_types=1);

namespace Tests\Support;

final class YahooChartFixture
{
    /**
     * @param  list<float>  $closes
     * @return array<string, mixed>
     */
    public static function chart(float $regularMarketPrice, array $closes = []): array
    {
        if ($closes === []) {
            $closes = [$regularMarketPrice];
        }

        $timestamps = [];
        $start = strtotime('2026-01-01 UTC');

        foreach (array_keys($closes) as $index) {
            $timestamps[] = $start + ($index * 86_400);
        }

        return [
            'chart' => [
                'result' => [
                    [
                        'meta' => [
                            'regularMarketPrice' => $regularMarketPrice,
                        ],
                        'timestamp' => $timestamps,
                        'indicators' => [
                            'quote' => [
                                ['close' => $closes],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function openFigiMapping(string $ticker, string $exchCode): array
    {
        return [
            [
                'data' => [
                    [
                        'ticker' => $ticker,
                        'exchCode' => $exchCode,
                        'securityType' => 'ETP',
                    ],
                ],
            ],
        ];
    }
}
