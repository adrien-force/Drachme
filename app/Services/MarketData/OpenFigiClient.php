<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Exceptions\MarketDataQuotaExceededException;
use App\Support\Isin;
use App\Support\MarketDataLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenFigiClient
{
    /**
     * Resolves a Yahoo Finance symbol (e.g. WPEA.PA) from an ISIN, or null.
     */
    public function resolveYahooSymbol(string $isin): ?string
    {
        $normalized = Isin::normalize($isin);

        try {
            $response = $this->mappingRequest($normalized);
        } catch (ConnectionException $exception) {
            MarketDataLogger::warning('openfigi_connection_failed', [
                'isin' => $normalized,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->guardAgainstQuota($response);

        /** @var list<array<string, mixed>>|null $body */
        $body = $response->json();

        if (! is_array($body) || $body === []) {
            return null;
        }

        $firstJob = $body[0] ?? null;

        if (! is_array($firstJob)) {
            return null;
        }

        if (isset($firstJob['error'])) {
            MarketDataLogger::warning('openfigi_job_error', [
                'isin' => $normalized,
                'error' => $firstJob['error'],
            ]);

            return null;
        }

        /** @var list<array<string, mixed>>|null $rows */
        $rows = $firstJob['data'] ?? null;

        if (! is_array($rows) || $rows === []) {
            return null;
        }

        $symbol = $this->pickBestYahooSymbol($rows);

        MarketDataLogger::info('openfigi_resolved', [
            'isin' => $normalized,
            'symbol' => $symbol,
            'status' => $response->status(),
        ]);

        return $symbol;
    }

    private function mappingRequest(string $isin): Response
    {
        $headers = ['Content-Type' => 'application/json'];
        $apiKey = config('market_data.openfigi.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $headers['X-OPENFIGI-APIKEY'] = $apiKey;
        }

        return Http::timeout(20)
            ->withHeaders($headers)
            ->post($this->mappingUrl(), [
                [
                    'idType' => 'ID_ISIN',
                    'idValue' => $isin,
                ],
            ]);
    }

    private function mappingUrl(): string
    {
        return rtrim((string) config('market_data.openfigi.base_url'), '/').'/mapping';
    }

    private function guardAgainstQuota(Response $response): void
    {
        if ($response->status() === 429) {
            throw new MarketDataQuotaExceededException('openfigi_rate_limit');
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function pickBestYahooSymbol(array $rows): ?string
    {
        $candidates = [];

        foreach ($rows as $row) {
            $ticker = $row['ticker'] ?? null;
            $exchCode = $row['exchCode'] ?? null;

            if (! is_string($ticker) || $ticker === '' || ! is_string($exchCode) || $exchCode === '') {
                continue;
            }

            $yahooSymbol = YahooExchangeSuffix::toYahooSymbol($ticker, $exchCode);
            $priority = array_search(strtoupper($exchCode), YahooExchangeSuffix::preferredExchangeCodes(), true);

            $candidates[] = [
                'symbol' => $yahooSymbol,
                'priority' => $priority === false ? 999 : $priority,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);

        return $candidates[0]['symbol'];
    }
}
