<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Exceptions\MarketDataQuotaExceededException;
use App\Support\MarketDataLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class YahooFinanceClient
{
    /**
     * Returns a unit price string (6 decimals) or null when unavailable.
     */
    public function quotePrice(string $yahooSymbol): ?string
    {
        $body = $this->chartRequest($yahooSymbol, [
            'interval' => '1d',
            'range' => '5d',
        ]);

        if ($body === null) {
            return null;
        }

        $meta = $this->resultMeta($body);
        $priceRaw = $meta['regularMarketPrice'] ?? null;

        if (! is_string($priceRaw) && ! is_numeric($priceRaw)) {
            return null;
        }

        return number_format((float) $priceRaw, 6, '.', '');
    }

    /**
     * @return list<array{ date: string, price: float }>
     */
    public function historicalDailyClose(string $yahooSymbol, ?int $limit = null): array
    {
        $body = $this->chartRequest($yahooSymbol, [
            'interval' => '1d',
            'range' => '1y',
        ]);

        if ($body === null) {
            return [];
        }

        $result = $this->firstResult($body);

        if ($result === null) {
            return [];
        }

        /** @var list<int>|null $timestamps */
        $timestamps = $result['timestamp'] ?? null;

        /** @var list<array<string, mixed>>|null $quoteBlocks */
        $quoteBlocks = $result['indicators']['quote'] ?? null;

        if (! is_array($timestamps) || $timestamps === [] || ! is_array($quoteBlocks) || $quoteBlocks === []) {
            return [];
        }

        $closes = $quoteBlocks[0]['close'] ?? null;

        if (! is_array($closes)) {
            return [];
        }

        $points = [];

        foreach ($timestamps as $index => $timestamp) {
            if (! is_int($timestamp)) {
                continue;
            }

            $close = $closes[$index] ?? null;

            if (! is_string($close) && ! is_numeric($close)) {
                continue;
            }

            $points[] = [
                'date' => gmdate('Y-m-d', $timestamp),
                'price' => (float) $close,
            ];
        }

        usort($points, static fn (array $a, array $b): int => strcmp($a['date'], $b['date']));

        $max = $limit ?? (int) config('market_data.history_limit', 100);

        if (count($points) > $max) {
            $points = array_slice($points, -$max);
        }

        return $points;
    }

    /**
     * @param  array<string, string>  $query
     * @return array<string, mixed>|null
     */
    private function chartRequest(string $yahooSymbol, array $query): ?array
    {
        $symbol = strtoupper(trim($yahooSymbol));

        try {
            $response = Http::timeout(20)
                ->withHeaders(['User-Agent' => $this->userAgent()])
                ->acceptJson()
                ->get($this->chartUrl($symbol), $query);
        } catch (ConnectionException $exception) {
            MarketDataLogger::warning('yahoo_connection_failed', [
                'symbol' => $symbol,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->logResponse($symbol, $response);
        $this->guardAgainstQuota($response);

        if (! $response->successful()) {
            return null;
        }

        /** @var array<string, mixed>|null $body */
        $body = $response->json();

        return $body;
    }

    private function chartUrl(string $symbol): string
    {
        $base = rtrim((string) config('market_data.yahoo.chart_base_url'), '/');

        return $base.'/'.rawurlencode($symbol);
    }

    private function userAgent(): string
    {
        return (string) config('market_data.yahoo.user_agent');
    }

    private function logResponse(string $symbol, Response $response): void
    {
        MarketDataLogger::info('yahoo_chart_request', [
            'symbol' => $symbol,
            'status' => $response->status(),
        ]);
    }

    private function guardAgainstQuota(Response $response): void
    {
        if ($response->status() === 429) {
            throw new MarketDataQuotaExceededException('yahoo_rate_limit');
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function resultMeta(array $body): array
    {
        $result = $this->firstResult($body);

        if ($result === null) {
            return [];
        }

        /** @var array<string, mixed> $meta */
        $meta = $result['meta'] ?? [];

        return is_array($meta) ? $meta : [];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>|null
     */
    private function firstResult(array $body): ?array
    {
        /** @var array<string, mixed>|null $chart */
        $chart = $body['chart'] ?? null;

        if (! is_array($chart)) {
            return null;
        }

        /** @var list<array<string, mixed>>|null $results */
        $results = $chart['result'] ?? null;

        if (! is_array($results) || $results === []) {
            return null;
        }

        $first = $results[0];

        return is_array($first) ? $first : null;
    }
}
